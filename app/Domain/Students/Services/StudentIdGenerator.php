<?php

namespace App\Domain\Students\Services;

use App\Domain\Institution\Models\Counter;
use App\Domain\Institution\Models\Institution;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StudentIdGenerator
{
    /**
     * Generate a sequential, race-safe student ID code scoped to the institution.
     * e.g. BYN-2026-0001
     */
    public function generate(Institution $institution, ?Carbon $date = null): string
    {
        $year = ($date ?? Carbon::now())->year;
        $prefix = $this->determinePrefix($institution);

        return DB::transaction(function () use ($institution, $year, $prefix) {
            // Guarantee row exists safely even under concurrency
            DB::table('counters')->insertOrIgnore([
                'institution_id' => $institution->id,
                'key' => 'student_code',
                'year' => $year,
                'value' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Atomically increment counter
            Counter::withoutInstitutionScope()
                ->where('institution_id', $institution->id)
                ->where('key', 'student_code')
                ->where('year', $year)
                ->increment('value');

            $sequence = (int) Counter::withoutInstitutionScope()
                ->where('institution_id', $institution->id)
                ->where('key', 'student_code')
                ->where('year', $year)
                ->value('value');

            return sprintf('%s-%d-%04d', $prefix, $year, $sequence);
        });
    }

    /**
     * Determine a 3-character uppercase alphanumeric prefix for the institution.
     */
    protected function determinePrefix(Institution $institution): string
    {
        $slug = strtolower($institution->slug ?? '');

        if (str_starts_with($slug, 'beyan')) {
            return 'BYN';
        }

        $cleaned = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $slug));

        if (strlen($cleaned) >= 3) {
            return substr($cleaned, 0, 3);
        }

        return str_pad($cleaned, 3, 'M');
    }
}
