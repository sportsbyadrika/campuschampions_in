<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csv;
use App\Core\Database;
use App\Core\Request;

/**
 * Audit log viewer — Super Admin only.
 */
class AuditLogController extends Controller
{
    private const PER_PAGE = 30;

    public function index(): void
    {
        $this->authorize('super_admin');
        [$rows, $total, $pages, $page] = $this->query();

        $db = Database::instance();
        $actions = $db->fetchAll("SELECT DISTINCT action FROM audit_logs ORDER BY action");
        $tables  = $db->fetchAll("SELECT DISTINCT table_name FROM audit_logs WHERE table_name IS NOT NULL ORDER BY table_name");

        $this->view('audit/index', [
            'title'   => 'Audit Logs',
            'rows'    => $rows,
            'total'   => $total,
            'pages'   => $pages,
            'page'    => $page,
            'actions' => $actions,
            'tables'  => $tables,
            'filters' => $this->filters(),
        ]);
    }

    public function export(): void
    {
        $this->authorize('super_admin');
        // Export all matching rows (no pagination)
        [$where, $params] = $this->buildWhere();
        $sql = "SELECT a.created_at, u.full_name AS user_name, u.email, a.action, a.table_name,
                       a.record_id, a.ip_address
                FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id
                $where ORDER BY a.created_at DESC LIMIT 50000";
        $rows = Database::instance()->fetchAll($sql, $params);
        $data = array_map(fn($r) => [
            $r['created_at'], $r['user_name'] ?? 'System', $r['email'] ?? '', $r['action'],
            $r['table_name'] ?? '', $r['record_id'] ?? '', $r['ip_address'] ?? '',
        ], $rows);
        Csv::download('audit_logs', ['Timestamp', 'User', 'Email', 'Action', 'Table', 'Record ID', 'IP'], $data);
    }

    private function filters(): array
    {
        return [
            'action'    => (string) Request::get('action', ''),
            'table'     => (string) Request::get('table', ''),
            'user'      => (string) Request::get('user', ''),
            'date_from' => (string) Request::get('date_from', ''),
            'date_to'   => (string) Request::get('date_to', ''),
        ];
    }

    private function buildWhere(): array
    {
        $f = $this->filters();
        $where = [];
        $params = [];
        if ($f['action'] !== '') { $where[] = 'a.action = ?'; $params[] = $f['action']; }
        if ($f['table'] !== '')  { $where[] = 'a.table_name = ?'; $params[] = $f['table']; }
        if ($f['user'] !== '')   { $where[] = '(u.full_name LIKE ? OR u.email LIKE ?)'; $params[] = '%' . $f['user'] . '%'; $params[] = '%' . $f['user'] . '%'; }
        if ($f['date_from'] !== '') { $where[] = 'a.created_at >= ?'; $params[] = $f['date_from'] . ' 00:00:00'; }
        if ($f['date_to'] !== '')   { $where[] = 'a.created_at <= ?'; $params[] = $f['date_to'] . ' 23:59:59'; }
        return [$where ? 'WHERE ' . implode(' AND ', $where) : '', $params];
    }

    private function query(): array
    {
        $db = Database::instance();
        [$where, $params] = $this->buildWhere();
        $page = max(1, (int) Request::get('page', 1));

        $total = (int) $db->scalar("SELECT COUNT(*) FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id $where", $params);
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min($page, $pages);
        $offset = ($page - 1) * self::PER_PAGE;

        $rows = $db->fetchAll(
            "SELECT a.*, u.full_name AS user_name, u.email
             FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id
             $where ORDER BY a.created_at DESC LIMIT " . self::PER_PAGE . " OFFSET $offset",
            $params
        );
        return [$rows, $total, $pages, $page];
    }
}
