<?php

namespace App\Models;

class KategoriPengeluaran extends Record
{
    protected $table = 'kategori_pengeluaran';

    public function expenses()
    {
        return $this->hasMany(Pengeluaran::class, 'kategori_pengeluaran_id');
    }
}
