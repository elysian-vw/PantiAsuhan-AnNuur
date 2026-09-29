<div class="form-grid">
    <x-field name="instansi" label="Instansi / komunitas (opsional)" maxlength="200" />
    <x-field name="peserta" label="Jumlah peserta" type="number" min="1" max="100000" inputmode="numeric"
        required />
    <div class="field"><label for="jenis_kunjungan_id">Jenis kunjungan *</label><select name="jenis_kunjungan_id"
            id="jenis_kunjungan_id" required>
            @foreach ($visitTypes as $type)
                <option value="{{ $type->id }}" @selected(old('jenis_kunjungan_id') == $type->id)>{{ $type->nama }}</option>
            @endforeach
        </select></div>
    <x-field name="tanggal" label="Tanggal kunjungan" type="date" :min="date('Y-m-d')" required />
    <x-field name="jam" label="Jam mulai (WIB)" type="time" min="07:00" max="20:59" required />
    <x-field name="jam_selesai" label="Jam selesai (WIB)" type="time" min="07:01" max="21:00" required />
</div><x-field name="tujuan" label="Tujuan kunjungan" type="textarea" maxlength="2000" required /><x-field
    name="catatan" label="Catatan (opsional)" type="textarea" maxlength="2000" />
