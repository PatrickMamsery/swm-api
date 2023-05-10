<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Query extends Model
{
    use HasFactory;

    protected $fillable = [
        'query_date',
        'query_action',
        'query_status',
    ];

    protected $dates = [
        'query_date',
    ];
}
