<div style="margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;">
    <div><h2 style="font-size:20px;font-weight:700;">Print your QR cards</h2><p style="font-size:13px;opacity:.7;margin-top:5px;">Choose the cards, orientation and size for this printout.</p></div>
    <button type="button" id="open-shared-print" class="qr-save" style="background:#d22630;color:white;padding:12px 20px;border:0;border-radius:8px;font-weight:700;cursor:pointer;">Print QR cards</button>
</div>
<style>
.shared-print-dialog{width:min(92vw,560px);max-height:90vh;overflow:auto;border:1px solid #8885;border-radius:16px;padding:24px;background:#191b22;color:#f4f1ec}.shared-print-dialog::backdrop{background:#000a}.shared-print-dialog h2{font-size:22px;font-weight:700}.shared-print-dialog label{display:grid;gap:7px;font-size:13px;margin-top:16px}.shared-print-dialog select{background:#101116;color:#fff;border:1px solid #8886;border-radius:8px;padding:11px;width:100%}.shared-print-dialog fieldset{border:1px solid #8885;border-radius:10px;padding:0 16px 16px;margin-top:20px}.shared-print-dialog legend{padding:0 6px;font-weight:700}.shared-print-dialog p{font-size:12px;opacity:.75;margin-top:12px}.shared-print-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:24px}.shared-print-actions button{border:1px solid #8886;border-radius:8px;padding:11px 16px;background:transparent;color:inherit;cursor:pointer}.shared-print-actions button[type=submit]{background:#d22630;border-color:#d22630;color:white;font-weight:700}.shared-print-dialog [hidden]{display:none!important}
</style>
<dialog class="shared-print-dialog" id="shared-print-dialog" aria-labelledby="shared-print-title">
<form method="get" action="{{ route('restaurant.tables.index') }}" target="_blank" id="shared-print-form">
<h2 id="shared-print-title">Print QR cards</h2>
<input type="hidden" name="print" value="menu-card">
@if(!$restaurant->isMenuOnly())<input type="hidden" name="card" value="information">@endif
<p>Select the cards to include. Each has its own layout and size.</p>
<input type="hidden" name="print_card" id="shared-print-card" value="menu">
@foreach(['menu' => 'Menu card', 'information' => 'Information card'] as $purpose => $label)
@if(($purpose === 'menu' && $restaurant->isMenuOnly()) || ($purpose === 'information' && $restaurant->informationBoardEnabled()))
@php($type = $purpose === 'information' ? 'information' : 'table')
@php($savedOrientation = $qrCardDesigns[$type]['preferred_orientation'])
<fieldset data-print-purpose="{{ $purpose }}"><legend><label style="display:flex;align-items:center;gap:8px;margin:0;"><input type="checkbox" data-include-card value="{{ $purpose }}" checked> {{ $label }}</label></legend>
<div style="width:228px;height:228px;overflow:hidden;background:#fff;border-radius:8px;margin:12px auto;"><iframe data-card-preview title="{{ $label }} print preview" style="width:600px;height:600px;border:0;transform:scale(.38);transform-origin:top left;pointer-events:none;" tabindex="-1"></iframe></div>
<label>Orientation<select name="{{ $purpose }}_orientation" data-print-orientation><option value="portrait" @selected($savedOrientation === 'portrait')>Portrait</option><option value="landscape" @selected($savedOrientation === 'landscape')>Landscape</option></select></label>
<label>Card size<select name="{{ $purpose }}_size" data-print-size>
@foreach(['small'=>'Small','medium'=>'Medium','large'=>'Large'] as $size=>$sizeLabel)<option value="{{ $size }}" @selected(($qrCardDesigns[$type]['orientations'][$savedOrientation]['card_size'] ?? 'large') === $size)>{{ $sizeLabel }}</option>@endforeach
</select></label><p data-print-dimensions></p>
</fieldset>
@endif
@endforeach
<p>Uses your saved design for each orientation. These choices apply to this printout. Choose “Save as PDF” in the print window to get the file.</p>
<div class="shared-print-actions"><button type="button" id="close-shared-print">Cancel</button><button type="submit">Print / Save PDF</button></div>
</form></dialog>
<script>
(()=>{const dialog=document.getElementById('shared-print-dialog'),form=document.getElementById('shared-print-form'),choice=document.getElementById('shared-print-card');
const endpoint=@json(route('restaurant.tables.index'));
function update(){
 const checked=[...form.querySelectorAll('[data-include-card]:checked')];choice.value=checked.length===2?'both':(checked[0]?.value||'menu');form.querySelector('button[type="submit"]').disabled=checked.length===0;
 form.querySelectorAll('[data-print-purpose]').forEach(group=>{
  const included=group.querySelector('[data-include-card]').checked;group.querySelectorAll('select').forEach(select=>select.disabled=!included);
  const purpose=group.dataset.printPurpose,size=group.querySelector('[data-print-size]').value,orientation=group.querySelector('[data-print-orientation]').value,landscape=orientation==='landscape';const dims={small:[50,93.3],medium:[62.5,116.7],large:[75,140]}[size];group.querySelector('[data-print-dimensions]').textContent=(landscape?[dims[1],dims[0]]:dims).join(' × ')+' mm';
  const url=new URL(endpoint);url.searchParams.set('print','menu-card');url.searchParams.set('print_card',purpose);url.searchParams.set('preview','1');if(purpose==='information')url.searchParams.set('card','information');url.searchParams.set(purpose+'_orientation',orientation);url.searchParams.set(purpose+'_size',size);const frame=group.querySelector('[data-card-preview]');if(dialog.open&&frame.getAttribute('src')!==url.href)frame.src=url.href;
 });
}
document.getElementById('open-shared-print').addEventListener('click',()=>{dialog.showModal();update();});document.getElementById('close-shared-print').addEventListener('click',()=>dialog.close());form.addEventListener('change',update);form.addEventListener('submit',()=>dialog.close());update();})();
</script>
