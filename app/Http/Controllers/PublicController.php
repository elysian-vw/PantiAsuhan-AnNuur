<?php

namespace App\Http\Controllers;

use App\Models\Berita;
use App\Models\Galeri;
use App\Models\JenisKunjungan;
use App\Models\KategoriBantuan;
use App\Models\KategoriDonasi;
use App\Models\Kebutuhan;
use App\Models\Kegiatan;
use App\Models\Pengurus;

class PublicController extends Controller
{
    public function home()
    {
        return view('public.home', [
            'needs' => Kebutuhan::where('status', '!=', 'Terpenuhi')->latest()->take(3)->get(),
            'activities' => Kegiatan::where('status', 'Terbit')->latest('tanggal')->take(3)->get(),
        ]);
    }

    public function page(string $page)
    {
        abort_unless(in_array($page, ['profil', 'kontak', 'kegiatan', 'berita', 'galeri', 'kebutuhan']), 404);
        $items = match ($page) {
            'profil' => Pengurus::orderBy('urutan')->get(),
            'kegiatan' => Kegiatan::where('status', 'Terbit')->latest('tanggal')->paginate(9),
            'berita' => Berita::where('status', 'Terbit')->latest('tanggal')->paginate(9),
            'galeri' => Galeri::where(
                fn ($q) => $q->whereNull('kegiatan_id')->orWhereHas('kegiatan', fn ($q) => $q->where('status', 'Terbit')),
            )
                ->latest()
                ->paginate(12),
            'kebutuhan' => Kebutuhan::latest()->paginate(9),
            default => collect(),
        };

        return view('public.page', compact('page', 'items'));
    }

    public function article(string $page, int $id)
    {
        $model = match ($page) {
            'kegiatan' => Kegiatan::class,
            'berita' => Berita::class,
            default => abort(404),
        };
        $item = $model::where('status', 'Terbit')->findOrFail($id);

        return view('public.article', compact('page', 'item'));
    }

    public function form(string $kind)
    {
        abort_unless(in_array($kind, ['donasi', 'bantuan', 'kunjungan']), 404);

        return view('public.form', [
            'kind' => $kind,
            'donationCategories' => KategoriDonasi::all(),
            'assistanceCategories' => KategoriBantuan::all(),
            'visitTypes' => JenisKunjungan::all(),
        ]);
    }
}
