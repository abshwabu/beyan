<?php

namespace App\Domain\Platform\Http\Controllers;

use App\Domain\Institution\Models\DemoRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlatformDemoRequestController extends Controller
{
    /**
     * Display listing of public demo inquiries and requests.
     */
    public function index(Request $request): Response
    {
        $query = DemoRequest::query();

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $demoRequests = $query->latest()->paginate(15)->withQueryString();

        return Inertia::render('Platform/DemoRequests/Index', [
            'demoRequests' => $demoRequests,
            'filters' => $request->only('status'),
        ]);
    }

    /**
     * Update status and administrative notes for a demo request.
     */
    public function updateStatus(Request $request, string $id): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:new,contacted,closed'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $demoRequest = DemoRequest::findOrFail($id);
        $demoRequest->update($validated);

        return back()->with('success', "Demo request status updated to '{$demoRequest->status}'.");
    }

    /**
     * Convert demo request into an institution creation draft.
     */
    public function convert(string $id): RedirectResponse
    {
        $demoRequest = DemoRequest::findOrFail($id);

        return redirect()->route('platform.institutions.create', [
            'name' => $demoRequest->madrasa_name,
            'phone' => $demoRequest->phone,
            'email' => $demoRequest->email,
            'mode' => $demoRequest->mode,
        ]);
    }
}
