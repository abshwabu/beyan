<?php

namespace App\Domain\Institution\Models;

use App\Domain\Institution\Audit\Auditable;
use App\Domain\Institution\Tenancy\BelongsToInstitution;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Counter extends Model
{
    use Auditable, BelongsToInstitution, HasFactory;

    protected $table = 'counters';

    protected $fillable = [
        'institution_id',
        'key',
        'year',
        'value',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'value' => 'integer',
        ];
    }
}
