<?php

namespace App\Domain\Hifz\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuranSurah extends Model
{
    protected $table = 'quran_surahs';

    protected $primaryKey = 'number';

    public $incrementing = false;

    protected $fillable = [
        'number',
        'name_arabic',
        'name_english',
        'name_transliteration',
        'total_ayahs',
        'revelation_type',
    ];

    protected $appends = [
        'english_name',
        'arabic_name',
    ];

    public function getEnglishNameAttribute(): string
    {
        return $this->name_transliteration ?? '';
    }

    public function getArabicNameAttribute(): string
    {
        return $this->name_arabic ?? '';
    }

    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'total_ayahs' => 'integer',
        ];
    }

    public function hifzEntries(): HasMany
    {
        return $this->hasMany(HifzEntry::class, 'surah_number', 'number');
    }
}
