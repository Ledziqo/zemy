@include('restaurant.tables.card-style')
<style>
.qr-studio{margin-bottom:28px;border:1px solid #8884;border-radius:16px;overflow:hidden}
.qr-studio-head{padding:22px;border-bottom:1px solid #8884}.qr-studio-head h2{font-size:22px;font-weight:800;margin:0}.qr-studio-head p{margin:6px 0 0;opacity:.75;font-size:14px}
.qr-studio-body{display:grid;grid-template-columns:minmax(0,1fr) 330px;gap:28px;padding:24px}
.qr-studio fieldset{margin:0 0 22px;padding:0;border:0}.qr-studio legend{font-size:12px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;margin-bottom:12px}
.qr-controls{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.qr-studio label{display:block;font-size:13px}.qr-studio input[type=color]{width:100%;height:38px;cursor:pointer;margin-top:6px;border:1px solid #8885;border-radius:6px;background:transparent}
.qr-studio input[type=range]{width:100%;margin-top:10px;accent-color:#d22630}.qr-studio input[type=text]{display:block;width:100%;margin-top:6px;padding:10px;border:1px solid #8886;border-radius:6px;background:transparent;color:inherit}
.qr-presets{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px}.qr-presets button,.qr-preview select,.qr-reset{padding:8px 12px;border:1px solid #8886;border-radius:7px;background:transparent;color:inherit;cursor:pointer}
.qr-preview{background:#d9d9d6;border-radius:12px;padding:20px 12px;color:#171717;align-self:start;display:flex;flex-direction:column;align-items:center;gap:14px}.qr-preview select{background:white}.qr-preview p:not(.signature-title):not(.signature-kicker):not(.signature-hint){font-size:12px;text-align:center;margin:0}.qr-save{background:#d22630;color:#fff;border:0;border-radius:8px;padding:12px 18px;font-weight:700;cursor:pointer}.qr-notice{font-size:12px;opacity:.75;margin:12px 0}
@media(max-width:1000px){.qr-studio-body{grid-template-columns:1fr}.qr-preview{width:100%}}@media(max-width:480px){.qr-studio-body{padding:12px}.qr-controls{grid-template-columns:1fr}.qr-preview{padding:12px 0;overflow:auto}}
</style>
<section class="qr-studio">
<div class="qr-studio-head"><h2>QR design studio</h2><p>The ZemTab signature collection. Your brand, beautifully presented.</p></div>
@php($designSaveUrl = \Illuminate\Support\Facades\Route::has('restaurant.tables.design') ? route('restaurant.tables.design') : url('/restaurant/tables/qr/design'))
<form method="post" action="{{ $designSaveUrl }}" id="qr-design-form" class="qr-studio-body">
@csrf @method('PATCH')
<div>
@if($errors->any())<p role="alert" class="mb-4 text-red-500">{{ $errors->first() }}</p>@endif
<fieldset><legend>01 / Brand palette</legend>
<div class="qr-presets"><button type="button" data-palette="signature">Signature red</button><button type="button" data-palette="forest">Forest & cream</button><button type="button" data-palette="midnight">Midnight & gold</button><button type="button" data-palette="brand">Venue brand</button></div>
<div class="qr-controls">
@foreach(['background_color'=>'Card background','text_color'=>'Typography','accent_color'=>'Abstract artwork','border_color'=>'Border & fine lines'] as $key=>$label)
<label>{{ $label }}<input type="color" name="{{ $key }}" value="{{ old($key,$sticker[$key]) }}"></label>
@endforeach
</div></fieldset>
<fieldset><legend>02 / Scale & detail</legend><div class="qr-controls">
@foreach(['logo_size'=>['Logo height',18,30,'mm'],'text_size'=>['Headline size',14,22,'pt'],'qr_size'=>['QR size',38,50,'mm'],'detail_size'=>['Small text',6,9,'pt'],'art_opacity'=>['Artwork intensity',10,100,'%']] as $key=>[$label,$min,$max,$unit])
<label>{{ $label }} <output data-value="{{ $key }}"></output><input type="range" name="{{ $key }}" min="{{ $min }}" max="{{ $max }}" step="1" value="{{ old($key,$sticker[$key]) }}" data-unit="{{ $unit }}"></label>
@endforeach
</div></fieldset>
<fieldset><legend>03 / Scan instructions</legend><div class="qr-controls">
<label>Table headline<input type="text" name="table_scan_text" required maxlength="40" value="{{ old('table_scan_text',$sticker['table_scan_text']) }}"></label>
<label>Room headline<input type="text" name="room_scan_text" required maxlength="40" value="{{ old('room_scan_text',$sticker['room_scan_text']) }}"></label>
</div></fieldset>
<p class="qr-notice">12 upright cards per A3 portrait sheet. QR codes stay dark on white for scanning. Long headlines shrink to fit. Save before opening the print pack.</p>
<button class="qr-save">Save QR design</button> <button type="button" class="qr-reset" id="qr-design-reset">Reset to signature</button>
<p id="qr-design-status" class="qr-notice" role="status">Preview of saved settings.</p>
</div>
<aside class="qr-preview">
<select id="qr-preview-type" aria-label="Preview headline"><option value="table">Table card preview</option><option value="room">Room card preview</option></select>
@if($previewQr)
@include('restaurant.tables.card',['qrImage'=>$previewQr,'scanText'=>$sticker['table_scan_text']])
<p>Actual QR for {{ $previewTable->displayLabel() }}.<br>Headline toggle only; the preview link stays the same.</p>
@else
<p>Add your first table or room to preview its QR card. You can save your design now.</p>
@endif
</aside>
</form></section>
<script>
(() => {
 const form=document.getElementById('qr-design-form'),card=form.querySelector('.signature-card'),type=document.getElementById('qr-preview-type'),status=document.getElementById('qr-design-status');
 const palettes={signature:['#FFFFFF','#171717','#D22630','#D6D0CA'],forest:['#FBF8F0','#173F35','#38715C','#B7C3B5'],midnight:['#15232D','#FFF7E7','#B99151','#57636A'],brand:['#FFFFFF','#171717',@json($restaurant->primary_color ?: '#D22630'),'#D6D0CA']};
 const keys=['background_color','text_color','accent_color','border_color'];
 function update(dirty=true){
  const properties={background_color:'--card-bg',text_color:'--card-text',accent_color:'--card-accent',border_color:'--card-border',logo_size:'--logo-size',text_size:'--text-size',qr_size:'--qr-size',detail_size:'--detail-size',art_opacity:'--art-opacity'};
  Object.entries(properties).forEach(([key,property])=>{const input=form.elements.namedItem(key),unit=input.dataset.unit||'',value=key==='art_opacity'?Number(input.value)/100:input.value+unit;if(card)card.style.setProperty(property,value);const output=form.querySelector('[data-value="'+key+'"]');if(output)output.textContent=input.value+unit;});
  if(card){card.querySelector('.signature-title').textContent=form.elements.namedItem(type.value+'_scan_text').value;window.fitSignatureTitles(form);}
  if(dirty)status.textContent='Unsaved preview — save your design to apply it to the print pack.';
 }
 form.addEventListener('input',()=>update());type.addEventListener('change',()=>update(false));
 form.querySelectorAll('[data-palette]').forEach(button=>button.addEventListener('click',()=>{keys.forEach((key,index)=>form.elements.namedItem(key).value=palettes[button.dataset.palette][index]);update();}));
 document.getElementById('qr-design-reset').addEventListener('click',()=>{keys.forEach((key,index)=>form.elements.namedItem(key).value=palettes.signature[index]);Object.entries({logo_size:24,text_size:18,qr_size:46,detail_size:7,art_opacity:100,table_scan_text:'SCAN TO ORDER',room_scan_text:'SCAN FOR ROOM SERVICE'}).forEach(([key,value])=>form.elements.namedItem(key).value=value);update();});
 update(false);
})();
</script>
