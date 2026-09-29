@extends('layouts.public')
@php($title=match($kind){'donasi'=>'Berbagi untuk harapan','bantuan'=>'Kebaikan dalam setiap pemberian','kunjungan'=>'Mari menjalin silaturahmi'})
@section('title',ucfirst($kind))
@section('content')
<section class="page-heading"><div class="container"><p class="eyebrow">{{ ['donasi'=>'DONASI UANG','bantuan'=>'BANTUAN BARANG','kunjungan'=>'PENDAFTARAN KUNJUNGAN'][$kind] }}</p><h1>{{ $title }}</h1><p>Isi formulir di bawah ini. Tidak perlu membuat akun.</p></div></section>
<div class="container form-layout section"><div><x-flash/>
<form action="{{ route('submission.store',$kind) }}" method="post" class="card form-card" x-data="{ amount: {{ (int)old('nominal',50000) }}, submitting:false }" @submit="submitting=true">
@csrf
@if($kind==='donasi')
<div class="form-section"><span class="step-number">01</span><h2>Pilih donasi Anda</h2></div>
<div class="field"><label for="kategori_donasi_id">Kategori donasi *</label><select name="kategori_donasi_id" id="kategori_donasi_id" required>@foreach($donationCategories as $category)<option value="{{ $category->id }}" @selected(old('kategori_donasi_id')==$category->id)>{{ $category->nama }}</option>@endforeach</select></div>
<fieldset><legend class="field-label">Nominal donasi</legend><div class="amount-grid">@foreach([25000,50000,100000,250000,500000] as $amount)<button type="button" class="amount-option" :class="{ selected: amount == {{ $amount }} }" :aria-pressed="amount == {{ $amount }}" @click="amount={{ $amount }}">Rp{{ number_format($amount,0,',','.') }}</button>@endforeach</div></fieldset>
<x-field name="nominal" label="Nominal pilihan / nominal lainnya (Rp)" type="number" min="5000" max="999999999" step="1" inputmode="numeric" x-model="amount" required/>
<p class="help-text">Minimal Rp5.000. Pembayaran saat ini menggunakan Midtrans Sandbox.</p>
<hr><div class="form-section"><span class="step-number">02</span><h2>Data donatur</h2></div>
@else
<div class="form-section"><span class="step-number">01</span><h2>{{ $kind==='kunjungan'?'Penanggung jawab':'Data pemberi bantuan' }}</h2></div>
@endif
<div class="form-grid"><x-field name="nama" label="Nama lengkap" autocomplete="name" maxlength="150" required/><x-field name="whatsapp" label="Nomor WhatsApp" type="tel" inputmode="tel" autocomplete="tel" placeholder="081234567890" required/></div>
@if($kind!=='kunjungan')<x-field name="email" label="Email (opsional)" type="email" autocomplete="email"/>@endif
@if($kind==='bantuan')
<hr><div class="form-section"><span class="step-number">02</span><h2>Barang yang akan diberikan</h2></div>
<div x-data="assistanceItems({{ Js::from(old('items',[['nama'=>'','kategori_bantuan_id'=>'','jumlah'=>1,'satuan'=>'']])) }})">
<template x-for="(item,index) in items" :key="item.key"><div class="item-form">
<div class="flex justify-between items-center mb-4"><strong x-text="'Barang '+(index+1)"></strong><button type="button" class="text-link danger inline-flex items-center gap-1" @click="items.splice(index,1)" x-show="items.length>1"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg> Hapus</button></div>
<div class="form-grid"><label class="field">Nama barang *<input :name="'items['+index+'][nama]'" x-model="item.nama" maxlength="150" required></label><label class="field">Kategori *<select :name="'items['+index+'][kategori_bantuan_id]'" x-model="item.kategori_bantuan_id" required><option value="">Pilih kategori</option>@foreach($assistanceCategories as $category)<option value="{{ $category->id }}">{{ $category->nama }}</option>@endforeach</select></label><label class="field">Jumlah *<input type="number" min="0.01" max="999999999" step="0.01" :name="'items['+index+'][jumlah]'" x-model="item.jumlah" required></label><label class="field">Satuan *<input :name="'items['+index+'][satuan]'" x-model="item.satuan" placeholder="kg, liter, buah..." maxlength="40" required></label></div>
</div></template><button type="button" class="button outline small inline-flex items-center gap-1.5" @click="add()" :disabled="items.length>=30"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg> Tambah barang</button></div>
<x-field class="mt-6" name="catatan" label="Catatan (opsional)" type="textarea" maxlength="2000"/>
@elseif($kind==='kunjungan')
<hr><div class="form-section"><span class="step-number">02</span><h2>Rencana kunjungan</h2></div>
@include('public.partials.visit-fields')
@endif
<hr><label class="checkbox-label"><input type="checkbox" name="consent" value="1" required @checked(old('consent'))><span>Saya menyetujui penggunaan data ini oleh pengurus untuk memproses {{ $kind }} dan mengirim pemberitahuan melalui WhatsApp.</span></label>
<button class="button w-full mt-6" type="submit" :disabled="submitting" x-text="submitting ? 'Menyimpan…' : '{{ $kind==='donasi'?'Lanjutkan donasi →':'Kirim pengajuan →' }}'">{{ $kind==='donasi'?'Lanjutkan donasi →':'Kirim pengajuan →' }}</button>
</form></div>
<aside class="form-aside"><p class="eyebrow">KEBAIKAN ANDA BERARTI</p><h2>Langkah kecil,<br>arti yang besar.</h2>
@if($kind==='donasi')<p>Pilih nominal, isi identitas, lalu lanjutkan pembayaran. Status diperbarui setelah konfirmasi dari penyedia pembayaran.</p><div class="notice">Mode pengujian sandbox. Jangan melakukan transfer uang nyata untuk transaksi pengujian.</div>
@elseif($kind==='bantuan')<p>Pengurus akan memeriksa dan mengonfirmasi bantuan Anda. Tanggal penerimaan dicatat setelah barang benar-benar diterima.</p>
@else<p>Waktu kunjungan 07.00–21.00 WIB. Jadwal berlaku setelah disetujui pengurus. Perubahan jadwal akan dikonfirmasi kembali kepada Anda.</p>@endif
<div class="aside-divider"></div><h3>Simpan tautan pribadi Anda</h3><p>Setelah mengirim formulir, simpan tautan halaman riwayat untuk memeriksa perkembangan. Jangan membagikannya kepada orang lain.</p><a class="text-link inline-flex items-center gap-1" href="{{ route('page','kontak') }}">Hubungi pengurus <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg></a>
</aside></div>
@endsection
