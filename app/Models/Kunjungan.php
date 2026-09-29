<?php

namespace App\Models;

class Kunjungan extends Record
{
    protected $table = 'kunjungan';

    protected $hidden = ['token_hash'];

    public function type()
    {
        return $this->belongsTo(JenisKunjungan::class, 'jenis_kunjungan_id');
    }

    public function assistance()
    {
        return $this->hasMany(Bantuan::class, 'kunjungan_id');
    }
}
