<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateDashboardPasswordRequest;
use Illuminate\Http\RedirectResponse;

class DashboardPasswordController extends Controller
{
    public function update(UpdateDashboardPasswordRequest $request): RedirectResponse
    {
        $request->user()->update([
            'password' => $request->validated('password'),
        ]);

        return redirect()
            ->route('dashboard')
            ->with('toast', [
                'type' => 'success',
                'message' => 'Password berhasil diperbarui.',
            ]);
    }
}
