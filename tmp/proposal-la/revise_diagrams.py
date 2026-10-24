from pathlib import Path
from PIL import Image, ImageDraw, ImageFont
from lxml import etree as E
from docx import Document
from docx.shared import Pt,Cm,RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH,WD_TAB_ALIGNMENT,WD_TAB_LEADER
from docx.oxml import OxmlElement
from docx.oxml.ns import qn
import math,shutil

ROOT=Path(__file__).parent/'revision-20261024';ROOT.mkdir(exist_ok=True)
OUT=Path(r'D:/poltekk/SEMESTER 5/PROPOSAL LAPORAN AKHIR/Proposal LA Web Mobile')
DOC=OUT/'Proposal_LA_Donasi_Web_Mobile_An_Nuur_2.docx'
BACK=ROOT/'proposal-before-revision.docx'
if not BACK.exists():shutil.copy2(DOC,BACK)
mx=E.Element('mxfile',host='app.diagrams.net',version='24.7.17')
F=r'C:/Windows/Fonts/arial.ttf';FB=r'C:/Windows/Fonts/arialbd.ttf'
assets=[]

class D:
 def __init__(self,name,w,h):
  self.name=name;self.im=Image.new('RGB',(w,h),'white');self.d=ImageDraw.Draw(self.im);self.count=0
  di=E.SubElement(mx,'diagram',name=name,id='d'+str(len(mx)))
  model=E.SubElement(di,'mxGraphModel',grid='1',gridSize='10',page='1',pageWidth=str(w),pageHeight=str(h))
  self.root=E.SubElement(model,'root');E.SubElement(self.root,'mxCell',id='0');E.SubElement(self.root,'mxCell',id='1',parent='0')
 def cell(self,b,t,style):
  self.count+=1;x,y,x2,y2=b
  c=E.SubElement(self.root,'mxCell',id=str(self.count+1),value=t,style='html=0;whiteSpace=wrap;fillColor=#ffffff;strokeColor=#000000;strokeWidth=2;fontColor=#000000;fontFamily=Arial;'+style,vertex='1',parent='1')
  E.SubElement(c,'mxGeometry',x=str(x),y=str(y),width=str(x2-x),height=str(y2-y),attrib={'as':'geometry'})
 def text(self,pos,t,size=30,bold=False,background=False):
  font=ImageFont.truetype(FB if bold else F,size);bb=self.d.multiline_textbbox(pos,t,font=font,anchor='mm',align='center',spacing=5)
  if background:self.d.rectangle((bb[0]-5,bb[1]-3,bb[2]+5,bb[3]+3),fill='white')
  self.d.multiline_text(pos,t,font=font,anchor='mm',align='center',spacing=5,fill='black')
  self.cell((bb[0]-4,bb[1]-4,bb[2]+4,bb[3]+4),t,f'text;strokeColor=none;fillColor=none;fontSize={size};fontStyle={1 if bold else 0};align=center;')
 def box(self,b,t='',size=30,kind='rect',bold=False):
  x,y,x2,y2=b
  if kind=='ellipse':self.d.ellipse(b,outline='black',width=3)
  elif kind=='round':self.d.rounded_rectangle(b,radius=20,outline='black',width=3)
  elif kind=='diamond':self.d.polygon([((x+x2)/2,y),(x2,(y+y2)/2),((x+x2)/2,y2),(x,(y+y2)/2)],outline='black',width=3)
  else:self.d.rectangle(b,outline='black',width=3)
  if t:self.d.multiline_text(((x+x2)/2,(y+y2)/2),t,font=ImageFont.truetype(FB if bold else F,size),anchor='mm',align='center',spacing=5,fill='black')
  style={'ellipse':'ellipse;','round':'rounded=1;arcSize=15;','diamond':'rhombus;','rect':'rounded=0;'}[kind]
  self.cell(b,t,style+f'fontSize={size};fontStyle={1 if bold else 0};')
 def line(self,pts,arrow=True,dash=False,label=None,lp=None,double=False):
  if dash:
   for a,b in zip(pts,pts[1:]):
    dist=math.dist(a,b);n=max(1,int(dist/12))
    for j in range(0,n,2):
     p=(a[0]+(b[0]-a[0])*j/n,a[1]+(b[1]-a[1])*j/n);q=(a[0]+(b[0]-a[0])*min(j+1,n)/n,a[1]+(b[1]-a[1])*min(j+1,n)/n);self.d.line([p,q],fill='black',width=3)
  else:self.d.line(pts,fill='black',width=3)
  def head(a,b):
   ang=math.atan2(b[1]-a[1],b[0]-a[0]);self.d.line([(b[0]-16*math.cos(ang-.45),b[1]-16*math.sin(ang-.45)),b,(b[0]-16*math.cos(ang+.45),b[1]-16*math.sin(ang+.45))],fill='black',width=3)
  if arrow:head(pts[-2],pts[-1])
  if double:head(pts[1],pts[0])
  self.count+=1;c=E.SubElement(self.root,'mxCell',id=str(self.count+1),value='',style=f'endArrow={"open" if arrow else "none"};startArrow={"open" if double else "none"};endFill=0;strokeColor=#000000;strokeWidth=2;dashed={1 if dash else 0};',edge='1',parent='1')
  geo=E.SubElement(c,'mxGeometry',relative='1',attrib={'as':'geometry'})
  for p,typ in [(pts[0],'sourcePoint'),(pts[-1],'targetPoint')]:E.SubElement(geo,'mxPoint',x=str(p[0]),y=str(p[1]),attrib={'as':typ})
  if len(pts)>2:
   arr=E.SubElement(geo,'Array',attrib={'as':'points'})
   for p in pts[1:-1]:E.SubElement(arr,'mxPoint',x=str(p[0]),y=str(p[1]))
  if label:self.text(lp,label,27,background=True)
 def actor(self,x,y,t):
  self.d.ellipse((x-24,y-65,x+24,y-17),outline='black',width=3)
  self.d.line([(x,y-17),(x,y+55)],fill='black',width=3);self.d.line([(x-45,y+15),(x+45,y+15)],fill='black',width=3);self.d.line([(x-42,y+110),(x,y+55),(x+42,y+110)],fill='black',width=3)
  self.cell((x-45,y-65,x+45,y+110),'','shape=umlActor;')
  self.text((x,y+145),t,30,True)
 def dot(self,x,y,end=False):
  self.d.ellipse((x-16,y-16,x+16,y+16),fill='black');self.cell((x-16,y-16,x+16,y+16),'','ellipse;fillColor=#000000;')
  if end:self.d.ellipse((x-24,y-24,x+24,y+24),outline='black',width=3);self.cell((x-24,y-24,x+24,y+24),'','ellipse;fillColor=none;')
 def lanes(self,title,names):
  w=self.im.width;h=self.im.height;self.text((w/2,40),title,34,True)
  step=(w-40)/len(names)
  for i,name in enumerate(names):
   x=20+i*step;self.box((x,90,x+step,h-25));self.line([(x,155),(x+step,155)],False);self.text((x+step/2,122),name,30,True)
 def save(self):
  f=ROOT/(self.name+'.png');self.im.save(f);assets.append(f)

# 3.1 Public use cases: separate viewing and donating actors.
g=D('01-use-case-publik',1500,1000);g.box((330,30,1440,960));g.text((885,85),'Layanan web publik An-Nuur 2',35,True)
g.actor(145,240,'Pengunjung');g.actor(145,640,'Donatur biasa')
for y,t in [(180,'Melihat profil dan informasi panti'),(440,'Berdonasi melalui web'),(620,'Mengajukan bantuan barang'),(800,'Melihat status transaksi')]:g.box((510,y,1330,y+115),t,32,'ellipse')
g.line([(190,255),(510,237)],False)
for y in [497,677,857]:g.line([(190,655),(510,y)],False)
g.save()
# 3.2 Mobile use cases.
g=D('02-use-case-mobile',1500,1160);g.box((330,30,1440,1120));g.text((885,85),'Layanan mobile donatur tetap',35,True);g.actor(145,535,'Donatur tetap')
for i,t in enumerate(['Mendaftar dan mengaktifkan akun','Login dan mengelola profil','Berdonasi melalui mobile','Melihat riwayat donasi pribadi','Memantau transparansi dana']):
 y=175+i*175;g.box((510,y,1330,y+110),t,32,'ellipse');g.line([(190,550),(510,y+55)],False)
g.save()
# 3.3 Administrator with optional export as extend.
g=D('03-use-case-pengurus',1500,1390);g.box((330,30,1440,1360));g.text((885,85),'Pengelolaan melalui web pengurus',35,True);g.actor(145,625,'Pengurus')
for i,t in enumerate(['Mengelola konten dan kebutuhan','Mengelola data dan akun donatur','Mencatat donasi manual','Mengonfirmasi bantuan barang','Membukukan pengeluaran','Melihat saldo dan laporan']):
 y=160+i*155;g.box((510,y,1330,y+100),t,32,'ellipse');g.line([(190,640),(510,y+50)],False)
g.box((640,1190,1200,1290),'Mengekspor laporan',32,'ellipse');g.line([(920,1190),(920,1035)],True,True,'<<extend>>',(1060,1115));g.save()
# Activity payment, with ownership and provider responsibilities separated.
g=D('04-activity-donasi',1500,1900);g.lanes('Proses donasi uang melalui web dan mobile',['Donatur','Backend Laravel','Midtrans'])
g.dot(265,205);g.box((90,265,440,370),'Isi kategori\ndan nominal',31,'round');g.line([(265,221),(265,265)])
g.box((590,420,910,520),'Validasi data dan\npemilik transaksi',30,'round');g.line([(440,320),(750,320),(750,420)])
g.box((650,575,850,705),'Valid?',30,'diamond');g.line([(750,520),(750,575)])
g.box((90,585,440,695),'Tampilkan kesalahan\ndan perbaiki data',29,'round');g.line([(650,640),(440,640)],label='[Tidak]',lp=(540,610));g.line([(265,585),(60,585),(60,320),(90,320)])
g.box((590,780,910,890),'Simpan donasi\ndan minta checkout',30,'round');g.line([(750,705),(750,780)],label='[Ya]',lp=(805,740))
g.box((1070,780,1430,890),'Sediakan halaman\npembayaran',30,'round');g.line([(910,835),(1070,835)])
g.box((90,975,440,1085),'Selesaikan\npembayaran sandbox',30,'round');g.line([(1250,890),(1250,930),(265,930),(265,975)])
g.box((1070,1145,1430,1255),'Kirim webhook\nstatus pembayaran',30,'round');g.line([(440,1030),(1250,1030),(1250,1145)])
g.box((590,1145,910,1255),'Periksa signature\ndan nominal',30,'round');g.line([(1070,1200),(910,1200)])
g.box((650,1320,850,1450),'Sah?',30,'diamond');g.line([(750,1255),(750,1320)])
g.dot(945,1385,True);g.line([(850,1385),(921,1385)],label='[Tidak]',lp=(915,1300))
g.box((590,1530,910,1650),'Perbarui status\nsekali secara idempoten',28,'round');g.line([(750,1450),(750,1530)],label='[Ya]',lp=(805,1490))
g.box((90,1695,440,1805),'Lihat status dan\nriwayat dari server',30,'round');g.line([(590,1590),(265,1590),(265,1695)]);g.dot(750,1800,True);g.line([(440,1750),(750,1750),(750,1776)]);g.save()
# Activity account.
g=D('05-activity-akun',1500,1850);g.lanes('Pendaftaran dan aktivasi akun donatur tetap',['Donatur','Backend Laravel','Pengurus'])
g.dot(265,210);g.box((90,275,440,385),'Isi formulir\npendaftaran',31,'round');g.line([(265,226),(265,275)])
g.box((590,435,910,555),'Validasi dan simpan\nakun Menunggu',30,'round');g.line([(440,330),(750,330),(750,435)])
g.box((1070,620,1430,730),'Tinjau pengajuan\nidentitas donatur',30,'round');g.line([(910,495),(1250,495),(1250,620)])
g.box((1150,795,1350,925),'Setujui?',30,'diamond');g.line([(1250,730),(1250,795)])
g.box((590,805,910,915),'Akun tetap\nbelum aktif',30,'round');g.line([(1150,860),(910,860)],label='[Tidak]',lp=(1020,825));g.dot(750,990,True);g.line([(750,915),(750,966)])
g.box((1070,1000,1430,1110),'Setujui dan kirim\nundangan aktivasi',30,'round');g.line([(1250,925),(1250,1000)],label='[Ya]',lp=(1305,962))
g.box((590,1170,910,1280),'Kirim tautan\naktivasi melalui email',29,'round');g.line([(1250,1110),(1250,1225),(910,1225)])
g.box((90,1170,440,1280),'Buka tautan\naktivasi akun',30,'round');g.line([(590,1225),(440,1225)])
g.box((650,1360,850,1500),'Token\nsah?',29,'diamond');g.line([(265,1280),(265,1330),(750,1330),(750,1360)])
g.dot(945,1430,True);g.line([(850,1430),(921,1430)],label='[Tidak]',lp=(915,1350))
g.box((590,1570,910,1680),'Verifikasi email\ndan aktifkan akun',29,'round');g.line([(750,1500),(750,1570)],label='[Ya]',lp=(805,1535))
g.box((90,1570,440,1680),'Login dan gunakan\nlayanan pribadi',30,'round');g.line([(590,1625),(440,1625)]);g.dot(265,1770,True);g.line([(265,1680),(265,1746)]);g.save()
# Activity expense.
g=D('06-activity-pengeluaran',1500,1650);g.lanes('Pencatatan dan pembukuan pengeluaran',['Pengurus','Sistem'])
g.dot(385,210);g.box((175,275,595,395),'Isi rincian pengeluaran\ndan unggah bukti',32,'round');g.line([(385,226),(385,275)])
g.box((905,465,1325,585),'Simpan sebagai Draft\nSaldo belum berkurang',31,'round');g.line([(595,335),(1115,335),(1115,465)])
g.box((175,660,595,780),'Periksa catatan\ndan pilih Bukukan',32,'round');g.line([(1115,585),(1115,620),(385,620),(385,660)])
g.box((995,840,1235,1010),'Data\nvalid?',31,'diamond');g.line([(595,720),(1115,720),(1115,840)])
g.box((175,865,595,985),'Tampilkan kesalahan\ndan perbaiki Draft',31,'round');g.line([(995,925),(595,925)],label='[Tidak]',lp=(795,890));g.line([(175,925),(90,925),(90,720),(175,720)])
g.box((905,1095,1325,1225),'Ubah menjadi Dibukukan\nCatat pengurus dan waktu',30,'round');g.line([(1115,1010),(1115,1095)],label='[Ya]',lp=(1175,1050))
g.box((905,1315,1325,1435),'Hitung laporan dan saldo\nPublikasikan bukti berizin',30,'round');g.line([(1115,1225),(1115,1315)])
g.box((175,1315,595,1435),'Lihat hasil pembukuan\ndan laporan',31,'round');g.line([(905,1375),(595,1375)]);g.dot(385,1550,True);g.line([(385,1435),(385,1526)]);g.save()

def entity(g,b,name,fields):
 x,y,x2,y2=b;g.box(b);g.line([(x,y+60),(x2,y+60)],False);g.text(((x+x2)/2,y+30),name,32,True);g.text(((x+x2)/2,(y+60+y2)/2),fields,29)
# Relational diagrams, conventional table compartments and cardinality labels.
g=D('07-relasi-akun-donasi',1500,1250)
for b,n,f in [((30,40,590,270),'users','PK id\nname · email · role\naccount_status'),((910,40,1470,270),'donatur','PK id\nFK user_id (unik, opsional)\nnama · whatsapp · jenis'),((30,480,590,690),'kategori_donasi','PK id\nnama'),((910,430,1470,760),'donasi','PK id · FK donatur_id\nFK kategori_donasi_id\nFK owner_user_id (opsional)\nnominal · biaya · status'),((30,990,590,1220),'donor_account_audits','PK id · FK donatur_id\nFK actor_user_id\naction · details'),((910,990,1470,1220),'payment_transactions','PK id · FK donasi_id (unik)\nReferensi pembayaran\nStatus provider')]:entity(g,b,n,f)
g.line([(590,140),(910,140)],False,label='1 : 0..1',lp=(750,110));g.line([(1190,270),(1190,430)],False,label='1 : N',lp=(1250,350));g.line([(590,580),(910,580)],False,label='1 : N',lp=(750,550));g.line([(1190,760),(1190,990)],False,label='1 : 0..1',lp=(1270,875))
g.line([(310,270),(310,350),(700,350),(700,690),(910,690)],False,label='pemilik 1 : N',lp=(800,725));g.line([(910,220),(800,220),(800,1100),(590,1100)],False,label='1 : N',lp=(700,1070));g.save()
g=D('08-relasi-bantuan-kas',1500,1380)
for b,n,f in [((30,40,590,240),'donatur','PK id'),((910,40,1470,240),'bantuan','PK id · FK donatur_id\nstatus'),((30,430,590,670),'kategori_bantuan','PK id · nama'),((910,430,1470,670),'bantuan_items','PK id · FK bantuan_id\nFK kategori_bantuan_id\nnama_barang · jumlah · satuan'),((30,850,590,1140),'kategori_pengeluaran','PK id · nama'),((910,850,1470,1140),'pengeluaran','PK id · FK kategori_pengeluaran_id\nFK created_by / posted_by (users)\nnominal · status · is_public')]:entity(g,b,n,f)
for pts,pos in [([(590,140),(910,140)],(750,110)), ([(1190,240),(1190,430)],(1250,335)), ([(590,550),(910,550)],(750,520)), ([(590,990),(910,990)],(750,960))]:g.line(pts,False,label='1 : N',lp=pos)
g.text((750,1250),'Relasi pendukung: saldo_pembukaan.disahkan_oleh → users.id (N : 1)\nKeterangan: PK = primary key; FK = foreign key',29);g.save()
# Architecture, orthogonal links and component compartments.
g=D('09-arsitektur',1500,1100)
for b,n,f in [((40,40,650,230),'Web publik dan pengurus','Blade · Tailwind CSS · Alpine.js'),((850,40,1460,230),'Aplikasi Android','Flutter · donatur tetap'),((300,425,1200,650),'Backend Laravel','Web dan REST API /api/v1\nAutentikasi · validasi · pengolahan transaksi')]:entity(g,b,n,f)
g.line([(345,230),(345,425)],double=True,label='HTTP(S)',lp=(425,325));g.line([(1155,230),(1155,425)],double=True,label='HTTP(S) / JSON',lp=(1300,325))
for x,t in [(40,'MySQL\nBasis data bersama'),(550,'Midtrans sandbox\nPembayaran dan webhook'),(1060,'Fonnte dan email\nNotifikasi dan aktivasi')]:g.box((x,860,x+400,1040),t,30)
for x in [240,750,1260]:g.line([(750,650),(750,740),(x,740),(x,860)],double=True)
g.save()
# Wireframes use the same monochrome sans serif and straight borders.
g=D('10-antarmuka-web',1500,1030);g.box((30,30,1470,1000));g.line([(30,145),(1470,145)],False);g.text((750,87),'AN-NUUR 2   |   Profil   Kegiatan   Kebutuhan   Donasi',35,True)
g.box((90,210,1410,460),'Profil panti dan informasi kegiatan\n\n[ Lihat kebutuhan ]     [ Salurkan donasi ]',35)
for x,t in [(90,'Kebutuhan panti\nTarget dan realisasi'),(550,'Kegiatan\nFoto dan ringkasan'),(1010,'Bantuan barang\nForm pengajuan')]:g.box((x,550,x+400,770),t,32)
g.text((750,900),'Kontak dan lokasi panti',32);g.save()
g=D('11-antarmuka-pengurus',1500,1030);g.box((30,30,1470,1000));g.box((30,30,340,1000));g.text((185,160),'PENGURUS',33,True);g.text((185,570),'Dashboard\n\nDonatur\n\nDonasi\n\nBantuan\n\nPengeluaran\n\nArus kas\n\nLaporan',30)
g.text((905,110),'DONASI DAN ARUS KAS',36,True)
for x,t in [(390,'Penerimaan\nbersih'),(750,'Pengeluaran\ndibukukan'),(1110,'Saldo\ntercatat')]:g.box((x,210,x+310,370),t,31)
g.box((400,450,1420,850),'Filter periode dan status\n\nTanggal   |   Keterangan   |   Nominal   |   Status\n\nDaftar transaksi dan pengeluaran\n\n[ Detail ]  [ Bukukan ]  [ PDF ]  [ Excel ]',32);g.text((905,935),'Peringatan saldo sementara apabila biaya belum tersedia',29);g.save()
g=D('12-antarmuka-mobile',1500,1180)
for x,title,t in [(30,'Beranda','Total donasi pribadi\n\n[ Donasi sekarang ]\n\nRingkasan dana panti\nPenerimaan bersih\nPengeluaran\nSaldo'),(540,'Donasi','Kategori donasi\n[ Pilih kategori ]\n\nNominal\n[ Pilihan / lainnya ]\n\n[ Lanjut bayar ]\n\nStatus dari server'),(1050,'Transparansi','Bulan dan tahun\n[ Pilih periode ]\n\nRingkasan arus kas\n\nDaftar pengeluaran\n[ Detail ]\n[ Bukti publik ]')]:
 g.box((x,30,x+420,1140));g.text((x+210,120),title,37,True);g.line([(x,190),(x+420,190)],False);g.text((x+210,595),t,29);g.line([(x,995),(x+420,995)],False);g.text((x+210,1060),'Beranda · Riwayat\nDana · Profil',28)
g.save()
E.ElementTree(mx).write(str(OUT/'Diagram_Proposal_LA_An_Nuur_2.drawio'),encoding='utf-8',xml_declaration=True,pretty_print=True)

d=Document(DOC)
# Replace illustrations by their caption number; keep all existing prose.
ps=list(d.paragraphs)
for i,p in enumerate(ps):
 if not p._p.xpath('.//a:blip') or i+1>=len(ps):continue
 cap=ps[i+1].text
 if not cap.startswith('Gambar 3.'):continue
 n=int(cap.split()[1].split('.')[1]);p.clear();p.alignment=WD_ALIGN_PARAGRAPH.CENTER;p.paragraph_format.first_line_indent=Cm(0)
 p.paragraph_format.keep_with_next=True;p.paragraph_format.space_before=Pt(6);p.paragraph_format.space_after=Pt(3)
 p.add_run().add_picture(str(assets[n-1]),width=Cm(14))
 # Begin each detailed activity / relational diagram on a fresh page for readability.
 if n in [4,5,6,7,8]:p.paragraph_format.page_break_before=True
 # Keep the image, caption, and source together.
 ps[i+1].paragraph_format.keep_with_next=True
 if i+2<len(ps):ps[i+2].paragraph_format.keep_with_next=False
for p in d.paragraphs:
 if p.text.startswith('Diagram berikut memperlihatkan alur utama.'):
  p.text='Diagram berikut membagi aktivitas ke dalam swimlane sesuai pelaksana. Simbol keputusan menunjukkan percabangan kondisi, panah menunjukkan aliran proses, dan simpul akhir menunjukkan berakhirnya alur. Kondisi tambahan dijelaskan setelah masing-masing diagram.'
 if p.text.startswith('Notasi 1 : N menunjukkan'):
  p.text='Notasi 1 : N menunjukkan satu record induk dapat terkait banyak record anak; 0..1 menunjukkan hubungan opsional dengan paling banyak satu record. Garis penghubung pada diagram menunjukkan relasi data, bukan aliran proses. Setiap tabel memisahkan nama tabel dan atribut, dengan penanda PK untuk primary key serta FK untuk foreign key. Gambar memperlihatkan relasi inti, sedangkan data pendukung dirangkum pada tabel berikut.'
 if p.text.startswith('3.3.4.1 '):p.style=d.styles['Heading 4']
# Explicit black in every part and style, with no theme-color override.
for part in d.part.package.parts:
 root=getattr(part,'_element',None)
 if root is None:continue
 for color in root.iter(qn('w:color')):
  color.attrib.clear();color.set(qn('w:val'),'000000')
 for r in root.iter(qn('w:r')):
  rp=r.find(qn('w:rPr'))
  if rp is None:rp=E.Element(qn('w:rPr'));r.insert(0,rp)
  color=rp.find(qn('w:color'))
  if color is None:color=E.SubElement(rp,qn('w:color'))
  color.attrib.clear();color.set(qn('w:val'),'000000')
for s in d.styles:
 if s.type in [1,2]:
  s.font.color.rgb=RGBColor(0,0,0);s.font.name='Times New Roman'
  for c in s.element.iter(qn('w:color')):c.attrib.clear();c.set(qn('w:val'),'000000')
  for rf in s.element.iter(qn('w:rFonts')):
   for a in list(rf.attrib):
    if 'Theme' in a:del rf.attrib[a]
for n in range(1,5):
 s=d.styles[f'Heading {n}'];s.font.color.rgb=RGBColor(0,0,0);s.font.size=Pt(14 if n==1 else 12);s.font.bold=True
for n in range(1,4):
 s=d.styles[f'TOC{n}'];s.font.size=Pt(12);s.font.color.rgb=RGBColor(0,0,0);s.font.bold=n==1;s.font.underline=False
 pf=s.paragraph_format;pf.line_spacing=1;pf.space_before=Pt(0);pf.space_after=Pt(6);pf.left_indent=Cm((n-1)*.5);pf.first_line_indent=Cm(0);pf.right_indent=Cm(.5);pf.alignment=WD_ALIGN_PARAGRAPH.LEFT
 pf.tab_stops.clear_all();pf.tab_stops.add_tab_stop(Cm(14),WD_TAB_ALIGNMENT.RIGHT,WD_TAB_LEADER.DOTS)
 # Disable automatic style mutation when someone updates the contents.
 for e in list(s.element.findall(qn('w:autoRedefine'))):s.element.remove(e)
for name in ['Hyperlink','FollowedHyperlink']:
 if name in d.styles:d.styles[name].font.color.rgb=RGBColor(0,0,0);d.styles[name].font.underline=False
# Rebuild the contents with a proper unlocked Word TOC field, preserving its location.
for p in list(d.paragraphs):
 if p.style.style_id.startswith('TOC') and p.style.style_id!='TOCHeading':p._p.getparent().remove(p._p)
# Existing field may be wrapped in a content control. Remove that control only when it contains TOC.
for sdt in list(d.element.body.findall(qn('w:sdt'))):
 if any('TOC ' in (x.text or '') for x in sdt.iter(qn('w:instrText'))):d.element.body.remove(sdt)
for p in d.paragraphs:
 if p.text.strip()=='DAFTAR ISI':
  # Insert TOC directly after the contents heading.
  np=OxmlElement('w:p');p._p.addnext(np)
  for typ,txt in [('begin',None),(None,' TOC \\o "1-3" \\h \\z '),('separate',None),('end',None)]:
   r=OxmlElement('w:r')
   if typ:
    f=OxmlElement('w:fldChar');f.set(qn('w:fldCharType'),typ)
    if typ=='begin':f.set(qn('w:dirty'),'true')
   else:f=OxmlElement('w:instrText');f.set(qn('xml:space'),'preserve');f.text=txt
   r.append(f);np.append(r)
  break
settings=d.settings.element
for old in list(settings.findall(qn('w:updateFields'))):settings.remove(old)
u=OxmlElement('w:updateFields');u.set(qn('w:val'),'true');settings.append(u)
d.save(DOC)
print('Updated',DOC,'figures',len(assets))
