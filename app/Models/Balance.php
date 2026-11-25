<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Hidden(['user_id', 'created_at', 'updated_at'])]
class Balance extends Model
{
    protected function casts(): array {
        return [
            'current' => 'decimal:2',
            'income' => 'decimal:2',
            'expenses' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }
}
