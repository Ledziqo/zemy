<section class="qr-page">
@foreach($tables as $table)
@include('restaurant.tables.card', ['qrImage'=>$qrImages[$table->id], 'scanText'=>$table->isRoomServicePoint() ? $sticker['room_scan_text'] : $sticker['table_scan_text'], 'locationLabel'=>$table->displayLabel()])
@endforeach
</section>
