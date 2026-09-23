<style>
.signature-card{box-sizing:border-box;width:74.25mm;height:140mm;padding:6mm;position:relative;isolation:isolate;overflow:hidden;display:flex;flex-direction:column;align-items:center;justify-content:space-between;gap:1mm;background:var(--card-bg);color:var(--card-text);border:.2mm solid var(--card-border);font-family:Arial,Helvetica,sans-serif;text-align:center;print-color-adjust:exact;-webkit-print-color-adjust:exact;flex-shrink:0}
.signature-card *{box-sizing:border-box}
.signature-card .signature-art{position:absolute;inset:0;width:100%;height:100%;z-index:-1;opacity:var(--art-opacity);pointer-events:none}
.signature-logo-wrap{width:100%;height:24mm;display:flex;align-items:center;justify-content:center;position:relative;flex-shrink:0;overflow:visible}
.signature-logo{position:absolute;left:50%;top:50%;width:var(--logo-width);height:var(--logo-size);max-width:none;max-height:none;transform:translate(-50%,-50%);object-fit:contain;background:transparent;border-radius:0;padding:0;filter:drop-shadow(0 .35mm .45mm rgba(255,255,255,.7));z-index:1}
.logo-resize-handle{display:none}
.qr-preview .signature-logo-wrap{cursor:pointer}
.qr-preview .signature-logo-wrap.is-selected .signature-logo{outline:.45mm dashed var(--card-accent);outline-offset:1mm}
.qr-preview .logo-resize-handle{display:block;position:absolute;width:3.5mm;height:3.5mm;border:1px solid #fff;border-radius:1mm;background:var(--card-accent);box-shadow:0 1px 3px #0006;z-index:3;touch-action:none}
.qr-preview .logo-resize-handle[data-resize="width"]{left:calc(50% + var(--logo-half-width));right:auto;top:50%;transform:translate(-50%,-50%);cursor:ew-resize}
.qr-preview .logo-resize-handle[data-resize="height"]{left:50%;top:calc(50% + var(--logo-half-height));bottom:auto;transform:translate(-50%,-50%);cursor:ns-resize}
.qr-preview .logo-resize-handle[data-resize="both"]{left:calc(50% + var(--logo-half-width));top:calc(50% + var(--logo-half-height));right:auto;bottom:auto;transform:translate(-50%,-50%);cursor:nwse-resize}
.signature-heading{height:24mm;width:100%;display:flex;flex-direction:column;justify-content:center;align-items:center;flex-shrink:0}
.signature-kicker{width:100%;font-size:max(4pt,calc(var(--text-size) * .34));font-weight:700;letter-spacing:.16em;margin:0 0 2mm;display:flex;align-items:center;justify-content:center;gap:1.5mm;line-height:1}
.signature-kicker-cross{font-size:2.6em;font-weight:400;letter-spacing:0;line-height:.68;color:var(--card-accent);flex:0 0 auto;transform:translateY(-.02em)}
.signature-kicker-line{height:.35mm;background:var(--card-accent);opacity:.9;flex:1 1 auto;min-width:2mm}
.signature-kicker-text{white-space:nowrap;flex:0 1 auto}
.signature-title{font-size:var(--text-size);font-weight:900;letter-spacing:-.045em;line-height:.98;margin:0;width:100%;overflow-wrap:anywhere;text-wrap:balance}
.signature-scan{display:flex;flex-direction:column;align-items:center;gap:2mm;flex-shrink:0;transform:translateY(-3mm)}
.signature-frame{padding:2mm;background:#fff;border-radius:3mm;position:relative;box-shadow:0 0 0 .45mm var(--card-accent),1.5mm 1.5mm 0 var(--card-accent)}
.signature-frame img{display:block;width:var(--qr-size);height:var(--qr-size)}
.signature-hint{font-size:var(--detail-size);margin:1mm 0 0;letter-spacing:.015em;line-height:1.2}
.signature-location{display:block;max-width:100%;margin:1mm 0 0;padding:.8mm 2.5mm;color:var(--card-accent);font-size:calc(var(--detail-size) * 1.05);font-weight:900;letter-spacing:.1em;line-height:1.05;text-transform:uppercase;overflow-wrap:anywhere}
.signature-footer{position:absolute;left:6mm;right:6mm;bottom:3mm;width:auto;height:8mm;display:flex;align-items:center;justify-content:center;gap:2mm;background:rgba(255,255,255,.96);color:#171717;padding:1mm 2.5mm;border:0;box-shadow:none;border-radius:1.5mm;z-index:2}
.signature-footer:before,.signature-footer:after{display:none}
.signature-footer span{font-size:calc(var(--detail-size) * .85);font-weight:700;letter-spacing:.11em;text-transform:uppercase;white-space:nowrap}
.signature-footer img{width:24mm;height:6mm;object-fit:contain}
@media print{.signature-card{break-inside:avoid;page-break-inside:avoid}}
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
