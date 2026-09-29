@extends('layouts.admin')
@section('title', 'Laporan ' . ucfirst($kind))
@section('content')
    <form method="get" class="card">
        <div class="report-filters"><x-field name="awal" label="Tanggal awal" type="date" :value="request('awal')" /><x-field
                name="akhir" label="Tanggal akhir" type="date" :value="request('akhir')" /><x-field name="bulan"
                label="Bulan (opsional)" type="number" min="1" max="12" :value="request('bulan')" /><x-field
                name="tahun" label="Tahun (opsional)" type="number" min="2000" max="2200" :value="request('tahun')" />
            <div class="field"><label for="status">Status</label><select name="status" id="status">
                    <option value="">Semua status</option>
                    @foreach (match ($kind) {
            'donasi' => ['Menunggu pembayaran', 'Berhasil', 'Gagal', 'Kedaluwarsa'],
            'bantuan' => ['Diajukan', 'Dikonfirmasi', 'Diterima', 'Ditolak'],
            'kunjungan' => ['Menunggu', 'Disetujui', 'Dijadwalkan ulang', 'Ditolak', 'Hadir', 'Selesai'],
        } as $status)
                        <option @selected(request('status') === $status)>{{ $status }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="flex flex-wrap gap-3"><button class="button small">Tampilkan</button><button
                class="button outline small" name="format" value="pdf">Unduh PDF</button><button
                class="button outline small" name="format" value="xlsx">Unduh Excel</button></div>
    </form>
    <p class="help-text my-5">Tanggal acuan: {{ str_replace('_', ' ', $column) }}. {{ count($rows) }} catatan sesuai
        filter.
    </p>
    <div class="card my-5">
        <h2>Ringkasan sesuai filter</h2>
        @foreach ($summary as $label => $value)
            <div class="detail-row">
                <strong>{{ $label }}</strong><span>{{ is_numeric($value) ? number_format($value, $value == floor($value) ? 0 : 2, ',', '.') : $value }}</span>
            </div>
        @endforeach
    </div>
    @if ($net !== null)
        <div class="notice success">Total donasi bersih yang biayanya sudah tercatat:
            Rp{{ number_format($net, 0, ',', '.') }}.
            @if ($unknown)
                {{ $unknown }} pembayaran berhasil belum memiliki biaya dan belum masuk total bersih.
            @endif
        </div>
    @endif
    <div class="card table-card">
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        @foreach ($headers as $header)
                            <th>{{ $header }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            @foreach ($row as $cell)
                                <td>{{ $cell }}</td>
                            @endforeach
                        </tr>
                    @empty

                        <tr>
                            <td colspan="{{ count($headers) }}" class="empty-cell">Tidak ada data sesuai filter.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
