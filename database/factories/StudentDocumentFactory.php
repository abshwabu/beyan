<?php

namespace Database\Factories;

use App\Domain\Institution\Models\Institution;
use App\Domain\Students\Models\Student;
use App\Domain\Students\Models\StudentDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentDocumentFactory extends Factory
{
    protected $model = StudentDocument::class;

    public function definition(): array
    {
        return [
            'institution_id' => Institution::factory(),
            'student_id' => Student::factory(),
            'uploaded_by_id' => null,
            'title' => fake()->words(3, true),
            'file_path' => 'tenants/test/students/test/documents/doc.pdf',
            'file_name' => 'doc.pdf',
            'file_size' => fake()->numberBetween(10000, 500000),
            'mime_type' => 'application/pdf',
        ];
    }
}
