<?php

namespace App\Domain\Hifz\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuranJuz extends Model
{
    protected $table = 'quran_juzs';

    protected $primaryKey = 'number';

    public $incrementing = false;

    protected $fillable = [
        'number',
        'name_arabic',
        'start_surah_number',
        'start_ayah',
        'end_surah_number',
        'end_ayah',
    ];

    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'start_surah_number' => 'integer',
            'start_ayah' => 'integer',
            'end_surah_number' => 'integer',
            'end_ayah' => 'integer',
        ];
    }

    public function startSurah(): BelongsTo
    {
        return $this->belongsTo(QuranSurah::class, 'start_surah_number', 'number');
    }

    public function endSurah(): BelongsTo
    {
        return $this->belongsTo(QuranSurah::class, 'end_surah_number', 'number');
    }
}
