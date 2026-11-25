<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Hidden(['user_id', 'created_at', 'updated_at'])]
class Balance extends Model
{
    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }
}
