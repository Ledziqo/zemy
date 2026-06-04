@extends('layouts.dashboard', ['heading' => 'Menu Items'])

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.css">
<form method="post" action="{{ route('restaurant.menu-items.store') }}" enctype="multipart/form-data" class="mb-6 grid gap-3 rounded-md border border-zem-border bg-zem-card p-4 md:grid-cols-2 xl:grid-cols-5">
    @csrf
    <select name="category_id" class="rounded-md border border-zem-border bg-zem-bg px-3 py-3">@foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select>
    <input name="name" required placeholder="Item name" class="rounded-md border border-zem-border bg-zem-bg px-3 py-3">
    <input name="price" required type="number" step="0.01" placeholder="Price" class="rounded-md border border-zem-border bg-zem-bg px-3 py-3">
    <input name="image" type="file" accept="image/*" class="rounded-md border border-zem-border bg-zem-bg px-3 py-3 text-sm" data-image-crop-input>
    <input name="cropped_image" type="hidden" data-cropped-image>
    <div class="flex flex-wrap items-center gap-4 rounded-md border border-zem-border bg-zem-bg px-3 py-3">
        <label class="flex items-center gap-2"><input name="is_available" type="checkbox" value="1" checked> Available</label>
        <label class="flex items-center gap-2"><input name="is_featured" type="checkbox" value="1"> Featured</label>
    </div>
    <textarea name="description" placeholder="Description" class="rounded-md border border-zem-border bg-zem-bg px-3 py-3 md:col-span-2 xl:col-span-4"></textarea>
    <button class="rounded-md bg-zem-gold px-4 py-3 font-bold text-white">Add item</button>
</form>

<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
@foreach($items as $menuItem)
    @php($imageUrl = $menuItem->image_path ? (\Illuminate\Support\Str::startsWith($menuItem->image_path, ['http://', 'https://', 'uploads/']) ? (str_starts_with($menuItem->image_path, 'uploads/') ? asset($menuItem->image_path) : $menuItem->image_path) : asset('storage/'.$menuItem->image_path)) : null)
    <article class="overflow-hidden rounded-md border border-zem-border bg-zem-card">
        <div class="relative aspect-square bg-zem-bg">
            @if($imageUrl)
                <img src="{{ $imageUrl }}" alt="{{ $menuItem->name }}" class="h-full w-full object-cover">
            @else
                <div class="grid h-full place-items-center bg-[linear-gradient(135deg,#111,#2a0710)] text-5xl font-extrabold text-white">{{ strtoupper(substr($menuItem->name, 0, 1)) }}</div>
            @endif
            <form method="post" action="{{ route('restaurant.menu-items.availability', $menuItem) }}" class="absolute left-3 top-3">
                @csrf @method('PATCH')
                <button class="rounded-full border px-3 py-1 text-xs font-extrabold backdrop-blur transition {{ $menuItem->is_available ? 'border-green-500/30 bg-green-500/15 text-green-200 hover:bg-green-500/30' : 'border-red-500/30 bg-red-500/15 text-red-200 hover:bg-red-500/30' }}">{{ $menuItem->is_available ? 'Available' : 'Unavailable' }}</button>
            </form>
            <div class="absolute bottom-3 left-3 flex flex-wrap gap-2">
                <form method="post" action="{{ route('restaurant.menu-items.update', $menuItem) }}" enctype="multipart/form-data" class="flex flex-wrap gap-2">
                    @csrf @method('PATCH')
                    <input name="category_id" type="hidden" value="{{ $menuItem->category_id }}">
                    <input name="name" type="hidden" value="{{ $menuItem->name }}">
                    <input name="price" type="hidden" value="{{ $menuItem->price }}">
                    <input name="description" type="hidden" value="{{ $menuItem->description }}">
                    @if($menuItem->is_available)<input name="is_available" type="hidden" value="1">@endif
                    @if($menuItem->is_featured)<input name="is_featured" type="hidden" value="1">@endif
                    <input name="image" type="file" accept="image/*" class="hidden" data-image-crop-input data-auto-submit-on-crop>
                    <input name="cropped_image" type="hidden" data-cropped-image>
                    <button type="button" class="rounded-full bg-black/80 px-3 py-1 text-xs font-extrabold text-white backdrop-blur transition hover:bg-zem-gold" data-photo-edit-button data-current-image="{{ $imageUrl }}">{{ $imageUrl ? 'Edit photo' : 'Add photo' }}</button>
                    @if($imageUrl)
                        <button type="button" class="rounded-full bg-black/80 px-3 py-1 text-xs font-extrabold text-white backdrop-blur transition hover:bg-zem-gold" data-photo-replace-button>Replace</button>
                    @endif
                </form>
                @if($imageUrl)
                    <form method="post" action="{{ route('restaurant.menu-items.remove-photo', $menuItem) }}">
                        @csrf @method('PATCH')
                        <button class="rounded-full bg-black/80 px-3 py-1 text-xs font-extrabold text-red-200 backdrop-blur transition hover:bg-red-700 hover:text-white">Remove</button>
                    </form>
                @endif
            </div>
            <div class="absolute bottom-3 right-3 rounded-full bg-black/80 px-3 py-1 text-sm font-extrabold text-white">{{ number_format($menuItem->price) }} ETB</div>
        </div>
        <div class="p-4">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-bold uppercase tracking-widest text-zem-gold">{{ $menuItem->category?->name }}</p>
                    <h2 class="mt-1 font-display text-xl font-bold">{{ $menuItem->name }}</h2>
                </div>
                @if($menuItem->is_featured)<span class="rounded-full border border-zem-gold/40 px-2 py-1 text-xs font-bold text-zem-gold">Featured</span>@endif
            </div>
            <p class="mt-2 min-h-10 text-sm text-zem-muted">{{ $menuItem->description ?: 'No description yet.' }}</p>

            <details class="mt-4 rounded-md border border-zem-border bg-zem-bg p-3">
                <summary class="cursor-pointer text-sm font-bold text-white">Edit item</summary>
                <form method="post" action="{{ route('restaurant.menu-items.update', $menuItem) }}" enctype="multipart/form-data" class="mt-3 grid gap-3">
                    @csrf @method('PATCH')
                    <select name="category_id" class="rounded-md border border-zem-border bg-zem-card px-3 py-2">@foreach($categories as $category)<option value="{{ $category->id }}" @selected($menuItem->category_id===$category->id)>{{ $category->name }}</option>@endforeach</select>
                    <input name="name" value="{{ $menuItem->name }}" class="rounded-md border border-zem-border bg-zem-card px-3 py-2">
                    <input name="price" value="{{ $menuItem->price }}" type="number" step="0.01" class="rounded-md border border-zem-border bg-zem-card px-3 py-2">
                    <input name="image" type="file" accept="image/*" class="rounded-md border border-zem-border bg-zem-card px-3 py-2 text-sm" data-image-crop-input>
                    <input name="cropped_image" type="hidden" data-cropped-image>
                    <textarea name="description" class="rounded-md border border-zem-border bg-zem-card px-3 py-2">{{ $menuItem->description }}</textarea>
                    <div class="flex flex-wrap gap-4 text-sm">
                        <label class="flex items-center gap-2"><input name="is_available" type="checkbox" value="1" @checked($menuItem->is_available)> Available</label>
                        <label class="flex items-center gap-2"><input name="is_featured" type="checkbox" value="1" @checked($menuItem->is_featured)> Featured</label>
                    </div>
                    <button class="rounded-md bg-zem-gold px-4 py-2 font-bold text-white">Save</button>
                </form>
                <form method="post" action="{{ route('restaurant.menu-items.destroy', $menuItem) }}" class="mt-3">@csrf @method('DELETE')<button class="rounded-md border border-red-500/40 px-4 py-2 text-sm font-bold text-red-200">Delete item</button></form>
            </details>
        </div>
    </article>
@endforeach
</div>
<div class="mt-5">{{ $items->links() }}</div>
<div id="image-crop-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 px-4 py-6">
    <div class="w-full max-w-3xl rounded-xl border border-zem-border bg-zem-card p-4 shadow-2xl">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-display text-xl font-bold">Adjust item photo</h2>
                <p class="mt-1 text-sm text-zem-muted">Drag the image and use zoom to frame the menu item.</p>
            </div>
            <button type="button" class="rounded-md border border-zem-border px-3 py-2 text-sm font-bold text-zem-muted hover:text-white" data-crop-cancel>Cancel</button>
        </div>
        <div class="mt-4 max-h-[62vh] overflow-hidden rounded-lg bg-black">
            <img id="image-crop-target" alt="Crop preview" class="max-h-[62vh] w-full object-contain">
        </div>
        <div class="mt-4 grid gap-3 sm:grid-cols-2 md:grid-cols-[auto_auto_auto_auto]">
            <button type="button" class="rounded-md border border-zem-border px-4 py-2 font-bold text-white hover:border-zem-gold" data-crop-zoom-out>Zoom out</button>
            <button type="button" class="rounded-md border border-zem-border px-4 py-2 font-bold text-white hover:border-zem-gold" data-crop-zoom-in>Zoom in</button>
            <button type="button" class="rounded-md border border-zem-border px-4 py-2 font-bold text-white hover:border-zem-gold" data-crop-reset>Reset</button>
            <button type="button" class="rounded-md bg-zem-gold px-4 py-2 font-bold text-white" data-crop-apply>Use cropped photo</button>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.js"></script>
<script>
    (() => {
        const modal = document.getElementById('image-crop-modal');
        const image = document.getElementById('image-crop-target');
        let cropper = null;
        let activeInput = null;
        let activeHidden = null;
        let activeForm = null;
        let shouldAutoSubmit = false;

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
            if (clearFile && activeInput) {
                activeInput.value = '';
            }
            activeInput = null;
            activeHidden = null;
            activeForm = null;
            shouldAutoSubmit = false;
        }

        document.querySelectorAll('[data-image-crop-input]').forEach((input) => {
            input.addEventListener('change', () => {
                const file = input.files && input.files[0];
                if (! file) return;

                activeInput = input;
                activeForm = input.closest('form');
                activeHidden = activeForm.querySelector('[data-cropped-image]');
                shouldAutoSubmit = input.hasAttribute('data-auto-submit-on-crop');

                const reader = new FileReader();
                reader.onload = () => openCropper(reader.result);
                reader.readAsDataURL(file);
            });
        });

        document.querySelectorAll('[data-photo-edit-button]').forEach((button) => {
            button.addEventListener('click', () => {
                const form = button.closest('form');
                const input = form.querySelector('[data-image-crop-input]');
                const currentImage = button.dataset.currentImage;

                if (! currentImage) {
                    input.click();
                    return;
                }

                activeInput = input;
                activeForm = form;
                activeHidden = form.querySelector('[data-cropped-image]');
                shouldAutoSubmit = true;
                openCropper(currentImage);
            });
        });

        document.querySelectorAll('[data-photo-replace-button]').forEach((button) => {
            button.addEventListener('click', () => {
                const input = button.closest('form').querySelector('[data-image-crop-input]');
                input.click();
            });
        });

        modal.querySelector('[data-crop-zoom-out]').addEventListener('click', () => {
            if (! cropper) return;
            cropper.zoom(-0.1);
        });

        modal.querySelector('[data-crop-zoom-in]').addEventListener('click', () => {
            if (! cropper) return;
            cropper.zoom(0.1);
        });

        modal.querySelector('[data-crop-reset]').addEventListener('click', () => {
            if (! cropper) return;
            cropper.reset();
        });

        modal.querySelector('[data-crop-cancel]').addEventListener('click', () => closeCropper(true));

        modal.querySelector('[data-crop-apply]').addEventListener('click', () => {
            if (! cropper || ! activeHidden) return;
            activeHidden.value = cropper.getCroppedCanvas({
                width: 1200,
                height: 1200,
                imageSmoothingEnabled: true,
                imageSmoothingQuality: 'high',
            }).toDataURL('image/jpeg', 0.88);
            const form = activeForm;
            const shouldSubmit = shouldAutoSubmit;
            closeCropper();
            if (shouldSubmit && form) {
                form.submit();
            }
        });
    })();
</script>
@endsection
