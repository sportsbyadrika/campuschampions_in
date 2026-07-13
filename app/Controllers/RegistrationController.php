<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Models\ContestantMaster;
use App\Models\ContestantRegistration;
use App\Models\EventInstance;

/**
 * Manage contestant registrations for a single event instance.
 */
class RegistrationController extends Controller
{
    /** Load instance + verify it belongs to the current campus. */
    private function instanceOrAbort(int $instanceId, array $roles): array
    {
        $this->authorize(...$roles);
        $detail = (new EventInstance())->detail($instanceId);
        if (!$detail) {
            $this->abort(404, 'Event instance not found.');
        }
        if (Auth::campusId() !== null && (int) $detail['campus_id'] !== (int) Auth::campusId()) {
            $this->abort(403, 'This event is not in your campus.');
        }
        return $detail;
    }

    public function show(string $instanceId): void
    {
        $instanceId = (int) $instanceId;
        // Viewers can see registrations; managers can edit
        $instance = $this->instanceOrAbort($instanceId, ['super_admin', 'campus_admin', 'event_user', 'campus_staff']);

        $registrations = (new ContestantRegistration())->forInstance($instanceId);

        // Contestants in campus not yet registered (for the add dropdown)
        $registeredIds = array_column($registrations, 'contestant_id');
        $available = $this->availableContestants((int) $instance['campus_id'], $registeredIds);

        $this->view('registrations/index', [
            'title'         => 'Registrations · ' . $instance['label'],
            'instance'      => $instance,
            'registrations' => $registrations,
            'available'     => $available,
            'canManage'     => Auth::is('super_admin', 'campus_admin', 'event_user'),
        ]);
    }

    public function store(string $instanceId): void
    {
        $instanceId = (int) $instanceId;
        $instance = $this->instanceOrAbort($instanceId, ['super_admin', 'campus_admin', 'event_user']);

        $contestantId = (int) Request::input('contestant_id');
        $contestant = (new ContestantMaster())->find($contestantId);
        if (!$contestant || (int) $contestant['campus_id'] !== (int) $instance['campus_id']) {
            $this->json(['success' => false, 'message' => 'Invalid contestant for this campus.'], 422);
        }

        $reg = new ContestantRegistration();
        if ($reg->isRegistered($contestantId, $instanceId)) {
            $this->json(['success' => false, 'message' => 'Contestant is already registered.'], 409);
        }

        $id = $reg->create([
            'contestant_id'     => $contestantId,
            'event_instance_id' => $instanceId,
            'registration_date' => date('Y-m-d'),
            'status'            => 'registered',
        ]);
        Audit::log('register', 'contestant_registrations', $id, null, ['contestant_id' => $contestantId, 'event_instance_id' => $instanceId]);
        $this->json(['success' => true, 'message' => 'Contestant registered.']);
    }

    public function updateStatus(string $instanceId, string $regId): void
    {
        $instanceId = (int) $instanceId; $regId = (int) $regId;
        $this->instanceOrAbort($instanceId, ['super_admin', 'campus_admin', 'event_user']);

        $status = (string) Request::input('status');
        if (!in_array($status, ['registered', 'confirmed', 'cancelled'], true)) {
            $this->json(['success' => false, 'message' => 'Invalid status.'], 422);
        }
        $reg = new ContestantRegistration();
        $row = $reg->find($regId);
        if (!$row || (int) $row['event_instance_id'] !== $instanceId) {
            $this->json(['success' => false, 'message' => 'Registration not found.'], 404);
        }
        $reg->update($regId, ['status' => $status]);
        Audit::log('update', 'contestant_registrations', $regId, null, ['status' => $status]);
        $this->json(['success' => true, 'message' => 'Status updated.']);
    }

    public function destroy(string $instanceId, string $regId): void
    {
        $instanceId = (int) $instanceId; $regId = (int) $regId;
        $this->instanceOrAbort($instanceId, ['super_admin', 'campus_admin', 'event_user']);

        $reg = new ContestantRegistration();
        $row = $reg->find($regId);
        if (!$row || (int) $row['event_instance_id'] !== $instanceId) {
            $this->json(['success' => false, 'message' => 'Registration not found.'], 404);
        }
        $reg->delete($regId);
        Audit::log('unregister', 'contestant_registrations', $regId, $row, null);
        $this->json(['success' => true, 'message' => 'Registration removed.']);
    }

    private function availableContestants(int $campusId, array $excludeIds): array
    {
        $sql = "SELECT id, unique_number, name FROM contestant_masters WHERE campus_id = ? AND status = 'active'";
        $params = [$campusId];
        if (!empty($excludeIds)) {
            $in = implode(',', array_fill(0, count($excludeIds), '?'));
            $sql .= " AND id NOT IN ($in)";
            $params = array_merge($params, $excludeIds);
        }
        $sql .= " ORDER BY name ASC";
        return Database::instance()->fetchAll($sql, $params);
    }
}
