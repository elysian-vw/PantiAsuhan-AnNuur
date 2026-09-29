<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AdminResources;
use App\Support\Phone;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ResourceController extends Controller
{
    public function index(Request $request, string $resource)
    {
        [$title, $model, $fields] = AdminResources::get($resource);
        $query = $model::query();
        if ($request->filled('q')) {
            $column = isset($fields['nama']) ? 'nama' : (isset($fields['name']) ? 'name' : 'judul');
            $query->where($column, 'like', '%'.substr($request->string('q'), 0, 150).'%');
        }
        $items = $query->latest()->paginate(15)->withQueryString();

        return view('admin.resources.index', compact('title', 'resource', 'fields', 'items'));
    }

    public function form(string $resource, ?int $id = null)
    {
        [$title, $model, $fields] = AdminResources::get($resource);
        $item = $id ? $model::findOrFail($id) : new $model;

        return view('admin.resources.form', compact('title', 'resource', 'fields', 'item'));
    }

    public function save(Request $request, string $resource, ?int $id = null)
    {
        [$title, $model, $fields] = AdminResources::get($resource);
        $item = $id ? $model::findOrFail($id) : new $model;
        $rules = collect($fields)->map(fn ($f) => $f[2])->all();
        if ($resource === 'pengguna') {
            $rules['email'] = ['required', 'email', 'max:200', Rule::unique('users')->ignore($item->id)];
            $rules['password'] = [($id ? 'nullable' : 'required'), 'string', 'min:12', 'confirmed'];
        }
        if ($resource === 'donatur') {
            $request->merge(['whatsapp' => Phone::normalize($request->input('whatsapp'))]);
            $rules['whatsapp'][] = Rule::unique('donatur')->ignore($item->id);
        }
        if (in_array($resource, ['kategori-donasi', 'kategori-bantuan', 'jenis-kunjungan'])) {
            $rules['nama'] = ['required', 'string', 'max:150', Rule::unique($item->getTable(), 'nama')->ignore($item->id)];
        }
        if ($resource === 'galeri' && ! $id) {
            $rules['foto'] = 'required|image|mimes:jpg,jpeg,png,webp|max:2048';
        }
        $data = $request->validate($rules);
        if (empty($data['password'])) {
            unset($data['password']);
        }
        if ($request->hasFile('foto')) {
            $data['foto'] = $request->file('foto')->store('photos', 'public');
        } else {
            unset($data['foto']);
        }
        if ($resource === 'kebutuhan') {
            $data['status'] = $data['terpenuhi'] >= $data['target'] ? 'Terpenuhi' : ($data['terpenuhi'] > 0 ? 'Sebagian terpenuhi' : 'Dibutuhkan');
        }
        $item->fill($data)->save();

        return redirect()->route('admin.resource.index', $resource)->with('success', "$title berhasil disimpan.");
    }

    public function destroy(Request $request, string $resource, int $id)
    {
        [, $model] = AdminResources::get($resource);
        try {
            DB::transaction(function () use ($model, $id, $resource, $request) {
                if ($resource === 'pengguna') {
                    $users = User::lockForUpdate()->get();
                    if ($users->count() <= 1 || $request->user()->id === $id) {
                        throw ValidationException::withMessages(['hapus' => 'Akun sendiri atau admin terakhir tidak boleh dihapus.']);
                    }
                }
                $model::findOrFail($id)->delete();
            });
        } catch (QueryException $e) {
            throw ValidationException::withMessages(['hapus' => 'Data masih dipakai pada catatan lain sehingga tidak dapat dihapus.']);
        }

        return back()->with('success', 'Data berhasil dihapus.');
    }
}
