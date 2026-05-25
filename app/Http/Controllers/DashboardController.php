<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('dashboard', [
            'user' => $request->user(),
            'summaryCards' => [
                [
                    'label' => 'Status Login',
                    'value' => 'Aktif',
                    'description' => 'Sesi Anda berjalan normal.',
                ],
                [
                    'label' => 'Role Aktif',
                    'value' => $request->user()?->roles->pluck('display_name')->implode(', ') ?: 'Belum diatur',
                    'description' => 'Role diambil dari pengaturan akses pengguna.',
                ],
                [
                    'label' => 'Waktu Akses',
                    'value' => now()->format('H:i'),
                    'description' => 'Jam akses terakhir ke dashboard.',
                ],
            ],
        ]);
    }
}
