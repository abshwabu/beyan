<?php

namespace App\Domain\Institution\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DemoRequest extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'demo_requests';

    protected $fillable = [
        'name',
        'madrasa_name',
        'phone',
        'email',
        'mode',
        'student_count_range',
        'message',
        'status',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
