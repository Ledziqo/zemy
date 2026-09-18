from pathlib import Path
from reportlab.pdfgen import canvas
from reportlab.lib.pagesizes import A3
from reportlab.lib.units import mm
from reportlab.graphics.barcode.qr import QrCodeWidget
from reportlab.graphics.shapes import Drawing
from reportlab.graphics import renderPDF
import pymupdf

ROOT = Path(__file__).resolve().parents[2]
OUT = ROOT / 'output/pdf/tulip-olympia-print-pack.pdf'
OUT.parent.mkdir(parents=True, exist_ok=True)
sections = [
    [(f'restaurant-{i}', f'Restaurant Table {i}', False) for i in range(1,22)],
    [(f'lobby-{i}', f'Lobby Table {i}', False) for i in range(1,10)],
    [(str(f*100+i), f'Room {f*100+i}', True) for f in range(2,9) for i in range(1,16)],
    [(str(900+i), f'Suite {900+i}', True) for i in range(1,5)],
]
c = canvas.Canvas(str(OUT), pagesize=A3)
c.setTitle('Tulip Olympia - Table and Room QR Print Pack')
W,H=A3
cw,ch=W/2,H/5
pages=0
for section in sections:
    for offset in range(0,len(section),10):
        for slot,(number,label,room) in enumerate(section[offset:offset+10]):
            x=(slot%2)*cw; y=H-(slot//2+1)*ch; center=x+cw/2
            c.setStrokeColorRGB(.55,.55,.55); c.setLineWidth(.35)
            c.rect(x+1,y+1,cw-2,ch-2)
            c.drawImage(str(ROOT/'public/uploads/restaurants/tulip-olympia-logo.png'),center-12*mm,y+ch-22*mm,24*mm,18*mm,preserveAspectRatio=True,anchor='c',mask='auto')
            c.setFillColorRGB(.824,.149,.188); c.setFont('Helvetica-Bold',7)
            c.drawCentredString(center,y+ch-28*mm,'SCAN FOR ROOM SERVICE' if room else 'SCAN TO ORDER')
            url=f'https://www.zemtab.com/r/tulip-olympia/table/{number}'
            qr=QrCodeWidget(url,barLevel='M',barBorder=4)
            bounds=qr.getBounds(); size=31*mm
            d=Drawing(size,size,transform=[size/(bounds[2]-bounds[0]),0,0,size/(bounds[3]-bounds[1]),0,0]); d.add(qr)
            renderPDF.draw(d,c,center-size/2,y+21*mm)
            c.setFillColorRGB(0,0,0); c.setFont('Helvetica-Bold',10)
            c.drawCentredString(center,y+17*mm,label)
            c.setStrokeColorRGB(.85,.85,.85); c.line(x+8*mm,y+13*mm,x+cw-8*mm,y+13*mm)
            c.setFont('Helvetica',7); c.drawString(center-21*mm,y+6*mm,'Powered by')
            c.drawImage(str(ROOT/'public/logo/zemtab-pantone-1795-c-icon-text-transparent.png'),center-5*mm,y+4*mm,27*mm,7*mm,preserveAspectRatio=True,anchor='c',mask='auto')
        c.showPage(); pages+=1
c.save()
doc=pymupdf.open(OUT)
assert len(doc)==16
for idx in [0,1,2,14,15]:
    doc[idx].get_pixmap(matrix=pymupdf.Matrix(1,1)).save(str(ROOT/f'tmp/pdfs/tulip-page-{idx+1}.png'))
print(f'PASS: {len(doc)} A3 pages, 139 cards. {OUT}')
