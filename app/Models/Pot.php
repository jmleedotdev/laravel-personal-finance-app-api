<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['name', 'target', 'theme'])]
#[Hidden(['user_id', 'created_at', 'updated_at'])]
class Pot extends Model
{
    use HasUuids;

    protected $attributes = [
        'total' => 0,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
