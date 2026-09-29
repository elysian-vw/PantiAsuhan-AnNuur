<?php

namespace App\Models;

class Kebutuhan extends Record
{
    protected $table = 'kebutuhan';

    public function category()
    {
        return $this->belongsTo(KategoriBantuan::class, 'kategori_bantuan_id');
    }
}
