<section class="qr-page{{ ($sticker['orientation'] ?? 'portrait') === 'landscape' ? ' is-landscape' : '' }}">
@foreach($tables as $table)
@include('restaurant.tables.card', ['qrImage'=>$qrImages[$table->id], 'scanText'=>$table->isRoomServicePoint() ? $sticker['room_scan_text'] : $sticker['table_scan_text'], 'locationLabel'=>$table->displayLabel()])
@endforeach
</section>
