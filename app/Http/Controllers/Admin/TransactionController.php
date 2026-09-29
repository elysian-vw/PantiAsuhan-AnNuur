<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\SubmissionController;
use App\Http\Requests\SubmissionRequest;
use App\Models\JenisKunjungan;
use App\Models\Kunjungan;
use App\Services\StatusService;
use App\Services\WhatsappService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TransactionController extends Controller
{
    public function index(Request $request, string $kind)
    {
        $query = SubmissionController::model($kind)::query();
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('q')) {
            $query->where(
                fn ($q) => $q
                    ->where('nama', 'like', '%'.substr($request->input('q'), 0, 100).'%')
                    ->orWhere('kode', 'like', '%'.substr($request->input('q'), 0, 100).'%'),
            );
        }
        $items = $query->latest()->paginate(15)->withQueryString();

        return view('admin.transactions.index', compact('kind', 'items'));
    }

    public function show(string $kind, int $id)
    {
        $record = SubmissionController::model($kind)::findOrFail($id);
        $allowed = app(StatusService::class)->allowed($kind, $record->status);
        $conflicts = collect();
        if ($kind === 'kunjungan') {
            $conflicts = Kunjungan::whereKeyNot($id)
                ->whereDate('tanggal', $record->tanggal)
                ->whereIn('status', ['Disetujui', 'Hadir', 'Dijadwalkan ulang'])
                ->where('jam', '<', $record->jam_selesai)
                ->where('jam_selesai', '>', $record->jam)
                ->get();
        }

        return view('admin.transactions.show', compact('kind', 'record', 'allowed', 'conflicts'));
    }

    public function update(Request $request, string $kind, int $id)
    {
        abort_unless(in_array($kind, ['bantuan', 'kunjungan']), 404);
        DB::transaction(function () use ($request, $kind, $id) {
            $record = SubmissionController::model($kind)::whereKey($id)->lockForUpdate()->firstOrFail();
            $status = app(StatusService::class);
            $rules = [
                'status' => ['required', Rule::in($status->allowed($kind, $record->status))],
                'alasan' => 'nullable|string|max:2000',
            ];
            if ($request->input('status') === 'Ditolak' || $request->input('status') === 'Dijadwalkan ulang') {
                $rules['alasan'] = 'required|string|max:2000';
            }
            if ($kind === 'bantuan') {
                $rules['kunjungan_id'] = 'nullable|exists:kunjungan,id';
                if ($request->input('status') === 'Diterima') {
                    $rules['tanggal_diterima'] = 'required|date_format:Y-m-d|before_or_equal:today';
                }
            }
            if ($kind === 'kunjungan' && $request->input('status') === 'Dijadwalkan ulang') {
                $rules += [
                    'tanggal' => 'required|date_format:Y-m-d|after_or_equal:today',
                    'jam' => 'required|date_format:H:i|after_or_equal:07:00|before:21:00',
                    'jam_selesai' => 'required|date_format:H:i|after:jam|before_or_equal:21:00',
                ];
            }
            if ($record->status === 'Dijadwalkan ulang' && $request->input('status') === 'Disetujui') {
                $rules['konfirmasi'] = 'accepted';
            }
            $data = $request->validate($rules);
            $from = $record->status;
            $note = $data['alasan'] ?? null;
            if ($kind === 'kunjungan' && $data['status'] === 'Dijadwalkan ulang') {
                $note = "Jadwal sebelumnya: {$record->tanggal} {$record->jam}–{$record->jam_selesai}. ".$note;
                $data['konfirmasi_ulang_pada'] = null;
            }
            if (isset($rules['konfirmasi'])) {
                $data['konfirmasi_ulang_pada'] = now();
            }
            unset($data['alasan'], $data['konfirmasi']);
            $record->update($data);
            $status->record($record, $from, 'admin', $note);
            app(WhatsappService::class)->notify($record, $kind);
        });

        return back()->with('success', 'Status berhasil diperbarui.');
    }

    public function walkInForm()
    {
        return view('admin.transactions.walk-in', ['visitTypes' => JenisKunjungan::all()]);
    }

    public function walkIn(SubmissionRequest $request)
    {
        $data = collect($request->validated())
            ->except(['consent', 'email'])
            ->all();
        $record = DB::transaction(function () use ($data) {
            $record = Kunjungan::create(
                $data + [
                    'kode' => 'KUN-'.Str::ulid(),
                    'token_hash' => hash('sha256', Str::random(64)),
                    'status' => 'Hadir',
                    'sumber' => 'langsung',
                ],
            );
            app(StatusService::class)->record($record, null, 'admin', 'Tamu langsung dicatat pada buku tamu.');

            return $record;
        });

        return redirect()
            ->route('admin.transaction.show', ['kunjungan', $record->id])
            ->with('success', 'Tamu langsung berhasil dicatat.');
    }

    public function calendar()
    {
        $events = Kunjungan::whereIn('status', ['Disetujui', 'Dijadwalkan ulang', 'Hadir', 'Selesai'])
            ->get()
            ->map(
                fn ($v) => [
                    'title' => $v->nama.' · '.$v->status,
                    'start' => $v->tanggal.'T'.$v->jam,
                    'end' => $v->tanggal.'T'.$v->jam_selesai,
                    'url' => route('admin.transaction.show', ['kunjungan', $v->id]),
                    'color' => $v->status === 'Dijadwalkan ulang' ? '#a66e24' : '#205b43',
                ],
            );

        return view('admin.calendar', compact('events'));
    }
}
