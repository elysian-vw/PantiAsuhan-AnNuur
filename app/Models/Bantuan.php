<?php

namespace App\Models;

class Bantuan extends Record
{
    protected $table = 'bantuan';

    protected $hidden = ['token_hash'];

    public function donor()
    {
        return $this->belongsTo(Donatur::class, 'donatur_id');
    }

    public function visit()
    {
        return $this->belongsTo(Kunjungan::class, 'kunjungan_id');
    }

    public function items()
    {
        return $this->hasMany(BantuanItem::class, 'bantuan_id');
    }
}
