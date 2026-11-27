import React, { useMemo, useState } from "react";
import AppLayout from "@/Layouts/AppLayout";
import Button from "@/Components/UI/Button";
import Card from "@/Components/UI/Card";
import Input from "@/Components/UI/Input";
import Select from "@/Components/UI/Select";
import { route } from "@/Utils/route";

const DEFAULT_CRITERIA = "Persiapan, Pelaksanaan, Hasil, Sikap";

function ActionCard({ icon, title, description, disabled, disabledHint, onPrint, children }) {
    return (
        <Card className="flex h-full flex-col">
            <div className="flex items-start gap-4">
                <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-primary/10">
                    <span className="material-symbols-outlined text-[24px] text-primary">
                        {icon}
                    </span>
                </div>

                <div className="min-w-0 space-y-1">
                    <h3 className="font-headline text-lg font-semibold text-on-surface">
                        {title}
                    </h3>
                    <p className="text-sm text-on-surface-variant font-body">
                        {description}
                    </p>
                </div>
            </div>

            {children && <div className="mt-4">{children}</div>}

            <div className="mt-auto pt-5">
                <Button
                    variant="primary"
                    onClick={onPrint}
                    disabled={disabled}
                    className="w-full justify-center"
                >
                    <span className="material-symbols-outlined mr-1 text-[18px]">
                        print
                    </span>
                    Cetak PDF
                </Button>

                {disabled && disabledHint && (
                    <p className="mt-2 text-[11px] text-on-surface-variant font-body">
                        {disabledHint}
                    </p>
                )}
            </div>
        </Card>
    );
}

export default function Index({
    title,
    school = null,
    activeAcademicYear = null,
    examTypes = [],
    subjects = [],
    classes = [],
    filters = {},
}) {
    const [examTypeId, setExamTypeId] = useState(
        filters?.exam_type_id ? String(filters.exam_type_id) : "",
    );
    const [subjectId, setSubjectId] = useState(
        filters?.subject_id ? String(filters.subject_id) : "",
    );
    const [classId, setClassId] = useState(
        filters?.class_id ? String(filters.class_id) : "",
    );
    const [criteria, setCriteria] = useState(DEFAULT_CRITERIA);

    const schoolId = school?.id
        ? String(school.id)
        : filters?.school_id
          ? String(filters.school_id)
          : "";
    const academicYearId = activeAcademicYear?.id
        ? String(activeAcademicYear.id)
        : "";

    const effectiveExamTypeId = useMemo(() => {
        if (
            examTypeId &&
            examTypes.some((examType) => String(examType.id) === examTypeId)
        ) {
            return examTypeId;
        }

        return examTypes[0] ? String(examTypes[0].id) : "";
    }, [examTypeId, examTypes]);

    const effectiveClassId = useMemo(() => {
        if (
            classId &&
            classes.some((schoolClass) => String(schoolClass.id) === classId)
        ) {
            return classId;
        }

        return "";
    }, [classId, classes]);

    const hasRequiredFilters = Boolean(schoolId && academicYearId);
    const hasExamFilters = Boolean(
        hasRequiredFilters && effectiveExamTypeId && subjectId,
    );

    const buildParams = (extra = {}) => {
        const params = {};

        if (schoolId) {
            params.school_id = schoolId;
        }

        if (academicYearId) {
            params.academic_year_id = academicYearId;
        }

        if (effectiveExamTypeId) {
            params.exam_type_id = effectiveExamTypeId;
        }

        if (subjectId) {
            params.subject_id = subjectId;
        }

        if (effectiveClassId) {
            params.class_id = effectiveClassId;
        }

        return { ...params, ...extra };
    };

    const openPdf = (url) => {
        if (!url || url === "#") {
            return;
        }

        window.open(url, "_blank", "noopener");
    };

    const cardsUrl = hasRequiredFilters
        ? route("admin.exam-participants.cards", buildParams())
        : null;

    const nominalUrl = hasRequiredFilters
        ? route("admin.nominal-list.pdf", buildParams())
        : null;

    const attendanceUrl = hasExamFilters
        ? route("admin.exam-administration.attendance", buildParams())
        : null;

    const practicalGradesUrl = hasExamFilters
        ? route("admin.exam-administration.practical-grades", {
              ...buildParams(),
              criteria,
          })
        : null;

    return (
        <AppLayout>
            <div className="space-y-6 max-w-[1720px] mx-auto w-full">
                <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <div className="space-y-1">
                        <div className="flex flex-wrap items-center gap-2 text-xs font-label uppercase tracking-widest text-on-surface-variant font-semibold">
                            <span>Sistem Evaluasi Terpadu</span>
                            <span>/</span>
                            <span>Pengelolaan Ujian</span>
                            <span>/</span>
                            <span className="text-primary font-semibold">
                                {title}
                            </span>
                        </div>
                        <h1 className="font-headline text-3xl font-bold text-on-surface tracking-tight">
                            {title}
                        </h1>
                        <p className="text-sm text-on-surface-variant font-body">
                            Unduh berkas administrasi ujian dalam format PDF.
                            Sekolah dan tahun pelajaran mengikuti akun serta
                            tahun pelajaran aktif.
                        </p>
                    </div>
                </div>

                {!hasRequiredFilters && (
                    <div className="flex items-start gap-3 rounded-2xl bg-error-container/30 border border-error-container/60 px-4 py-3">
                        <span className="material-symbols-outlined text-error text-[20px] shrink-0">
                            error
                        </span>
                        <div>
                            <p className="font-label font-semibold text-sm text-on-surface">
                                Tahun pelajaran aktif belum diatur
                            </p>
                            <p className="text-xs text-on-surface-variant font-body mt-0.5">
                                Aktifkan salah satu tahun pelajaran terlebih
                                dahulu agar administrasi ujian dapat dicetak.
                            </p>
                        </div>
                    </div>
                )}

                <Card>
                    <div className="mb-4 flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <h3 className="font-headline text-lg font-semibold text-on-surface">
                                Filter Cetak
                            </h3>
                            <p className="text-[11px] text-on-surface-variant font-body mt-0.5">
                                Pilih jenis ujian, mata pelajaran, dan kelas
                                yang akan dicetak.
                            </p>
                        </div>

                        <div className="flex flex-wrap items-center gap-2">
                            <span className="inline-flex items-center gap-1.5 rounded-full bg-surface-container px-3 py-1 text-[11px] font-label font-semibold text-on-surface-variant">
                                <span className="material-symbols-outlined text-[16px]">
                                    school
                                </span>
                                {school?.name ?? "Sekolah belum tersedia"}
                            </span>

                            <span className="inline-flex items-center gap-1.5 rounded-full bg-surface-container px-3 py-1 text-[11px] font-label font-semibold text-on-surface-variant">
                                <span className="material-symbols-outlined text-[16px]">
                                    calendar_month
                                </span>
                                {activeAcademicYear
                                    ? `${activeAcademicYear.name}${
                                          activeAcademicYear.semester_label
                                              ? ` - ${activeAcademicYear.semester_label}`
                                              : ""
                                      }`
                                    : "Tahun pelajaran belum aktif"}
                            </span>
                        </div>
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        <Select
                            id="administration-exam-type"
                            label="Jenis Ujian"
                            value={effectiveExamTypeId}
                            onChange={(event) =>
                                setExamTypeId(event.target.value)
                            }
                            placeholder="Pilih Jenis Ujian"
                            disabled={examTypes.length === 0}
                        >
                            <option value="">Pilih Jenis Ujian</option>
                            {examTypes.map((examType) => (
                                <option key={examType.id} value={examType.id}>
                                    {examType.name}
                                </option>
                            ))}
                        </Select>

                        <Select
                            id="administration-subject"
                            label="Mata Pelajaran"
                            value={subjectId}
                            onChange={(event) =>
                                setSubjectId(event.target.value)
                            }
                            placeholder="Pilih Mata Pelajaran"
                        >
                            <option value="">Pilih Mata Pelajaran</option>
                            {subjects.map((subject) => (
                                <option key={subject.id} value={subject.id}>
                                    {subject.name}
                                </option>
                            ))}
                        </Select>

                        <Select
                            id="administration-class"
                            label="Kelas (Opsional)"
                            value={effectiveClassId}
                            onChange={(event) => setClassId(event.target.value)}
                            placeholder="Semua Kelas"
                            disabled={classes.length === 0}
                        >
                            <option value="">Semua Kelas</option>
                            {classes.map((schoolClass) => (
                                <option
                                    key={schoolClass.id}
                                    value={schoolClass.id}
                                >
                                    {schoolClass.name}
                                </option>
                            ))}
                        </Select>
                    </div>

                    {examTypes.length === 0 && (
                        <p className="mt-3 text-[11px] text-on-surface-variant font-body">
                            Belum ada jenis ujian aktif untuk sekolah ini.
                        </p>
                    )}
                </Card>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <ActionCard
                        icon="badge"
                        title="Cetak Kartu Peserta"
                        description="Cetak kartu peserta ujian berisi identitas, username, dan kata sandi peserta."
                        disabled={!hasRequiredFilters}
                        disabledHint="Tahun pelajaran aktif belum diatur."
                        onPrint={() => openPdf(cardsUrl)}
                    />

                    <ActionCard
                        icon="fact_check"
                        title="Cetak Daftar Hadir"
                        description="Cetak daftar hadir peserta per mata pelajaran dan jenis ujian."
                        disabled={!hasExamFilters}
                        disabledHint="Pilih jenis ujian dan mata pelajaran terlebih dahulu."
                        onPrint={() => openPdf(attendanceUrl)}
                    />

                    <ActionCard
                        icon="list_alt"
                        title="Cetak Daftar Nominatif"
                        description="Cetak daftar nominatif peserta ujian lengkap dengan NISN dan status kredensial."
                        disabled={!hasRequiredFilters}
                        disabledHint="Tahun pelajaran aktif belum diatur."
                        onPrint={() => openPdf(nominalUrl)}
                    />

                    <ActionCard
                        icon="grading"
                        title="Cetak Format Nilai Praktek"
                        description="Cetak format penilaian praktek per mata pelajaran dan jenis ujian untuk setiap kelas yang diajar, tanpa bergantung pada jadwal ujian. Nama penguji diambil dari guru pengampu mata pelajaran."
                        disabled={!hasExamFilters}
                        disabledHint="Pilih jenis ujian dan mata pelajaran terlebih dahulu."
                        onPrint={() => openPdf(practicalGradesUrl)}
                    >
                        <Input
                            id="administration-criteria"
                            label="Kriteria Penilaian"
                            value={criteria}
                            onChange={(event) => setCriteria(event.target.value)}
                            placeholder={DEFAULT_CRITERIA}
                        />
                        <p className="mt-1.5 text-[11px] text-on-surface-variant font-body">
                            Pisahkan kriteria dengan koma. Maksimal 8 kriteria.
                        </p>
                    </ActionCard>
                </div>
            </div>
        </AppLayout>
    );
}
