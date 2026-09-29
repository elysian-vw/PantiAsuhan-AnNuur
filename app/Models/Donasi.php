<?php

namespace App\Models;

class Donasi extends Record
{
    protected $table = 'donasi';

    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return ['dibayar_pada' => 'datetime'];
    }

    public function donor()
    {
        return $this->belongsTo(Donatur::class, 'donatur_id');
    }

    public function category()
    {
        return $this->belongsTo(KategoriDonasi::class, 'kategori_donasi_id');
    }

    public function payment()
    {
        return $this->hasOne(PaymentTransaction::class, 'donasi_id');
    }
}
