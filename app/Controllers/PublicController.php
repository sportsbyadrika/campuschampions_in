<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Cache;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;

/**
 * Public results page (no login required), with light caching.
 */
class PublicController extends Controller
{
    private const PER_PAGE = 20;

    public function results(): void
    {
        $db = Database::instance();

        $q        = trim((string) Request::get('q', ''));
        $meetId   = (int) Request::get('meet_id', 0);
        $catId    = (int) Request::get('category_id', 0);
        $position = (string) Request::get('position', '');
        $page     = max(1, (int) Request::get('page', 1));

        // Cached filter option lists (5 min)
        $meets = Cache::remember('public_meets', 300, fn() =>
            $db->fetchAll("SELECT id, title FROM meet_masters ORDER BY start_date DESC, title"));
        $categories = Cache::remember('public_categories', 300, fn() =>
            $db->fetchAll("SELECT DISTINCT c.id, c.name FROM categories c JOIN event_instances ei ON ei.category_id = c.id JOIN results r ON r.event_instance_id = ei.id ORDER BY c.name"));

        // Build the filtered query
        $where = [];
        $params = [];
        if ($q !== '') {
            $where[] = '(cm.name LIKE ? OR cm.unique_number LIKE ? OR e.name LIKE ?)';
            $like = '%' . $q . '%';
            array_push($params, $like, $like, $like);
        }
        if ($meetId > 0) { $where[] = 'm.id = ?'; $params[] = $meetId; }
        if ($catId > 0)  { $where[] = 'c.id = ?'; $params[] = $catId; }
        if (in_array($position, ['first', 'second', 'third', 'participant'], true)) {
            $where[] = 'r.position = ?'; $params[] = $position;
        }
        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $from = "FROM results r
                 JOIN contestant_masters cm ON cm.id = r.contestant_id
                 JOIN institutions inst ON inst.id = cm.campus_id
                 JOIN event_instances ei ON ei.id = r.event_instance_id
                 JOIN event_masters e ON e.id = ei.event_id
                 JOIN discipline_masters d ON d.id = e.discipline_id
                 JOIN categories c ON c.id = ei.category_id
                 JOIN meet_masters m ON m.id = d.meet_id
                 $whereSql";

        // Cache the full result set for this query (60s)
        $cacheKey = 'public_results:' . md5($whereSql . '|' . implode(',', $params) . '|' . $page);
        $payload = Cache::remember($cacheKey, 60, function () use ($db, $from, $params, $page) {
            $total = (int) $db->scalar("SELECT COUNT(*) $from", $params);
            $pages = max(1, (int) ceil($total / self::PER_PAGE));
            $page  = min($page, $pages);
            $offset = ($page - 1) * self::PER_PAGE;
            $rows = $db->fetchAll(
                "SELECT r.position, cm.name AS contestant_name, cm.unique_number,
                        inst.name AS institution_name, e.name AS event_name,
                        d.name AS discipline_name, c.name AS category_name, m.title AS meet_title
                 $from
                 ORDER BY m.start_date DESC, FIELD(r.position,'first','second','third','participant'), cm.name
                 LIMIT " . self::PER_PAGE . " OFFSET $offset",
                $params
            );
            return ['rows' => $rows, 'total' => $total, 'pages' => $pages, 'page' => $page];
        });

        $this->view('public/results', [
            'title'      => 'Public Results',
            'rows'       => $payload['rows'],
            'total'      => $payload['total'],
            'pages'      => $payload['pages'],
            'page'       => $payload['page'],
            'meets'      => $meets,
            'categories' => $categories,
            'q'          => $q,
            'meetId'     => $meetId,
            'catId'      => $catId,
            'position'   => $position,
        ], 'layouts/public');
    }
}
