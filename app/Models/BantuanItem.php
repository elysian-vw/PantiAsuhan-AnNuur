<?php

namespace App\Models;

class BantuanItem extends Record
{
    protected $table = 'bantuan_items';

    public function assistance()
    {
        return $this->belongsTo(Bantuan::class, 'bantuan_id');
    }

    public function category()
    {
        return $this->belongsTo(KategoriBantuan::class, 'kategori_bantuan_id');
    }
}
