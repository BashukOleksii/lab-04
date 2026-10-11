<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

class RepairRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'department_id',
        'subject',
        'priority',
        'status',
        'reported_at',
        'cost',
        'urgent',
        'completed_at'
    ];

    protected function casts() : array
    {
        return [
            'priority' => 'integer',
            'reported_at' => 'datetime',
            'cost' => 'decimal:2',
            'urgent' => 'boolean',
            'completed_at'=> 'datetime'
        ];
    }

    public function department() : BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function repairComments() : HasMany
    {
        return $this->hasMany(RepairComment::class);
    }

}
