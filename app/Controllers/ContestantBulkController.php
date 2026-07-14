<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csv;
use App\Core\Database;
use App\Core\Flash;
use App\Core\Request;
use App\Models\ContestantMaster;
use App\Models\Course;
use App\Models\CourseCategoryGroup;
use App\Models\Division;
use App\Models\House;

/**
 * Bulk contestant upload — separate multi-step page (upload -> preview -> import).
 *
 * The optional `event_instances` column registers the contestant for the listed
 * event instances (matched by label within the campus; multiple separated by
 * ";" or "|"). Rows whose unique_number already exists are treated as updates
 * that only add the listed event instances (master fields are left untouched).
 */
class ContestantBulkController extends Controller
{
    private const HEADERS = [
        'unique_number', 'name', 'dob', 'gender', 'course', 'division',
        'house', 'course_category_group', 'mobile', 'email', 'guardian_name', 'status', 'event_instances',
    ];

    private function guard(): void
    {
        $this->authorize('super_admin', 'campus_admin');
    }

    public function form(): void
    {
        $this->guard();
        $this->view('contestants/bulk', ['title' => 'Bulk Upload Contestants']);
    }

    /** Download a CSV template with the expected headers. */
    public function template(): void
    {
        $this->guard();
        $example = [[
            'CC001', 'John Doe', '2008-05-14', 'M', 'Grade 10', 'A',
            'Red House', 'Senior', '9000000000', 'john@example.com', 'Jane Doe', 'active',
            'Boys Junior Solo Singing - Final; 100m Sprint Heat 1',
        ]];
        Csv::download('contestants_template', self::HEADERS, $example);
    }

    /** Parse the uploaded CSV, validate each row, and show a preview. */
    public function preview(): void
    {
        $this->guard();
        $file = Request::file('csv_file');
        if (!$file || ($file['error'] ?? 4) !== UPLOAD_ERR_OK) {
            Flash::error('Please choose a CSV file to upload.');
            $this->redirect('/contestants/bulk');
        }
        if ($file['size'] > 5 * 1024 * 1024) {
            Flash::error('File is too large (max 5 MB).');
            $this->redirect('/contestants/bulk');
        }

        $rows = $this->parseCsv($file['tmp_name']);
        if (empty($rows)) {
            Flash::error('The CSV file is empty or has no valid header row.');
            $this->redirect('/contestants/bulk');
        }

        [$inCampus, $globalSet] = $this->existingMaps();
        $instanceMap = $this->instanceLabelMap();

        $preview = [];
        $store = [];
        $seen = [];
        foreach ($rows as $row) {
            $errors = [];
            $uniqueNo = trim((string) ($row['unique_number'] ?? ''));
            $name = trim((string) ($row['name'] ?? ''));

            if ($uniqueNo === '') $errors[] = 'Missing unique number';
            if ($name === '') $errors[] = 'Missing name';

            $gender = strtoupper(trim((string) ($row['gender'] ?? '')));
            if ($gender !== '' && !in_array($gender, ['M', 'F', 'O'], true)) $errors[] = 'Invalid gender';

            [$instanceIds, $unknown] = $this->resolveInstances((string) ($row['event_instances'] ?? ''), $instanceMap);
            foreach ($unknown as $u) $errors[] = "Unknown event instance '{$u}'";

            $existingId = $uniqueNo !== '' ? ($inCampus[$uniqueNo] ?? null) : null;
            $mode = $existingId !== null ? 'update' : 'new';

            if ($mode === 'new' && $uniqueNo !== '' && isset($globalSet[$uniqueNo])) {
                $errors[] = 'Unique number belongs to another campus';
            }
            if ($uniqueNo !== '') {
                if (isset($seen[$uniqueNo])) $errors[] = 'Duplicate within file';
                $seen[$uniqueNo] = true;
            }

            $ok = empty($errors);
            $preview[] = [
                'raw'         => $row,
                'valid'       => $ok,
                'errors'      => $errors,
                'mode'        => $mode,
                'event_count' => count($instanceIds),
            ];
            if ($ok) {
                $store[] = ['raw' => $row, 'unique_number' => $uniqueNo, 'instance_ids' => $instanceIds];
            }
        }

        $_SESSION['_bulk_contestants'] = $store;

        $this->view('contestants/bulk_preview', [
            'title'      => 'Preview Import',
            'preview'    => $preview,
            'headers'    => self::HEADERS,
            'validCount' => count($store),
            'totalCount' => count($preview),
        ]);
    }

    /** Import the previously previewed valid rows. */
    public function import(): void
    {
        $this->guard();
        $rows = $_SESSION['_bulk_contestants'] ?? [];
        unset($_SESSION['_bulk_contestants']);

        if (empty($rows)) {
            Flash::error('No validated rows to import. Please upload again.');
            $this->redirect('/contestants/bulk');
        }

        $lookups = $this->campusLookups();
        $model = new ContestantMaster();
        $imported = 0;
        $updated = 0;

        foreach ($rows as $entry) {
            $row = $entry['raw'];
            $uniqueNo = $entry['unique_number'];
            $instanceIds = $entry['instance_ids'] ?? [];

            // Existing contestant in this campus -> update event instances only
            $existingId = $this->findInCampus($uniqueNo);
            if ($existingId !== null) {
                $this->registerInstances($existingId, $instanceIds);
                $updated++;
                continue;
            }

            $data = [
                'unique_number' => $uniqueNo,
                'name'          => trim((string) ($row['name'] ?? '')),
                'dob'           => $this->normalizeDate($row['dob'] ?? ''),
                'gender'        => in_array(strtoupper(trim((string) ($row['gender'] ?? ''))), ['M', 'F', 'O'], true) ? strtoupper(trim((string) $row['gender'])) : null,
                'course_id'     => $lookups['course'][strtolower(trim((string) ($row['course'] ?? '')))] ?? null,
                'division_id'   => $lookups['division'][strtolower(trim((string) ($row['division'] ?? '')))] ?? null,
                'house_id'      => $lookups['house'][strtolower(trim((string) ($row['house'] ?? '')))] ?? null,
                'course_category_group_id' => $lookups['group'][strtolower(trim((string) ($row['course_category_group'] ?? '')))] ?? null,
                'mobile'        => trim((string) ($row['mobile'] ?? '')) ?: null,
                'email'         => ($e = strtolower(trim((string) ($row['email'] ?? '')))) !== '' ? $e : null,
                'guardian_name' => trim((string) ($row['guardian_name'] ?? '')) ?: null,
                'status'        => in_array(strtolower(trim((string) ($row['status'] ?? 'active'))), ['active', 'inactive'], true) ? strtolower(trim((string) $row['status'])) : 'active',
            ];
            try {
                $id = $model->create($data);
                $this->registerInstances($id, $instanceIds);
                $imported++;
            } catch (\PDOException $e) {
                error_log('Bulk import row skipped: ' . $e->getMessage());
            }
        }

        Audit::log('bulk_import', 'contestant_masters', null, null, ['imported' => $imported, 'updated' => $updated]);
        $msg = "Imported {$imported} new contestant(s).";
        if ($updated > 0) {
            $msg .= " Updated event instances for {$updated} existing contestant(s).";
        }
        Flash::success($msg);
        $this->redirect('/contestants');
    }

    // ------------------------------------------------------------------
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

    /** Build name(lowercase) => id lookups for the current campus. */
    private function campusLookups(): array
    {
        $map = fn(array $list) => array_column(
            array_map(fn($r) => ['k' => strtolower($r['name']), 'v' => (int) $r['id']], $list),
            'v', 'k'
        );
        return [
            'course'   => $map((new Course())->options()),
            'division' => $map((new Division())->options()),
            'house'    => $map((new House())->options()),
            'group'    => $map((new CourseCategoryGroup())->options()),
        ];
    }

    /** [inCampus: unique_number => id], [globalSet: unique_number => true]. */
    private function existingMaps(): array
    {
        $db = Database::instance();
        $inCampus = [];
        $params = [];
        $sql = "SELECT unique_number, id FROM contestant_masters";
        if (Auth::campusId() !== null) {
            $sql .= " WHERE campus_id = ?";
            $params[] = Auth::campusId();
        }
        foreach ($db->fetchAll($sql, $params) as $r) {
            $inCampus[$r['unique_number']] = (int) $r['id'];
        }
        $globalSet = [];
        foreach ($db->fetchAll("SELECT unique_number FROM contestant_masters") as $r) {
            $globalSet[$r['unique_number']] = true;
        }
        return [$inCampus, $globalSet];
    }

    private function findInCampus(string $uniqueNo): ?int
    {
        if ($uniqueNo === '') return null;
        $db = Database::instance();
        $params = [$uniqueNo];
        $sql = "SELECT id FROM contestant_masters WHERE unique_number = ?";
        if (Auth::campusId() !== null) {
            $sql .= " AND campus_id = ?";
            $params[] = Auth::campusId();
        }
        $id = $db->scalar($sql, $params);
        return $id !== false && $id !== null ? (int) $id : null;
    }

    /** [lower(label) => [instance_id,...]] for the campus. */
    private function instanceLabelMap(): array
    {
        $params = [];
        $scope = '';
        if (Auth::campusId() !== null) {
            $scope = 'WHERE m.campus_id = ?';
            $params[] = Auth::campusId();
        }
        $rows = Database::instance()->fetchAll(
            "SELECT ei.id, ei.label
             FROM event_instances ei
             JOIN event_masters e ON e.id = ei.event_id
             JOIN discipline_masters d ON d.id = e.discipline_id
             JOIN meet_masters m ON m.id = d.meet_id
             $scope",
            $params
        );
        $map = [];
        foreach ($rows as $r) {
            $map[strtolower(trim((string) $r['label']))][] = (int) $r['id'];
        }
        return $map;
    }

    /** Resolve a cell of labels (";" or "|" separated) -> [ids[], unknownLabels[]]. */
    private function resolveInstances(string $cell, array $map): array
    {
        $cell = trim($cell);
        if ($cell === '') return [[], []];
        $labels = preg_split('/\s*[;|]\s*/', $cell, -1, PREG_SPLIT_NO_EMPTY);
        $ids = [];
        $unknown = [];
        foreach ($labels as $label) {
            $key = strtolower(trim($label));
            if (isset($map[$key])) {
                foreach ($map[$key] as $id) $ids[$id] = $id;
            } else {
                $unknown[] = trim($label);
            }
        }
        return [array_values($ids), $unknown];
    }

    private function registerInstances(int $contestantId, array $instanceIds): void
    {
        $db = Database::instance();
        foreach (array_unique($instanceIds) as $iid) {
            try {
                $db->query(
                    "INSERT INTO contestant_registrations (contestant_id, event_instance_id, registration_date, status)
                     VALUES (?, ?, ?, 'registered')",
                    [$contestantId, (int) $iid, date('Y-m-d')]
                );
            } catch (\PDOException $e) {
                // Ignore duplicates (unique contestant_id + event_instance_id)
            }
        }
    }

    private function normalizeDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') return null;
        $ts = strtotime($value);
        return $ts ? date('Y-m-d', $ts) : null;
    }
}
