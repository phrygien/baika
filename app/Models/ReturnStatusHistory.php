<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnStatusHistory extends Model
{
    protected $fillable = ['return_id', 'from_status', 'to_status', 'note', 'performed_by', 'performed_by_id'];

    public function returnRequest(): BelongsTo { return $this->belongsTo(ReturnRequest::class, 'return_id'); }
    public function performer(): BelongsTo { return $this->belongsTo(User::class, 'performed_by_id'); }
}
