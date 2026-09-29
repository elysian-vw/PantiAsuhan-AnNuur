<?php

namespace App\Models;

class Kegiatan extends Record
{
    protected $table = 'kegiatan';

    public function photos()
    {
        return $this->hasMany(Galeri::class, 'kegiatan_id');
    }
}
