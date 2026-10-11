<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'completed_at',
        'defect_path',
    ];

    protected function casts(): array
    {
        return [
            'priority' => 'integer',
            'reported_at' => 'datetime',
            'cost' => 'decimal:2',
            'urgent' => 'boolean',
            'completed_at' => 'datetime',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function repairComments(): HasMany
    {
        return $this->hasMany(RepairComment::class);
    }

    #[Scope]
    protected function filtered(Builder $query, array $filters): void
    {
        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $search = trim((string) ($filters['search'] ?? ''));

        if ($search !== '') {
            $query->where(function (Builder $query) use ($search) {
                $query->where('subject', 'like', '%' . $search . '%')
                    ->orWhereHas('repairComments', function (Builder $query) use ($search) {
                        $query->where('body', 'like', '%' . $search . '%');
                    });
            });
        }
    }
}
