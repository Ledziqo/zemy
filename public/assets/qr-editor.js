window.initQrEditor = function (form, card) {
    if (!card) return;
    const names = {logo:'Venue logo', cross:'Cross', line_left:'Left line', line_right:'Right line', kicker_text:'At your service', title:'Headline', location:'Table / room label', frame:'QR code', hint:'Scan. Tap. Enjoy.', footer:'Powered by ZemTab (whole pill)', art:'Abstract artwork'};
    const select = form.querySelector('#qr-layer');
    const width = form.querySelector('#qr-layer-width'), height = form.querySelector('#qr-layer-height');
    const status = form.querySelector('#qr-design-status');
    const box = document.createElement('div');
    box.className = 'qr-selection no-print';
    box.innerHTML = '<button type="button" data-axis="x" aria-label="Resize width"></button><button type="button" data-axis="y" aria-label="Resize height"></button><button type="button" data-axis="xy" aria-label="Resize width and height"></button>';
    card.appendChild(box);
    let selected = null, drag = null;
    const field = (key, dimension) => form.elements.namedItem('elements['+key+']['+dimension+']');
    const state = key => Object.fromEntries(['x','y','sx','sy'].map(d => [d, Number(field(key,d).value)]));
    const element = key => card.querySelector('[data-layer="'+key+'"]');
    const clamp = (v,a,b) => Math.min(b,Math.max(a,v));
    function apply(key) {
        const el = element(key), s = state(key);
        if (!el) return;
        el.style.translate = s.x+'mm '+s.y+'mm';
        el.style.scale = s.sx+' '+s.sy;
    }
    function outline() {
        const el = element(selected);
        box.hidden = !el;
        if (!el || !card.getBoundingClientRect().width) return;
        const r = el.getBoundingClientRect(), c = card.getBoundingClientRect();
        Object.assign(box.style,{left:(r.left-c.left-card.clientLeft)+'px',top:(r.top-c.top-card.clientTop)+'px',width:Math.max(28,r.width)+'px',height:Math.max(28,r.height)+'px'});
    }
    function choose(key) {
        selected = key;
        select.value = key;
        width.value = Math.round(state(key).sx*100);
        height.value = Math.round(state(key).sy*100);
        outline();
    }
    function changed() {
        apply(selected); outline();
        status.textContent = 'Unsaved changes — click Save QR design.';
    }
    Object.entries(names).forEach(([key,label]) => {
        const option = new Option(label,key);
        select.add(option); apply(key);
    });
    select.addEventListener('change',()=>choose(select.value));
    [width,height].forEach((input,i)=>input.addEventListener('input',()=>{
        if (!Number.isFinite(input.valueAsNumber)) return;
        field(selected,i?'sy':'sx').value=clamp(input.valueAsNumber/100,.02,20);
        changed();
    }));
    form.querySelector('#qr-layer-reset').addEventListener('click',()=>{
        ['x','y','sx','sy'].forEach(d=>field(selected,d).value=d.startsWith('s')?1:0);
        changed();choose(selected);
    });
    form.querySelector('#qr-design-reset').addEventListener('click',()=>{
        Object.keys(names).forEach(key=>{
            ['x','y','sx','sy'].forEach(d=>field(key,d).value=d.startsWith('s')?1:0);
            apply(key);
        });
        choose(selected);
    });
    card.addEventListener('dragstart',e=>e.preventDefault());
    card.addEventListener('pointerdown',event=>{
        const handle=event.target.closest('[data-axis]');
        if (!box.contains(event.target)) {
            const target=event.target.closest('[data-layer]');
            if (!target) return;
            choose(target.dataset.layer);
        }
        const el=element(selected);
        if (!el) return;
        event.preventDefault();
        card.setPointerCapture(event.pointerId);
        const r=el.getBoundingClientRect(), c=card.getBoundingClientRect();
        // Ancestor scaling affects translation in the footer children.
        const parentScale=el.closest('.signature-footer') && el!==card.querySelector('.signature-footer')
            ? state('footer') : {sx:1,sy:1};
        const footerScale=el.closest('.signature-footer') && el!==card.querySelector('.signature-footer')
            ? Number(form.elements.namedItem('footer_size').value)/100 : 1;
        drag={axis:handle?.dataset.axis, startX:event.clientX,startY:event.clientY,state:state(selected),w:r.width,h:r.height,px:c.width/74.25,parentX:parentScale.sx*footerScale,parentY:parentScale.sy*footerScale};
    });
    card.addEventListener('pointermove',event=>{
        if (!drag) return;
        const dx=event.clientX-drag.startX,dy=event.clientY-drag.startY,s=drag.state;
        if (!drag.axis) {
            field(selected,'x').value=clamp(s.x+dx/drag.px/drag.parentX,-1000,1000).toFixed(3);
            field(selected,'y').value=clamp(s.y+dy/drag.px/drag.parentY,-1000,1000).toFixed(3);
        } else {
            if(drag.axis.includes('x'))field(selected,'sx').value=clamp(s.sx*(1+2*dx/Math.max(drag.w,1)),.02,20).toFixed(4);
            if(drag.axis.includes('y'))field(selected,'sy').value=clamp(s.sy*(1+2*dy/Math.max(drag.h,1)),.02,20).toFixed(4);
            width.value=Math.round(state(selected).sx*100);height.value=Math.round(state(selected).sy*100);
        }
        changed();
    });
    ['pointerup','pointercancel','lostpointercapture'].forEach(name=>card.addEventListener(name,()=>drag=null));
    form.addEventListener('input',()=>requestAnimationFrame(outline));
    form.addEventListener('load',()=>requestAnimationFrame(outline),true);
    form.closest('details').addEventListener('toggle',()=>requestAnimationFrame(outline));
    window.addEventListener('resize',outline);
    choose(element('logo')?'logo':'title');
};
