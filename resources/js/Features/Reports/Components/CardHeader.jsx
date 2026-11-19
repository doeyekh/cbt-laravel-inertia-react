import React from "react";
import { router } from "@inertiajs/react";
import { route } from "@/Utils/route";
import Button from "@/Components/UI/Button";
import CardHeaderPage from "../../../Components/Layout/CardHeaderPage";
function CardHeader({ schedule }) {
    return (
        <CardHeaderPage
            subTitle="Detail Laporan Ujian"
            title={schedule.name}
            description={`${schedule.code} · ${schedule.subject} · ${schedule.class} · ${schedule.exam_date}`}
        >
            
            <div className="flex flex-wrap gap-3">
                <Button
                    variant="outline"
                    onClick={() => router.visit(route("admin.reports.index"))}
                >
                    Kembali
                </Button>
                <Button
                    variant="secondary"
                    onClick={() =>
                        window.open(
                            route("admin.reports.pdf", schedule.id),
                            "_blank",
                            "noopener",
                        )
                    }
                >
                    <span
                        className="material-symbols-outlined text-[18px]"
                        aria-hidden="true"
                    >
                        picture_as_pdf
                    </span>
                    Cetak PDF
                </Button>
                <Button
                    variant="primary"
                    onClick={() =>
                        window.location.assign(
                            route("admin.reports.export", schedule.id),
                        )
                    }
                >
                    <span
                        className="material-symbols-outlined text-[18px]"
                        aria-hidden="true"
                    >
                        download
                    </span>
                    Export Excel
                </Button>
            </div>
        </CardHeaderPage>
    );
}

export default CardHeader;
