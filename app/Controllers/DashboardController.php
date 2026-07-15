<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;

class DashboardController extends Controller
{
    public function index(): void
    {
        $db = Database::instance();
        $campusId = Auth::campusId();
        $scope = $campusId !== null ? ' WHERE campus_id = ' . (int) $campusId : '';

        $stats = [];

        if (Auth::isSuperAdmin()) {
            $stats['Institutions'] = (int) $db->scalar("SELECT COUNT(*) FROM institutions");
            $stats['Users']        = (int) $db->scalar("SELECT COUNT(*) FROM users");
            $stats['Contestants']  = (int) $db->scalar("SELECT COUNT(*) FROM contestant_masters");
            $stats['Meets']        = (int) $db->scalar("SELECT COUNT(*) FROM meet_masters");
        } else {
            $stats['Contestants'] = (int) $db->scalar("SELECT COUNT(*) FROM contestant_masters" . $scope);
            $stats['Meets']       = (int) $db->scalar("SELECT COUNT(*) FROM meet_masters" . $scope);
            $stats['Courses']     = (int) $db->scalar("SELECT COUNT(*) FROM courses" . $scope);
            $stats['Houses']      = (int) $db->scalar("SELECT COUNT(*) FROM houses" . $scope);
        }

        // Activity counts (scoped to the campus for non-super-admin), joined to
        // the meet so they respect campus ownership.
        $meetScope = $campusId !== null ? ' AND m.campus_id = ' . (int) $campusId : '';
        $fromInstances = "FROM event_instances ei
             JOIN event_masters e ON e.id = ei.event_id
             JOIN discipline_masters d ON d.id = e.discipline_id
             JOIN meet_masters m ON m.id = d.meet_id
             WHERE 1 = 1" . $meetScope;

        $stats['Total Event Instances']  = (int) $db->scalar("SELECT COUNT(*) $fromInstances");
        $stats['Total Results Entered']  = (int) $db->scalar(
            "SELECT COUNT(*) FROM results r JOIN event_instances ei ON ei.id = r.event_instance_id
             JOIN event_masters e ON e.id = ei.event_id JOIN discipline_masters d ON d.id = e.discipline_id
             JOIN meet_masters m ON m.id = d.meet_id WHERE 1 = 1" . $meetScope
        );
        $stats['Total Results Published'] = (int) $db->scalar(
            "SELECT COUNT(*) FROM results r JOIN event_instances ei ON ei.id = r.event_instance_id
             JOIN event_masters e ON e.id = ei.event_id JOIN discipline_masters d ON d.id = e.discipline_id
             JOIN meet_masters m ON m.id = d.meet_id WHERE ei.results_published = 1" . $meetScope
        );
        $stats['Certificates Generated'] = (int) $db->scalar(
            "SELECT COUNT(*) FROM certificates ce JOIN event_instances ei ON ei.id = ce.event_instance_id
             JOIN event_masters e ON e.id = ei.event_id JOIN discipline_masters d ON d.id = e.discipline_id
             JOIN meet_masters m ON m.id = d.meet_id WHERE ce.status = 'generated'" . $meetScope
        );

        $this->view('dashboard/index', [
            'title' => 'Dashboard',
            'stats' => $stats,
        ]);
    }
}
