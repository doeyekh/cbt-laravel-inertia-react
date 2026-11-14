<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRoomRequest;
use App\Http\Requests\Admin\UpdateRoomRequest;
use App\Models\Room;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class RoomController extends Controller
{
    /**
     * Display a paginated listing of rooms.
     */
    public function index(Request $request): Response
    {
        $this->authorizeRoomAccess($request->user());

        $accessibleSchoolIds = $request->user()->accessibleSchoolIds();
        $search = trim((string) $request->string('search'));

        $schoolFilter = (int) $request->integer('school_id');

        if (! in_array($schoolFilter, $accessibleSchoolIds, true)) {
            $schoolFilter = 0;
        }

        $baseQuery = Room::query()
            ->whereIn('school_id', $accessibleSchoolIds)
            ->when($schoolFilter > 0, fn ($query) => $query->where('school_id', $schoolFilter));

        $rooms = (clone $baseQuery)
            ->with('school')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('building', 'like', "%{$search}%")
                        ->orWhereHas('school', function ($query) use ($search): void {
                            $query->where('name', 'like', "%{$search}%")
                                ->orWhere('code', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate(5)
            ->withQueryString()
            ->through(fn (Room $room): array => $this->presentRoom($room));

        $totalRooms = (clone $baseQuery)->count();
        $activeCount = (clone $baseQuery)->where('is_active', true)->count();

        $activeRoom = (clone $baseQuery)
            ->with('school')
            ->where('is_active', true)
            ->first();

        $schools = School::query()
            ->whereIn('id', $accessibleSchoolIds)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'is_active']);

        return Inertia::render('Rooms/Index', [
            'title' => 'Manajemen Ruangan Ujian',
            'rooms' => $rooms,
            'schools' => $schools,
            'filters' => [
                'search' => $search,
                'school_id' => $schoolFilter > 0 ? $schoolFilter : null,
            ],
            'stats' => [
                'total' => $totalRooms,
                'active' => $activeRoom ? $this->presentRoom($activeRoom) : null,
                'inactive' => $totalRooms - $activeCount,
            ],
        ]);
    }

    /**
     * Store a newly created room.
     */
    public function store(StoreRoomRequest $request): RedirectResponse
    {
        $this->authorizeRoomManage($request->user());

        $data = $request->validated();

        $this->authorizeSchoolAccess($request->user(), (int) $data['school_id']);

        $room = DB::transaction(
            fn (): Room => Room::create([
                'school_id' => (int) $data['school_id'],
                'code' => strtoupper(trim((string) $data['code'])),
                'name' => trim((string) $data['name']),
                'building' => trim((string) $data['building']),
                'floor' => trim((string) $data['floor']),
                'capacity' => (int) $data['capacity'],
                'is_active' => false,
            ])
        );

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Berhasil Ditambahkan',
            'description' => "Ruangan \"{$room->name}\" (kode {$room->code}) telah ditambahkan.",
        ]);
    }

    /**
     * Update the specified room.
     */
    public function update(UpdateRoomRequest $request, Room $room): RedirectResponse
    {
        $this->authorizeRoomManage($request->user());
        $this->authorizeRecordAccess($request->user(), $room);

        $data = $request->validated();

        $room->update([
            'code' => isset($data['code']) ? strtoupper(trim((string) $data['code'])) : $room->code,
            'name' => isset($data['name']) ? trim((string) $data['name']) : $room->name,
            'building' => isset($data['building']) ? trim((string) $data['building']) : $room->building,
            'floor' => isset($data['floor']) ? trim((string) $data['floor']) : $room->floor,
            'capacity' => $data['capacity'] ?? $room->capacity,
            'is_active' => array_key_exists('is_active', $data) ? $data['is_active'] : $room->is_active,
        ]);

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Berhasil Diperbarui',
            'description' => "Ruangan \"{$room->name}\" (kode {$room->code}) telah diperbarui.",
        ]);
    }

    /**
     * Toggle the active status of the specified room.
     * Lebih dari satu ruangan boleh aktif sekaligus.
     */
    public function toggleStatus(Request $request, $roomId): RedirectResponse
    {
        $this->authorizeRoomManage($request->user());

        $room = Room::findOrFail($roomId);

        $this->authorizeRecordAccess($request->user(), $room);

        $wasActive = $room->is_active;

        $room->update([
            'is_active' => ! $wasActive,
        ]);

        $isActive = $room->is_active;

        return back()->with('toast', [
            'type' => 'success',
            'title' => $isActive ? 'Ruangan Diaktifkan' : 'Ruangan Dinonaktifkan',
            'description' => "Ruangan \"{$room->name}\" sekarang ".($isActive ? 'aktif' : 'tidak aktif').'.',
        ]);
    }

    /**
     * Remove the specified room.
     */
    public function destroy(Request $request, Room $room): RedirectResponse
    {
        $this->authorizeRoomManage($request->user());
        $this->authorizeRecordAccess($request->user(), $room);

        $name = $room->name;

        $room->delete();

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Berhasil Dihapus',
            'description' => "Ruangan \"{$name}\" telah dihapus.",
        ]);
    }

    /**
     * Determine whether the user may access the room module.
     */
    private function authorizeRoomAccess(?User $user): void
    {
        if ($user === null) {
            abort(403);
        }

        if (! $user->isAdministrator() && ! $user->isHeadmaster() && ! $user->isSchoolAdmin()) {
            abort(403);
        }
    }

    /**
     * Determine whether the user may make changes to room data.
     */
    private function authorizeRoomManage(User $user): void
    {
        if ($user->isAdministrator() || $user->isSchoolAdmin()) {
            return;
        }

        abort(403);
    }

    /**
     * Determine whether the user may use the given school.
     */
    private function authorizeSchoolAccess(User $user, int $schoolId): void
    {
        if (! in_array($schoolId, $user->accessibleSchoolIds(), true)) {
            abort(403);
        }
    }

    /**
     * Determine whether the user may access the given room.
     */
    private function authorizeRecordAccess(User $user, Room $room): void
    {
        $this->authorizeSchoolAccess($user, (int) $room->school_id);
    }

    /**
     * Transform a room into its frontend representation.
     *
     * @return array{id: int, school_id: int|null, school_name: string|null, school_code: string|null, code: string, name: string, building: string, floor: string, capacity: int, is_active: bool}
     */
    private function presentRoom(Room $room): array
    {
        return [
            'id' => $room->id,
            'school_id' => $room->school_id,
            'school_name' => $room->school?->name,
            'school_code' => $room->school?->code,
            'code' => $room->code,
            'name' => $room->name,
            'building' => $room->building,
            'floor' => $room->floor,
            'capacity' => $room->capacity,
            'is_active' => (bool) $room->is_active,
        ];
    }
}
