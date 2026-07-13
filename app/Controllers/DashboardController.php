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

        $this->view('dashboard/index', [
            'title' => 'Dashboard',
            'stats' => $stats,
        ]);
    }
}
