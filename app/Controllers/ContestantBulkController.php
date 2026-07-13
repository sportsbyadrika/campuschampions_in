<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csv;
use App\Core\Flash;
use App\Core\Request;
use App\Models\ContestantMaster;
use App\Models\Course;
use App\Models\CourseCategoryGroup;
use App\Models\Division;
use App\Models\House;

/**
 * Bulk contestant upload — separate multi-step page (upload -> preview -> import).
 */
class ContestantBulkController extends Controller
{
    private const HEADERS = [
        'unique_number', 'name', 'dob', 'gender', 'course', 'division',
        'house', 'course_category_group', 'mobile', 'email', 'guardian_name', 'status',
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

        $lookups = $this->campusLookups();
        $existing = $this->existingUniqueNumbers();

        $preview = [];
        $seen = [];
        foreach ($rows as $i => $row) {
            $errors = [];
            $uniqueNo = trim((string) ($row['unique_number'] ?? ''));
            $name = trim((string) ($row['name'] ?? ''));

            if ($uniqueNo === '') $errors[] = 'Missing unique number';
            if ($name === '') $errors[] = 'Missing name';
            if ($uniqueNo !== '' && isset($existing[$uniqueNo])) $errors[] = 'Unique number already exists';
            if ($uniqueNo !== '' && isset($seen[$uniqueNo])) $errors[] = 'Duplicate within file';
            $gender = strtoupper(trim((string) ($row['gender'] ?? '')));
            if ($gender !== '' && !in_array($gender, ['M', 'F', 'O'], true)) $errors[] = 'Invalid gender';

            if ($uniqueNo !== '') $seen[$uniqueNo] = true;

            $preview[] = [
                'raw'    => $row,
                'valid'  => empty($errors),
                'errors' => $errors,
            ];
        }

        // Stash valid rows in session for the import step
        $_SESSION['_bulk_contestants'] = array_values(array_filter($preview, fn($p) => $p['valid']));

        $this->view('contestants/bulk_preview', [
            'title'   => 'Preview Import',
            'preview' => $preview,
            'headers' => self::HEADERS,
            'validCount' => count($_SESSION['_bulk_contestants']),
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

        foreach ($rows as $entry) {
            $row = $entry['raw'];
            $data = [
                'unique_number' => trim((string) ($row['unique_number'] ?? '')),
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
                $model->create($data);
                $imported++;
            } catch (\PDOException $e) {
                // Skip rows that hit a race (e.g. unique number inserted meanwhile)
                error_log('Bulk import row skipped: ' . $e->getMessage());
            }
        }

        Audit::log('bulk_import', 'contestant_masters', null, null, ['imported' => $imported]);
        Flash::success("Imported {$imported} contestant(s) successfully.");
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
        // Strip BOM from first header cell
        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]);
        $header = array_map(fn($c) => strtolower(trim((string) $c)), $header);

        while (($line = fgetcsv($h, 0, ',', '"', '\\')) !== false) {
            if (count(array_filter($line, fn($c) => trim((string) $c) !== '')) === 0) {
                continue; // skip blank lines
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

    private function existingUniqueNumbers(): array
    {
        $model = new ContestantMaster();
        $sql = "SELECT unique_number FROM contestant_masters";
        $params = [];
        // scope to campus for non-super-admins
        if (Auth::campusId() !== null) {
            $sql .= " WHERE campus_id = ?";
            $params[] = Auth::campusId();
        }
        $out = [];
        foreach (\App\Core\Database::instance()->fetchAll($sql, $params) as $r) {
            $out[$r['unique_number']] = true;
        }
        return $out;
    }

    private function normalizeDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') return null;
        $ts = strtotime($value);
        return $ts ? date('Y-m-d', $ts) : null;
    }
}
