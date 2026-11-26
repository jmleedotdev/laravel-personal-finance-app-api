<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Hidden(['user_id', 'created_at', 'updated_at'])]
class Transaction extends Model
{
    use HasUuids;

    protected function casts(): array {
        return [
            'date' => 'datetime',
            'amount' => 'decimal:2',
            'recurring' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
