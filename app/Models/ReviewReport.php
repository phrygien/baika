<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewReport extends Model
{
    protected $fillable = [
        'review_id', 'reported_by', 'reason', 'description',
        'status', 'admin_decision', 'reviewed_by', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

    public function review(): BelongsTo { return $this->belongsTo(Review::class); }
    public function reportedBy(): BelongsTo { return $this->belongsTo(User::class, 'reported_by'); }
    public function reviewedBy(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }
}
