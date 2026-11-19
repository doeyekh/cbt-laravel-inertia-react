import React from "react";

function CardHeaderPage({ subTitle,title,description, children }) {
    return (
        <section className="relative isolate overflow-hidden rounded-[28px] bg-primary px-6 py-7 text-on-primary shadow-sm sm:px-8 sm:py-9">
            <div
                className="absolute -right-16 -top-24 -z-10 h-72 w-72 rounded-full bg-white/10 blur-3xl"
                aria-hidden="true"
            />
            <div
                className="absolute -bottom-32 right-1/3 -z-10 h-64 w-64 rounded-full bg-white/5 blur-3xl"
                aria-hidden="true"
            />

            <div className="flex flex-col justify-between gap-8 xl:flex-row xl:items-end">
                <div className="max-w-3xl space-y-4">
                    <div className="flex flex-wrap items-center gap-2 text-xs font-label font-semibold uppercase tracking-[0.18em] text-on-primary/70">
                        <span>Sistem Evaluasi Terpadu</span>
                        <span aria-hidden="true">/</span>
                        <span className="text-on-primary">
                            {subTitle}
                        </span>
                    </div>
                    <div className="space-y-2">
                        <h1 className="font-headline text-3xl font-bold tracking-tight sm:text-4xl">
                            {title}
                        </h1>
                        <p className="max-w-2xl text-sm leading-relaxed text-on-primary/75 sm:text-base">
                            {description}
                        </p>
                    </div>
                </div>

                {children && (
                    <div className="flex shrink-0 items-center gap-3 self-start rounded-2xl border border-white/15 bg-white/10 px-4 py-3 xl:self-auto">
                        {children}
                    </div>
                )}
            </div>
        </section>
    );
}

export default CardHeaderPage;
