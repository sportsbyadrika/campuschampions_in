<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\CertificatePdf;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Flash;
use App\Core\Request;
use App\Models\CertificateTemplate;

/**
 * Certificate templates — full-page add/edit (not a modal), with print-layout
 * configuration: page orientation, content margins, and the positions of the
 * certificate number and date, plus the per-meet numbering (prefix/next/suffix).
 */
class CertificateTemplateController extends Controller
{
    private const MANAGE = ['super_admin', 'campus_admin'];

    /** List templates the current user can manage. */
    public function index(): void
    {
        $this->authorize(...self::MANAGE);
        $campusId = Auth::campusId();
        $sql = "SELECT id, name, orientation, is_default, status, updated_at FROM certificate_templates";
        $params = [];
        if ($campusId !== null) {
            $sql .= " WHERE campus_id = ? OR campus_id IS NULL";
            $params[] = $campusId;
        }
        $sql .= " ORDER BY is_default DESC, name";
        $this->view('certificate_templates/index', [
            'title'     => 'Certificate Templates',
            'templates' => Database::instance()->fetchAll($sql, $params),
        ]);
    }

    /** New-template form. */
    public function create(): void
    {
        $this->authorize(...self::MANAGE);
        $this->view('certificate_templates/form', [
            'title'    => 'New Certificate Template',
            'template' => $this->defaults(),
            'isNew'    => true,
        ]);
    }

    /** Edit-template form. */
    public function editPage(string $id): void
    {
        $this->authorize(...self::MANAGE);
        $template = (new CertificateTemplate())->find((int) $id);
        if (!$template) {
            $this->abort(404, 'Template not found.');
        }
        $this->view('certificate_templates/form', [
            'title'    => 'Edit Certificate Template',
            'template' => $template,
            'isNew'    => false,
        ]);
    }

    public function store(): void
    {
        $this->authorize(...self::MANAGE);
        $data = $this->collect();
        if ($err = $this->validate($data)) {
            Flash::error($err);
            $this->redirect('/certificate-templates/new');
        }
        $data['campus_id'] = Auth::campusId();
        $id = (new CertificateTemplate())->create($data);
        Audit::log('create', 'certificate_templates', $id, null, ['name' => $data['name']]);
        Flash::success('Certificate template created.');
        $this->redirect('/certificate-templates');
    }

    public function update(string $id): void
    {
        $this->authorize(...self::MANAGE);
        $id = (int) $id;
        $model = new CertificateTemplate();
        $existing = $model->find($id);
        if (!$existing) {
            $this->abort(404, 'Template not found.');
        }
        $data = $this->collect();
        if ($err = $this->validate($data)) {
            Flash::error($err);
            $this->redirect('/certificate-templates/' . $id . '/edit');
        }
        $model->update($id, $data);
        Audit::log('update', 'certificate_templates', $id, $existing, $data);
        Flash::success('Certificate template updated.');
        $this->redirect('/certificate-templates');
    }

    public function destroy(string $id): void
    {
        $this->authorize(...self::MANAGE);
        $id = (int) $id;
        $model = new CertificateTemplate();
        if (!$model->find($id)) {
            $this->abort(404, 'Template not found.');
        }
        try {
            $model->delete($id);
        } catch (\PDOException $e) {
            Flash::error('This template is in use by generated certificates and cannot be deleted.');
            $this->redirect('/certificate-templates');
        }
        Audit::log('delete', 'certificate_templates', $id);
        Flash::success('Certificate template deleted.');
        $this->redirect('/certificate-templates');
    }

    /** Preview the current form config as an inline PDF with sample data. */
    public function preview(): void
    {
        $this->authorize(...self::MANAGE);
        $tpl = $this->collect();
        $sample = [
            'contestant_name'    => 'Aarav Sharma',
            'unique_number'      => 'U-1024',
            'house_name'         => 'Red House',
            'course'             => 'Grade 10',
            'division'           => 'A',
            'position'           => 'First',
            'event_label'        => 'Boys Solo Singing — Final',
            'event_name'         => 'Solo Singing',
            'category'           => 'Junior',
            'meet_title'         => 'Annual Arts Fest 2026',
            'issue_date'         => date('d M Y'),
            'certificate_number' => (string) ($tpl['number_prefix'] ?? '') . (int) ($tpl['number_next'] ?? 1) . (string) ($tpl['number_suffix'] ?? ''),
        ];
        CertificatePdf::stream(CertificatePdf::compose($tpl, $sample), 'certificate_preview', $tpl['orientation']);
    }

    // ------------------------------------------------------------------
    private function defaults(): array
    {
        return [
            'name' => '', 'body_html' => CertificateTemplate::sampleBody(),
            'orientation' => 'portrait',
            'margin_top' => 95, 'margin_right' => 22, 'margin_bottom' => 80, 'margin_left' => 22,
            'number_top' => 12, 'number_left' => 15, 'number_label' => '', 'number_font_size' => 11, 'number_font_color' => '#333333',
            'date_top' => 262, 'date_left' => 20, 'date_font_size' => 11, 'date_font_color' => '#333333',
            'number_prefix' => '', 'number_suffix' => '', 'number_next' => 1,
            'is_default' => 0, 'status' => 'active',
        ];
    }

    /** Collect + sanitise the posted form fields. */
    private function collect(): array
    {
        $int = fn(string $k, int $def, int $min = 0, int $max = 1000) => max($min, min($max, (int) Request::input($k, $def)));
        return [
            'name'          => trim((string) Request::input('name', '')),
            'body_html'     => (string) Request::input('body_html', ''),
            'orientation'   => Request::input('orientation') === 'landscape' ? 'landscape' : 'portrait',
            'margin_top'    => $int('margin_top', 95),
            'margin_right'  => $int('margin_right', 22),
            'margin_bottom' => $int('margin_bottom', 80),
            'margin_left'   => $int('margin_left', 22),
            'number_top'    => $int('number_top', 12),
            'number_left'   => $int('number_left', 15),
            'number_label'  => substr((string) Request::input('number_label', ''), 0, 60),
            'number_font_size'  => $int('number_font_size', 11, 6, 72),
            'number_font_color' => $this->hex(Request::input('number_font_color', '#333333')),
            'date_top'      => $int('date_top', 262),
            'date_left'     => $int('date_left', 20),
            'date_font_size'    => $int('date_font_size', 11, 6, 72),
            'date_font_color'   => $this->hex(Request::input('date_font_color', '#333333')),
            'number_prefix' => substr((string) Request::input('number_prefix', ''), 0, 30),
            'number_suffix' => substr((string) Request::input('number_suffix', ''), 0, 30),
            'number_next'   => max(1, (int) Request::input('number_next', 1)),
            'is_default'    => Request::input('is_default') ? 1 : 0,
            'status'        => Request::input('status') === 'inactive' ? 'inactive' : 'active',
        ];
    }

    /** Sanitise a hex colour value. */
    private function hex(mixed $value): string
    {
        $v = trim((string) $value);
        return preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $v) ? $v : '#333333';
    }

    private function validate(array $data): ?string
    {
        if ($data['name'] === '') {
            return 'Template name is required.';
        }
        if (trim($data['body_html']) === '') {
            return 'Certificate body HTML is required.';
        }
        return null;
    }
}
