<?php

namespace App\Models;

class KategoriDonasi extends Record
{
    protected $table = 'kategori_donasi';

    public function donations()
    {
        return $this->hasMany(Donasi::class, 'kategori_donasi_id');
    }
}
