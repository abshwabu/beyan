<?php

namespace App\Domain\Institution\Actions;

use App\Domain\Institution\Models\DemoRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class SubmitDemoRequestAction
{
    /**
     * Submit and record a demo request from the public landing page.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function execute(array $data): ?DemoRequest
    {
        // 1. Honeypot check: If the hidden honeypot field is filled, silently discard spam
        if (! empty($data['hp_website'])) {
            Log::info('Spam demo request discarded via honeypot', [
                'ip' => $data['ip_address'] ?? null,
                'name' => $data['name'] ?? null,
            ]);

            return null;
        }

        // 2. Save demo request inside a short transaction
        /** @var DemoRequest $demoRequest */
        $demoRequest = DB::transaction(function () use ($data) {
            return DemoRequest::create([
                'name' => trim($data['name']),
                'madrasa_name' => trim($data['madrasa_name']),
                'phone' => trim($data['phone']),
                'email' => ! empty($data['email']) ? trim($data['email']) : null,
                'mode' => $data['mode'] ?? 'physical',
                'student_count_range' => $data['student_count_range'] ?? '50_150',
                'message' => ! empty($data['message']) ? trim($data['message']) : null,
                'status' => 'new',
                'ip_address' => $data['ip_address'] ?? null,
                'user_agent' => $data['user_agent'] ?? null,
            ]);
        });

        // 3. Post-transaction external notification dispatch (Never send email inside DB transaction)
        Log::info("Demo request received for madrasa: {$demoRequest->madrasa_name} ({$demoRequest->phone})");

        return $demoRequest;
    }
}
