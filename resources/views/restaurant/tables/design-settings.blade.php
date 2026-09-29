@include('restaurant.tables.card-style')
@php
    $fallbackLogoPath = $sticker['qr_logo_path'] ?? $restaurant->logo_path;
    $fallbackLogoUrl = $fallbackLogoPath ? (\Illuminate\Support\Str::startsWith($fallbackLogoPath, ['http://', 'https://', 'uploads/']) ? (str_starts_with($fallbackLogoPath, 'uploads/') ? asset($fallbackLogoPath) : $fallbackLogoPath) : asset('storage/'.$fallbackLogoPath)) : null;
    $fallbackRestaurantLogoPath = $restaurant->logo_path;
    $fallbackRestaurantLogoUrl = $fallbackRestaurantLogoPath ? (\Illuminate\Support\Str::startsWith($fallbackRestaurantLogoPath, ['http://', 'https://', 'uploads/']) ? (str_starts_with($fallbackRestaurantLogoPath, 'uploads/') ? asset($fallbackRestaurantLogoPath) : $fallbackRestaurantLogoPath) : asset('storage/'.$fallbackRestaurantLogoPath)) : null;
    if (!isset($qrCardDesigns)) {
        $qrCardDesigns = [];
        foreach (['table' => 'SCAN TO ORDER', 'room' => 'SCAN FOR ROOM SERVICE'] as $type => $text) {
            $variants = [];
            foreach (['portrait', 'landscape'] as $orientation) {
                $variants[$orientation] = array_merge($sticker, ['orientation' => $orientation, 'scan_text' => $sticker[$type.'_scan_text'] ?? $text, 'logo_url' => $fallbackLogoUrl, 'restaurant_logo_url' => $fallbackRestaurantLogoUrl]);
            }
            $qrCardDesigns[$type] = ['preferred_orientation' => $sticker['orientation'] ?? 'portrait', 'orientations' => $variants];
        }
    }
    $previewCards = $previewCards ?? ['table' => ['qr' => $previewQr ?? null, 'label' => $previewTable?->displayLabel() ?? 'Table 1'], 'room' => ['qr' => $previewQr ?? null, 'label' => 'Room 204']];
    $requestedDesignType = old('design_type', $designType ?? 'table');
    $designType = in_array($requestedDesignType, ['table', 'room'], true) ? $requestedDesignType : 'table';
    $designOrientation = old('orientation', $designOrientation ?? $sticker['orientation'] ?? 'portrait');
    $designOrientation = in_array($designOrientation, ['portrait', 'landscape'], true) ? $designOrientation : 'portrait';
    $sticker = $qrCardDesigns[$designType]['orientations'][$designOrientation] ?? $sticker;
@endphp
<style>
.qr-studio{margin-bottom:28px;border:1px solid #8884;border-radius:16px;overflow:hidden}.qr-studio summary{list-style:none;cursor:pointer}.qr-studio summary::-webkit-details-marker{display:none}.qr-studio summary:after{content:'＋';float:right;font-size:24px;font-weight:400;line-height:1}.qr-studio[open] summary:after{content:'−'}
.qr-studio-head{padding:22px;border-bottom:1px solid #8884}.qr-studio-head h2{font-size:22px;font-weight:800;margin:0}.qr-studio-head p{margin:6px 0 0;opacity:.75;font-size:14px}
.qr-studio-body{display:grid;grid-template-columns:minmax(0,1fr) minmax(330px,550px);gap:28px;padding:24px}
.qr-studio fieldset{margin:0 0 22px;padding:0;border:0}.qr-studio legend{font-size:12px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;margin-bottom:12px}
.qr-controls{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.qr-studio label{display:block;font-size:13px}.qr-studio input[type=color]{width:100%;height:38px;cursor:pointer;margin-top:6px;border:1px solid #8885;border-radius:6px;background:transparent}
.qr-studio input[type=range]{width:100%;margin-top:10px;accent-color:#d22630}.qr-studio input[type=text]{display:block;width:100%;margin-top:6px;padding:10px;border:1px solid #8886;border-radius:6px;background:transparent;color:inherit}
.qr-presets{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px}.qr-presets button,.qr-preview select,.qr-reset{padding:8px 12px;border:1px solid #8886;border-radius:7px;background:transparent;color:inherit;cursor:pointer}
.qr-orientation{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.qr-orientation label{display:flex;align-items:center;gap:10px;padding:12px;border:1px solid #8886;border-radius:9px;cursor:pointer}.qr-orientation input{width:auto;accent-color:#d22630}.qr-orientation span{display:grid;gap:3px}.qr-orientation small{font-size:11px;opacity:.7}
.qr-preview{background:#d9d9d6;border-radius:12px;padding:20px 12px;color:#171717;align-self:start;display:flex;flex-direction:column;align-items:center;gap:14px;max-width:100%;overflow-x:auto}.qr-preview.is-landscape{align-items:flex-start}.qr-preview select{background:white}.qr-preview > p{font-size:12px;text-align:center;margin:0}.qr-preview-location{max-width:290px;padding:8px 12px;border:1px solid #17171733;border-radius:7px;background:#fff8;font-weight:600}.qr-save{background:#d22630;color:#fff;border:0;border-radius:8px;padding:12px 18px;font-weight:700;cursor:pointer}.qr-notice{font-size:12px;opacity:.75;margin:12px 0}
@media(max-width:1000px){.qr-studio-body{grid-template-columns:1fr}.qr-preview{width:100%}}@media(max-width:480px){.qr-studio-body{padding:12px}.qr-controls{grid-template-columns:1fr}.qr-preview{padding:12px 0;overflow:auto}}
</style>
<details class="qr-studio">
<summary class="qr-studio-head"><h2>QR design studio</h2><p>The ZemTab signature collection. Your brand, beautifully presented. Open to customize.</p></summary>
@php($designSaveUrl = \Illuminate\Support\Facades\Route::has('restaurant.tables.design') ? route('restaurant.tables.design', [], false) : '/restaurant/tables/qr/design')
<form method="post" action="{{ $designSaveUrl }}" id="qr-design-form" class="qr-studio-body" enctype="multipart/form-data">
@csrf @method('PATCH')
<input type="hidden" name="design_type" id="qr-design-type-value" value="{{ $designType }}">
<div>
@if($errors->any())<p role="alert" class="mb-4 text-red-500">{{ $errors->first() }}</p>@endif
<fieldset><legend>00 / Card orientation</legend>
<div class="qr-orientation" role="radiogroup" aria-label="Card orientation">
<label><input type="radio" name="orientation" value="portrait" @checked($designOrientation === 'portrait')><span><strong>Portrait</strong><small>75 × 140 mm · 4 across × 2 down</small></span></label>
<label><input type="radio" name="orientation" value="landscape" @checked($designOrientation === 'landscape')><span><strong>Landscape</strong><small>140 × 75 mm · 2 across × 4 down</small></span></label>
</div><p class="qr-notice">Each card type has separate portrait and landscape designs. Switching orientation loads that layout’s own settings; changing table designs won’t affect room cards.</p></fieldset>
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
<label>Headline for this card type<input type="text" name="scan_text" required maxlength="40" value="{{ old('scan_text',$sticker['scan_text'] ?? $sticker[$designType.'_scan_text'] ?? '') }}"></label>
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
<button class="qr-save" id="qr-design-save">Save {{ $designOrientation }} {{ $designType }} card design</button> <button type="button" class="qr-reset" id="qr-design-reset">Reset this layout</button>
<p id="qr-design-status" class="qr-notice" role="status">Preview of saved settings.</p>
</div>
<aside class="qr-preview">
<div class="qr-editor-controls">
<label>Selected element<select id="qr-layer"></select></label>
<div class="qr-controls">
<label>Width %<input id="qr-layer-width" type="number" min="2" max="2000" step="1"></label>
<label>Height %<input id="qr-layer-height" type="number" min="2" max="2000" step="1"></label>
</div>
<button type="button" id="qr-layer-reset" class="qr-reset">Reset selected element</button>
<p>Drag the selection to move. Drag its right, bottom, or corner handle to resize. Use the list to reach overlapping elements.</p>
</div>
@foreach(['logo','cross','line_left','line_right','kicker_text','title','location','frame','hint','footer','art'] as $element)
@foreach(['x'=>0,'y'=>0,'sx'=>1,'sy'=>1] as $dimension=>$default)
<input type="hidden" name="elements[{{ $element }}][{{ $dimension }}]" value="{{ old('elements.'.$element.'.'.$dimension,$sticker['elements'][$element][$dimension] ?? $default) }}">
@endforeach
@endforeach
<select id="qr-preview-type" aria-label="Choose card type to edit"><option value="table" @selected($designType === 'table')>Table card design & preview</option><option value="room" @selected($designType === 'room')>Room card design & preview</option></select>
@if($previewCards[$designType]['qr'])
@include('restaurant.tables.card',['sticker'=>$sticker,'qrImage'=>$previewCards[$designType]['qr'],'scanText'=>$sticker['scan_text'] ?? $sticker[$designType.'_scan_text'],'locationLabel'=>$previewCards[$designType]['label']])
<p class="qr-preview-location"><strong>Preview label:</strong> <span id="qr-preview-label">{{ $previewCards[$designType]['label'] }}</span></p>
@else
<p>Add your first table or room to preview its QR card. You can save your design now.</p>
@endif
</aside>
</form></details>
<script src="{{ asset('assets/qr-editor.js') }}?v=5"></script>
<script>
(() => {
 const form=document.getElementById('qr-design-form'),card=form.querySelector('.signature-card'),preview=form.querySelector('.qr-preview'),type=document.getElementById('qr-preview-type'),typeValue=document.getElementById('qr-design-type-value'),status=document.getElementById('qr-design-status'),logoInput=form.elements.namedItem('qr_logo'),logoWrap=card?.querySelector('.signature-logo-wrap'),logoSizeInput=form.elements.namedItem('logo_size'),logoWidthInput=form.elements.namedItem('logo_width');
 const designs=@json($qrCardDesigns),previewCards=@json($previewCards),fileDrafts={table:{portrait:null,landscape:null},room:{portrait:null,landscape:null}},dirty=new Set();
 let activeOrientation=field('orientation').value;
 let logo=card?.querySelector('.signature-logo');
 const palettes={signature:['#FFFFFF','#171717','#D22630','#D6D0CA'],forest:['#FBF8F0','#173F35','#38715C','#B7C3B5'],midnight:['#15232D','#FFF7E7','#B99151','#57636A'],brand:['#FFFFFF','#171717',@json($restaurant->primary_color ?: '#D22630'),'#D6D0CA']};
 const keys=['background_color','text_color','accent_color','border_color'];
 const scalarKeys=['orientation',...keys,'logo_size','logo_width','logo_x','logo_y','heading_x','heading_y','kicker_x','kicker_y','title_x','title_y','location_x','location_y','scan_x','scan_y','frame_x','frame_y','hint_x','hint_y','footer_x','footer_y','text_size','qr_size','detail_size','footer_size','art_opacity','scan_text'];
 function field(name){return form.elements.namedItem(name)}
 function activeDesign(){return designs[type.value].orientations[activeOrientation]}
 function capture(){const state={};scalarKeys.forEach(key=>{if(key==='orientation')state[key]=activeOrientation;else state[key]=field(key).value});state.elements={};['logo','cross','line_left','line_right','kicker_text','title','location','frame','hint','footer','credit','zemtab','art'].forEach(name=>{state.elements[name]={};['x','y','sx','sy'].forEach(d=>{const input=field(`elements[${name}][${d}]`);if(input)state.elements[name][d]=input.value})});state.qr_logo_path=activeDesign().qr_logo_path;state.logo_url=activeDesign().logo_url;state.restaurant_logo_url=activeDesign().restaurant_logo_url;state.remove_qr_logo=field('remove_qr_logo').checked;return state}
 function restoreFileInput(value){if(!logoInput)return;const transfer=new DataTransfer();if(value)transfer.items.add(value);logoInput.files=transfer.files}
 function applyDesign(state){scalarKeys.forEach(key=>{const input=field(key);if(!input)return;if(key==='orientation')input.value=state[key];else input.value=state[key]??''});Object.entries(state.elements||{}).forEach(([name,dimensions])=>Object.entries(dimensions).forEach(([dimension,value])=>{const input=field(`elements[${name}][${dimension}]`);if(input)input.value=value}));field('remove_qr_logo').checked=!!state.remove_qr_logo;restoreFileInput(fileDrafts[type.value][activeOrientation]);update(false);form.refreshQrEditor?.()}
 function setLogo(src){if(!card)return;if(src){if(!logo){logo=document.createElement('img');logo.className='signature-logo';logo.dataset.layer='logo';logo.alt='QR logo';logo.setAttribute('data-print-resource','');logoWrap.appendChild(logo)}logo.src=src;logo.hidden=false}else if(logo){logo.remove();logo=null}}
 function update(markDirty=true){
  const properties={background_color:'--card-bg',text_color:'--card-text',accent_color:'--card-accent',border_color:'--card-border',logo_size:'--logo-size',logo_width:'--logo-width',logo_x:'--logo-x',logo_y:'--logo-y',heading_x:'--heading-x',heading_y:'--heading-y',kicker_x:'--kicker-x',kicker_y:'--kicker-y',title_x:'--title-x',title_y:'--title-y',location_x:'--location-x',location_y:'--location-y',scan_x:'--scan-x',scan_y:'--scan-y',frame_x:'--frame-x',frame_y:'--frame-y',hint_x:'--hint-x',hint_y:'--hint-y',footer_x:'--footer-x',footer_y:'--footer-y',text_size:'--text-size',qr_size:'--qr-size',detail_size:'--detail-size',footer_size:'--footer-scale',art_opacity:'--art-opacity'};
  Object.entries(properties).forEach(([key,property])=>{const input=form.elements.namedItem(key),unit=input.dataset.unit||(/_[xy]$/.test(key)?'mm':''),value=['art_opacity','footer_size'].includes(key)?Number(input.value)/100:input.value+unit;if(card)card.style.setProperty(property,value);const output=form.querySelector('[data-value="'+key+'"]');if(output)output.textContent=input.value+unit;});
  if(card){card.style.setProperty('--logo-half-width',(Number(logoWidthInput.value)/2)+'mm');card.style.setProperty('--logo-half-height',(Number(logoSizeInput.value)/2)+'mm');}
  const orientation=field('orientation').value;
  card?.classList.toggle('is-landscape',orientation==='landscape');
  preview?.classList.toggle('is-landscape',orientation==='landscape');
  const artwork=card?.querySelector('.signature-art');
  if(artwork){artwork.setAttribute('viewBox',orientation==='landscape'?'0 0 560 297':'0 0 297 560');card.querySelector('.signature-artwork-landscape')?.style.setProperty('display',orientation==='landscape'?'':'none');card.querySelector('.signature-artwork-portrait')?.style.setProperty('display',orientation==='portrait'?'':'none');}
  if(card){card.querySelector('.signature-title').textContent=field('scan_text').value;card.querySelector('.signature-location').textContent=previewCards[type.value].label;card.querySelector('.signature-frame img').src=previewCards[type.value].qr;const label=document.getElementById('qr-preview-label');if(label)label.textContent=previewCards[type.value].label;const uploaded=fileDrafts[type.value][activeOrientation];setLogo(uploaded?URL.createObjectURL(uploaded):(field('remove_qr_logo').checked?activeDesign().restaurant_logo_url:activeDesign().logo_url));window.fitSignatureTitles(form);}
  if(markDirty){designs[type.value].orientations[activeOrientation]=capture();dirty.add(`${type.value}:${activeOrientation}`);status.textContent=`Unsaved ${activeOrientation} ${type.value} design — save to apply this exact layout to the print pack.`;}
 }
 type.addEventListener('change',()=>{const previous=typeValue.value;designs[previous].orientations[activeOrientation]=capture();if(logoInput?.files?.[0])fileDrafts[previous][activeOrientation]=logoInput.files[0];typeValue.value=type.value;applyDesign(designs[type.value].orientations[activeOrientation]);document.getElementById('qr-design-save').textContent=`Save ${activeOrientation} ${type.value} card design`;status.textContent=`Editing ${activeOrientation} ${type.value} cards.`});
 form.querySelectorAll('input[name="orientation"]').forEach(input=>input.addEventListener('change',()=>{const next=input.value;if(next===activeOrientation)return;designs[type.value].orientations[activeOrientation]=capture();if(logoInput?.files?.[0])fileDrafts[type.value][activeOrientation]=logoInput.files[0];activeOrientation=next;applyDesign(designs[type.value].orientations[activeOrientation]);document.getElementById('qr-design-save').textContent=`Save ${activeOrientation} ${type.value} card design`;status.textContent=`Editing the independent ${activeOrientation} ${type.value} layout.`}));
 logoInput?.addEventListener('change',()=>{fileDrafts[type.value][activeOrientation]=logoInput.files?.[0]||null;update();});
 form.elements.namedItem('remove_qr_logo').addEventListener('change',()=>update());
 form.addEventListener('submit',event=>{const current=`${type.value}:${activeOrientation}`,others=[...dirty].filter(key=>key!==current);if(others.length&&!confirm(`Only the ${activeOrientation} ${type.value} layout will be saved. Other unsaved card layouts will be discarded. Continue?`)){event.preventDefault();return}dirty.delete(current)});
 form.querySelectorAll('[data-palette]').forEach(button=>button.addEventListener('click',()=>{keys.forEach((key,index)=>field(key).value=palettes[button.dataset.palette][index]);update()}));
 document.getElementById('qr-design-reset').addEventListener('click',()=>{keys.forEach((key,index)=>field(key).value=palettes.signature[index]);Object.entries({orientation:activeOrientation,logo_size:24,logo_width:53,logo_x:37,logo_y:18,heading_x:0,heading_y:0,kicker_x:0,kicker_y:0,title_x:0,title_y:0,location_x:0,location_y:0,scan_x:0,scan_y:-3,frame_x:0,frame_y:0,hint_x:0,hint_y:0,footer_x:0,footer_y:0,text_size:18,qr_size:46,detail_size:7,footer_size:100,art_opacity:100,scan_text:type.value==='table'?'SCAN TO ORDER':'SCAN FOR ROOM SERVICE'}).forEach(([key,value])=>field(key).value=value);fileDrafts[type.value][activeOrientation]=null;restoreFileInput(null);field('remove_qr_logo').checked=false;logoWrap?.classList.remove('is-selected');update()});
 form.addEventListener('input',event=>{if(event.target!==type&&event.target.name!=='orientation')update()});
 card?.addEventListener('pointerup',()=>update());
 update(false);
 window.initQrEditor(form, card);
})();
</script>
