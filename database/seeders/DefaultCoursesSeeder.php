<?php

namespace Database\Seeders;

use App\Domain\Academics\Models\Course;
use App\Domain\Institution\Models\Institution;
use Illuminate\Database\Seeder;

class DefaultCoursesSeeder extends Seeder
{
    /**
     * Seed the default core madrasa courses for an institution.
     */
    public function run(?string $institutionId = null): void
    {
        $institutions = $institutionId
            ? Institution::where('id', $institutionId)->get()
            : Institution::all();

        $defaultCourses = [
            [
                'name' => 'Qaida Nooraniyah',
                'code' => 'QAI-101',
                'level' => 'Beginner',
                'duration' => '3 Months',
                'learning_objectives' => [
                    'Master Arabic letter recognition and phonetics',
                    'Accurate vowel elongation and connecting letters',
                    'Foundations of fluent Qur\'anic reading',
                ],
                'description' => 'Essential foundation course teaching proper Arabic letter articulation and reading rules using the Nooraniyah method.',
            ],
            [
                'name' => 'Qur\'an Reading',
                'code' => 'QUR-101',
                'level' => 'Elementary',
                'duration' => '6 Months',
                'learning_objectives' => [
                    'Read directly from the Mushaf with continuous flow',
                    'Recognize basic stop signs (Waqf) and verse markers',
                    'Build reading stamina across full Surahs',
                ],
                'description' => 'Guided reading practice from the Mus\'haf focusing on continuous recitation, fluency, and proper pronunciation.',
            ],
            [
                'name' => 'Tajweed',
                'code' => 'TAJ-201',
                'level' => 'Intermediate',
                'duration' => '6 Months',
                'learning_objectives' => [
                    'Makharij (articulation points) and Sifaat (characteristics)',
                    'Rules of Noon Saakinah, Meem Saakinah, and Madd',
                    'Practical application during daily recitation',
                ],
                'description' => 'Comprehensive theoretical and practical study of the rules of Tajweed based on the narration of Hafs from Asim.',
            ],
            [
                'name' => 'Hifz',
                'code' => 'HIFZ-100',
                'level' => 'All Levels',
                'duration' => 'Ongoing',
                'learning_objectives' => [
                    'Systematic daily memorization with Sabaq and Sabqi',
                    'Rigorous cumulative revision (Manzil) system',
                    'Preparation for complete Qur\'an memorization and evaluation',
                ],
                'description' => 'Structured Qur\'an memorization program with individual tracking, daily revision cycles, and teacher evaluations.',
            ],
            [
                'name' => 'Islamic Studies',
                'code' => 'ISL-101',
                'level' => 'Beginner',
                'duration' => '1 Year',
                'learning_objectives' => [
                    'Aqeedah foundations and pillars of Iman',
                    'Essential Fiqh of Taharah and Salah',
                    'Seerah of Prophet Muhammad (SAW) and Islamic manners (Adab)',
                ],
                'description' => 'Holistic Islamic curriculum covering fundamental beliefs, acts of worship, morals, and prophetic history.',
            ],
            [
                'name' => 'Arabic',
                'code' => 'ARB-101',
                'level' => 'Intermediate',
                'duration' => '1 Year',
                'learning_objectives' => [
                    'Basic Qur\'anic and conversational Arabic vocabulary',
                    'Fundamental grammar (Nahw) and morphology (Sarf)',
                    'Direct comprehension of frequently repeated Qur\'anic words',
                ],
                'description' => 'Applied Arabic language course designed to help students understand the Qur\'an and Islamic texts directly.',
            ],
        ];

        foreach ($institutions as $institution) {
            foreach ($defaultCourses as $courseData) {
                Course::withoutInstitutionScope()->firstOrCreate(
                    [
                        'institution_id' => $institution->id,
                        'code' => $courseData['code'],
                    ],
                    array_merge($courseData, [
                        'institution_id' => $institution->id,
                        'status' => 'active',
                    ])
                );
            }
        }
    }
}
