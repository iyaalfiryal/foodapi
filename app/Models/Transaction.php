<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'food_id', 'quantity', 'total', 'status', 'payment_url'
    ];

    // Relation Database to Table Food
    public function food()
    {
        return $this->hasOne(Food::class, 'id', 'food_id');
    }

    // Relation Database to Table User
    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    // Create Accessors convert to timestamp
    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->timestamp;
    }

    public function getUpdatedAtAttribute($value)
    {
        return Carbon::parse($value)->timestamp;
    }
}
