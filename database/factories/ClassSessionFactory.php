<?php

namespace Database\Factories;

use App\Domain\Academics\Models\ClassGroup;
use App\Domain\Academics\Models\ClassSession;
use App\Domain\Institution\Models\Institution;
use App\Domain\Teachers\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClassSessionFactory extends Factory
{
    protected $model = ClassSession::class;

    public function definition(): array
    {
        $date = fake()->dateTimeBetween('now', '+1 month')->format('Y-m-d');
        $start = Carbon::parse("{$date} 06:00:00", 'UTC');
        $end = Carbon::parse("{$date} 07:30:00", 'UTC');

        return [
            'institution_id' => Institution::factory(),
            'class_group_id' => ClassGroup::factory(),
            'teacher_id' => Teacher::factory(),
            'scheduled_date' => $date,
            'start_time_utc' => $start,
            'end_time_utc' => $end,
            'type' => 'physical',
            'meeting_link' => null,
            'room' => 'Room 101',
            'status' => 'scheduled',
            'notes' => null,
        ];
    }
}
