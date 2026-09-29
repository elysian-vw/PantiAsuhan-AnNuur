<?php

namespace App\Models;

class Galeri extends Record
{
    protected $table = 'galeri';

    public function kegiatan()
    {
        return $this->belongsTo(Kegiatan::class, 'kegiatan_id');
    }
}
