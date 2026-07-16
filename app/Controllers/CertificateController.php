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
        $instanceId = (int) Request::get('instance_id', 0);

        $where = [];
        $params = [];
        if (Auth::campusId() !== null) { $where[] = 'm.campus_id = ?'; $params[] = Auth::campusId(); }
        if ($meetId > 0) { $where[] = 'm.id = ?'; $params[] = $meetId; }

        // Searchable event-instance dropdown options (campus + meet scope), alphabetical.
        $optWhereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $instanceChoices = Database::instance()->fetchAll(
            "SELECT ei.id, ei.label
             FROM event_instances ei
             JOIN event_masters e ON e.id = ei.event_id
             JOIN discipline_masters d ON d.id = e.discipline_id
             JOIN meet_masters m ON m.id = d.meet_id
             $optWhereSql
             ORDER BY ei.label ASC",
            $params
        );

        if ($instanceId > 0) { $where[] = 'ei.id = ?'; $params[] = $instanceId; }
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
            'title'           => 'Certificates',
            'instances'       => $instances,
            'meets'           => (new MeetMaster())->options(),
            'meetId'          => $meetId,
            'instanceChoices' => $instanceChoices,
            'instanceId'      => $instanceId,
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
            'certCount'   => (int) Database::instance()->scalar(
                "SELECT COUNT(*) FROM certificates WHERE event_instance_id = ?", [$instanceId]
            ),
        ]);
    }

    /** Bulk view: all generated certificates for an instance as one PDF. */
    public function printAll(string $instanceId): void
    {
        $instanceId = (int) $instanceId;
        $instance = $this->instanceOrAbort($instanceId);

        $rows = Database::instance()->fetchAll(
            "SELECT ce.certificate_number, ce.issue_date, ce.template_used,
                    cm.name AS contestant_name, cm.unique_number, h.name AS house_name,
                    co.name AS course_name, dv.name AS division_name, r.position
             FROM certificates ce
             JOIN contestant_masters cm ON cm.id = ce.contestant_id
             JOIN results r ON r.contestant_id = cm.id AND r.event_instance_id = ce.event_instance_id
             LEFT JOIN houses h ON h.id = cm.house_id
             LEFT JOIN courses co ON co.id = cm.course_id
             LEFT JOIN divisions dv ON dv.id = cm.division_id
             WHERE ce.event_instance_id = ?
             ORDER BY FIELD(r.position,'first','second','third','participant'), cm.name",
            [$instanceId]
        );
        if (empty($rows)) {
            $this->abort(404, 'No certificates have been generated for this event yet.');
        }

        $tplModel = new CertificateTemplate();
        $tplCache = [];
        $orientation = 'portrait';
        $pages = [];
        foreach ($rows as $row) {
            $tid = (int) $row['template_used'];
            if (!array_key_exists($tid, $tplCache)) {
                $tplCache[$tid] = $tplModel->usableById($tid);
            }
            $tpl = $tplCache[$tid];
            if (!$tpl) {
                continue;
            }
            $orientation = $tpl['orientation'] ?? 'portrait';
            $pages[] = CertificatePdf::compose($tpl, [
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
                'issue_date'        => $row['issue_date'] ? date('d M Y', strtotime((string) $row['issue_date'])) : '',
                'certificate_number'=> $row['certificate_number'],
            ]);
        }
        CertificatePdf::streamCombined($pages, $orientation, 'certificates_' . $instanceId);
    }

    /** Delete a generated certificate (so it can be regenerated after a fix). */
    public function deleteCertificate(string $certId): void
    {
        $this->authorize('super_admin', 'campus_admin', 'event_user');
        $certId = (int) $certId;
        $cert = (new Certificate())->find($certId);
        if (!$cert) {
            $this->json(['success' => false, 'message' => 'Certificate not found.'], 404);
        }
        // Ownership: the certificate's instance must be in the user's campus.
        $detail = (new EventInstance())->detail((int) $cert['event_instance_id']);
        if (!$detail || (Auth::campusId() !== null && (int) $detail['campus_id'] !== (int) Auth::campusId())) {
            $this->json(['success' => false, 'message' => 'Not allowed.'], 403);
        }
        \App\Core\FileUpload::delete($cert['file_path'] ?? null);
        (new Certificate())->delete($certId);
        Audit::log('delete', 'certificates', $certId, $cert, null);
        $this->json(['success' => true, 'message' => 'Certificate deleted.']);
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
        $db = Database::instance();
        $meetId = (int) $instance['meet_id'];

        // Certificate date: chosen on the form (defaults to today), shown formatted.
        $rawDate   = (string) (Request::input('issue_date') ?: date('Y-m-d'));
        $ts        = strtotime($rawDate) ?: time();
        $issueDate = date('Y-m-d', $ts);
        $displayDate = date('d M Y', $ts);

        // Per-meet running sequence, seeded from the template's configured next number.
        $seq = $db->scalar("SELECT cert_next_seq FROM meet_masters WHERE id = ?", [$meetId]);
        $seq = $seq !== null ? (int) $seq : (int) ($template['number_next'] ?? 1);
        $orientation = $template['orientation'] ?? 'portrait';

        foreach ($contestantIds as $cid) {
            $cid = (int) $cid;
            // Fetch contestant + result + house + course/division
            $row = $db->fetch(
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
            if ($existing) {
                $number = $existing['certificate_number']; // keep the original number
            } else {
                $number = (string) ($template['number_prefix'] ?? '') . $seq . (string) ($template['number_suffix'] ?? '');
                $seq++;
            }

            $html = CertificatePdf::compose($template, [
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
                'issue_date'        => $displayDate,
                'certificate_number'=> $number,
            ]);

            try {
                $path = CertificatePdf::generate($html, 'cert_' . $instanceId . '_' . $cid . '_' . $number, $orientation);
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

        // Persist the advanced per-meet sequence.
        $db->query("UPDATE meet_masters SET cert_next_seq = ? WHERE id = ?", [$seq, $meetId]);

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
