from pathlib import Path
from reportlab.pdfgen import canvas
from reportlab.lib.pagesizes import A3, landscape
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
c = canvas.Canvas(str(OUT), pagesize=landscape(A3))
c.setTitle('Tulip Olympia - Table and Room QR Print Pack')
W,H=landscape(A3)
cw,ch=W/3,H/2
pages=0
cards = [card for section in sections for card in section]
for offset in range(0,len(cards),6):
    for slot,(number,label,room) in enumerate(cards[offset:offset+6]):
        x=(slot%3)*cw; y=H-(slot//3+1)*ch; center=x+cw/2
        c.setStrokeColorRGB(.55,.55,.55); c.setLineWidth(.35)
        c.rect(x+2*mm,y+2*mm,cw-4*mm,ch-4*mm)
        c.drawImage(str(ROOT/'public/uploads/restaurants/tulip-olympia-logo.png'),center-10*mm,y+ch-24*mm,20*mm,16*mm,preserveAspectRatio=True,anchor='c',mask='auto')
        c.setFillColorRGB(0,0,0); c.setFont('Helvetica-Bold',13)
        c.drawCentredString(center,y+ch-24*mm,'Tulip Olympia')
        c.setFillColorRGB(.824,.149,.188); c.setFont('Helvetica-Bold',9)
        c.drawCentredString(center,y+ch-31*mm,'SCAN FOR ROOM SERVICE' if room else 'SCAN TO ORDER')
        url=f'https://www.zemtab.com/r/tulip-olympia/table/{number}'
        qr=QrCodeWidget(url,barLevel='M',barBorder=4)
        bounds=qr.getBounds(); size=44*mm
        d=Drawing(size,size,transform=[size/(bounds[2]-bounds[0]),0,0,size/(bounds[3]-bounds[1]),0,0]); d.add(qr)
        renderPDF.draw(d,c,center-size/2,y+54*mm)
        c.setFillColorRGB(.824,.149,.188); c.setFont('Helvetica-Bold',9)
        c.drawCentredString(center,y+47*mm,'ROOM' if room else 'TABLE')
        c.setFillColorRGB(0,0,0); c.setFont('Helvetica-Bold',16)
        c.drawCentredString(center,y+39*mm,label)
        c.setFillColorRGB(.3,.3,.3); c.setFont('Helvetica',6.5)
        c.drawCentredString(center,y+30*mm,url)
        c.setStrokeColorRGB(.85,.85,.85); c.line(x+15*mm,y+20*mm,x+cw-15*mm,y+20*mm)
        c.setFont('Helvetica',7); c.drawString(center-21*mm,y+10*mm,'Powered by')
        c.drawImage(str(ROOT/'public/logo/zemtab-pantone-1795-c-icon-text-transparent.png'),center-5*mm,y+8*mm,27*mm,7*mm,preserveAspectRatio=True,anchor='c',mask='auto')
    c.showPage(); pages+=1
c.save()
doc=pymupdf.open(OUT)
assert len(doc)==24
for idx in [0,1,22,23]:
    doc[idx].get_pixmap(matrix=pymupdf.Matrix(1,1)).save(str(ROOT/f'tmp/pdfs/tulip-page-{idx+1}.png'))
print(f'PASS: {len(doc)} landscape A3 pages, 139 cards, 6 cards per page. {OUT}')
