<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Validator;
use App\Models\Category;
use App\Models\ContestantMaster;
use App\Models\DisciplineMaster;
use App\Models\EventInstance;
use App\Models\EventMaster;
use App\Models\MeetMaster;
use App\Models\PointConfig;

/**
 * Configuration hub for a single meet: disciplines, categories, events,
 * event instances and points. Every action re-verifies that the meet belongs
 * to the current user's campus.
 */
class MeetSetupController extends Controller
{
    private function meetOrAbort(int $meetId): array
    {
        $this->authorize('super_admin', 'campus_admin');
        $meet = (new MeetMaster())->find($meetId);
        if (!$meet) {
            $this->abort(404, 'Meet not found.');
        }
        return $meet;
    }

    // ------------------------------------------------------------------
    public function show(string $meetId): void
    {
        $meetId = (int) $meetId;
        $meet = $this->meetOrAbort($meetId);

        $disciplines = (new DisciplineMaster())->forMeet($meetId);
        $categories  = (new Category())->forMeet($meetId);
        $events      = (new EventMaster())->forMeet($meetId);
        $instances   = (new EventInstance())->forMeet($meetId);
        $points      = (new PointConfig())->forMeet($meetId);

        $this->view('meets/setup', [
            'title'       => 'Meet Setup · ' . $meet['title'],
            'meet'        => $meet,
            'disciplines' => $disciplines,
            'categories'  => $categories,
            'events'      => $events,
            'instances'   => $instances,
            'points'      => $points,
            'disciplineOptions' => (new DisciplineMaster())->optionsForMeet($meetId),
            'categoryOptions'   => (new Category())->optionsForMeet($meetId),
        ]);
    }

    // ================= Disciplines =================
    public function storeDiscipline(string $meetId): void
    {
        $meetId = (int) $meetId;
        $this->meetOrAbort($meetId);
        $data = ['name' => Request::input('name'), 'description' => Request::input('description'), 'status' => Request::input('status', 'active'), 'meet_id' => $meetId];
        if ($err = $this->validateChild($data, ['name' => 'required|max:150', 'status' => 'required|in:active,inactive'])) {
            $this->json(['success' => false, 'errors' => $err], 422);
        }
        $id = (new DisciplineMaster())->create($data);
        Audit::log('create', 'discipline_masters', $id, null, $data);
        $this->json(['success' => true, 'message' => 'Discipline added.']);
    }

    public function updateDiscipline(string $meetId, string $id): void
    {
        $meetId = (int) $meetId; $id = (int) $id;
        $this->meetOrAbort($meetId);
        $model = new DisciplineMaster();
        $this->childBelongsOrAbort($model->find($id), $meetId);
        $data = ['name' => Request::input('name'), 'description' => Request::input('description'), 'status' => Request::input('status', 'active')];
        if ($err = $this->validateChild($data, ['name' => 'required|max:150', 'status' => 'required|in:active,inactive'])) {
            $this->json(['success' => false, 'errors' => $err], 422);
        }
        $model->update($id, $data);
        Audit::log('update', 'discipline_masters', $id, null, $data);
        $this->json(['success' => true, 'message' => 'Discipline updated.']);
    }

    public function deleteDiscipline(string $meetId, string $id): void
    {
        $meetId = (int) $meetId; $id = (int) $id;
        $this->meetOrAbort($meetId);
        $model = new DisciplineMaster();
        $this->childBelongsOrAbort($model->find($id), $meetId);
        $this->tryDelete(fn() => $model->delete($id), 'discipline_masters', $id, 'This discipline has events.');
    }

    // ================= Categories =================
    public function storeCategory(string $meetId): void
    {
        $meetId = (int) $meetId;
        $this->meetOrAbort($meetId);
        $data = ['name' => Request::input('name'), 'description' => Request::input('description'), 'status' => Request::input('status', 'active'), 'meet_id' => $meetId];
        if ($err = $this->validateChild($data, ['name' => 'required|max:150', 'status' => 'required|in:active,inactive'])) {
            $this->json(['success' => false, 'errors' => $err], 422);
        }
        $id = (new Category())->create($data);
        Audit::log('create', 'categories', $id, null, $data);
        $this->json(['success' => true, 'message' => 'Category added.']);
    }

    public function updateCategory(string $meetId, string $id): void
    {
        $meetId = (int) $meetId; $id = (int) $id;
        $this->meetOrAbort($meetId);
        $model = new Category();
        $this->childBelongsOrAbort($model->find($id), $meetId);
        $data = ['name' => Request::input('name'), 'description' => Request::input('description'), 'status' => Request::input('status', 'active')];
        if ($err = $this->validateChild($data, ['name' => 'required|max:150', 'status' => 'required|in:active,inactive'])) {
            $this->json(['success' => false, 'errors' => $err], 422);
        }
        $model->update($id, $data);
        Audit::log('update', 'categories', $id, null, $data);
        $this->json(['success' => true, 'message' => 'Category updated.']);
    }

    public function deleteCategory(string $meetId, string $id): void
    {
        $meetId = (int) $meetId; $id = (int) $id;
        $this->meetOrAbort($meetId);
        $model = new Category();
        $this->childBelongsOrAbort($model->find($id), $meetId);
        $this->tryDelete(fn() => $model->delete($id), 'categories', $id, 'This category is used by event instances.');
    }

    // ================= Events =================
    public function storeEvent(string $meetId): void
    {
        $meetId = (int) $meetId;
        $this->meetOrAbort($meetId);
        $disciplineId = (int) Request::input('discipline_id');
        $this->assertDisciplineInMeet($disciplineId, $meetId);
        $data = ['name' => Request::input('name'), 'discipline_id' => $disciplineId, 'event_type' => Request::input('event_type', 'individual'), 'status' => Request::input('status', 'active')];
        if ($err = $this->validateChild($data, ['name' => 'required|max:150', 'event_type' => 'required|in:individual,group', 'status' => 'required|in:active,inactive'])) {
            $this->json(['success' => false, 'errors' => $err], 422);
        }
        $id = (new EventMaster())->create($data);
        Audit::log('create', 'event_masters', $id, null, $data);
        $this->json(['success' => true, 'message' => 'Event added.']);
    }

    public function updateEvent(string $meetId, string $id): void
    {
        $meetId = (int) $meetId; $id = (int) $id;
        $this->meetOrAbort($meetId);
        $model = new EventMaster();
        $event = $model->find($id);
        if (!$event) $this->json(['success' => false, 'message' => 'Not found.'], 404);
        $disciplineId = (int) Request::input('discipline_id');
        $this->assertDisciplineInMeet($disciplineId, $meetId);
        $this->assertDisciplineInMeet((int) $event['discipline_id'], $meetId); // original also in meet
        $data = ['name' => Request::input('name'), 'discipline_id' => $disciplineId, 'event_type' => Request::input('event_type', 'individual'), 'status' => Request::input('status', 'active')];
        if ($err = $this->validateChild($data, ['name' => 'required|max:150', 'event_type' => 'required|in:individual,group', 'status' => 'required|in:active,inactive'])) {
            $this->json(['success' => false, 'errors' => $err], 422);
        }
        $model->update($id, $data);
        Audit::log('update', 'event_masters', $id, null, $data);
        $this->json(['success' => true, 'message' => 'Event updated.']);
    }

    public function deleteEvent(string $meetId, string $id): void
    {
        $meetId = (int) $meetId; $id = (int) $id;
        $this->meetOrAbort($meetId);
        $model = new EventMaster();
        $event = $model->find($id);
        if (!$event) $this->json(['success' => false, 'message' => 'Not found.'], 404);
        $this->assertDisciplineInMeet((int) $event['discipline_id'], $meetId);
        $this->tryDelete(fn() => $model->delete($id), 'event_masters', $id, 'This event has instances.');
    }

    // ================= Event Instances =================
    public function storeInstance(string $meetId): void
    {
        $meetId = (int) $meetId;
        $this->meetOrAbort($meetId);
        $eventId = (int) Request::input('event_id');
        $categoryId = (int) Request::input('category_id');
        $this->assertEventInMeet($eventId, $meetId);
        $this->assertCategoryInMeet($categoryId, $meetId);
        $data = $this->instanceData($eventId, $categoryId);
        if ($err = $this->validateChild($data, ['label' => 'required|max:200', 'status' => 'required|in:scheduled,ongoing,completed,cancelled'])) {
            $this->json(['success' => false, 'errors' => $err], 422);
        }
        $id = (new EventInstance())->create($data);
        Audit::log('create', 'event_instances', $id, null, $data);
        $this->json(['success' => true, 'message' => 'Event instance added.']);
    }

    public function updateInstance(string $meetId, string $id): void
    {
        $meetId = (int) $meetId; $id = (int) $id;
        $this->meetOrAbort($meetId);
        $detail = (new EventInstance())->detail($id);
        if (!$detail || (int) $detail['meet_id'] !== $meetId) {
            $this->json(['success' => false, 'message' => 'Not found.'], 404);
        }
        $eventId = (int) Request::input('event_id');
        $categoryId = (int) Request::input('category_id');
        $this->assertEventInMeet($eventId, $meetId);
        $this->assertCategoryInMeet($categoryId, $meetId);
        $data = $this->instanceData($eventId, $categoryId);
        if ($err = $this->validateChild($data, ['label' => 'required|max:200', 'status' => 'required|in:scheduled,ongoing,completed,cancelled'])) {
            $this->json(['success' => false, 'errors' => $err], 422);
        }
        (new EventInstance())->update($id, $data);
        Audit::log('update', 'event_instances', $id, null, $data);
        $this->json(['success' => true, 'message' => 'Event instance updated.']);
    }

    public function deleteInstance(string $meetId, string $id): void
    {
        $meetId = (int) $meetId; $id = (int) $id;
        $this->meetOrAbort($meetId);
        $detail = (new EventInstance())->detail($id);
        if (!$detail || (int) $detail['meet_id'] !== $meetId) {
            $this->json(['success' => false, 'message' => 'Not found.'], 404);
        }
        $this->tryDelete(fn() => (new EventInstance())->delete($id), 'event_instances', $id, 'This instance has registrations or results.');
    }

    // ================= Points =================
    public function savePoints(string $meetId): void
    {
        $meetId = (int) $meetId;
        $this->meetOrAbort($meetId);
        $points = [
            'first'       => (float) Request::input('first', 0),
            'second'      => (float) Request::input('second', 0),
            'third'       => (float) Request::input('third', 0),
            'participant' => (float) Request::input('participant', 0),
        ];
        (new PointConfig())->save($meetId, $points);
        Audit::log('update', 'point_configs', $meetId, null, $points);
        $this->json(['success' => true, 'message' => 'Point configuration saved.']);
    }

    // ================= Live-screen settings =================
    /**
     * Save the live big-screen settings for a meet: prize-winners scroll
     * speed and the three images (meet logo, banner, institution logo).
     * Each image arrives as an already-cropped file; any that is absent is
     * left unchanged. A `remove_<field>` flag clears an existing image.
     */
    public function saveLiveSettings(string $meetId): void
    {
        $meetId = (int) $meetId;
        $meet = $this->meetOrAbort($meetId);

        $data = [];

        $speed = (int) Request::input('winners_scroll_speed', 28);
        $data['winners_scroll_speed'] = max(5, min(200, $speed));

        $images = [
            'logo'             => 'logo_path',
            'banner'           => 'banner_path',
            'institution_logo' => 'institution_logo_path',
        ];
        foreach ($images as $field => $column) {
            $file = Request::file($field);
            if ($file && ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                try {
                    $data[$column] = \App\Core\FileUpload::image($file, 'meets');
                    \App\Core\FileUpload::delete($meet[$column] ?? null);
                } catch (\RuntimeException $e) {
                    $this->json(['success' => false, 'errors' => [$field => $e->getMessage()], 'message' => $e->getMessage()], 422);
                }
            } elseif (Request::input('remove_' . $field)) {
                \App\Core\FileUpload::delete($meet[$column] ?? null);
                $data[$column] = null;
            }
        }

        try {
            (new MeetMaster())->update($meetId, $data);
        } catch (\PDOException $e) {
            // Most commonly the live-screen columns are missing because the
            // database migration has not been applied yet. Surface a clear,
            // actionable message instead of a generic 500.
            $missingColumn = stripos($e->getMessage(), 'column') !== false;
            error_log('saveLiveSettings failed: ' . $e->getMessage());
            $this->json([
                'success' => false,
                'message' => $missingColumn
                    ? 'Live-screen settings could not be saved: the database is missing the live-screen columns. Please apply migration 002_add_meet_live_settings.sql.'
                    : 'Live-screen settings could not be saved due to a database error.',
            ], 500);
        }
        Audit::log('update', 'meet_masters', $meetId, null, $data);

        $pathFor = fn(string $column) => !array_key_exists($column, $data)
            ? (!empty($meet[$column]) ? asset($meet[$column]) : '')   // unchanged
            : (!empty($data[$column]) ? asset($data[$column]) : '');   // updated or cleared
        $paths = [
            'logo'             => $pathFor('logo_path'),
            'banner'           => $pathFor('banner_path'),
            'institution_logo' => $pathFor('institution_logo_path'),
        ];
        $this->json(['success' => true, 'message' => 'Live-screen settings saved.', 'paths' => $paths, 'winners_scroll_speed' => $data['winners_scroll_speed']]);
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------
    private function instanceData(int $eventId, int $categoryId): array
    {
        return [
            'event_id'      => $eventId,
            'category_id'   => $categoryId,
            'label'         => Request::input('label'),
            'instance_date' => Request::input('instance_date') ?: null,
            'instance_time' => Request::input('instance_time') ?: null,
            'venue'         => Request::input('venue') ?: null,
            'status'        => Request::input('status', 'scheduled'),
        ];
    }

    private function validateChild(array $data, array $rules): ?array
    {
        $v = Validator::make($data, $rules);
        return $v->fails() ? $v->firstErrors() : null;
    }

    private function childBelongsOrAbort(?array $child, int $meetId): void
    {
        if (!$child || (int) ($child['meet_id'] ?? 0) !== $meetId) {
            $this->json(['success' => false, 'message' => 'Record not found in this meet.'], 404);
        }
    }

    private function assertDisciplineInMeet(int $disciplineId, int $meetId): void
    {
        $ok = (int) Database::instance()->scalar(
            "SELECT COUNT(*) FROM discipline_masters WHERE id = ? AND meet_id = ?",
            [$disciplineId, $meetId]
        );
        if (!$ok) $this->json(['success' => false, 'errors' => ['discipline_id' => 'Invalid discipline.']], 422);
    }

    private function assertCategoryInMeet(int $categoryId, int $meetId): void
    {
        $ok = (int) Database::instance()->scalar(
            "SELECT COUNT(*) FROM categories WHERE id = ? AND meet_id = ?",
            [$categoryId, $meetId]
        );
        if (!$ok) $this->json(['success' => false, 'errors' => ['category_id' => 'Invalid category.']], 422);
    }

    private function assertEventInMeet(int $eventId, int $meetId): void
    {
        $ok = (int) Database::instance()->scalar(
            "SELECT COUNT(*) FROM event_masters e JOIN discipline_masters d ON d.id = e.discipline_id
             WHERE e.id = ? AND d.meet_id = ?",
            [$eventId, $meetId]
        );
        if (!$ok) $this->json(['success' => false, 'errors' => ['event_id' => 'Invalid event.']], 422);
    }

    private function tryDelete(callable $fn, string $table, int $id, string $conflictMsg): void
    {
        try {
            $fn();
        } catch (\PDOException $e) {
            $this->json(['success' => false, 'message' => $conflictMsg], 409);
        }
        Audit::log('delete', $table, $id);
        $this->json(['success' => true, 'message' => 'Deleted.']);
    }
}
