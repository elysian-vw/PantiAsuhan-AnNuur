<!DOCTYPE html>
<html lang="id">

    <head>
        <meta charset="utf-8">
        <style>
            body {
                font-family: DejaVu Sans, sans-serif;
                font-size: 9px;
                color: #23392e
            }

            h1 {
                font-size: 18px;
                margin-bottom: 4px
            }

            h2 {
                font-size: 13px
            }

            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 18px;
                table-layout: fixed
            }

            th,
            td {
                padding: 7px;
                border: 1px solid #ced8d0;
                text-align: left;
                word-wrap: break-word
            }

            th {
                background: #eaf1eb
            }

            thead {
                display: table-header-group
            }

            tr {
                page-break-inside: avoid
            }

            .muted {
                color: #627268
            }
        </style>
    </head>

    <body>
        <h1>Panti Asuhan NU An-Nuur 2 Kota Kediri</h1>
        <h2>Laporan {{ ucfirst($kind) }}</h2>
        <p>Periode: {{ $data['awal'] ?? 'Semua' }} s.d. {{ $data['akhir'] ?? 'Semua' }} · Bulan:
            {{ $data['bulan'] ?? 'Semua' }} · Tahun: {{ $data['tahun'] ?? 'Semua' }} · Status:
            {{ $data['status'] ?? 'Semua' }}</p>
        <p class="muted">Tanggal acuan: {{ str_replace('_', ' ', $column) }}. Dibuat {{ now()->format('d/m/Y H:i') }}
            WIB.
        </p>
        @if ($net !== null)
            <p>Total bersih tercatat: Rp{{ number_format($net, 0, ',', '.') }}. {{ $unknown }} transaksi berhasil
                belum memiliki biaya.</p>
        @endif
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
                        <td colspan="{{ count($headers) }}">Tidak ada data sesuai filter.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <p>{{ count($rows) }} catatan.</p>
        <h2>Ringkasan</h2>
        @foreach ($summary as $label => $value)
            <p>{{ $label }}: {{ $value }}</p>
        @endforeach
    </body>

</html>
