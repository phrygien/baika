<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisputeStatusHistory extends Model
{
    protected $fillable = ['dispute_id', 'from_status', 'to_status', 'note', 'performed_by'];

    public function dispute(): BelongsTo { return $this->belongsTo(Dispute::class); }
    public function performer(): BelongsTo { return $this->belongsTo(User::class, 'performed_by'); }
}
