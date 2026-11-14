<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSessionRequest;
use App\Http\Requests\Admin\UpdateSessionRequest;
use App\Models\School;
use App\Models\Session;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SessionController extends Controller
{
    /**
     * Display a paginated listing of sessions.
     */
    public function index(Request $request): Response
    {
        $this->authorizeSessionAccess($request->user());

        $accessibleSchoolIds = $request->user()->accessibleSchoolIds();
        $search = trim((string) $request->string('search'));

        $schoolFilter = (int) $request->integer('school_id');

        if (! in_array($schoolFilter, $accessibleSchoolIds, true)) {
            $schoolFilter = 0;
        }

        $baseQuery = Session::query()
            ->whereIn('school_id', $accessibleSchoolIds)
            ->when($schoolFilter > 0, fn ($query) => $query->where('school_id', $schoolFilter));

        $sessions = (clone $baseQuery)
            ->with('school')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhereHas('school', function ($query) use ($search): void {
                            $query->where('name', 'like', "%{$search}%")
                                ->orWhere('code', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate(5)
            ->withQueryString()
            ->through(fn (Session $session): array => $this->presentSession($session));

        $totalSessions = (clone $baseQuery)->count();
        $activeCount = (clone $baseQuery)->where('is_active', true)->count();

        $activeSession = (clone $baseQuery)
            ->with('school')
            ->where('is_active', true)
            ->first();

        $schools = School::query()
            ->whereIn('id', $accessibleSchoolIds)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'is_active']);

        return Inertia::render('Sessions/Index', [
            'title' => 'Manajemen Sesi Ujian',
            'sessions' => $sessions,
            'schools' => $schools,
            'filters' => [
                'search' => $search,
                'school_id' => $schoolFilter > 0 ? $schoolFilter : null,
            ],
            'stats' => [
                'total' => $totalSessions,
                'active' => $activeSession ? $this->presentSession($activeSession) : null,
                'inactive' => $totalSessions - $activeCount,
            ],
        ]);
    }

    /**
     * Store a newly created session.
     */
    public function store(StoreSessionRequest $request): RedirectResponse
    {
        $this->authorizeSessionManage($request->user());

        $data = $request->validated();

        $this->authorizeSchoolAccess($request->user(), (int) $data['school_id']);

        $session = DB::transaction(
            fn (): Session => Session::create([
                'school_id' => (int) $data['school_id'],
                'code' => strtoupper(trim((string) $data['code'])),
                'name' => trim((string) $data['name']),
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'is_active' => false,
            ])
        );

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Berhasil Ditambahkan',
            'description' => "Sesi \"{$session->name}\" (kode {$session->code}) telah ditambahkan.",
        ]);
    }

    /**
     * Update the specified session.
     */
    public function update(UpdateSessionRequest $request, Session $session): RedirectResponse
    {
        $this->authorizeSessionManage($request->user());
        $this->authorizeRecordAccess($request->user(), $session);

        $data = $request->validated();

        $session->update([
            'code' => isset($data['code']) ? strtoupper(trim((string) $data['code'])) : $session->code,
            'name' => isset($data['name']) ? trim((string) $data['name']) : $session->name,
            'start_time' => $data['start_time'] ?? $session->start_time,
            'end_time' => $data['end_time'] ?? $session->end_time,
            'is_active' => array_key_exists('is_active', $data) ? $data['is_active'] : $session->is_active,
        ]);

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Berhasil Diperbarui',
            'description' => "Sesi \"{$session->name}\" (kode {$session->code}) telah diperbarui.",
        ]);
    }

    /**
     * Toggle the active status of the specified session.
     * Lebih dari satu sesi boleh aktif sekaligus.
     */
    public function toggleStatus(Request $request, $sessionId): RedirectResponse
    {
        $this->authorizeSessionManage($request->user());

        $session = Session::findOrFail($sessionId);

        $this->authorizeRecordAccess($request->user(), $session);

        $wasActive = $session->is_active;

        $session->update([
            'is_active' => ! $wasActive,
        ]);

        $isActive = $session->is_active;

        return back()->with('toast', [
            'type' => 'success',
            'title' => $isActive ? 'Sesi Diaktifkan' : 'Sesi Dinonaktifkan',
            'description' => "Sesi \"{$session->name}\" sekarang ".($isActive ? 'aktif' : 'tidak aktif').'.',
        ]);
    }

    /**
     * Remove the specified session.
     */
    public function destroy(Request $request, Session $session): RedirectResponse
    {
        $this->authorizeSessionManage($request->user());
        $this->authorizeRecordAccess($request->user(), $session);

        $name = $session->name;

        $session->delete();

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Berhasil Dihapus',
            'description' => "Sesi \"{$name}\" telah dihapus.",
        ]);
    }

    /**
     * Determine whether the user may access the session module.
     */
    private function authorizeSessionAccess(?User $user): void
    {
        if ($user === null) {
            abort(403);
        }

        if (! $user->isAdministrator() && ! $user->isHeadmaster() && ! $user->isSchoolAdmin()) {
            abort(403);
        }
    }

    /**
     * Determine whether the user may make changes to session data.
     */
    private function authorizeSessionManage(User $user): void
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
     * Determine whether the user may access the given session.
     */
    private function authorizeRecordAccess(User $user, Session $session): void
    {
        $this->authorizeSchoolAccess($user, (int) $session->school_id);
    }

    /**
     * Transform a session into its frontend representation.
     *
     * @return array{id: int, school_id: int|null, school_name: string|null, school_code: string|null, code: string, name: string, start_time: string, end_time: string, is_active: bool}
     */
    private function presentSession(Session $session): array
    {
        return [
            'id' => $session->id,
            'school_id' => $session->school_id,
            'school_name' => $session->school?->name,
            'school_code' => $session->school?->code,
            'code' => $session->code,
            'name' => $session->name,
            'start_time' => $session->start_time,
            'end_time' => $session->end_time,
            'is_active' => (bool) $session->is_active,
        ];
    }
}
