<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCleaningScheduleRequest;
use App\Http\Requests\UpdateCleaningScheduleRequest;
use App\Models\CleaningSchedule;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CleaningScheduleManagementController extends Controller
{
    public function index(): View
    {
        return view('cleaning-schedules.index', [
            'schedules' => CleaningSchedule::query()
                ->with('user.roles')
                ->orderByDesc('id')
                ->paginate(10),
        ]);
    }

    public function create(): View
    {
        return view('cleaning-schedules.create', [
            'cleaningUsers' => User::query()
                ->whereHas('roles', fn ($query) => $query->where('name', 'kebersihan'))
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(StoreCleaningScheduleRequest $request): RedirectResponse
    {
        CleaningSchedule::query()->create($request->validated());

        return redirect()
            ->route('cleaning-schedules.index')
            ->with('toast', [
                'type' => 'success',
                'message' => 'Jadwal kebersihan berhasil ditambahkan.',
            ]);
    }

    public function show(CleaningSchedule $cleaningSchedule): View
    {
        return view('cleaning-schedules.show', [
            'schedule' => $cleaningSchedule->load('user.roles'),
        ]);
    }

    public function edit(CleaningSchedule $cleaningSchedule): View
    {
        return view('cleaning-schedules.edit', [
            'schedule' => $cleaningSchedule->load('user.roles'),
            'cleaningUsers' => User::query()
                ->whereHas('roles', fn ($query) => $query->where('name', 'kebersihan'))
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(UpdateCleaningScheduleRequest $request, CleaningSchedule $cleaningSchedule): RedirectResponse
    {
        $cleaningSchedule->update($request->validated());

        return redirect()
            ->route('cleaning-schedules.index')
            ->with('toast', [
                'type' => 'success',
                'message' => 'Jadwal kebersihan berhasil diperbarui.',
            ]);
    }

    public function destroy(CleaningSchedule $cleaningSchedule): RedirectResponse
    {
        CleaningSchedule::query()->whereKey($cleaningSchedule->id)->delete();

        return redirect()
            ->route('cleaning-schedules.index')
            ->with('toast', [
                'type' => 'success',
                'message' => 'Jadwal kebersihan berhasil dihapus.',
            ]);
    }
}
