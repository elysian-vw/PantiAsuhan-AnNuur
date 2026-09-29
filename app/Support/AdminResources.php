<?php

namespace App\Support;

use App\Models\Berita;
use App\Models\Donatur;
use App\Models\Galeri;
use App\Models\JenisKunjungan;
use App\Models\KategoriBantuan;
use App\Models\KategoriDonasi;
use App\Models\Kebutuhan;
use App\Models\Kegiatan;
use App\Models\Pengurus;
use App\Models\User;

class AdminResources
{
    public static function all(): array
    {
        $name = ['nama' => ['Nama', 'text', 'required|string|max:150']];
        $description = ['deskripsi' => ['Deskripsi', 'textarea', 'nullable|string|max:20000']];
        $photo = [
            'foto' => [
                'Foto (JPG, PNG, WebP; maksimal 2 MB)',
                'file',
                'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            ],
        ];
        $publication = [
            'judul' => ['Judul', 'text', 'required|string|max:200'],
            'tanggal' => ['Tanggal', 'date', 'required|date'],
            'lokasi' => ['Lokasi', 'text', 'nullable|string|max:200'],
            'deskripsi' => ['Isi / deskripsi', 'textarea', 'required|string|max:50000'],
        ] +
            $photo + [
                'status' => [
                    'Status publikasi',
                    'select',
                    'required|in:Draft,Terbit',
                    ['Draft' => 'Draft', 'Terbit' => 'Terbit'],
                ],
            ];

        return [
            'pengguna' => [
                'Pengguna',
                User::class,
                [
                    'name' => ['Nama', 'text', 'required|string|max:150'],
                    'email' => ['Email', 'email', 'required|email|max:200'],
                    'password' => ['Password (minimal 12 karakter)', 'password', 'nullable|string|min:12|confirmed'],
                ],
                'Master Data',
            ],
            'kategori-donasi' => ['Kategori Donasi', KategoriDonasi::class, $name + $description, 'Master Data'],
            'kategori-bantuan' => ['Kategori Bantuan', KategoriBantuan::class, $name + $description, 'Master Data'],
            'jenis-kunjungan' => ['Jenis Kunjungan', JenisKunjungan::class, $name + $description, 'Master Data'],
            'donatur' => [
                'Data Donatur',
                Donatur::class,
                $name + [
                    'whatsapp' => ['WhatsApp', 'tel', ['required', 'regex:/^628[0-9]{7,11}$/']],
                    'email' => ['Email', 'email', 'nullable|email|max:200'],
                ],
                'Donasi & Bantuan',
            ],
            'kebutuhan' => [
                'Kebutuhan Panti',
                Kebutuhan::class,
                $name +
                    $description + [
                        'kategori_bantuan_id' => [
                            'Kategori',
                            'relation',
                            'required|exists:kategori_bantuan,id',
                            KategoriBantuan::class,
                        ],
                        'target' => ['Target', 'number', 'required|numeric|min:0.01|max:999999999'],
                        'terpenuhi' => ['Sudah terpenuhi', 'number', 'required|numeric|min:0|max:999999999'],
                        'satuan' => ['Satuan', 'text', 'required|string|max:40'],
                    ],
                'Donasi & Bantuan',
            ],
            'pengurus' => [
                'Struktur Organisasi',
                Pengurus::class,
                $name + [
                    'jabatan' => ['Jabatan', 'text', 'required|string|max:150'],
                    'urutan' => ['Urutan tampil', 'number', 'required|integer|min:0|max:1000'],
                ] +
                $photo,
                'Publikasi',
            ],
            'kegiatan' => ['Kegiatan', Kegiatan::class, $publication, 'Publikasi'],
            'berita' => ['Berita', Berita::class, $publication, 'Publikasi'],
            'galeri' => [
                'Galeri',
                Galeri::class,
                [
                    'judul' => ['Judul foto', 'text', 'required|string|max:200'],
                    'kegiatan_id' => [
                        'Kegiatan (opsional)',
                        'relation',
                        'nullable|exists:kegiatan,id',
                        Kegiatan::class,
                    ],
                ] +
                $description +
                $photo,
                'Publikasi',
            ],
        ];
    }

    public static function get(string $key): array
    {
        return self::all()[$key] ?? abort(404);
    }
}
