<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAppSettingRequest;
use App\Models\AppSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AppSettingController extends Controller
{
    public function edit(): View
    {
        return view('settings.edit', [
            'settings' => AppSetting::current(),
        ]);
    }

    public function update(UpdateAppSettingRequest $request): RedirectResponse
    {
        $settings = AppSetting::current();

        $settings->update($request->validated());

        return redirect()
            ->route('settings.edit')
            ->with('toast', [
                'type' => 'success',
                'message' => 'Pengaturan berhasil diperbarui.',
            ]);
    }
}
