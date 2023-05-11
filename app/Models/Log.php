<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    use HasFactory;

    protected $fillable = [
        'action',
        'platform',
        'user_id',
        'description'
    ];

    public function actor()
    {
      return  $this->belongsTo(User::class,'user_id','id');
    }
}
