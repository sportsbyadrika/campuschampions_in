<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Controller;
use App\Core\Csv;
use App\Core\Database;
use App\Core\Flash;
use App\Core\Request;
use App\Models\Category;
use App\Models\DisciplineMaster;
use App\Models\EventInstance;
use App\Models\EventMaster;
use App\Models\MeetMaster;

/**
 * Bulk CSV upload of Events and Event Instances into a meet's configuration.
 * Two-step (upload -> validated preview -> import), meet + campus scoped.
 */
class MeetBulkController extends Controller
{
    private const EVENT_HEADERS    = ['discipline', 'event_name', 'event_type', 'status'];
    private const INSTANCE_HEADERS = ['discipline', 'event_name', 'category', 'label', 'instance_date', 'instance_time', 'venue', 'status'];

    private function meetOrAbort(int $meetId): array
    {
        $this->authorize('super_admin', 'campus_admin');
        $meet = (new MeetMaster())->find($meetId); // campus-scoped
        if (!$meet) {
            $this->abort(404, 'Meet not found.');
        }
        return $meet;
    }

    // ------------------------------------------------------------------
    public function form(string $meetId): void
    {
        $meetId = (int) $meetId;
        $meet = $this->meetOrAbort($meetId);
        $this->view('meets/bulk', ['title' => 'Bulk Import · ' . $meet['title'], 'meet' => $meet]);
    }

    // ================= Templates =================
    public function eventsTemplate(string $meetId): void
    {
        $this->meetOrAbort((int) $meetId);
        Csv::download('events_template', self::EVENT_HEADERS, [
            ['Music', 'Solo Singing', 'individual', 'active'],
            ['Athletics', '100m Sprint', 'individual', 'active'],
        ]);
    }

    public function instancesTemplate(string $meetId): void
    {
        $this->meetOrAbort((int) $meetId);
        Csv::download('event_instances_template', self::INSTANCE_HEADERS, [
            ['Music', 'Solo Singing', 'Junior', 'Boys Junior Solo Singing - Final', '2026-03-02', '10:30', 'Auditorium', 'scheduled'],
        ]);
    }

    // ================= Events: preview + import =================
    public function eventsPreview(string $meetId): void
    {
        $meetId = (int) $meetId;
        $meet = $this->meetOrAbort($meetId);

        $rows = $this->readUpload('csv_file', '/meets/' . $meetId . '/bulk');

        $disc = $this->nameIdMap((new DisciplineMaster())->forMeet($meetId));
        $existingEvents = $this->existingEventKeys($meetId);

        $preview = [];
        $valid = [];
        $seen = [];
        foreach ($rows as $row) {
            $errors = [];
            $discName = trim((string) ($row['discipline'] ?? ''));
            $name = trim((string) ($row['event_name'] ?? ''));
            $type = strtolower(trim((string) ($row['event_type'] ?? 'individual'))) ?: 'individual';
            $status = strtolower(trim((string) ($row['status'] ?? 'active'))) ?: 'active';

            $disciplineId = $disc[strtolower($discName)] ?? null;
            if ($name === '') $errors[] = 'Missing event_name';
            if ($discName === '') $errors[] = 'Missing discipline';
            elseif ($disciplineId === null) $errors[] = "Unknown discipline '{$discName}'";
            if (!in_array($type, ['individual', 'group'], true)) $errors[] = 'Invalid event_type';
            if (!in_array($status, ['active', 'inactive'], true)) $errors[] = 'Invalid status';

            $key = $disciplineId . '|' . strtolower($name);
            if ($disciplineId !== null && $name !== '') {
                if (isset($existingEvents[$key])) $errors[] = 'Event already exists in this discipline';
                elseif (isset($seen[$key])) $errors[] = 'Duplicate within file';
                $seen[$key] = true;
            }

            $ok = empty($errors);
            $preview[] = ['raw' => ['discipline' => $discName, 'event_name' => $name, 'event_type' => $type, 'status' => $status], 'valid' => $ok, 'errors' => $errors];
            if ($ok) {
                $valid[] = ['name' => $name, 'discipline_id' => $disciplineId, 'event_type' => $type, 'status' => $status];
            }
        }

        $_SESSION['_bulk_events'][$meetId] = $valid;
        $this->view('meets/bulk_preview', [
            'title'      => 'Preview Events · ' . $meet['title'],
            'meet'       => $meet,
            'type'       => 'events',
            'headers'    => ['Discipline', 'Event', 'Type', 'Status', 'Issues'],
            'cols'       => ['discipline', 'event_name', 'event_type', 'status'],
            'preview'    => $preview,
            'validCount' => count($valid),
            'importUrl'  => url('meets/' . $meetId . '/bulk/events-import'),
            'backUrl'    => url('meets/' . $meetId . '/bulk'),
        ]);
    }

    public function eventsImport(string $meetId): void
    {
        $meetId = (int) $meetId;
        $this->meetOrAbort($meetId);
        $rows = $_SESSION['_bulk_events'][$meetId] ?? [];
        unset($_SESSION['_bulk_events'][$meetId]);

        if (empty($rows)) {
            Flash::error('No validated events to import. Please upload again.');
            $this->redirect('/meets/' . $meetId . '/bulk');
        }

        $model = new EventMaster();
        $count = 0;
        foreach ($rows as $r) {
            // Re-verify the discipline still belongs to the meet (defense in depth)
            $ok = (int) Database::instance()->scalar(
                "SELECT COUNT(*) FROM discipline_masters WHERE id = ? AND meet_id = ?",
                [$r['discipline_id'], $meetId]
            );
            if (!$ok) continue;
            try {
                $model->create($r);
                $count++;
            } catch (\PDOException $e) {
                error_log('Bulk event skipped: ' . $e->getMessage());
            }
        }
        Audit::log('bulk_import', 'event_masters', $meetId, null, ['imported' => $count]);
        Flash::success("Imported {$count} event(s).");
        $this->redirect('/meets/' . $meetId . '/setup#events');
    }

    // ================= Instances: preview + import =================
    public function instancesPreview(string $meetId): void
    {
        $meetId = (int) $meetId;
        $meet = $this->meetOrAbort($meetId);

        $rows = $this->readUpload('csv_file', '/meets/' . $meetId . '/bulk');

        $disc = $this->nameIdMap((new DisciplineMaster())->forMeet($meetId));
        $cats = $this->nameIdMap((new Category())->forMeet($meetId));
        $events = $this->eventKeyMap($meetId); // "discId|name" => event_id

        $preview = [];
        $valid = [];
        foreach ($rows as $row) {
            $errors = [];
            $discName = trim((string) ($row['discipline'] ?? ''));
            $eventName = trim((string) ($row['event_name'] ?? ''));
            $catName = trim((string) ($row['category'] ?? ''));
            $label = trim((string) ($row['label'] ?? ''));
            $status = strtolower(trim((string) ($row['status'] ?? 'scheduled'))) ?: 'scheduled';

            $disciplineId = $disc[strtolower($discName)] ?? null;
            $categoryId = $cats[strtolower($catName)] ?? null;
            $eventId = ($disciplineId !== null) ? ($events[$disciplineId . '|' . strtolower($eventName)] ?? null) : null;

            if ($label === '') $errors[] = 'Missing label';
            if ($discName === '' || $disciplineId === null) $errors[] = "Unknown discipline '{$discName}'";
            if ($eventName === '' || $eventId === null) $errors[] = "Unknown event '{$eventName}' in discipline";
            if ($catName === '' || $categoryId === null) $errors[] = "Unknown category '{$catName}'";
            if (!in_array($status, ['scheduled', 'ongoing', 'completed', 'cancelled'], true)) $errors[] = 'Invalid status';

            $ok = empty($errors);
            $preview[] = ['raw' => ['discipline' => $discName, 'event_name' => $eventName, 'category' => $catName, 'label' => $label, 'status' => $status], 'valid' => $ok, 'errors' => $errors];
            if ($ok) {
                $valid[] = [
                    'event_id'      => $eventId,
                    'category_id'   => $categoryId,
                    'label'         => $label,
                    'instance_date' => $this->normDate((string) ($row['instance_date'] ?? '')),
                    'instance_time' => $this->normTime((string) ($row['instance_time'] ?? '')),
                    'venue'         => trim((string) ($row['venue'] ?? '')) ?: null,
                    'status'        => $status,
                ];
            }
        }

        $_SESSION['_bulk_instances'][$meetId] = $valid;
        $this->view('meets/bulk_preview', [
            'title'      => 'Preview Event Instances · ' . $meet['title'],
            'meet'       => $meet,
            'type'       => 'instances',
            'headers'    => ['Discipline', 'Event', 'Category', 'Label', 'Status', 'Issues'],
            'cols'       => ['discipline', 'event_name', 'category', 'label', 'status'],
            'preview'    => $preview,
            'validCount' => count($valid),
            'importUrl'  => url('meets/' . $meetId . '/bulk/instances-import'),
            'backUrl'    => url('meets/' . $meetId . '/bulk'),
        ]);
    }

    public function instancesImport(string $meetId): void
    {
        $meetId = (int) $meetId;
        $this->meetOrAbort($meetId);
        $rows = $_SESSION['_bulk_instances'][$meetId] ?? [];
        unset($_SESSION['_bulk_instances'][$meetId]);

        if (empty($rows)) {
            Flash::error('No validated instances to import. Please upload again.');
            $this->redirect('/meets/' . $meetId . '/bulk');
        }

        $model = new EventInstance();
        $count = 0;
        foreach ($rows as $r) {
            // Re-verify the event belongs to the meet
            $ok = (int) Database::instance()->scalar(
                "SELECT COUNT(*) FROM event_masters e JOIN discipline_masters d ON d.id = e.discipline_id
                 WHERE e.id = ? AND d.meet_id = ?",
                [$r['event_id'], $meetId]
            );
            if (!$ok) continue;
            try {
                $model->create($r);
                $count++;
            } catch (\PDOException $e) {
                error_log('Bulk instance skipped: ' . $e->getMessage());
            }
        }
        Audit::log('bulk_import', 'event_instances', $meetId, null, ['imported' => $count]);
        Flash::success("Imported {$count} event instance(s).");
        $this->redirect('/meets/' . $meetId . '/setup#instances');
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------
    private function readUpload(string $field, string $redirectPath): array
    {
        $file = Request::file($field);
        if (!$file || ($file['error'] ?? 4) !== UPLOAD_ERR_OK) {
            Flash::error('Please choose a CSV file to upload.');
            $this->redirect($redirectPath);
        }
        if ($file['size'] > 5 * 1024 * 1024) {
            Flash::error('File is too large (max 5 MB).');
            $this->redirect($redirectPath);
        }
        $rows = $this->parseCsv($file['tmp_name']);
        if (empty($rows)) {
            Flash::error('The CSV file is empty or has no valid header row.');
            $this->redirect($redirectPath);
        }
        return $rows;
    }

    private function parseCsv(string $path): array
    {
        $rows = [];
        if (($h = fopen($path, 'r')) === false) {
            return $rows;
        }
        $header = fgetcsv($h, 0, ',', '"', '\\');
        if ($header === false) {
            fclose($h);
            return $rows;
        }
        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]);
        $header = array_map(fn($c) => strtolower(trim((string) $c)), $header);
        while (($line = fgetcsv($h, 0, ',', '"', '\\')) !== false) {
            if (count(array_filter($line, fn($c) => trim((string) $c) !== '')) === 0) {
                continue;
            }
            $row = [];
            foreach ($header as $idx => $col) {
                $row[$col] = $line[$idx] ?? '';
            }
            $rows[] = $row;
        }
        fclose($h);
        return $rows;
    }

    /** [lower(name) => id] from rows having id + name. */
    private function nameIdMap(array $rows): array
    {
        $map = [];
        foreach ($rows as $r) {
            $map[strtolower($r['name'])] = (int) $r['id'];
        }
        return $map;
    }

    /** existing event keys "discipline_id|lower(name)" for dedupe. */
    private function existingEventKeys(int $meetId): array
    {
        $out = [];
        foreach ((new EventMaster())->forMeet($meetId) as $e) {
            $out[(int) $e['discipline_id'] . '|' . strtolower($e['name'])] = true;
        }
        return $out;
    }

    /** "discipline_id|lower(name)" => event_id for instance matching. */
    private function eventKeyMap(int $meetId): array
    {
        $out = [];
        foreach ((new EventMaster())->forMeet($meetId) as $e) {
            $out[(int) $e['discipline_id'] . '|' . strtolower($e['name'])] = (int) $e['id'];
        }
        return $out;
    }

    private function normDate(string $v): ?string
    {
        $v = trim($v);
        if ($v === '') return null;
        $ts = strtotime($v);
        return $ts ? date('Y-m-d', $ts) : null;
    }

    private function normTime(string $v): ?string
    {
        $v = trim($v);
        if ($v === '') return null;
        $ts = strtotime($v);
        return $ts ? date('H:i:s', $ts) : null;
    }
}
