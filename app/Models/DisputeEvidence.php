<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisputeEvidence extends Model
{
    protected $fillable = [
        'dispute_id', 'submitted_by', 'party',
        'type', 'file_path', 'original_name', 'description',
        'is_validated', 'validated_by', 'validated_at',
    ];

    protected function casts(): array
    {
        return [
            'is_validated' => 'boolean',
            'validated_at' => 'datetime',
        ];
    }

    public function dispute(): BelongsTo { return $this->belongsTo(Dispute::class); }
    public function submittedBy(): BelongsTo { return $this->belongsTo(User::class, 'submitted_by'); }
    public function validatedBy(): BelongsTo { return $this->belongsTo(User::class, 'validated_by'); }
}
