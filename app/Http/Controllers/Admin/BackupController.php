<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BackupType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RestoreBackupRequest;
use App\Http\Requests\Admin\StoreBackupRequest;
use App\Services\DatabaseBackupService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class BackupController extends Controller
{
    public function index(DatabaseBackupService $service): Response
    {
        return Inertia::render('Backups/Index', [
            'title' => 'Backup & Restore Database',
            'backups' => $service->all(),
            'types' => collect(BackupType::cases())
                ->map(fn (BackupType $type): array => [
                    'value' => $type->value,
                    'label' => $type->label(),
                    'description' => $type->description(),
                ])
                ->values()
                ->all(),
            'settings' => [
                'connection' => (string) config('database.default'),
                'disk' => (string) (config('backup.backup.destination.disks')[0] ?? 'backups'),
                'keep_days' => 7,
            ],
        ]);
    }

    public function store(StoreBackupRequest $request, DatabaseBackupService $service): RedirectResponse
    {
        $type = $request->enum('type', BackupType::class) ?? BackupType::Database;

        try {
            $backup = $service->create($this->currentUser(), $type);
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('toast', [
                'type' => 'error',
                'title' => 'Backup Gagal',
                'description' => $exception->getMessage(),
            ]);
        }

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Backup Berhasil',
            'description' => "Backup {$backup['type_label']} {$backup['filename']} telah dibuat (".number_format($backup['size_bytes'] / 1024, 1).' KB).',
        ]);
    }

    public function download(string $backup, DatabaseBackupService $service): StreamedResponse
    {
        try {
            return $service->download($backup);
        } catch (Throwable $exception) {
            abort(404, $exception->getMessage());
        }
    }

    public function restore(
        RestoreBackupRequest $request,
        string $backup,
        DatabaseBackupService $service
    ): RedirectResponse {
        try {
            $result = $service->restore($backup, $this->currentUser());
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('toast', [
                'type' => 'error',
                'title' => 'Restore Gagal',
                'description' => $exception->getMessage(),
            ]);
        }

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Restore Berhasil',
            'description' => "Database dipulihkan dari {$result['backup']}. Safety backup: {$result['safety']}.",
        ]);
    }

    public function destroy(string $backup, DatabaseBackupService $service): RedirectResponse
    {
        try {
            $service->delete($backup);
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('toast', [
                'type' => 'error',
                'title' => 'Hapus Gagal',
                'description' => $exception->getMessage(),
            ]);
        }

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Backup Dihapus',
            'description' => "Backup {$backup} telah dihapus.",
        ]);
    }

    private function currentUser(): ?Authenticatable
    {
        return auth()->user();
    }
}
