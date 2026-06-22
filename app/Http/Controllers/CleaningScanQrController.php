<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class CleaningScanQrController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('cleaning-attendance.scan-qr', [
            'user' => $request->user(),
        ]);
    }
}
