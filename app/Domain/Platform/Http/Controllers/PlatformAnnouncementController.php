<?php

namespace App\Domain\Platform\Http\Controllers;

use App\Domain\Platform\Models\PlatformAnnouncement;
use App\Domain\Platform\Models\PlatformAnnouncementDismissal;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlatformAnnouncementController extends Controller
{
    /**
     * Display list of all platform announcements.
     */
    public function index(): Response
    {
        $announcements = PlatformAnnouncement::withCount('dismissals')->latest()->paginate(15);

        return Inertia::render('Platform/Announcements/Index', [
            'announcements' => $announcements,
        ]);
    }

    /**
     * Create a new announcement targeting institutions.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
            'target_mode' => ['nullable', 'in:all,physical,online,hybrid'],
            'target_plan' => ['nullable', 'string', 'max:50'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'is_active' => ['boolean'],
        ]);

        $validated['target_mode'] = ($validated['target_mode'] === 'all') ? null : $validated['target_mode'];
        $validated['target_plan'] = ($validated['target_plan'] === 'all') ? null : $validated['target_plan'];
        $validated['created_by'] = $request->user()->name ?? 'Administrator';

        PlatformAnnouncement::create($validated);

        return redirect()->route('platform.announcements.index')
            ->with('success', 'Platform announcement published successfully.');
    }

    /**
     * Update an announcement.
     */
    public function update(Request $request, string $id): RedirectResponse
    {
        $announcement = PlatformAnnouncement::findOrFail($id);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
            'target_mode' => ['nullable', 'in:all,physical,online,hybrid'],
            'target_plan' => ['nullable', 'string', 'max:50'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'is_active' => ['boolean'],
        ]);

        $validated['target_mode'] = ($validated['target_mode'] === 'all') ? null : $validated['target_mode'];
        $validated['target_plan'] = ($validated['target_plan'] === 'all') ? null : $validated['target_plan'];

        $announcement->update($validated);

        return redirect()->route('platform.announcements.index')
            ->with('success', 'Announcement updated.');
    }

    /**
     * Delete an announcement.
     */
    public function destroy(string $id): RedirectResponse
    {
        $announcement = PlatformAnnouncement::findOrFail($id);
        $announcement->delete();

        return redirect()->route('platform.announcements.index')
            ->with('success', 'Announcement deleted.');
    }

    /**
     * Dismiss an announcement for the currently authenticated tenant user.
     */
    public function dismiss(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        if ($user) {
            PlatformAnnouncementDismissal::firstOrCreate([
                'announcement_id' => $id,
                'user_id' => $user->id,
            ], [
                'dismissed_at' => Carbon::now(),
            ]);
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back();
    }
}
