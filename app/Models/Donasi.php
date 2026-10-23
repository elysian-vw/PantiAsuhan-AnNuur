<?php

namespace App\Models;

class Donasi extends Record
{
    protected $table = 'donasi';

    protected $hidden = ['token_hash'];

    // Manual receipts have no gateway fee, including legacy rows with null biaya.
    // Online receipts without a recorded fee have no confirmed net amount yet.
    public const NET_SQL = "CASE WHEN sumber = 'manual' THEN nominal WHEN biaya IS NOT NULL THEN nominal - biaya ELSE 0 END";

    public function recordedFee(): ?string
    {
        return $this->isManual() ? '0.00' : ($this->biaya === null ? null : (string) $this->biaya);
    }

    public function netAmount(): ?string
    {
        $fee = $this->recordedFee();

        return $this->status === 'Berhasil' && $fee !== null
            ? bcsub((string) $this->nominal, $fee, 2) : null;
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

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

    public function recorder()
    {
        return $this->belongsTo(User::class, 'pencatat_user_id');
    }

    public function isManual(): bool
    {
        return $this->sumber === 'manual';
    }

    public function scopeSuccess($query)
    {
        return $query->where('status', 'Berhasil');
    }

    public function scopeManual($query)
    {
        return $query->where('sumber', 'manual');
    }

    public function scopeOnline($query)
    {
        return $query->where('sumber', 'online');
    }
}
