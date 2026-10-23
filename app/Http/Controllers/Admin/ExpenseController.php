<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KategoriPengeluaran;
use App\Models\Pengeluaran;
use App\Services\ExpenseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $query = Pengeluaran::with(['category', 'creator', 'poster']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('kategori')) {
            $query->where('kategori_pengeluaran_id', $request->input('kategori'));
        }
        if ($request->filled('awal')) {
            $query->whereDate('tanggal', '>=', $request->input('awal'));
        }
        if ($request->filled('akhir')) {
            $query->whereDate('tanggal', '<=', $request->input('akhir'));
        }
        if ($request->filled('q')) {
            $term = substr($request->input('q'), 0, 100);
            $query->where(fn ($q) => $q->where('kode', 'like', "%{$term}%")
                ->orWhere('keperluan', 'like', "%{$term}%")
                ->orWhere('penerima', 'like', "%{$term}%")
            );
        }

        $items = $query->latest('tanggal')->latest('id')->paginate(15)->withQueryString();
        $categories = KategoriPengeluaran::orderBy('nama')->get();

        $metrics = [
            'total_dibukukan' => Pengeluaran::where('status', 'Dibukukan')->sum('nominal'),
            'total_draft' => Pengeluaran::where('status', 'Draft')->sum('nominal'),
            'count_draft' => Pengeluaran::where('status', 'Draft')->count(),
            'total_dibatalkan' => Pengeluaran::where('status', 'Dibatalkan')->sum('nominal'),
        ];

        return view('admin.expenses.index', compact('items', 'categories', 'metrics'));
    }

    public function create()
    {
        $expense = new Pengeluaran(['tanggal' => now()]);
        $categories = KategoriPengeluaran::orderBy('nama')->get();

        return view('admin.expenses.form', compact('expense', 'categories'));
    }

    public function store(Request $request, ExpenseService $service)
    {
        $data = $request->validate([
            'tanggal' => 'required|date',
            'kategori_pengeluaran_id' => 'required|exists:kategori_pengeluaran,id',
            'keperluan' => 'required|string|max:255',
            'nominal' => 'required|numeric|min:1|max:999999999999',
            'penerima' => 'nullable|string|max:150',
            'keterangan' => 'nullable|string|max:2000',
            'is_public' => 'nullable|boolean',
            'bukti' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
            'langsung_dibukukan' => 'nullable|boolean',
        ]);

        $data['is_public'] = $request->boolean('is_public');
        $expense = $service->saveDraft(null, $data, $request->file('bukti'), $request->user());

        if ($request->boolean('langsung_dibukukan')) {
            $service->post($expense, $request->user());
            return redirect()->route('admin.expenses.show', $expense)->with('success', 'Pengeluaran berhasil disimpan dan langsung dibukukan.');
        }

        return redirect()->route('admin.expenses.show', $expense)->with('success', 'Draft pengeluaran berhasil disimpan.');
    }

    public function show(int $id)
    {
        $expense = Pengeluaran::with(['category', 'creator', 'poster', 'canceller', 'histories.user'])->findOrFail($id);

        return view('admin.expenses.show', compact('expense'));
    }

    public function edit(int $id)
    {
        $expense = Pengeluaran::findOrFail($id);
        abort_unless($expense->isDraft(), 403, 'Hanya draft yang dapat disunting.');

        $categories = KategoriPengeluaran::orderBy('nama')->get();

        return view('admin.expenses.form', compact('expense', 'categories'));
    }

    public function update(Request $request, int $id, ExpenseService $service)
    {
        $expense = Pengeluaran::findOrFail($id);
        abort_unless($expense->isDraft(), 403, 'Hanya draft yang dapat disunting.');

        $data = $request->validate([
            'tanggal' => 'required|date',
            'kategori_pengeluaran_id' => 'required|exists:kategori_pengeluaran,id',
            'keperluan' => 'required|string|max:255',
            'nominal' => 'required|numeric|min:1|max:999999999999',
            'penerima' => 'nullable|string|max:150',
            'keterangan' => 'nullable|string|max:2000',
            'is_public' => 'nullable|boolean',
            'bukti' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
            'langsung_dibukukan' => 'nullable|boolean',
        ]);

        $data['is_public'] = $request->boolean('is_public');
        $expense = $service->saveDraft($id, $data, $request->file('bukti'), $request->user());

        if ($request->boolean('langsung_dibukukan')) {
            $service->post($expense, $request->user());
            return redirect()->route('admin.expenses.show', $expense)->with('success', 'Pengeluaran diperbarui dan berhasil dibukukan.');
        }

        return redirect()->route('admin.expenses.show', $expense)->with('success', 'Draft pengeluaran berhasil diperbarui.');
    }

    public function post(int $id, ExpenseService $service, Request $request)
    {
        $expense = Pengeluaran::findOrFail($id);
        $service->post($expense, $request->user());

        return back()->with('success', 'Pengeluaran telah dibukukan dan kini mempengaruhi laporan keuangan.');
    }

    public function cancel(Request $request, int $id, ExpenseService $service)
    {
        $data = $request->validate([
            'alasan' => 'required|string|min:5|max:2000',
        ]);

        $expense = Pengeluaran::findOrFail($id);
        $service->cancel($expense, $data['alasan'], $request->user());

        return back()->with('success', 'Pengeluaran berhasil dibatalkan dengan catatan koreksi audit.');
    }

    public function destroy(int $id, ExpenseService $service)
    {
        $expense = Pengeluaran::findOrFail($id);
        $service->deleteDraft($expense);

        return redirect()->route('admin.expenses.index')->with('success', 'Draft pengeluaran telah dihapus.');
    }

    public function downloadBukti(int $id)
    {
        $expense = Pengeluaran::findOrFail($id);
        abort_unless($expense->bukti_path && Storage::exists($expense->bukti_path), 404, 'Berkas bukti tidak ditemukan.');

        $ext = pathinfo($expense->bukti_path, PATHINFO_EXTENSION);
        return Storage::download($expense->bukti_path, "bukti-{$expense->kode}.{$ext}");
    }
}
