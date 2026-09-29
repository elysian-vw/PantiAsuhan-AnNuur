<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\WhatsappLog;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public const FIELDS = [
        'tentang' => 'Tentang panti',
        'sejarah' => 'Sejarah',
        'visi' => 'Visi',
        'misi' => 'Misi',
        'alamat' => 'Alamat lengkap',
        'telepon' => 'Nomor telepon',
        'email' => 'Email kontak',
        'maps_url' => 'Tautan lokasi Google Maps',
        'penerima_manfaat' => 'Jumlah penerima manfaat',
        'periode_pengurus' => 'Periode kepengurusan',
        'pendiri' => 'Pendiri yayasan',
        'periode_statistik' => 'Periode statistik penerima manfaat',
        'rekap_penerima' => 'Rincian rekap penerima manfaat',
        'tata_tertib' => 'Ringkasan tata tertib santri',
        'sumber_profil' => 'Sumber informasi profil',
        'tindak_lanjut' => 'Catatan tindak lanjut (khusus admin)',
    ];

    public function index()
    {
        return view('admin.settings', [
            'fields' => self::FIELDS,
            'values' => Setting::pluck('value', 'key'),
            'logs' => WhatsappLog::latest()->paginate(15),
        ]);
    }

    public function save(Request $request)
    {
        $rules = array_fill_keys(array_keys(self::FIELDS), 'nullable|string|max:20000');
        $rules['email'] = 'nullable|email|max:200';
        $rules['maps_url'] = 'nullable|url:http,https|max:1000';
        $rules['telepon'] = 'nullable|string|max:100';
        $rules['penerima_manfaat'] = 'nullable|integer|min:0|max:1000000';
        $data = $request->validate($rules);
        foreach ($data as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
            cache()->forget("setting:{$key}");
        }

        return back()->with('success', 'Profil dan pengaturan website berhasil disimpan.');
    }
}
