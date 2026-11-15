<?php

namespace App\Domain\Finance\Models;

use App\Domain\Institution\Audit\Auditable;
use App\Domain\Institution\Tenancy\BelongsToInstitution;
use App\Domain\Students\Models\Student;
use Database\Factories\FeeInvoiceFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeInvoice extends Model
{
    use Auditable, BelongsToInstitution, HasFactory, HasUlids;

    protected $table = 'fee_invoices';

    protected $fillable = [
        'institution_id',
        'student_id',
        'invoice_number',
        'title',
        'amount',
        'paid_amount',
        'status',
        'due_date',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'due_date' => 'date',
        ];
    }

    protected static function newFactory(): FeeInvoiceFactory
    {
        return FeeInvoiceFactory::new();
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}
