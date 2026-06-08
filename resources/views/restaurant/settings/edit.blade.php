@extends('layouts.dashboard', ['heading' => 'Settings'])

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.css">
@php($logoUrl = $restaurant->logo_path ? (\Illuminate\Support\Str::startsWith($restaurant->logo_path, ['http://', 'https://', 'uploads/']) ? (str_starts_with($restaurant->logo_path, 'uploads/') ? asset($restaurant->logo_path) : $restaurant->logo_path) : asset('storage/'.$restaurant->logo_path)) : null)
@php($primaryColor = $restaurant->primary_color ?: '#F84C47')
@php($serviceCharge = (float) ($restaurant->settings['service_charge_percentage'] ?? 0))
@php($vat = (float) ($restaurant->settings['vat_percentage'] ?? 0))
@php($businessType = strtolower($restaurant->businessTypeLabel()))
<form method="post" action="{{ route('restaurant.settings.update') }}" enctype="multipart/form-data" class="grid max-w-5xl gap-4 rounded-md border border-zem-border bg-zem-card p-5 md:grid-cols-2 xl:grid-cols-3">
    @csrf @method('PATCH')
    <div class="rounded-md border border-zem-border bg-zem-bg p-4 md:col-span-2 xl:col-span-3">
        <p class="text-sm font-bold text-zem-muted">{{ $restaurant->businessTypeLabel() }} logo shown on scanned menu</p>
        <div class="mt-3 flex flex-wrap items-center gap-4">
            @if($logoUrl)
                <img src="{{ $logoUrl }}" alt="{{ $restaurant->name }} logo" class="h-24 w-24 rounded-md border border-zem-border bg-zem-card object-cover">
            @else
                <div class="grid h-24 w-24 place-items-center rounded-md border border-zem-border bg-zem-card text-2xl font-extrabold">{{ strtoupper(substr($restaurant->name, 0, 1)) }}</div>
            @endif
            <div class="grid gap-2">
                <input name="logo" type="file" accept="image/*" class="rounded-md border border-zem-border bg-zem-card px-3 py-3 text-sm" data-logo-crop-input>
                <p class="text-xs text-zem-muted">After choosing a logo, crop and zoom it before saving.</p>
            </div>
            <input name="cropped_logo" type="hidden" data-cropped-logo>
        </div>
    </div>
    <input name="name" value="{{ $restaurant->name }}" placeholder="{{ $restaurant->businessTypeLabel() }} name" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2">
    <input name="slug" value="{{ $restaurant->slug }}" placeholder="{{ $restaurant->businessTypeLabel() }} link name" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2">
    <input name="phone" value="{{ $restaurant->phone }}" placeholder="Phone" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2">
    <input name="location" value="{{ $restaurant->location }}" placeholder="Location" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2">
    <label class="flex items-center justify-between gap-3 rounded-md border border-zem-border bg-zem-bg px-3 py-2 text-zem-muted">
        <span>Menu theme color</span>
        <input name="primary_color" value="{{ $primaryColor }}" type="color" class="h-10 w-20 cursor-pointer rounded border border-zem-border bg-zem-card p-1">
    </label>
    <input name="service_charge_percentage" value="{{ $serviceCharge > 0 ? $serviceCharge : '' }}" type="number" step="0.01" placeholder="Service charge %" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2">
    <input name="vat_percentage" value="{{ $vat > 0 ? $vat : '' }}" type="number" step="0.01" placeholder="VAT %" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2">
@php($paymentMethods = $restaurant->settings['payment_methods'] ?? ['cash', 'telebirr', 'cbe'])
@php($telebirrQrUrl = ! empty($restaurant->settings['telebirr_qr_path']) ? asset($restaurant->settings['telebirr_qr_path']) : null)
@php($cbeQrUrl = ! empty($restaurant->settings['cbe_qr_path']) ? asset($restaurant->settings['cbe_qr_path']) : null)
    <fieldset class="rounded-md border border-zem-border bg-zem-bg p-4 md:col-span-2">
        <legend class="px-2 text-sm font-bold text-zem-muted">Accepted customer payment methods</legend>
        <div class="mt-2 flex flex-wrap gap-4">
            <label class="flex items-center gap-2"><input name="payment_methods[]" type="checkbox" value="cash" @checked(in_array('cash', $paymentMethods, true))> Cash</label>
            <label class="flex items-center gap-2"><input name="payment_methods[]" type="checkbox" value="telebirr" @checked(in_array('telebirr', $paymentMethods, true))> Telebirr</label>
            <label class="flex items-center gap-2"><input name="payment_methods[]" type="checkbox" value="cbe" @checked(in_array('cbe', $paymentMethods, true))> CBE</label>
        </div>
    </fieldset>
    <fieldset class="rounded-md border border-zem-border bg-zem-bg p-4 md:col-span-2 xl:col-span-3">
        <legend class="px-2 text-sm font-bold text-zem-muted">Payment account details shown at checkout</legend>
        <div class="mt-2 grid gap-3 md:grid-cols-2">
            <input name="telebirr_number" value="{{ $restaurant->settings['telebirr_number'] ?? '' }}" placeholder="Telebirr phone number" class="rounded-md border border-zem-border bg-zem-card px-3 py-3">
            <input name="cbe_account_number" value="{{ $restaurant->settings['cbe_account_number'] ?? '' }}" placeholder="CBE account number" class="rounded-md border border-zem-border bg-zem-card px-3 py-3">
            <label class="grid gap-2 rounded-md border border-zem-border bg-zem-card p-3 text-sm text-zem-muted">
                <span class="font-bold text-zem-cream">Telebirr QR image</span>
                @if($telebirrQrUrl)<img src="{{ $telebirrQrUrl }}" alt="Telebirr QR" class="h-28 w-28 rounded-md bg-white object-contain p-2">@endif
                <input name="telebirr_qr" type="file" accept="image/*" class="text-sm">
            </label>
            <label class="grid gap-2 rounded-md border border-zem-border bg-zem-card p-3 text-sm text-zem-muted">
                <span class="font-bold text-zem-cream">CBE QR image</span>
                @if($cbeQrUrl)<img src="{{ $cbeQrUrl }}" alt="CBE QR" class="h-28 w-28 rounded-md bg-white object-contain p-2">@endif
                <input name="cbe_qr" type="file" accept="image/*" class="text-sm">
            </label>
        </div>
    </fieldset>
    <button class="rounded-md bg-zem-gold px-4 py-3 font-bold text-white md:col-span-2 xl:col-span-3">Save settings</button>
</form>

<div id="logo-crop-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 px-4 py-6">
    <div class="w-full max-w-3xl rounded-xl border border-zem-border bg-zem-card p-4 shadow-2xl">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-display text-xl font-bold">Adjust {{ $businessType }} logo</h2>
                <p class="mt-1 text-sm text-zem-muted">Drag the image and use zoom to remove extra space around the logo.</p>
            </div>
            <button type="button" class="rounded-md border border-zem-border px-3 py-2 text-sm font-bold text-zem-muted hover:text-zem-gold" data-logo-crop-cancel>Cancel</button>
        </div>
        <div class="mt-4 max-h-[62vh] overflow-hidden rounded-lg bg-black">
            <img id="logo-crop-target" alt="Logo crop preview" class="max-h-[62vh] w-full object-contain">
        </div>
        <div class="mt-4 grid gap-3 sm:grid-cols-2 md:grid-cols-[auto_auto_auto_auto]">
            <button type="button" class="rounded-md border border-zem-border px-4 py-2 font-bold text-zem-cream hover:border-zem-gold" data-logo-crop-zoom-out>Zoom out</button>
            <button type="button" class="rounded-md border border-zem-border px-4 py-2 font-bold text-zem-cream hover:border-zem-gold" data-logo-crop-zoom-in>Zoom in</button>
            <button type="button" class="rounded-md border border-zem-border px-4 py-2 font-bold text-zem-cream hover:border-zem-gold" data-logo-crop-reset>Reset</button>
            <button type="button" class="rounded-md bg-zem-gold px-4 py-2 font-bold text-white" data-logo-crop-apply>Use cropped logo</button>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.js"></script>
<script>
    (() => {
        const input = document.querySelector('[data-logo-crop-input]');
        const hidden = document.querySelector('[data-cropped-logo]');
        const modal = document.getElementById('logo-crop-modal');
        const image = document.getElementById('logo-crop-target');
        let cropper = null;

        if (! input || ! hidden || ! modal || ! image) return;

        function openCropper(source) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            if (cropper) cropper.destroy();
            image.onload = () => {
                cropper = new Cropper(image, {
                    aspectRatio: 1,
                    viewMode: 1,
                    dragMode: 'move',
                    autoCropArea: 1,
                    background: false,
                    responsive: true,
                    zoomOnWheel: true,
                });
                image.onload = null;
            };
            image.src = source;
        }

        function closeCropper(clearFile = false) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            if (cropper) {
                cropper.destroy();
                cropper = null;
            }
            image.removeAttribute('src');
            if (clearFile) {
                input.value = '';
                hidden.value = '';
            }
        }

        input.addEventListener('change', () => {
            const file = input.files && input.files[0];
            if (! file) return;

            const reader = new FileReader();
            reader.onload = () => openCropper(reader.result);
            reader.readAsDataURL(file);
        });

        modal.querySelector('[data-logo-crop-zoom-out]').addEventListener('click', () => {
            if (! cropper) return;
            cropper.zoom(-0.1);
        });

        modal.querySelector('[data-logo-crop-zoom-in]').addEventListener('click', () => {
            if (! cropper) return;
            cropper.zoom(0.1);
        });

        modal.querySelector('[data-logo-crop-reset]').addEventListener('click', () => {
            if (! cropper) return;
            cropper.reset();
        });

        modal.querySelector('[data-logo-crop-cancel]').addEventListener('click', () => closeCropper(true));

        modal.querySelector('[data-logo-crop-apply]').addEventListener('click', () => {
            if (! cropper) return;
            hidden.value = cropper.getCroppedCanvas({
                width: 900,
                height: 900,
                imageSmoothingEnabled: true,
                imageSmoothingQuality: 'high',
            }).toDataURL('image/png');
            closeCropper();
        });
    })();
</script>
@endsection
