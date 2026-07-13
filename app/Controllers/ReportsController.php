<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

/**
 * System-wide reports — Super Admin only.
 */
class ReportsController extends Controller
{
    public function index(): void
    {
        $this->authorize('super_admin');
        $db = Database::instance();

        $totals = [
            'Institutions' => (int) $db->scalar("SELECT COUNT(*) FROM institutions"),
            'Users'        => (int) $db->scalar("SELECT COUNT(*) FROM users"),
            'Contestants'  => (int) $db->scalar("SELECT COUNT(*) FROM contestant_masters"),
            'Meets'        => (int) $db->scalar("SELECT COUNT(*) FROM meet_masters"),
            'Results'      => (int) $db->scalar("SELECT COUNT(*) FROM results"),
            'Certificates' => (int) $db->scalar("SELECT COUNT(*) FROM certificates"),
        ];

        $perCampus = $db->fetchAll(
            "SELECT i.id, i.name, i.status, i.subscription_end_date,
                    (SELECT COUNT(*) FROM users u WHERE u.campus_id = i.id) AS users,
                    (SELECT COUNT(*) FROM contestant_masters c WHERE c.campus_id = i.id) AS contestants,
                    (SELECT COUNT(*) FROM meet_masters m WHERE m.campus_id = i.id) AS meets
             FROM institutions i
             ORDER BY i.name"
        );

        // Exportable master tables (super admin sees all campuses via existing endpoints)
        $exports = [
            'Institutions' => 'institutions/export',
            'Users'        => 'users/export',
            'Courses'      => 'courses/export',
            'Divisions'    => 'divisions/export',
            'Houses'       => 'houses/export',
            'Contestants'  => 'contestants/export',
            'Meets'        => 'meets/export',
            'Audit Logs'   => 'audit-logs/export',
        ];

        $this->view('reports/index', [
            'title'     => 'System Reports',
            'totals'    => $totals,
            'perCampus' => $perCampus,
            'exports'   => $exports,
        ]);
    }
}
