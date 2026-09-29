{{-- Profil Info Cards: Tentang, Sejarah, Visi, Misi --}}
<div class="profil-cards-grid">

    <section class="profil-card profil-card-green" id="tentang">
        <div class="profil-card-icon" aria-hidden="true">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
        </div>
        <p class="profil-card-label">Tentang kami</p>
        <p class="prose-text profil-card-body">{{ \App\Models\Setting::read('tentang') ?: 'Informasi sedang disiapkan oleh pengurus.' }}</p>
    </section>

    <section class="profil-card profil-card-gold" id="sejarah">
        <div class="profil-card-icon" aria-hidden="true">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <p class="profil-card-label">Sejarah panti</p>
        <p class="prose-text profil-card-body">{{ \App\Models\Setting::read('sejarah') ?: 'Informasi sedang disiapkan oleh pengurus.' }}</p>
    </section>

    <section class="profil-card profil-card-teal" id="visi">
        <div class="profil-card-icon" aria-hidden="true">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
        </div>
        <p class="profil-card-label">Visi</p>
        <p class="prose-text profil-card-body">{{ \App\Models\Setting::read('visi') ?: 'Informasi sedang disiapkan oleh pengurus.' }}</p>
    </section>

    <section class="profil-card profil-card-amber" id="misi">
        <div class="profil-card-icon" aria-hidden="true">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
            </svg>
        </div>
        <p class="profil-card-label">Misi</p>
        <p class="prose-text profil-card-body">{{ \App\Models\Setting::read('misi') ?: 'Informasi sedang disiapkan oleh pengurus.' }}</p>
    </section>

</div>
