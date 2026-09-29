<?php

namespace App\Models;

class StatusHistory extends Record
{
    protected $table = 'status_histories';

    public function subject()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
