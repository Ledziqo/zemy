<style>
.signature-card{box-sizing:border-box;width:75mm;height:140mm;padding:6mm;position:relative;isolation:isolate;overflow:hidden;display:flex;flex-direction:column;align-items:center;justify-content:space-between;gap:1mm;background:var(--card-bg);color:var(--card-text);border:.2mm solid var(--card-border);font-family:Arial,Helvetica,sans-serif;text-align:center;print-color-adjust:exact;-webkit-print-color-adjust:exact;flex-shrink:0}
.signature-card *{box-sizing:border-box}
.signature-card:not(.is-landscape){display:grid;grid-template-rows:24mm 24mm 11mm 1fr;gap:2mm;justify-items:center;align-items:center}
.signature-card{font-family:'Trebuchet MS','Segoe UI',sans-serif}
.signature-card.is-landscape{width:140mm;height:75mm;padding:5mm;display:grid;grid-template-columns:75mm 50mm;grid-template-rows:1fr;column-gap:5mm;align-items:center}
.signature-card.is-landscape .signature-logo-slot{position:absolute;width:1px;height:1px;flex:none}
.signature-card.is-landscape .signature-heading{grid-column:1;grid-row:1;width:75mm;height:auto;min-height:24mm;align-self:end;margin-bottom:9mm;padding:0 2mm 4mm}
.signature-card.is-landscape .signature-scan{grid-column:2;grid-row:1;width:50mm;align-self:center;gap:1mm}
.signature-card.is-landscape .signature-frame{max-width:50mm;padding:1.5mm}
.signature-card.is-landscape .signature-frame img{max-width:47mm;max-height:47mm}
.signature-card.is-landscape .signature-location{position:absolute;left:5mm;right:auto;top:72%;width:75mm;max-width:none;margin:0;padding:0;background:transparent;font-size:calc(var(--detail-size) * 1.15);letter-spacing:.1em;line-height:1.1;white-space:nowrap;text-align:center;transform:translate(var(--location-x),var(--location-y))}
.signature-card.is-landscape .signature-footer{left:auto;right:-1.5mm;top:calc(50% - 3.8mm);bottom:auto;transform:translate(var(--footer-x),var(--footer-y)) rotate(90deg) scale(var(--footer-scale))}
.signature-card .signature-art{position:absolute;inset:0;width:100%;height:100%;z-index:-1;opacity:var(--art-opacity);pointer-events:none}
.signature-logo-slot{width:100%;height:24mm;flex:0 0 24mm;pointer-events:none}
.signature-logo-wrap{position:absolute;inset:0;width:auto;height:auto;pointer-events:none;overflow:visible;z-index:3}
.signature-logo{position:absolute;left:var(--logo-x);top:var(--logo-y);width:var(--logo-width);height:var(--logo-size);max-width:none;max-height:none;transform:translate(-50%,-50%);object-fit:contain;background:transparent;border-radius:0;padding:0;filter:drop-shadow(0 .35mm .45mm rgba(255,255,255,.7));z-index:1;pointer-events:auto;touch-action:none}
.logo-resize-handle{display:none}
.qr-preview .signature-logo-wrap{cursor:grab}
.qr-preview .signature-logo-wrap:active{cursor:grabbing}
.qr-preview .signature-logo-wrap.is-selected .signature-logo{outline:.45mm dashed var(--card-accent);outline-offset:1mm}
.qr-preview .logo-resize-handle{display:block;position:absolute;width:3.5mm;height:3.5mm;border:1px solid #fff;border-radius:1mm;background:var(--card-accent);box-shadow:0 1px 3px #0006;z-index:3;touch-action:none;pointer-events:auto}
.qr-preview .logo-resize-handle[data-resize="width"]{left:calc(var(--logo-x) + var(--logo-half-width));right:auto;top:var(--logo-y);transform:translate(-50%,-50%);cursor:ew-resize}
.qr-preview .logo-resize-handle[data-resize="height"]{left:var(--logo-x);top:calc(var(--logo-y) + var(--logo-half-height));bottom:auto;transform:translate(-50%,-50%);cursor:ns-resize}
.qr-preview .logo-resize-handle[data-resize="both"]{left:calc(var(--logo-x) + var(--logo-half-width));top:calc(var(--logo-y) + var(--logo-half-height));right:auto;bottom:auto;transform:translate(-50%,-50%);cursor:nwse-resize}
.signature-heading{height:24mm;width:100%;display:flex;flex-direction:column;justify-content:center;align-items:center;flex-shrink:0;position:relative;z-index:1;transform:translate(var(--heading-x),var(--heading-y));touch-action:none}
.signature-kicker{width:100%;position:relative;font-size:max(4pt,calc(var(--text-size) * .34));font-weight:700;letter-spacing:.16em;margin:0 0 2mm;display:grid;grid-template-columns:minmax(0,1fr) auto minmax(0,1fr);align-items:center;gap:1.5mm;line-height:1;transform:translate(var(--kicker-x),var(--kicker-y));touch-action:none}
.signature-kicker-cross{position:absolute;left:0;top:50%;font-size:1.7em;font-weight:400;letter-spacing:0;line-height:.68;color:var(--card-accent);transform:translateY(-50%)}
.signature-kicker-line[data-layer="line_left"]{margin-left:2.6em}
.signature-kicker-line{height:.18mm;background:var(--card-accent);opacity:.55;flex:1 1 auto;min-width:2mm}
.signature-kicker-text{white-space:nowrap;flex:0 1 auto}
.signature-title{font-family:Georgia,'Palatino Linotype',serif;font-size:var(--text-size);font-weight:700;letter-spacing:-.025em;line-height:1.12;margin:0;width:100%;overflow-wrap:anywhere;text-wrap:balance;transform:translate(var(--title-x),var(--title-y));touch-action:none}
.signature-scan{display:flex;flex-direction:column;align-items:center;gap:2mm;flex-shrink:0;position:relative;z-index:1;transform:translate(var(--scan-x),var(--scan-y));touch-action:none}
.signature-frame{padding:2mm;background:#fff;border-radius:2.6mm;position:relative;box-shadow:0 0 0 .25mm var(--card-accent),.7mm .7mm 0 var(--card-accent);transform:translate(var(--frame-x),var(--frame-y));touch-action:none}
.signature-frame img{display:block;width:var(--qr-size);height:var(--qr-size)}
.signature-hint{font-family:Georgia,'Palatino Linotype',serif;font-style:italic;font-size:calc(var(--detail-size) * 1.15);margin:1mm 0 0;padding:0;background:transparent;letter-spacing:.035em;line-height:1.3;transform:translate(var(--hint-x),var(--hint-y));touch-action:none}
.signature-card:not(.is-landscape) .signature-location{border-top:.2mm solid var(--card-accent);border-bottom:.2mm solid var(--card-accent);padding:1.4mm 3mm}
.signature-location{display:block;position:relative;z-index:2;flex-shrink:0;max-width:100%;margin:0;padding:.8mm 2.5mm;background:transparent;color:var(--card-text);font-size:calc(var(--detail-size) * 1.2);font-weight:700;letter-spacing:.1em;line-height:1.2;text-transform:uppercase;overflow-wrap:anywhere;transform:translate(var(--location-x),var(--location-y));touch-action:none}
.signature-footer{position:absolute;left:50%;right:auto;bottom:7mm;width:max-content;height:auto;display:flex;align-items:center;justify-content:center;background:transparent;color:var(--card-text);padding:0;border:0;box-shadow:none;border-radius:0;z-index:2;transform:translateX(-50%) translate(var(--footer-x),var(--footer-y)) scale(var(--footer-scale));transform-origin:center bottom;touch-action:none}
.signature-footer img{width:auto;height:3.8mm;max-width:none;object-fit:contain;flex:none}
.qr-preview .logo-resize-handle{display:none}
.qr-preview [data-layer]{cursor:move;touch-action:none;user-select:none}
.qr-preview .signature-art{pointer-events:auto}
.signature-kicker-text{display:inline-block}
.qr-selection{position:absolute;border:1px dashed #1688ff;z-index:100;cursor:move;touch-action:none}
.qr-selection[hidden]{display:none}
.qr-selection button{position:absolute;width:12px;height:12px;padding:0;background:#1688ff;border:2px solid white;border-radius:2px;touch-action:none}
.qr-selection [data-rotate]{left:50%;top:-28px;width:18px;height:18px;border-radius:50%;transform:translateX(-50%);cursor:grab;background:#1688ff}
.qr-selection [data-rotate]::after{content:'↻';display:block;color:#fff;font:14px/14px Arial,sans-serif}
.qr-selection [data-axis=x]{right:-6px;top:50%;cursor:ew-resize}
.qr-selection [data-axis=y]{bottom:-6px;left:50%;cursor:ns-resize}
.qr-selection [data-axis=xy]{right:-6px;bottom:-6px;cursor:nwse-resize}
.qr-editor-controls{width:100%;font-size:12px}
.qr-editor-controls select,.qr-editor-controls input{width:100%;color:#171717;background:white;border:1px solid #888;padding:6px}
/* CSS image filters flatten even SVG logos when browsers export a PDF. */
@media print{.signature-card{break-inside:avoid;page-break-inside:avoid}.signature-logo{filter:none!important}.qr-selection{display:none!important}}

/* Palazzo signature layout; artwork and QR colours remain editable. */
.signature-card{display:block!important;padding:0;font-family:'Segoe UI',sans-serif}
.signature-logo{left:calc(var(--logo-x) + .5mm);top:calc(var(--logo-y) + 15mm);width:calc(var(--logo-width) + 4mm);height:var(--logo-size);filter:none}
.signature-heading{position:absolute;left:6mm;top:54mm;width:63mm;height:auto}
.signature-kicker{font-size:6pt;font-weight:500;letter-spacing:.23em;gap:2mm;margin-bottom:3mm}
.signature-kicker-cross{display:none}
.signature-kicker-line[data-layer="line_left"]{margin-left:0}
.signature-kicker-line{background:var(--card-border);opacity:1}
.signature-title{font-family:Georgia,serif;font-size:calc(var(--text-size) + 3pt);font-weight:400;letter-spacing:-.025em;line-height:1.12}
.signature-card:not(.is-landscape) .signature-location{position:absolute;top:1mm;left:5mm;width:65mm;height:7mm;max-width:none;margin:0;padding:0;border:0;color:var(--card-bg);display:flex;align-items:center;justify-content:center;gap:4mm}
.signature-location span{font-family:'Segoe UI',sans-serif;font-size:var(--detail-size);font-weight:600;letter-spacing:.18em}
.signature-location strong{font-family:Georgia,serif;font-size:23pt;font-weight:400;line-height:1;letter-spacing:0}
.signature-scan{position:absolute;left:50%;top:84mm;transform:translateX(-50%) translate(var(--scan-x),var(--scan-y));gap:1mm}
.signature-frame{padding:2mm;background:var(--card-bg);border-radius:1.5mm;box-shadow:0 0 0 .2mm var(--card-border),0 0 0 1.3mm var(--card-accent),0 0 0 1.5mm var(--card-border)}
.signature-frame .signature-frame-detail{position:absolute;inset:0;width:100%;height:100%;pointer-events:none;stroke:var(--card-border)}
.signature-frame img{width:calc(var(--qr-size) - 6mm);height:calc(var(--qr-size) - 6mm)}
/* Keep the portrait QR square under the app's responsive img max-width rule.
   A squeezed image viewport letterboxes the SVG into oversized white bands. */
.signature-card:not(.is-landscape) .signature-scan{width:max-content}
.signature-card:not(.is-landscape) .signature-frame img{max-width:none;max-height:none}
.signature-hint{font-size:calc(var(--detail-size) + 1pt);color:var(--card-bg);margin:3mm 0 0;letter-spacing:.025em}
.signature-card:not(.is-landscape) .signature-footer{top:47mm;bottom:auto}
.signature-footer img{height:3mm;opacity:.75;filter:none}
.signature-card.is-landscape .signature-logo{left:calc(var(--logo-x) + 11mm);top:calc(var(--logo-y) + 4mm);width:calc(var(--logo-width) + 1mm);height:calc(var(--logo-size) + 1mm)}
.signature-card.is-landscape .signature-heading{left:20mm;top:40mm;width:54mm;min-height:0;height:auto;margin:0;padding:0}
.signature-card.is-landscape .signature-title{font-size:calc(var(--text-size) - 2pt);line-height:1.15}
.signature-card.is-landscape .signature-kicker{font-size:5pt;letter-spacing:.14em;margin-bottom:2.6mm}
.signature-card.is-landscape .signature-location{left:0;top:24mm;width:12mm;display:flex;flex-direction:column;align-items:center;gap:5mm;padding:0;margin:0;border:0;color:var(--card-bg);white-space:normal}
.signature-card.is-landscape .signature-location span{font-size:calc(var(--detail-size) - 2pt);letter-spacing:.22em}
.signature-card.is-landscape .signature-location strong{font-size:20pt;writing-mode:vertical-rl;letter-spacing:.1em}
.signature-card.is-landscape .signature-footer{left:48mm;right:auto;top:63mm;bottom:auto;transform:translateX(-50%) translate(var(--footer-x),var(--footer-y)) scale(var(--footer-scale))}
.signature-card.is-landscape .signature-scan{left:auto;right:7mm;top:19mm;width:45mm;transform:translate(var(--scan-x),var(--scan-y))}
.signature-card.is-landscape .signature-frame{padding:2mm;max-width:none}
.signature-card.is-landscape .signature-frame img{width:calc(var(--qr-size) - 6mm);height:calc(var(--qr-size) - 6mm);max-width:none;max-height:none}
.signature-card.is-landscape .signature-hint{margin-top:3mm}

</style>
<script>
window.fitSignatureTitles = function(root = document) {
    root.querySelectorAll('.signature-heading').forEach(heading => {
        const title = heading.querySelector('.signature-title');
        title.style.fontSize = '';
        let size = parseFloat(getComputedStyle(title).fontSize);
        while (heading.scrollHeight > heading.clientHeight + 1 && size > 12) {
            size -= .5;
            title.style.fontSize = size + 'px';
        }
    });
};
window.addEventListener('load', () => window.fitSignatureTitles());
</script>
