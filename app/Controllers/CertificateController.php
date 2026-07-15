<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\CertificatePdf;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\EventInstance;
use App\Models\MeetMaster;

/**
 * Certificate generation (multi-step, separate page).
 */
class CertificateController extends Controller
{
    private const POS_LABELS = ['first' => 'First', 'second' => 'Second', 'third' => 'Third', 'participant' => 'Participant'];

    private function instanceOrAbort(int $instanceId): array
    {
        $this->authorize('super_admin', 'campus_admin', 'event_user');
        $detail = (new EventInstance())->detail($instanceId);
        if (!$detail) {
            $this->abort(404, 'Event instance not found.');
        }
        if (Auth::campusId() !== null && (int) $detail['campus_id'] !== (int) Auth::campusId()) {
            $this->abort(403, 'This event is not in your campus.');
        }
        return $detail;
    }

    // Landing: pick an instance
    public function index(): void
    {
        $this->authorize('super_admin', 'campus_admin', 'event_user');
        $meetId = (int) Request::get('meet_id', 0);

        $where = [];
        $params = [];
        if (Auth::campusId() !== null) { $where[] = 'm.campus_id = ?'; $params[] = Auth::campusId(); }
        if ($meetId > 0) { $where[] = 'm.id = ?'; $params[] = $meetId; }
        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $instances = Database::instance()->fetchAll(
            "SELECT ei.id, ei.label, ei.instance_date, e.name AS event_name, d.name AS discipline_name,
                    c.name AS category_name, m.title AS meet_title,
                    (SELECT COUNT(*) FROM results r WHERE r.event_instance_id = ei.id) AS result_count,
                    (SELECT COUNT(*) FROM certificates ce WHERE ce.event_instance_id = ei.id) AS cert_count
             FROM event_instances ei
             JOIN event_masters e ON e.id = ei.event_id
             JOIN discipline_masters d ON d.id = e.discipline_id
             JOIN categories c ON c.id = ei.category_id
             JOIN meet_masters m ON m.id = d.meet_id
             $whereSql
             ORDER BY ei.label ASC",
            $params
        );

        $this->view('certificates/index', [
            'title'     => 'Certificates',
            'instances' => $instances,
            'meets'     => (new MeetMaster())->options(),
            'meetId'    => $meetId,
        ]);
    }

    // Generation page for one instance
    public function generateForm(string $instanceId): void
    {
        $instanceId = (int) $instanceId;
        $instance = $this->instanceOrAbort($instanceId);

        // Contestants with results for this instance
        $contestants = Database::instance()->fetchAll(
            "SELECT cm.id, cm.unique_number, cm.name, r.position,
                    ce.id AS certificate_id, ce.certificate_number, ce.file_path
             FROM results r
             JOIN contestant_masters cm ON cm.id = r.contestant_id
             LEFT JOIN certificates ce ON ce.event_instance_id = r.event_instance_id AND ce.contestant_id = cm.id
             WHERE r.event_instance_id = ?
             ORDER BY FIELD(r.position,'first','second','third','participant'), cm.name",
            [$instanceId]
        );

        $templates = (new CertificateTemplate())->usable();

        $this->view('certificates/generate', [
            'title'       => 'Generate Certificates · ' . $instance['label'],
            'instance'    => $instance,
            'contestants' => $contestants,
            'templates'   => $templates,
        ]);
    }

    // Generate certificates for selected contestants
    public function generate(string $instanceId): void
    {
        $instanceId = (int) $instanceId;
        $instance = $this->instanceOrAbort($instanceId);

        $templateId = (int) Request::input('template_id');
        $template = (new CertificateTemplate())->usableById($templateId);
        if (!$template) {
            $this->json(['success' => false, 'message' => 'Please choose a valid template.'], 422);
        }

        $contestantIds = Request::input('contestant_ids');
        if (!is_array($contestantIds) || empty($contestantIds)) {
            $this->json(['success' => false, 'message' => 'Select at least one contestant.'], 422);
        }

        $certModel = new Certificate();
        $generated = 0;
        $issueDate = date('Y-m-d');

        foreach ($contestantIds as $cid) {
            $cid = (int) $cid;
            // Fetch contestant + result + house
            $row = Database::instance()->fetch(
                "SELECT cm.name AS contestant_name, cm.unique_number, h.name AS house_name,
                        co.name AS course_name, dv.name AS division_name, r.position
                 FROM contestant_masters cm
                 JOIN results r ON r.contestant_id = cm.id AND r.event_instance_id = ?
                 LEFT JOIN houses h ON h.id = cm.house_id
                 LEFT JOIN courses co ON co.id = cm.course_id
                 LEFT JOIN divisions dv ON dv.id = cm.division_id
                 WHERE cm.id = ?",
                [$instanceId, $cid]
            );
            if (!$row) {
                continue; // no result -> skip
            }

            $existing = $certModel->existsFor($instanceId, $cid);
            $number = $existing['certificate_number'] ?? $certModel->nextNumber();

            $html = CertificatePdf::render($template['body_html'], [
                'contestant_name'   => $row['contestant_name'],
                'unique_number'     => $row['unique_number'],
                'house_name'        => $row['house_name'] ?? '',
                'course'            => $row['course_name'] ?? '',
                'division'          => $row['division_name'] ?? '',
                'position'          => self::POS_LABELS[$row['position']] ?? ucfirst($row['position']),
                'event_label'       => $instance['label'],
                'event_name'        => $instance['event_name'],
                'category'          => $instance['category_name'],
                'meet_title'        => $instance['meet_title'],
                'issue_date'        => $issueDate,
                'certificate_number'=> $number,
            ]);

            try {
                $path = CertificatePdf::generate($html, 'cert_' . $instanceId . '_' . $cid . '_' . $number);
            } catch (\RuntimeException $e) {
                $this->json(['success' => false, 'message' => $e->getMessage()], 500);
            }

            if ($existing) {
                $certModel->update((int) $existing['id'], [
                    'template_used' => $templateId,
                    'issue_date'    => $issueDate,
                    'file_path'     => $path,
                    'status'        => 'generated',
                ]);
            } else {
                $certModel->create([
                    'event_instance_id'  => $instanceId,
                    'contestant_id'      => $cid,
                    'certificate_number' => $number,
                    'template_used'      => $templateId,
                    'issue_date'         => $issueDate,
                    'file_path'          => $path,
                    'status'             => 'generated',
                ]);
            }
            $generated++;
        }

        Audit::log('generate_certificates', 'certificates', $instanceId, null, ['count' => $generated]);
        $this->json(['success' => true, 'message' => "Generated {$generated} certificate(s)."]);
    }

    // Download / stream a certificate PDF
    public function download(string $certId): void
    {
        $this->authorize('super_admin', 'campus_admin', 'event_user', 'campus_staff');
        $certId = (int) $certId;
        $cert = (new Certificate())->find($certId);
        if (!$cert) {
            $this->abort(404, 'Certificate not found.');
        }
        // Ownership via instance campus
        $detail = (new EventInstance())->detail((int) $cert['event_instance_id']);
        if (!$detail || (Auth::campusId() !== null && (int) $detail['campus_id'] !== (int) Auth::campusId())) {
            $this->abort(403, 'Not allowed.');
        }

        $full = PUBLIC_PATH . '/assets/' . ltrim((string) $cert['file_path'], '/');
        if (!$cert['file_path'] || !is_file($full)) {
            $this->abort(404, 'Certificate file is missing.');
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $cert['certificate_number'] . '.pdf"');
        header('Content-Length: ' . filesize($full));
        readfile($full);
        exit;
    }
}
