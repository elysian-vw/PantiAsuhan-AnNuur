{{-- Flash Data Container for Automatic SweetAlert2 Toast --}}
<div id="flash-data" data-success="{{ session('success') }}" data-warning="{{ session('warning') }}"
    data-info="{{ session('info') }}"
    data-error="{{ session('error') ?? ($errors->has('hapus') ? $errors->first('hapus') : '') }}">
</div>

@if (session('success'))
    <div class="notice success" role="status" x-data="{ show: true }" x-show="show" x-transition.opacity>
        <svg class="notice-icon text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <div class="notice-content">
            <strong>Berhasil!</strong> {{ session('success') }}
        </div>
        <button type="button" class="notice-close" @click="show = false" aria-label="Tutup pemberitahuan">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>
@endif

@if (session('warning'))
    <div class="notice" role="status" x-data="{ show: true }" x-show="show" x-transition.opacity>
        <svg class="notice-icon text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        <div class="notice-content">
            <strong>Perhatian:</strong> {{ session('warning') }}
        </div>
        <button type="button" class="notice-close" @click="show = false" aria-label="Tutup pemberitahuan">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>
@endif

@if (session('error'))
    <div class="notice error" role="alert" x-data="{ show: true }" x-show="show" x-transition.opacity>
        <svg class="notice-icon text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <div class="notice-content">
            <strong>Gagal:</strong> {{ session('error') }}
        </div>
        <button type="button" class="notice-close" @click="show = false" aria-label="Tutup pemberitahuan">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>
@endif

@if ($errors->any())
    <div class="notice error" role="alert" x-data="{ show: true }" x-show="show" x-transition.opacity>
        <svg class="notice-icon text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <div class="notice-content">
            <strong>Periksa kembali isian berikut:</strong>
            <ul class="mt-2 list-disc pl-5 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        <button type="button" class="notice-close" @click="show = false" aria-label="Tutup pemberitahuan">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>
@endif
