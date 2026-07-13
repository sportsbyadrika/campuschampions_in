<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csv;
use App\Core\Request;
use App\Models\MeetMaster;
use App\Models\Standing;

class StandingsController extends Controller
{
    public function index(): void
    {
        $this->authorize('super_admin', 'campus_admin', 'event_user', 'campus_staff');

        $meets = (new MeetMaster())->options();
        $meetId = (int) Request::get('meet_id', 0);

        $houses = [];
        $individuals = [];
        $meet = null;

        if ($meetId > 0) {
            $meet = (new MeetMaster())->find($meetId); // campus-scoped ownership
            if (!$meet) {
                $this->abort(404, 'Meet not found.');
            }
            $standing = new Standing();
            $houses = $standing->houses($meetId);
            $individuals = $standing->individuals($meetId, 50);
        }

        $this->view('standings/index', [
            'title'       => 'Championship Standings',
            'meets'       => $meets,
            'meetId'      => $meetId,
            'meet'        => $meet,
            'houses'      => $houses,
            'individuals' => $individuals,
        ]);
    }

    public function export(string $type): void
    {
        $this->authorize('super_admin', 'campus_admin', 'event_user', 'campus_staff');
        $meetId = (int) Request::get('meet_id', 0);
        $meet = (new MeetMaster())->find($meetId);
        if (!$meet) {
            $this->abort(404, 'Meet not found.');
        }
        $standing = new Standing();

        if ($type === 'houses') {
            $rows = array_map(fn($h) => [$h['name'], $h['total_points'], $h['result_count']], $standing->houses($meetId));
            Csv::download('house_standings', ['House', 'Total Points', 'Results'], $rows);
        }
        $rows = array_map(fn($i) => [
            $i['unique_number'], $i['name'], $i['house_name'], $i['total_points'], $i['golds'], $i['silvers'], $i['bronzes'],
        ], $standing->individuals($meetId, 500));
        Csv::download('individual_standings', ['Unique #', 'Contestant', 'House', 'Total Points', 'Gold', 'Silver', 'Bronze'], $rows);
    }
}
