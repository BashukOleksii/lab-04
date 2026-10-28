<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Override;

/** @property bool $is_active*/
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password'
    ];

    protected $hidden = [
        'password',
        'remember_token'
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function repairComments(): HasMany
    {
        return $this->hasMany(
            RepairComment::class,
            'author_id'
        );
    }

    public function ownedRequests(): HasMany
    {
        return $this->hasMany(
            RepairRequest::class,
            'owner_id'
        );
    }

    public function participatedRequests(): BelongsToMany
    {
        return $this->belongsToMany(
            RepairRequest::class,
            'repair_request_user',
            'user_id',
            'repair_request_id'
        )
            ->withPivot('role')
            ->withTimestamps();
    }
}
