<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamSchedule;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function dashboard(): Response
    {
        return Inertia::render('Home', [
            'title' => 'Beranda - CBT Panel',
        ]);
    }

    public function examStart(ExamSchedule $examSchedule): Response
    {
        return Inertia::render('Student/ExamStart', [
            'exam' => $examSchedule,
        ]);
    }

    public function monitoring(): Response
    {
        return Inertia::render('Monitoring/Index', [
            'title' => 'Monitoring Ujian',
        ]);
    }
}
