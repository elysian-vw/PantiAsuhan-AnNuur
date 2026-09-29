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
                    <strong>Biaya</strong><span>{{ $record->biaya === null ? 'Belum tersedia' : 'Rp' . number_format($record->biaya, 0, ',', '.') }}</span>
                </div>
                <p>{{ $record->sumber_biaya }}</p>
                <p class="mt-5">Status provider: {{ $record->payment?->status }}<br>Metode:
                    {{ $record->payment?->metode ?: '—' }}</p>
                <p class="help-text mt-4">Status pembayaran diperbarui otomatis oleh callback Midtrans.</p>
            @elseif($kind === 'bantuan')
                @foreach ($record->items as $item)
                    <div class="detail-row"><strong>{{ $item->nama }}</strong><span>{{ (float) $item->jumlah }}
                            {{ $item->satuan }}</span></div>
                @endforeach
                <p class="mt-4">Tanggal diterima: {{ $record->tanggal_diterima ?: 'Belum diterima' }}</p>
                @if ($record->visit)
                    <a class="text-link inline-flex items-center gap-1"
                        href="{{ route('admin.transaction.show', ['kunjungan', $record->kunjungan_id]) }}">Kunjungan:
                        {{ $record->visit->nama }} <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg></a>
                @endif
            @else
                <div class="detail-row">
                    <strong>Jadwal</strong><span>{{ $record->tanggal }}<br>{{ substr($record->jam, 0, 5) }}–{{ substr($record->jam_selesai, 0, 5) }}
                        WIB</span>
                </div>
                <div class="detail-row"><strong>Peserta</strong><span>{{ $record->peserta }}</span></div>
                <p>{{ $record->instansi }}</p>
                <p class="prose-text mt-4">{{ $record->tujuan }}</p>
                @if ($conflicts->count())
                    <div class="notice mt-4">Jadwal beririsan dengan:@foreach ($conflicts as $conflict)
                            <a class="block text-link"
                                href="{{ route('admin.transaction.show', ['kunjungan', $conflict->id]) }}">{{ $conflict->nama }}
                                · {{ substr($conflict->jam, 0, 5) }}–{{ substr($conflict->jam_selesai, 0, 5) }}</a>
                        @endforeach Pengurus menentukan apakah kunjungan dapat berlangsung bersamaan.
                    </div>
                @endif
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
                    <div class="field"><label for="kunjungan_id">Kaitkan dengan kunjungan (opsional)</label><select
                            name="kunjungan_id" id="kunjungan_id">
                            <option value="">Tidak terkait kunjungan</option>
                            @foreach (\App\Models\Kunjungan::latest()->get() as $visit)
                                <option value="{{ $visit->id }}" @selected(old('kunjungan_id', $record->kunjungan_id) == $visit->id)>{{ $visit->nama }} ·
                                    {{ $visit->tanggal }} · #{{ $visit->id }}</option>
                            @endforeach
                        </select>
                    </div>
                @else
                    <div x-show="status==='Dijadwalkan ulang'"><x-field name="tanggal" label="Tanggal baru" type="date"
                            :value="$record->tanggal" :min="date('Y-m-d')" />
                        <div class="form-grid"><x-field name="jam" label="Jam mulai" type="time"
                                :value="substr($record->jam, 0, 5)" /><x-field name="jam_selesai" label="Jam selesai" type="time"
                                :value="substr($record->jam_selesai, 0, 5)" /></div>
                    </div>
                    @if ($record->status === 'Dijadwalkan ulang')
                        <label class="checkbox-label" x-show="status==='Disetujui'"><input type="checkbox" name="konfirmasi"
                                value="1"><span>Pengunjung sudah menyetujui jadwal baru melalui
                                WhatsApp.</span></label>
                    @endif
                @endif
                <x-field name="alasan" label="Catatan / alasan (wajib untuk penolakan atau perubahan jadwal)"
                    type="textarea" />
                <button class="button w-full mt-4">Simpan status</button>
            </form>
        @endif
    </div>
@endsection
