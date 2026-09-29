<?php

namespace App\Models;

class Donatur extends Record
{
    protected $table = 'donatur';

    public function donations()
    {
        return $this->hasMany(Donasi::class, 'donatur_id');
    }

    public function assistance()
    {
        return $this->hasMany(Bantuan::class, 'donatur_id');
    }
}
