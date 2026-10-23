<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaldoPembukaan extends Model
{
    protected $table = 'saldo_pembukaan';

    protected $guarded = ['id'];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'nominal' => 'decimal:2',
    ];

    public function ratifier()
    {
        return $this->belongsTo(User::class, 'disahkan_oleh');
    }
}
