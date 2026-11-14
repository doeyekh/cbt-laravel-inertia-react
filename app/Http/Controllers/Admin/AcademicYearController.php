<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AcademicYearSemester;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAcademicYearRequest;
use App\Http\Requests\Admin\UpdateAcademicYearRequest;
use App\Models\AcademicYear;
use Carbon\CarbonInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AcademicYearController extends Controller
{
    /**
     * Display a paginated listing of academic years.
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('search'));

        $academicYears = AcademicYear::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('is_active')
            ->orderByDesc('code')
            ->paginate(5)
            ->withQueryString()
            ->through(fn (AcademicYear $academicYear): array => $this->presentAcademicYear($academicYear));

        $activeAcademicYear = AcademicYear::query()
            ->where('is_active', true)
            ->first();
        $totalAcademicYears = AcademicYear::query()->count();

        return Inertia::render('AcademicYears/Index', [
            'title' => 'Manajemen Tahun Pelajaran',
            'academicYears' => $academicYears,
            'filters' => [
                'search' => $search,
            ],
            'semesters' => array_map(
                fn (AcademicYearSemester $semester): array => [
                    'value' => $semester->value,
                    'label' => $semester->label(),
                ],
                AcademicYearSemester::cases()
            ),
            'stats' => [
                'total' => $totalAcademicYears,
                'inactive' => $totalAcademicYears - ($activeAcademicYear ? 1 : 0),
                'active' => $activeAcademicYear
                    ? $this->presentAcademicYear($activeAcademicYear)
                    : null,
            ],
        ]);
    }

    /**
     * Store a newly created academic year.
     */
    public function store(StoreAcademicYearRequest $request): RedirectResponse
    {
        $academicYear = DB::transaction(
            fn (): AcademicYear => AcademicYear::query()->create([
                ...$request->validated(),
                'is_active' => false,
            ])
        );

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Berhasil Ditambahkan',
            'description' => "Tahun pelajaran \"{$academicYear->name}\" semester {$academicYear->semester->label()} telah ditambahkan.",
        ]);
    }

    /**
     * Generate the academic year that matches today's date, if it does not exist yet.
     */
    public function generate(): RedirectResponse
    {
        $period = $this->resolveAcademicPeriod(now());

        $academicYear = AcademicYear::query()->firstOrCreate(
            [
                'name' => $period['name'],
                'semester' => $period['semester']->value,
            ],
            [
                'is_active' => false,
            ]
        );

        if (! $academicYear->wasRecentlyCreated) {
            return back()->with('toast', [
                'type' => 'info',
                'title' => 'Data Sudah Ada',
                'description' => "Tahun pelajaran \"{$academicYear->name}\" semester {$academicYear->semester->label()} (kode {$academicYear->code}) sudah terdaftar. Tidak ada data baru yang ditambahkan.",
            ]);
        }

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Berhasil Dibuat Otomatis',
            'description' => "Tahun pelajaran \"{$academicYear->name}\" semester {$academicYear->semester->label()} (kode {$academicYear->code}) telah ditambahkan berdasarkan tanggal hari ini.",
        ]);
    }

    /**
     * Update the specified academic year.
     */
    public function update(UpdateAcademicYearRequest $request, AcademicYear $academicYear): RedirectResponse
    {
        DB::transaction(fn () => $academicYear->update($request->validated()));

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Berhasil Diperbarui',
            'description' => "Tahun pelajaran \"{$academicYear->name}\" semester {$academicYear->semester->label()} telah diperbarui.",
        ]);
    }

    /**
     * Toggle the active status of the specified academic year.
     */
    public function toggleStatus(AcademicYear $academicYear): RedirectResponse
    {
        $academicYear->update([
            'is_active' => ! $academicYear->is_active,
        ]);

        $isActive = $academicYear->is_active;

        return back()->with('toast', [
            'type' => 'success',
            'title' => $isActive ? 'Tahun Pelajaran Diaktifkan' : 'Tahun Pelajaran Dinonaktifkan',
            'description' => "Tahun pelajaran \"{$academicYear->name}\" semester {$academicYear->semester->label()} sekarang ".($isActive ? 'aktif' : 'tidak aktif').'.',
        ]);
    }

    /**
     * Remove the specified academic year.
     */
    public function destroy(AcademicYear $academicYear): RedirectResponse
    {
        $name = $academicYear->name;
        $semester = $academicYear->semester->label();

        $academicYear->delete();

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Berhasil Dihapus',
            'description' => "Tahun pelajaran \"{$name}\" semester {$semester} telah dihapus.",
        ]);
    }

    /**
     * Resolve the academic year name and semester for the given date.
     *
     * January to June belongs to the even semester of the previous school
     * year, while July to December starts a new school year on the odd
     * semester.
     *
     * @return array{name: string, semester: AcademicYearSemester}
     */
    private function resolveAcademicPeriod(CarbonInterface $date): array
    {
        $isEvenSemester = $date->month <= 6;
        $startYear = $isEvenSemester ? $date->year - 1 : $date->year;

        return [
            'name' => "{$startYear}/".($startYear + 1),
            'semester' => $isEvenSemester
                ? AcademicYearSemester::Genap
                : AcademicYearSemester::Ganjil,
        ];
    }

    /**
     * Transform an academic year into its frontend representation.
     *
     * @return array{id: int, code: string, name: string, semester: string, semester_label: string, is_active: bool}
     */
    private function presentAcademicYear(AcademicYear $academicYear): array
    {
        return [
            'id' => $academicYear->id,
            'code' => $academicYear->code,
            'name' => $academicYear->name,
            'semester' => $academicYear->semester->value,
            'semester_label' => $academicYear->semester->label(),
            'is_active' => $academicYear->is_active,
        ];
    }
}
