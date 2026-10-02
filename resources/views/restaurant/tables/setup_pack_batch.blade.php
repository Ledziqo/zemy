<section class="qr-page{{ ($sticker['orientation'] ?? 'portrait') === 'landscape' ? ' is-landscape' : '' }}" data-card-type="{{ $type }}" data-card-size="{{ $sticker['card_size'] ?? 'large' }}">
@foreach($tables as $table)
@include('restaurant.tables.card', ['qrImage'=>$qrImages[$table->id], 'scanText'=>$sticker[$type.'_scan_text'], 'locationLabel'=>$table->displayLabel()])
@endforeach
</section>
