<?php

namespace App\Models;

class Donatur extends Record
{
    protected $table = 'donatur';

    protected $attributes = ['jenis' => 'biasa'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function donations()
    {
        return $this->hasMany(Donasi::class, 'donatur_id');
    }

    public function assistance()
    {
        return $this->hasMany(Bantuan::class, 'donatur_id');
    }
}
