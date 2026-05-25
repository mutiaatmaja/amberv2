<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreScheduleRequest;
use App\Http\Requests\UpdateScheduleRequest;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ScheduleManagementController extends Controller
{
    public function index(): View
    {
        return view('schedules.index', [
            'schedules' => Schedule::query()
                ->with('user.roles')
                ->orderByDesc('id')
                ->paginate(10),
        ]);
    }

    public function create(): View
    {
        return view('schedules.create', [
            'satpams' => User::query()
                ->whereHas('roles', fn ($query) => $query->where('name', 'satpam'))
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(StoreScheduleRequest $request): RedirectResponse
    {
        Schedule::query()->create($request->validated());

        return redirect()
            ->route('schedules.index')
            ->with('toast', [
                'type' => 'success',
                'message' => 'Jadwal satpam berhasil ditambahkan.',
            ]);
    }

    public function show(Schedule $schedule): View
    {
        return view('schedules.show', [
            'schedule' => $schedule->load('user.roles'),
        ]);
    }

    public function edit(Schedule $schedule): View
    {
        return view('schedules.edit', [
            'schedule' => $schedule->load('user.roles'),
            'satpams' => User::query()
                ->whereHas('roles', fn ($query) => $query->where('name', 'satpam'))
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(UpdateScheduleRequest $request, Schedule $schedule): RedirectResponse
    {
        $schedule->update($request->validated());

        return redirect()
            ->route('schedules.index')
            ->with('toast', [
                'type' => 'success',
                'message' => 'Jadwal satpam berhasil diperbarui.',
            ]);
    }

    public function destroy(Schedule $schedule): RedirectResponse
    {
        Schedule::query()->whereKey($schedule->id)->delete();

        return redirect()
            ->route('schedules.index')
            ->with('toast', [
                'type' => 'success',
                'message' => 'Jadwal satpam berhasil dihapus.',
            ]);
    }
}
