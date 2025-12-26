<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    // Allow mass assignment for these fields
    protected $fillable = [
        'title',
        'description',
        'event_date',
        'user_id',
    ];


     // Add this:
     public function user()
     {
         return $this->belongsTo(User::class);
     }
}
