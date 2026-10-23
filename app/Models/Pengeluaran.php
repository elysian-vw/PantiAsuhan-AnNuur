<?php

namespace App\Models;

class Pengeluaran extends Record
{
    protected $table = 'pengeluaran';

    protected $casts = [
        'tanggal' => 'date',
        'nominal' => 'decimal:2',
        'is_public' => 'boolean',
        'posted_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function category()
    {
        return $this->belongsTo(KategoriPengeluaran::class, 'kategori_pengeluaran_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function poster()
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function canceller()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function isDraft(): bool
    {
        return $this->status === 'Draft';
    }

    public function isBooked(): bool
    {
        return $this->status === 'Dibukukan';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'Dibatalkan';
    }

    public function scopeBooked($query)
    {
        return $query->where('status', 'Dibukukan');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'Draft');
    }
}
