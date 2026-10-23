@extends('layouts.admin')
@section('title', 'Detail ' . ucfirst($kind))
@section('content')
    <a class="text-link mb-5 inline-flex items-center gap-1.5" href="{{ route('admin.transaction.index', $kind) }}">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg> Kembali ke daftar</a>
    <div class="dashboard-grid">
        <section class="card"><span class="status">{{ $record->status }}</span>
            <h2 class="mt-5">{{ $record->nama }}</h2>
            <p class="code">{{ $record->kode }}</p>
            <div class="detail-row"><strong>WhatsApp</strong><span>{{ $record->whatsapp }}</span></div>
            @if ($kind === 'donasi')
                <div class="detail-row"><strong>Nominal
                        bruto</strong><span>Rp{{ number_format($record->nominal, 0, ',', '.') }}</span></div>
                <div class="detail-row">
                    <strong>Sumber Penerimaan</strong>
                    <span>{{ $record->isManual() ? 'Manual (' . ucfirst($record->metode) . ')' : 'Online (Midtrans)' }}</span>
                </div>
                @if ($record->isManual())
                    @if ($record->referensi_manual)
                        <div class="detail-row">
                            <strong>Nomor Referensi / Kuitansi</strong>
                            <span>{{ $record->referensi_manual }}</span>
                        </div>
                    @endif
                    @if ($record->recorder)
                        <div class="detail-row">
                            <strong>Dicatat Oleh</strong>
                            <span>{{ $record->recorder->name }}</span>
                        </div>
                    @endif
                    <div class="detail-row">
                        <strong>Tanggal Diterima</strong>
                        <span>{{ $record->dibayar_pada?->format('d/m/Y') }}</span>
                    </div>
                    @if ($record->bukti_transfer)
                        <div class="mt-3">
                            <a href="{{ route('admin.donation.manual.proof', $record) }}" class="button outline small">
                                ⬇ Lihat Berkas Bukti Transfer / Kuitansi
                            </a>
                        </div>
                    @endif
                @else
                    <div class="detail-row">
                        <strong>Biaya</strong><span>{{ $record->biaya === null ? 'Belum tersedia' : 'Rp' . number_format($record->biaya, 0, ',', '.') }}</span>
                    </div>
                    <p>{{ $record->sumber_biaya }}</p>
                    <p class="mt-5">Status provider: {{ $record->payment?->status }}<br>Metode:
                        {{ $record->payment?->metode ?: '—' }}</p>
                    <p class="help-text mt-4">Status pembayaran diperbarui otomatis oleh callback Midtrans.</p>
                @endif
            @elseif($kind === 'bantuan')
                @foreach ($record->items as $item)
                    <div class="detail-row"><strong>{{ $item->nama }}</strong><span>{{ (float) $item->jumlah }}
                            {{ $item->satuan }}</span></div>
                @endforeach
                <p class="mt-4">Tanggal diterima: {{ $record->tanggal_diterima ?: 'Belum diterima' }}</p>
            @endif
            <p class="prose-text mt-4">{{ $record->catatan }}</p>
            <hr>
            <h3>Riwayat perubahan</h3>
            <ol class="timeline">
                @foreach ($record->histories()->orderBy('id')->get() as $history)
                    <li><strong>{{ $history->ke }}</strong><small>{{ $history->created_at->format('d/m/Y H:i') }} ·
                            {{ $history->user?->name ?? $history->sumber }}</small>
                        <p>{{ $history->catatan }}</p>
                    </li>
                @endforeach
            </ol>
        </section>
        @if (count($allowed))
            <form class="card self-start" action="{{ route('admin.transaction.update', [$kind, $record->id]) }}"
                method="post" x-data="{ status: {{ Js::from(old('status', $allowed[0])) }} }">@csrf @method('PUT')<h2>Perbarui status</h2>
                <div class="field mt-5"><label for="status">Status baru</label><select id="status" name="status"
                        x-model="status">
                        @foreach ($allowed as $s)
                            <option>{{ $s }}</option>
                        @endforeach
                    </select>
                </div>
                @if ($kind === 'bantuan')
                    <div x-show="status==='Diterima'"><x-field name="tanggal_diterima" label="Tanggal diterima"
                            type="date" :value="date('Y-m-d')" :max="date('Y-m-d')" /></div>
                @endif
                <x-field name="alasan" label="Catatan / alasan (wajib untuk penolakan)" type="textarea" />
                <button class="button w-full mt-4">Simpan status</button>
            </form>
        @endif
    </div>
@endsection
