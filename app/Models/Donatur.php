<?php

namespace App\Models;

class Donatur extends Record
{
    protected $table = 'donatur';

    protected $attributes = [
        'jenis' => 'biasa',
        'status_verifikasi' => 'none',
    ];

    protected function casts(): array
    {
        return [
            'diajukan_pada' => 'datetime',
            'diputuskan_pada' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function decider()
    {
        return $this->belongsTo(User::class, 'diputuskan_oleh');
    }

    public function donations()
    {
        return $this->hasMany(Donasi::class, 'donatur_id');
    }

    public function assistance()
    {
        return $this->hasMany(Bantuan::class, 'donatur_id');
    }
}
