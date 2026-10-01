window.initQrEditor = function (form, card) {
    if (!card) return;
    const names = {logo:'Venue logo', cross:'Cross', line_left:'Left line', line_right:'Right line', kicker_text:'At your service', title:'Headline', location:'Table / room label', frame:'QR code', hint:'Scan. Tap. Enjoy.', footer:'Powered by ZemTab (whole pill)', art:'Abstract artwork'};
    const select = form.querySelector('#qr-layer');
    const width = form.querySelector('#qr-layer-width'), height = form.querySelector('#qr-layer-height');
    const status = form.querySelector('#qr-design-status');
    const box = document.createElement('div');
    box.className = 'qr-selection no-print';
    box.innerHTML = '<button type="button" data-rotate aria-label="Rotate selected element"></button><button type="button" data-axis="x" aria-label="Resize width"></button><button type="button" data-axis="y" aria-label="Resize height"></button><button type="button" data-axis="xy" aria-label="Resize proportionally"></button>';
    card.appendChild(box);
    let selected = null, drag = null;
    const field = (key, dimension) => form.elements.namedItem('elements['+key+']['+dimension+']');
    const rotation = form.querySelector('#qr-layer-rotation');
    const state = key => Object.fromEntries(['x','y','sx','sy','r'].map(d => [d, Number(field(key,d)?.value || 0)]));
    const element = key => card.querySelector('[data-layer="'+key+'"]');
    const clamp = (v,a,b) => Math.min(b,Math.max(a,v));
    function apply(key) {
        const el = element(key), s = state(key);
        if (!el) return;
        el.style.translate = s.x+'mm '+s.y+'mm';
        el.style.scale = s.sx+' '+s.sy;
        el.style.rotate = s.r+'deg';
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
        rotation.value = Math.round(state(key).r);
        outline();
    }
    form.refreshQrEditor = function() {
        Object.keys(names).forEach(apply);
        if (selected) choose(selected);
        requestAnimationFrame(outline);
    };
    function changed() {
        apply(selected); outline();
        status.textContent = 'Unsaved changes — click Save QR design.';
    }
    Object.entries(names).forEach(([key,label]) => {
        const option = new Option(label,key);
        select.add(option); apply(key);
    });
    select.addEventListener('change',()=>choose(select.value));
    form.querySelectorAll('[data-nudge]').forEach(button=>button.addEventListener('click',()=>{
        const key=selected||select.value;
        if (!key) return;
        const step=.25, s=state(key);
        if(button.dataset.nudge==='left')s.x-=step;
        if(button.dataset.nudge==='right')s.x+=step;
        if(button.dataset.nudge==='up')s.y-=step;
        if(button.dataset.nudge==='down')s.y+=step;
        selected=key;
        form.dispatchEvent(new CustomEvent('qr-editor-action-start'));
        field(key,'x').value=clamp(s.x,-1000,1000).toFixed(3);
        field(key,'y').value=clamp(s.y,-1000,1000).toFixed(3);
        changed();
        form.dispatchEvent(new CustomEvent('qr-editor-action-end'));
    }));
    card.addEventListener('keydown',event=>{
        if (!selected || !['ArrowLeft','ArrowRight','ArrowUp','ArrowDown'].includes(event.key)) return;
        if (event.target.matches('input,textarea,select,button,[contenteditable="true"]')) return;
        event.preventDefault();
        const step=event.shiftKey?1:.25, s=state(selected);
        if(event.key==='ArrowLeft')s.x-=step;
        if(event.key==='ArrowRight')s.x+=step;
        if(event.key==='ArrowUp')s.y-=step;
        if(event.key==='ArrowDown')s.y+=step;
        field(selected,'x').value=clamp(s.x,-1000,1000).toFixed(3);
        field(selected,'y').value=clamp(s.y,-1000,1000).toFixed(3);
        form.dispatchEvent(new CustomEvent('qr-editor-action-start'));
        changed();
        form.dispatchEvent(new CustomEvent('qr-editor-action-end'));
    });
    [width,height].forEach((input,i)=>input.addEventListener('input',()=>{
        if (!Number.isFinite(input.valueAsNumber)) return;
        field(selected,i?'sy':'sx').value=clamp(input.valueAsNumber/100,.02,20);
        changed();
    }));
    rotation.addEventListener('input',()=>{
        if (!Number.isFinite(rotation.valueAsNumber)) return;
        field(selected,'r').value=clamp(rotation.valueAsNumber,-360,360);
        changed();
    });
    form.querySelector('#qr-layer-reset').addEventListener('click',()=>{
        ['x','y','sx','sy'].forEach(d=>field(selected,d).value=d.startsWith('s')?1:0);
        field(selected,'r').value=0;
        rotation.value=0;
        form.dispatchEvent(new CustomEvent('qr-editor-change'));
        changed();choose(selected);
    });
    form.querySelector('#qr-design-reset').addEventListener('click',()=>{
        Object.keys(names).forEach(key=>{
            ['x','y','sx','sy','r'].forEach(d=>field(key,d).value=d.startsWith('s')?1:0);
            apply(key);
        });
        choose(selected);
        form.dispatchEvent(new CustomEvent('qr-editor-change'));
    });
    card.addEventListener('dragstart',e=>e.preventDefault());
    card.addEventListener('pointerdown',event=>{
        const handle=event.target.closest('[data-axis]'), rotate=event.target.closest('[data-rotate]');
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
        const cardWidthMm=card.classList.contains('is-landscape')?139.25:74.25;
        const current=state(selected), centerX=r.left+r.width/2,centerY=r.top+r.height/2;
        drag={axis:handle?.dataset.axis,rotate:!!rotate,startX:event.clientX,startY:event.clientY,state:current,w:r.width,h:r.height,px:c.width/cardWidthMm,parentX:parentScale.sx*footerScale,parentY:parentScale.sy*footerScale,centerX,centerY,startAngle:Math.atan2(event.clientY-centerY,event.clientX-centerX)};
        form.dispatchEvent(new CustomEvent('qr-editor-action-start'));
    });
    card.addEventListener('pointermove',event=>{
        if (!drag) return;
        const dx=event.clientX-drag.startX,dy=event.clientY-drag.startY,s=drag.state;
        if(drag.rotate){
            const angle=Math.atan2(event.clientY-drag.centerY,event.clientX-drag.centerX);
            let delta=angle-drag.startAngle;
            delta=((delta+Math.PI)%(Math.PI*2)+Math.PI*2)%(Math.PI*2)-Math.PI;
            field(selected,'r').value=clamp(s.r+delta*180/Math.PI,-360,360).toFixed(1);
            rotation.value=Math.round(Number(field(selected,'r').value));
        } else if (!drag.axis) {
            field(selected,'x').value=clamp(s.x+dx/drag.px/drag.parentX,-1000,1000).toFixed(3);
            field(selected,'y').value=clamp(s.y+dy/drag.px/drag.parentY,-1000,1000).toFixed(3);
        } else {
            if(drag.axis==='xy'){
                const relativeX=2*dx/Math.max(drag.w,1),relativeY=2*dy/Math.max(drag.h,1);
                const factor=1+(Math.abs(relativeX)>=Math.abs(relativeY)?relativeX:relativeY);
                const ratio=clamp(factor,Math.max(.02/s.sx,.02/s.sy),Math.min(20/s.sx,20/s.sy));
                field(selected,'sx').value=(s.sx*ratio).toFixed(4);
                field(selected,'sy').value=(s.sy*ratio).toFixed(4);
            } else {
                if(drag.axis.includes('x'))field(selected,'sx').value=clamp(s.sx*(1+2*dx/Math.max(drag.w,1)),.02,20).toFixed(4);
                if(drag.axis.includes('y'))field(selected,'sy').value=clamp(s.sy*(1+2*dy/Math.max(drag.h,1)),.02,20).toFixed(4);
            }
            width.value=Math.round(state(selected).sx*100);height.value=Math.round(state(selected).sy*100);
        }
        changed();
    });
    ['pointerup','pointercancel','lostpointercapture'].forEach(name=>card.addEventListener(name,()=>{
        if(!drag)return;
        drag=null;
        form.dispatchEvent(new CustomEvent('qr-editor-action-end'));
    }));
    form.addEventListener('input',()=>requestAnimationFrame(outline));
    form.addEventListener('load',()=>requestAnimationFrame(outline),true);
    form.closest('details').addEventListener('toggle',()=>requestAnimationFrame(outline));
    window.addEventListener('resize',outline);
    choose(element('logo')?'logo':'title');
};
