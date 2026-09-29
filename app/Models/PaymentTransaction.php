<?php

namespace App\Models;

class PaymentTransaction extends Record
{
    protected $table = 'payment_transactions';

    protected $hidden = ['snap_token'];

    public function donation()
    {
        return $this->belongsTo(Donasi::class, 'donasi_id');
    }
}
