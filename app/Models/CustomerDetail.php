<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'address',
    ];

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id', 'id');
    }

    // public function contacts()
    // {
    //     return $this->hasMany(CustomerContact::class, 'customer_id', 'customer_id');
    // }
}
