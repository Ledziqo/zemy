@extends('layouts.dashboard', ['heading' => __('Information board')])
@section('content')
<style>
.board-top{display:flex;gap:12px;justify-content:space-between;align-items:center;flex-wrap:wrap;margin-bottom:24px}.board-top h2{font-size:22px;font-weight:700}.board-top p{font-size:14px;color:#9299a6;margin-top:6px}.board-actions{display:flex;gap:10px;flex-wrap:wrap}.board-button{border:1px solid #8885;background:transparent;padding:10px 14px;border-radius:8px;font-weight:600;color:inherit;cursor:pointer;text-decoration:none}.board-button.primary{background:#d22630;border-color:#d22630;color:white}.board-grid{display:grid;grid-template-columns:minmax(0,1fr) minmax(300px,440px);gap:24px}.board-editor label{display:block;font-size:13px;font-weight:600;margin-bottom:8px}.board-editor input,.board-editor textarea{display:block;width:100%;padding:12px;border:1px solid #8885;background:transparent;border-radius:8px;color:inherit;margin-bottom:16px}.board-editor textarea{min-height:150px;resize:vertical;line-height:1.6}.board-section{border:1px solid #8884;border-radius:12px;padding:20px;margin-bottom:16px}.board-section-actions{display:flex;gap:8px;justify-content:flex-end;margin-bottom:16px}.board-section-actions button{border:1px solid #8885;border-radius:6px;background:transparent;color:inherit;padding:6px 10px;font-size:12px;cursor:pointer}.board-preview{position:sticky;top:20px;align-self:start;border:1px solid #8884;border-radius:14px;overflow:hidden}.board-preview-bar{display:flex;gap:8px;padding:12px;border-bottom:1px solid #8884}.board-preview iframe{width:100%;height:650px;background:#f7f6f2;border:0}.board-draft{background:#f7f6f2;color:#242420;padding:24px;max-height:650px;overflow:auto}.board-draft h2{font-size:26px;font-weight:700;margin-bottom:24px}.board-draft section{background:white;border:1px solid #e7e3d9;border-radius:12px;padding:20px;margin-bottom:14px}.board-draft h3{font-size:17px;font-weight:700;margin-bottom:12px}.board-draft p{white-space:pre-wrap;font-size:14px;line-height:1.8;color:#626159;overflow-wrap:anywhere}.board-preview [hidden]{display:none!important}@media(max-width:1000px){.board-grid{grid-template-columns:1fr}.board-preview{position:static}}
</style>
<div class="board-top"><div><h2>Edit your guest guide</h2><p>Add useful information and arrange it into sections. Save to update the same information QR.</p></div><div class="board-actions"><a class="board-button" href="{{ $previewUrl }}" target="_blank" rel="noopener">Open guest page</a><a class="board-button" href="{{ route('restaurant.tables.index', ['card' => 'information']) }}">Information QR cards</a></div></div>
@if($errors->any())<p role="alert" class="mb-4 text-red-500">{{ $errors->first() }}</p>@endif
<div class="board-grid">
<form method="post" action="{{ route('restaurant.settings.update') }}" class="board-editor" id="information-editor">
@csrf @method('PATCH')<input type="hidden" name="editor" value="information_board">
<label for="board-title">Guide title</label><input id="board-title" name="title" maxlength="120" required value="{{ old('title', $board['title']) }}">
<div id="board-sections">
@foreach(old('sections', $board['sections']) as $section)
<section class="board-section"><div class="board-section-actions"><button type="button" data-action="up" aria-label="Move section up">Move up</button><button type="button" data-action="down" aria-label="Move section down">Move down</button><button type="button" data-action="remove" aria-label="Remove section">Remove</button></div><label>Section heading<input data-field="title" name="sections[{{ $loop->index }}][title]" value="{{ $section['title'] }}" maxlength="200" required></label><label>Information<textarea data-field="body" name="sections[{{ $loop->index }}][body]" maxlength="20000" required>{{ $section['body'] }}</textarea></label></section>
@endforeach
</div>
<div class="board-actions"><button type="button" class="board-button" id="add-board-section">Add section</button><button class="board-button primary">Save information board</button></div>
<p style="font-size:12px;margin-top:12px;opacity:.7;">The draft preview updates as you type. The published guest page changes when you save.</p>
</form>
<aside class="board-preview" aria-label="Information board preview"><div class="board-preview-bar"><button type="button" class="board-button" id="show-board-draft">Draft preview</button><button type="button" class="board-button" id="show-board-published">Published page</button></div><div id="board-draft" class="board-draft" aria-live="polite"></div><iframe id="board-published" title="Published information board" src="{{ $previewUrl }}" hidden loading="lazy"></iframe></aside>
</div>
<template id="board-section-template"><section class="board-section"><div class="board-section-actions"><button type="button" data-action="up">Move up</button><button type="button" data-action="down">Move down</button><button type="button" data-action="remove">Remove</button></div><label>Section heading<input data-field="title" maxlength="200" required></label><label>Information<textarea data-field="body" maxlength="20000" required></textarea></label></section></template>
<script>
(()=>{
const form=document.getElementById('information-editor'),sections=document.getElementById('board-sections'),draft=document.getElementById('board-draft'),published=document.getElementById('board-published');
function refresh(){
 draft.replaceChildren();const heading=document.createElement('h2');heading.textContent=document.getElementById('board-title').value;draft.append(heading);
 [...sections.children].forEach((row,index)=>{
  row.querySelectorAll('[data-field]').forEach(input=>input.name=`sections[${index}][${input.dataset.field}]`);
  row.querySelector('[data-action="up"]').disabled=index===0;
  row.querySelector('[data-action="down"]').disabled=index===sections.children.length-1;
  row.querySelector('[data-action="remove"]').disabled=sections.children.length===1;
  const card=document.createElement('section'),title=document.createElement('h3'),body=document.createElement('p');
  title.textContent=row.querySelector('[data-field="title"]').value||'New section';body.textContent=row.querySelector('[data-field="body"]').value;card.append(title,body);draft.append(card);
 });
 document.getElementById('add-board-section').disabled=sections.children.length>=100;
}
form.addEventListener('input',refresh);
sections.addEventListener('click',event=>{const button=event.target.closest('[data-action]');if(!button)return;const row=button.closest('.board-section');if(button.dataset.action==='up'&&row.previousElementSibling)sections.insertBefore(row,row.previousElementSibling);if(button.dataset.action==='down'&&row.nextElementSibling)sections.insertBefore(row.nextElementSibling,row);if(button.dataset.action==='remove'&&sections.children.length>1)row.remove();refresh();});
document.getElementById('add-board-section').addEventListener('click',()=>{if(sections.children.length>=100)return;sections.append(document.getElementById('board-section-template').content.cloneNode(true));refresh();sections.lastElementChild.querySelector('input').focus();});
document.getElementById('show-board-draft').addEventListener('click',()=>{draft.hidden=false;published.hidden=true;});
document.getElementById('show-board-published').addEventListener('click',()=>{draft.hidden=true;published.hidden=false;});
form.addEventListener('submit',refresh);refresh();
})();
</script>
@endsection
