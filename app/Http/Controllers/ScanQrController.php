<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class ScanQrController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('attendance.scan-qr', [
            'user' => $request->user(),
        ]);
    }
}
