@include('restaurant.tables.card-style')
<style>
.qr-studio{margin-bottom:28px;border:1px solid #8884;border-radius:16px;overflow:hidden}.qr-studio summary{list-style:none;cursor:pointer}.qr-studio summary::-webkit-details-marker{display:none}.qr-studio summary:after{content:'＋';float:right;font-size:24px;font-weight:400;line-height:1}.qr-studio[open] summary:after{content:'−'}
.qr-studio-head{padding:22px;border-bottom:1px solid #8884}.qr-studio-head h2{font-size:22px;font-weight:800;margin:0}.qr-studio-head p{margin:6px 0 0;opacity:.75;font-size:14px}
.qr-studio-body{display:grid;grid-template-columns:minmax(0,1fr) 330px;gap:28px;padding:24px}
.qr-studio fieldset{margin:0 0 22px;padding:0;border:0}.qr-studio legend{font-size:12px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;margin-bottom:12px}
.qr-controls{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.qr-studio label{display:block;font-size:13px}.qr-studio input[type=color]{width:100%;height:38px;cursor:pointer;margin-top:6px;border:1px solid #8885;border-radius:6px;background:transparent}
.qr-studio input[type=range]{width:100%;margin-top:10px;accent-color:#d22630}.qr-studio input[type=text]{display:block;width:100%;margin-top:6px;padding:10px;border:1px solid #8886;border-radius:6px;background:transparent;color:inherit}
.qr-presets{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px}.qr-presets button,.qr-preview select,.qr-reset{padding:8px 12px;border:1px solid #8886;border-radius:7px;background:transparent;color:inherit;cursor:pointer}
.qr-preview{background:#d9d9d6;border-radius:12px;padding:20px 12px;color:#171717;align-self:start;display:flex;flex-direction:column;align-items:center;gap:14px}.qr-preview select{background:white}.qr-preview p:not(.signature-title):not(.signature-kicker):not(.signature-hint){font-size:12px;text-align:center;margin:0}.qr-preview-location{max-width:290px;padding:8px 12px;border:1px solid #17171733;border-radius:7px;background:#fff8;font-weight:600}.qr-save{background:#d22630;color:#fff;border:0;border-radius:8px;padding:12px 18px;font-weight:700;cursor:pointer}.qr-notice{font-size:12px;opacity:.75;margin:12px 0}
@media(max-width:1000px){.qr-studio-body{grid-template-columns:1fr}.qr-preview{width:100%}}@media(max-width:480px){.qr-studio-body{padding:12px}.qr-controls{grid-template-columns:1fr}.qr-preview{padding:12px 0;overflow:auto}}
</style>
<details class="qr-studio">
<summary class="qr-studio-head"><h2>QR design studio</h2><p>The ZemTab signature collection. Your brand, beautifully presented. Open to customize.</p></summary>
@php($designSaveUrl = \Illuminate\Support\Facades\Route::has('restaurant.tables.design') ? route('restaurant.tables.design') : url('/restaurant/tables/qr/design'))
<form method="post" action="{{ $designSaveUrl }}" id="qr-design-form" class="qr-studio-body" enctype="multipart/form-data">
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
@foreach(['logo_size'=>['Logo height',1,200,'mm'],'logo_width'=>['Logo width',1,200,'mm'],'text_size'=>['Headline size',14,22,'pt'],'qr_size'=>['QR size',38,50,'mm'],'detail_size'=>['Small text',6,9,'pt'],'footer_size'=>['Powered-by size',50,200,'%'],'art_opacity'=>['Artwork intensity',10,100,'%']] as $key=>[$label,$min,$max,$unit])
<label>{{ $label }} <output data-value="{{ $key }}"></output><input type="range" name="{{ $key }}" min="{{ $min }}" max="{{ $max }}" step="1" value="{{ old($key,$sticker[$key]) }}" data-unit="{{ $unit }}"></label>
@endforeach
</div></fieldset>
<fieldset><legend>03 / Scan instructions</legend><div class="qr-controls">
<label>Table headline<input type="text" name="table_scan_text" required maxlength="40" value="{{ old('table_scan_text',$sticker['table_scan_text']) }}"></label>
<label>Room headline<input type="text" name="room_scan_text" required maxlength="40" value="{{ old('room_scan_text',$sticker['room_scan_text']) }}"></label>
</div></fieldset>
<fieldset><legend>04 / QR logo</legend>
<label class="qr-logo-upload">Insert a logo for the QR cards<input type="file" name="qr_logo" accept="image/png,image/jpeg,image/webp,image/svg+xml" class="mt-2 block w-full rounded-md border border-zem-border bg-zem-bg px-3 py-2"></label>
<label class="mt-3 flex items-center gap-2 text-sm"><input type="checkbox" name="remove_qr_logo" value="1"> Remove the custom QR logo and use the restaurant logo again</label>
<p class="qr-notice">The uploaded logo replaces the current logo on printed QR cards only. It does not change the restaurant’s main logo. PNG, JPG, WEBP, or SVG up to 4 MB.</p>
</fieldset>
@foreach(['logo_x'=>37,'logo_y'=>18,'heading_x'=>0,'heading_y'=>0,'kicker_x'=>0,'kicker_y'=>0,'title_x'=>0,'title_y'=>0,'location_x'=>0,'location_y'=>0,'scan_x'=>0,'scan_y'=>-3,'frame_x'=>0,'frame_y'=>0,'hint_x'=>0,'hint_y'=>0,'footer_x'=>0,'footer_y'=>0] as $key=>$default)
<input type="hidden" name="{{ $key }}" value="{{ old($key,$sticker[$key] ?? $default) }}">
@endforeach
<p class="qr-notice">Drag the logo, headline, QR block, or powered-by footer anywhere on the preview. Resize the logo, QR, text, and powered-by block with the controls. Dragged elements can overlap without moving anything else. Save before opening the print pack.</p>
<button class="qr-save">Save QR design</button> <button type="button" class="qr-reset" id="qr-design-reset">Reset to signature</button>
<p id="qr-design-status" class="qr-notice" role="status">Preview of saved settings.</p>
</div>
<aside class="qr-preview">
<select id="qr-preview-type" aria-label="Preview headline"><option value="table">Table card preview</option><option value="room">Room card preview</option></select>
@if($previewQr)
@include('restaurant.tables.card',['qrImage'=>$previewQr,'scanText'=>$sticker['table_scan_text'],'locationLabel'=>$previewTable?->displayLabel()])
<p class="qr-preview-location"><strong>Label on this preview:</strong> {{ $previewTable->displayLabel() }}<br>Headline toggle only; the preview link stays the same.</p>
@else
<p>Add your first table or room to preview its QR card. You can save your design now.</p>
@endif
</aside>
</form></details>
<script>
(() => {
 const form=document.getElementById('qr-design-form'),card=form.querySelector('.signature-card'),type=document.getElementById('qr-preview-type'),status=document.getElementById('qr-design-status'),logoInput=form.elements.namedItem('qr_logo'),logoWrap=card?.querySelector('.signature-logo-wrap'),resizeHandles=card?.querySelectorAll('.logo-resize-handle'),logoSizeInput=form.elements.namedItem('logo_size'),logoWidthInput=form.elements.namedItem('logo_width'),logoXInput=form.elements.namedItem('logo_x'),logoYInput=form.elements.namedItem('logo_y');
 let logo=card?.querySelector('.signature-logo');
 const draggableElements=[
  {element:card?.querySelector('.signature-kicker'),x:form.elements.namedItem('kicker_x'),y:form.elements.namedItem('kicker_y'),label:'kicker'},
  {element:card?.querySelector('.signature-title'),x:form.elements.namedItem('title_x'),y:form.elements.namedItem('title_y'),label:'headline text'},
  {element:card?.querySelector('.signature-location'),x:form.elements.namedItem('location_x'),y:form.elements.namedItem('location_y'),label:'table or room label'},
  {element:card?.querySelector('.signature-frame'),x:form.elements.namedItem('frame_x'),y:form.elements.namedItem('frame_y'),label:'QR code'},
  {element:card?.querySelector('.signature-hint'),x:form.elements.namedItem('hint_x'),y:form.elements.namedItem('hint_y'),label:'scan hint'},
  {element:card?.querySelector('.signature-heading'),x:form.elements.namedItem('heading_x'),y:form.elements.namedItem('heading_y'),label:'headline'},
  {element:card?.querySelector('.signature-scan'),x:form.elements.namedItem('scan_x'),y:form.elements.namedItem('scan_y'),label:'QR block'},
  {element:card?.querySelector('.signature-footer'),x:form.elements.namedItem('footer_x'),y:form.elements.namedItem('footer_y'),label:'powered-by footer'},
 ];
 const palettes={signature:['#FFFFFF','#171717','#D22630','#D6D0CA'],forest:['#FBF8F0','#173F35','#38715C','#B7C3B5'],midnight:['#15232D','#FFF7E7','#B99151','#57636A'],brand:['#FFFFFF','#171717',@json($restaurant->primary_color ?: '#D22630'),'#D6D0CA']};
 const keys=['background_color','text_color','accent_color','border_color'];
 function update(dirty=true){
  const properties={background_color:'--card-bg',text_color:'--card-text',accent_color:'--card-accent',border_color:'--card-border',logo_size:'--logo-size',logo_width:'--logo-width',logo_x:'--logo-x',logo_y:'--logo-y',heading_x:'--heading-x',heading_y:'--heading-y',kicker_x:'--kicker-x',kicker_y:'--kicker-y',title_x:'--title-x',title_y:'--title-y',location_x:'--location-x',location_y:'--location-y',scan_x:'--scan-x',scan_y:'--scan-y',frame_x:'--frame-x',frame_y:'--frame-y',hint_x:'--hint-x',hint_y:'--hint-y',footer_x:'--footer-x',footer_y:'--footer-y',text_size:'--text-size',qr_size:'--qr-size',detail_size:'--detail-size',footer_size:'--footer-scale',art_opacity:'--art-opacity'};
  Object.entries(properties).forEach(([key,property])=>{const input=form.elements.namedItem(key),unit=input.dataset.unit||'',value=['art_opacity','footer_size'].includes(key)?Number(input.value)/100:input.value+unit;if(card)card.style.setProperty(property,value);const output=form.querySelector('[data-value="'+key+'"]');if(output)output.textContent=input.value+unit;});
  if(card){card.style.setProperty('--logo-half-width',(Number(logoWidthInput.value)/2)+'mm');card.style.setProperty('--logo-half-height',(Number(logoSizeInput.value)/2)+'mm');}
  if(card){card.querySelector('.signature-title').textContent=form.elements.namedItem(type.value+'_scan_text').value;window.fitSignatureTitles(form);}
  if(dirty)status.textContent='Unsaved preview — save your design to apply it to the print pack.';
 }
 form.addEventListener('input',()=>update());type.addEventListener('change',()=>update(false));
 logoWrap?.addEventListener('click',event=>{if(!event.target.closest('.logo-resize-handle'))logoWrap.classList.toggle('is-selected');});
 let resizeState=null;
 resizeHandles?.forEach(handle=>{
  handle.addEventListener('pointerdown',event=>{
   event.preventDefault();event.stopPropagation();
   resizeState={axis:handle.dataset.resize,startX:event.clientX,startY:event.clientY,startWidth:Number(logoWidthInput.value),startHeight:Number(logoSizeInput.value),pixelsPerMillimetre:card.getBoundingClientRect().height/140};
   handle.setPointerCapture(event.pointerId);logoWrap.classList.add('is-selected');
  });
  handle.addEventListener('pointermove',event=>{
   if(!resizeState)return;
   const dx=(event.clientX-resizeState.startX)/resizeState.pixelsPerMillimetre;
   const dy=(event.clientY-resizeState.startY)/resizeState.pixelsPerMillimetre;
   if(resizeState.axis==='width'||resizeState.axis==='both')logoWidthInput.value=Math.round(Math.max(1,Math.min(200,resizeState.startWidth+dx)));
   if(resizeState.axis==='height'||resizeState.axis==='both')logoSizeInput.value=Math.round(Math.max(1,Math.min(200,resizeState.startHeight+dy)));
   update();
  });
  handle.addEventListener('pointerup',()=>{resizeState=null;status.textContent='Logo size adjusted — save your design to apply it to the print pack.';});
  handle.addEventListener('pointercancel',()=>{resizeState=null;});
 });
 draggableElements.forEach(({element,x,y,label})=>{
  if(!element||!x||!y)return;
  element.addEventListener('pointerdown',event=>{
   if(event.target.closest('.signature-logo,.logo-resize-handle'))return;
   event.preventDefault();event.stopPropagation();element.setPointerCapture(event.pointerId);
   const rect=card.getBoundingClientRect();
   resizeState={axis:'move-element',element,x,y,label,startX:event.clientX,startY:event.clientY,startElementX:Number(x.value),startElementY:Number(y.value),pixelsPerMillimetre:rect.width/74.25};
  });
  element.addEventListener('pointermove',event=>{
   if(!resizeState||resizeState.axis!=='move-element'||resizeState.element!==element)return;
   const dx=(event.clientX-resizeState.startX)/resizeState.pixelsPerMillimetre;
   const dy=(event.clientY-resizeState.startY)/resizeState.pixelsPerMillimetre;
   x.value=Math.round(Math.max(-300,Math.min(400,resizeState.startElementX+dx)));
   y.value=Math.round(Math.max(-300,Math.min(400,resizeState.startElementY+dy)));
   update();
  });
  element.addEventListener('pointerup',()=>{if(resizeState?.axis==='move-element'&&resizeState.element===element){resizeState=null;status.textContent=label.charAt(0).toUpperCase()+label.slice(1)+' moved — save your design to apply it to the print pack.';}});
  element.addEventListener('pointercancel',()=>{if(resizeState?.axis==='move-element'&&resizeState.element===element)resizeState=null;});
 });
 logo?.addEventListener('pointerdown',event=>{
  event.preventDefault();event.stopPropagation();
  logo.setPointerCapture(event.pointerId);
  const rect=card.getBoundingClientRect();
  resizeState={axis:'move',startX:event.clientX,startY:event.clientY,startLogoX:Number(logoXInput.value),startLogoY:Number(logoYInput.value),pixelsPerMillimetre:rect.width/74.25,moved:false};
  logoWrap.classList.add('is-selected');
 });
 logo?.addEventListener('pointermove',event=>{
  if(!resizeState||resizeState.axis!=='move')return;
  const dx=(event.clientX-resizeState.startX)/resizeState.pixelsPerMillimetre;
  const dy=(event.clientY-resizeState.startY)/resizeState.pixelsPerMillimetre;
  resizeState.moved=Math.abs(dx)>1||Math.abs(dy)>1;
  logoXInput.value=Math.round(Math.max(-200,Math.min(300,resizeState.startLogoX+dx)));
  logoYInput.value=Math.round(Math.max(-200,Math.min(400,resizeState.startLogoY+dy)));
  update();
 });
 logo?.addEventListener('pointerup',()=>{if(resizeState?.axis==='move'){resizeState=null;status.textContent='Logo position adjusted — save your design to apply it to the print pack.';}});
 logo?.addEventListener('pointercancel',()=>{if(resizeState?.axis==='move')resizeState=null;});
 logoInput?.addEventListener('change',()=>{const file=logoInput.files?.[0];if(!file||!card)return;const reader=new FileReader();reader.onload=event=>{if(!logo){logo=document.createElement('img');logo.className='signature-logo';logo.alt='QR logo';card.querySelector('.signature-logo-wrap').appendChild(logo);}logo.src=event.target.result;status.textContent='Logo preview updated — drag it to position it, then save your design.';};reader.readAsDataURL(file);});
 form.querySelectorAll('[data-palette]').forEach(button=>button.addEventListener('click',()=>{keys.forEach((key,index)=>form.elements.namedItem(key).value=palettes[button.dataset.palette][index]);update();}));
 document.getElementById('qr-design-reset').addEventListener('click',()=>{keys.forEach((key,index)=>form.elements.namedItem(key).value=palettes.signature[index]);Object.entries({logo_size:24,logo_width:53,logo_x:37,logo_y:18,heading_x:0,heading_y:0,kicker_x:0,kicker_y:0,title_x:0,title_y:0,location_x:0,location_y:0,scan_x:0,scan_y:-3,frame_x:0,frame_y:0,hint_x:0,hint_y:0,footer_x:0,footer_y:0,text_size:18,qr_size:46,detail_size:7,footer_size:100,art_opacity:100,table_scan_text:'SCAN TO ORDER',room_scan_text:'SCAN FOR ROOM SERVICE'}).forEach(([key,value])=>form.elements.namedItem(key).value=value);logoWrap?.classList.remove('is-selected');update();});
 update(false);
})();
</script>
