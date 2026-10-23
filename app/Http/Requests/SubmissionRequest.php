<?php

namespace App\Http\Requests;

use App\Support\Phone;
use Illuminate\Foundation\Http\FormRequest;

class SubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['whatsapp' => Phone::normalize($this->input('whatsapp'))]);
    }

    public function rules(): array
    {
        $rules = [
            'nama' => 'required|string|max:150',
            'whatsapp' => ['required', 'regex:/^628[0-9]{7,11}$/'],
            'email' => 'nullable|email|max:200',
            'consent' => 'accepted',
        ];

        return $rules +
            match ($this->route('kind')) {
                'donasi' => [
                    'kategori_donasi_id' => 'required|exists:kategori_donasi,id',
                    'nominal' => 'required|integer|min:5000|max:999999999',
                ],
                'bantuan' => [
                    'catatan' => 'nullable|string|max:2000',
                    'items' => 'required|array|min:1|max:30',
                    'items.*' => 'array:nama,kategori_bantuan_id,jumlah,satuan',
                    'items.*.nama' => 'required|string|max:150',
                    'items.*.kategori_bantuan_id' => 'required|exists:kategori_bantuan,id',
                    'items.*.jumlah' => 'required|numeric|min:0.01|max:999999999',
                    'items.*.satuan' => 'required|string|max:40',
                ],
                default => [],
            };
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'min' => ':attribute di bawah batas minimum (:min).',
            'whatsapp.regex' => 'Gunakan nomor WhatsApp Indonesia yang valid, misalnya 081234567890.',
            'consent.accepted' => 'Persetujuan penggunaan data diperlukan untuk memproses pengajuan.',
        ];
    }
}
