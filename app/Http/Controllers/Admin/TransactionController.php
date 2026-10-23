<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\SubmissionController;
use App\Models\Donasi;
use App\Services\StatusService;
use App\Services\WhatsappService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class TransactionController extends Controller
{
    public function downloadManualProof(Donasi $donation)
    {
        abort_unless($donation->isManual() && $donation->bukti_transfer, 404);
        $disk = Storage::disk('local');
        abort_unless($disk->exists($donation->bukti_transfer), 404);

        return $disk->download($donation->bukti_transfer, 'bukti-'.$donation->kode.'.'.pathinfo($donation->bukti_transfer, PATHINFO_EXTENSION));
    }

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

        return view('admin.transactions.show', compact('kind', 'record', 'allowed'));
    }

    public function update(Request $request, string $kind, int $id)
    {
        abort_unless($kind === 'bantuan', 404);
        DB::transaction(function () use ($request, $kind, $id) {
            $record = SubmissionController::model($kind)::whereKey($id)->lockForUpdate()->firstOrFail();
            $status = app(StatusService::class);
            $rules = [
                'status' => ['required', Rule::in($status->allowed($kind, $record->status))],
                'alasan' => $request->input('status') === 'Ditolak' ? 'required|string|max:2000' : 'nullable|string|max:2000',
            ];
            if ($request->input('status') === 'Diterima') {
                $rules['tanggal_diterima'] = 'required|date_format:Y-m-d|before_or_equal:today';
            }
            $data = $request->validate($rules);
            $from = $record->status;
            $note = $data['alasan'] ?? null;
            unset($data['alasan']);
            $record->update($data);
            $status->record($record, $from, 'admin', $note);
            app(WhatsappService::class)->notify($record, $kind);
        });

        return back()->with('success', 'Status berhasil diperbarui.');
    }
}
