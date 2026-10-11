<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
    ];

    public function repairRequests(): HasMany
    {
        return $this->hasMany(RepairRequest::class);
    }

    #[Scope]
    protected function searchByName(Builder $query, ?string $search): void
    {
        $search = trim($search ?? '');

        if ($search !== '') {
            $query->where('name', 'like', '%' . $search . '%');
        }
    }

    #[Scope]
    protected function alphabetically(Builder $query): void
    {
        $query->orderBy('name')
            ->orderBy('id');
    }
}
