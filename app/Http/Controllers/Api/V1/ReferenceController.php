<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\KategoriDonasi;
use Illuminate\Http\JsonResponse;

class ReferenceController extends Controller
{
    public function donationCategories(): JsonResponse
    {
        $categories = KategoriDonasi::select('id', 'nama', 'deskripsi')->get();

        return response()->json([
            'data' => $categories,
        ]);
    }

    public function nominalPresets(): JsonResponse
    {
        return response()->json([
            'presets' => [25000, 50000, 100000, 250000, 500000, 1000000],
            'minimum_nominal' => 5000,
            'currency' => 'IDR',
        ]);
    }
}
