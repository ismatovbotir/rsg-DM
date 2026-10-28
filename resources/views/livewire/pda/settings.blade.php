@section('title', 'Sozlamalar')

<div class="contents" x-data="{
        saved: (() => { try { return localStorage.getItem('kb_mode') || '' } catch (e) { return '' } })(),
        msg: 'Hozirgi rejim: ' + (window.kbMode === 'on' ? 'yoqilgan' : 'o‘chiq (tugma bilan)'),
        choose(v) {
            this.saved = v
            try { v ? localStorage.setItem('kb_mode', v) : localStorage.removeItem('kb_mode') } catch (e) {}
            this.msg = 'Saqlandi. Keyingi sahifadan boshlab amal qiladi.'
        }
     }" @keydown.window.escape="window.location = '{{ route('menu') }}'">
    <x-pda.page title="Sozlamalar" :back="route('menu')" :notice="$notice" :failure="$failure">
        <div class="pad">
            <h3 class="mt-0">Ekran klaviaturasi</h3>
            <p class="sub">
                Bu qurilmaning o‘zi uchun. Administrator tanlagan rejim:
                <strong>{{ $modes[$device?->keyboard_mode] ?? $device?->keyboard_mode }}</strong>.
                Quyidagi tanlov faqat shu qurilmada ishlaydi va administrator sozlamasidan ustun turadi.
            </p>

            <label class="row"><input type="radio" name="kb" value="" :checked="saved === ''" @change="choose('')"> <span class="label">Administrator sozlamasi</span></label>
            <label class="row"><input type="radio" name="kb" value="auto" :checked="saved === 'auto'" @change="choose('auto')"> <span class="label">Avto (ekran va sensorga qarab)</span></label>
            <label class="row"><input type="radio" name="kb" value="on" :checked="saved === 'on'" @change="choose('on')"> <span class="label">Klaviatura yoqilgan</span></label>
            <label class="row"><input type="radio" name="kb" value="off" :checked="saved === 'off'" @change="choose('off')"> <span class="label">Klaviatura o‘chiq (⌨ tugmasi bilan chaqiriladi)</span></label>

            <p class="sub" x-text="msg"></p>
            <p class="sub">Qurilma: {{ $device?->name ?: 'nomsiz' }} · {{ substr((string) $device?->uuid, 0, 8) }}</p>
        </div>
    </x-pda.page>
</div>