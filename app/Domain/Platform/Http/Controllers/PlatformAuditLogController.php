<?php

namespace App\Domain\Platform\Http\Controllers;

use App\Domain\Institution\Audit\Models\AuditLog;
use App\Domain\Institution\Models\Institution;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PlatformAuditLogController extends Controller
{
    /**
     * Display searchable, filterable platform audit log.
     */
    public function index(Request $request): Response
    {
        $query = AuditLog::with('user');

        if ($instId = $request->query('institution_id')) {
            $query->where('institution_id', $instId);
        }

        if ($event = $request->query('event')) {
            $query->where('action', 'like', "%{$event}%");
        }

        if ($from = $request->query('date_from')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->query('date_to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $logs = $query->latest()->paginate(25)->withQueryString();
        $institutions = Institution::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('Platform/AuditLogs/Index', [
            'logs' => $logs,
            'filters' => $request->only(['institution_id', 'event', 'date_from', 'date_to']),
            'institutions' => $institutions,
        ]);
    }

    /**
     * Export audit log records to CSV with UTF-8 BOM.
     */
    public function export(Request $request): StreamedResponse
    {
        $query = AuditLog::with('user')->latest();

        if ($instId = $request->query('institution_id')) {
            $query->where('institution_id', $instId);
        }

        if ($event = $request->query('event')) {
            $query->where('action', 'like', "%{$event}%");
        }

        $logs = $query->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="platform-audit-logs-'.date('Y-m-d').'.csv"',
        ];

        return response()->stream(function () use ($logs) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'Log ID',
                'Event',
                'Institution ID',
                'User',
                'IP Address',
                'Timestamp',
                'Old Values',
                'New Values',
            ]);

            foreach ($logs as $l) {
                fputcsv($handle, [
                    $l->id,
                    $l->action,
                    $l->institution_id ?? 'Platform',
                    $l->user?->name ?? 'System',
                    $l->ip_address ?? '',
                    $l->created_at->format('Y-m-d H:i:s'),
                    json_encode($l->old_values, JSON_UNESCAPED_UNICODE),
                    json_encode($l->new_values, JSON_UNESCAPED_UNICODE),
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
