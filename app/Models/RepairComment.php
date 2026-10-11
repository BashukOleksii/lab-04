<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepairComment extends Model
{
    /** @use HasFactory<\Database\Factories\RepairCommentFactory> */
    use HasFactory;

    protected $fillable = [
        'body'
    ];

    public function repairRequest() : BelongsTo 
    {
        return $this->belongsTo(RepairRequest::class);
    }
}
