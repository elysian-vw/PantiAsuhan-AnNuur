import math
import shutil
from pathlib import Path
from PIL import Image, ImageDraw, ImageFont
from lxml import etree as E
from docx import Document
from docx.shared import Pt, Cm, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.oxml import OxmlElement
from docx.oxml.ns import qn

OUT_DIR = Path(r"d:/apps/laragon/www/projectTA/tmp/proposal-la/revision-20261024")
OUT_DIR.mkdir(parents=True, exist_ok=True)
PROP_DIR = Path(r"d:/apps/laragon/www/projectTA/tmp/proposal-la")
DOC_DIR = Path(r"D:/poltekk/SEMESTER 5/PROPOSAL LAPORAN AKHIR/Proposal LA Web Mobile")
DOC_MAIN = DOC_DIR / "Proposal_LA_Donasi_Web_Mobile_An_Nuur_2.docx"
DOC_UJIAN = Path(r"D:/poltekk/SEMESTER 5/PROPOSAL LAPORAN AKHIR/UJIAN PROPOSAL/Proposal LA.docx")
DRAWIO_PATH = DOC_DIR / "Diagram_Proposal_LA_An_Nuur_2.drawio"
ARTIFACT_DIR = Path(r"C:/Users/sang surya/.gemini/antigravity/brain/3f383136-6d3e-4f4c-95da-05a16c258249")

FONT_REG = r"C:/Windows/Fonts/arial.ttf"
FONT_BOLD = r"C:/Windows/Fonts/arialbd.ttf"
FONT_ITALIC = r"C:/Windows/Fonts/ariali.ttf"


class ChenERD:
    def __init__(self, width=2600, height=1580):
        self.w = width
        self.h = height
        self.im = Image.new("RGB", (width, height), "white")
        self.d = ImageDraw.Draw(self.im)
        self.cell_id = 10
        self.mx_diag = E.Element("diagram", name="07-erd-chen-full", id="erd_chen_full")
        model = E.SubElement(
            self.mx_diag,
            "mxGraphModel",
            grid="1",
            gridSize="10",
            page="1",
            pageWidth=str(width),
            pageHeight=str(height),
        )
        self.root = E.SubElement(model, "root")
        E.SubElement(self.root, "mxCell", id="0")
        E.SubElement(self.root, "mxCell", id="1", parent="0")

    def add_mx_vertex(self, box, value, style):
        self.cell_id += 1
        x1, y1, x2, y2 = box
        c = E.SubElement(
            self.root,
            "mxCell",
            id=str(self.cell_id),
            value=value,
            style="html=1;whiteSpace=wrap;fontFamily=Arial;" + style,
            vertex="1",
            parent="1",
        )
        E.SubElement(
            c,
            "mxGeometry",
            x=str(int(x1)),
            y=str(int(y1)),
            width=str(int(x2 - x1)),
            height=str(int(y2 - y1)),
            attrib={"as": "geometry"},
        )

    def add_mx_edge(self, pts, dashed=False):
        self.cell_id += 1
        style = f"endArrow=none;strokeColor=#000000;strokeWidth=2;dashed={1 if dashed else 0};"
        c = E.SubElement(self.root, "mxCell", id=str(self.cell_id), value="", style=style, edge="1", parent="1")
        geo = E.SubElement(c, "mxGeometry", relative="1", attrib={"as": "geometry"})
        E.SubElement(geo, "mxPoint", x=str(int(pts[0][0])), y=str(int(pts[0][1])), attrib={"as": "sourcePoint"})
        E.SubElement(geo, "mxPoint", x=str(int(pts[-1][0])), y=str(int(pts[-1][1])), attrib={"as": "targetPoint"})
        if len(pts) > 2:
            arr = E.SubElement(geo, "Array", attrib={"as": "points"})
            for p in pts[1:-1]:
                E.SubElement(arr, "mxPoint", x=str(int(p[0])), y=str(int(p[1])))

    def draw_dashed_line(self, pts, color="#000000", width=3, dash_len=11, gap_len=7):
        for a, b in zip(pts, pts[1:]):
            dist = math.dist(a, b)
            if dist == 0:
                continue
            dx = (b[0] - a[0]) / dist
            dy = (b[1] - a[1]) / dist
            pos = 0.0
            while pos < dist:
                end = min(pos + dash_len, dist)
                p1 = (a[0] + dx * pos, a[1] + dy * pos)
                p2 = (a[0] + dx * end, a[1] + dy * end)
                self.d.line([p1, p2], fill=color, width=width)
                pos += dash_len + gap_len

    def draw_dashed_rounded_rect(self, box, radius, color, width=3, dash_len=10, gap_len=7):
        x1, y1, x2, y2 = box
        pts = []
        steps = 12
        pts.append((x1 + radius, y1))
        pts.append((x2 - radius, y1))
        for i in range(1, steps + 1):
            ang = -math.pi / 2 + (math.pi / 2) * (i / steps)
            pts.append((x2 - radius + radius * math.cos(ang), y1 + radius + radius * math.sin(ang)))
        pts.append((x2, y2 - radius))
        for i in range(1, steps + 1):
            ang = 0 + (math.pi / 2) * (i / steps)
            pts.append((x2 - radius + radius * math.cos(ang), y2 - radius + radius * math.sin(ang)))
        pts.append((x1 + radius, y2))
        for i in range(1, steps + 1):
            ang = math.pi / 2 + (math.pi / 2) * (i / steps)
            pts.append((x1 + radius + radius * math.cos(ang), y2 - radius + radius * math.sin(ang)))
        pts.append((x1, y1 + radius))
        for i in range(1, steps + 1):
            ang = math.pi + (math.pi / 2) * (i / steps)
            pts.append((x1 + radius + radius * math.cos(ang), y1 + radius + radius * math.sin(ang)))

        self.draw_dashed_line(pts, color=color, width=width, dash_len=dash_len, gap_len=gap_len)

    def draw_title_banner(self, box, text):
        self.d.rounded_rectangle(box, radius=14, fill="#F1F5F9", outline="#000000", width=3)
        cx = (box[0] + box[2]) / 2
        cy = (box[1] + box[3]) / 2
        font = ImageFont.truetype(FONT_BOLD, 24)
        self.d.text((cx, cy), text, font=font, fill="#000000", anchor="mm")
        self.add_mx_vertex(
            box,
            f"<b>{text}</b>",
            "rounded=1;arcSize=20;fillColor=#F1F5F9;strokeColor=#000000;strokeWidth=2;fontSize=22;fontColor=#000000;",
        )

    def draw_cluster(self, box, title, border_color, text_color):
        self.draw_dashed_rounded_rect(box, radius=32, color=border_color, width=3, dash_len=9, gap_len=6)
        font = ImageFont.truetype(FONT_BOLD, 19)
        self.d.text((box[0] + 24, box[1] + 26), title, font=font, fill=text_color, anchor="lm")
        self.add_mx_vertex(
            box,
            "",
            f"rounded=1;arcSize=6;fillColor=none;strokeColor={border_color};strokeWidth=2;dashed=1;",
        )
        self.add_mx_vertex(
            (box[0] + 20, box[1] + 10, box[0] + 750, box[1] + 44),
            f"<b>{title}</b>",
            f"text;fillColor=none;strokeColor=none;align=left;verticalAlign=middle;fontSize=18;fontColor={text_color};",
        )

    def draw_entity(self, center, size, title_id, table_name, attrs):
        cx, cy = center
        w, h = size
        for attr_pos, attr_size, attr_text, is_pk in attrs:
            self.d.line([center, attr_pos], fill="#000000", width=2)
            self.add_mx_edge([center, attr_pos], dashed=False)

        for attr_pos, attr_size, attr_text, is_pk in attrs:
            ax, ay = attr_pos
            rx, ry = attr_size
            abox = (ax - rx, ay - ry, ax + rx, ay + ry)
            self.d.ellipse(abox, fill="#FFFFFF", outline="#000000", width=2)
            font = ImageFont.truetype(FONT_REG, 18)
            self.d.text((ax, ay), attr_text, font=font, fill="#000000", anchor="mm")
            if is_pk:
                bbox = self.d.textbbox((ax, ay), attr_text, font=font, anchor="mm")
                self.d.line([(bbox[0], bbox[3] + 2), (bbox[2], bbox[3] + 2)], fill="#000000", width=2)
            mx_val = f"<u>{attr_text}</u>" if is_pk else attr_text
            self.add_mx_vertex(
                abox,
                mx_val,
                "ellipse;fillColor=#FFFFFF;strokeColor=#000000;strokeWidth=2;fontSize=16;fontColor=#000000;",
            )

        x1, y1, x2, y2 = cx - w / 2, cy - h / 2, cx + w / 2, cy + h / 2
        self.d.rectangle((x1, y1, x2, y2), fill="#FFFFFF", outline="#000000", width=3)
        f_bold = ImageFont.truetype(FONT_BOLD, 20)
        f_ital = ImageFont.truetype(FONT_ITALIC, 18)
        self.d.text((cx, cy - 13), title_id, font=f_bold, fill="#000000", anchor="mm")
        self.d.text((cx, cy + 14), f"({table_name})", font=f_ital, fill="#000000", anchor="mm")
        self.add_mx_vertex(
            (x1, y1, x2, y2),
            f"<b>{title_id}</b><br><i>({table_name})</i>",
            "rounded=0;fillColor=#FFFFFF;strokeColor=#000000;strokeWidth=2;fontSize=18;fontColor=#000000;",
        )

    def draw_relationship(self, center, size, label, lines):
        for ln in lines:
            dashed = ln.get("dashed", False)
            if dashed:
                self.draw_dashed_line(ln["pts"], color="#000000", width=3, dash_len=10, gap_len=7)
            else:
                self.d.line(ln["pts"], fill="#000000", width=3)
            self.add_mx_edge(ln["pts"], dashed=dashed)
            if ln.get("card"):
                f_card = ImageFont.truetype(FONT_BOLD, 19)
                cpos = ln["card_pos"]
                bbox = self.d.textbbox(cpos, ln["card"], font=f_card, anchor="mm")
                self.d.rectangle((bbox[0] - 3, bbox[1] - 2, bbox[2] + 3, bbox[3] + 2), fill="#FFFFFF")
                self.d.text(cpos, ln["card"], font=f_card, fill="#000000", anchor="mm")
                self.add_mx_vertex(
                    (cpos[0] - 25, cpos[1] - 14, cpos[0] + 25, cpos[1] + 14),
                    f"<b>{ln['card']}</b>",
                    "text;fillColor=#FFFFFF;strokeColor=none;align=center;verticalAlign=middle;fontSize=16;fontColor=#000000;",
                )

        cx, cy = center
        hw, hh = size[0] / 2, size[1] / 2
        poly = [(cx, cy - hh), (cx + hw, cy), (cx, cy + hh), (cx - hw, cy)]
        self.d.polygon(poly, fill="#FFFFFF", outline="#000000", width=3)
        font = ImageFont.truetype(FONT_REG, 16)
        self.d.multiline_text((cx, cy), label, font=font, fill="#000000", anchor="mm", align="center", spacing=3)
        self.add_mx_vertex(
            (cx - hw, cy - hh, cx + hw, cy + hh),
            label.replace("\n", "<br>"),
            "rhombus;fillColor=#FFFFFF;strokeColor=#000000;strokeWidth=2;fontSize=15;fontColor=#000000;",
        )


def build_erd():
    erd = ChenERD(2600, 1580)

    # Top Title Banner
    erd.draw_title_banner(
        (400, 16, 2200, 72),
        "ENTITY RELATIONSHIP DIAGRAM (ERD) - SISTEM INFORMASI PENGELOLAAN DONASI AN-NUUR 2",
    )

    # 4 Clusters (Matching exact colors and positions of the reference image)
    erd.draw_cluster(
        (25, 138, 1230, 790),
        "KLUSTER 1: ADMINISTRASI AKUN, DONATUR & KEAMANAN SISTEM",
        border_color="#78909C",
        text_color="#455A64",
    )
    erd.draw_cluster(
        (1370, 138, 2575, 790),
        "KLUSTER 2: MASTER KATEGORI, DONASI UANG & PEMBAYARAN",
        border_color="#66BB6A",
        text_color="#2E7D32",
    )
    erd.draw_cluster(
        (25, 880, 1230, 1555),
        "KLUSTER 4: PENGELUARAN KAS, SALDO & PENGATURAN PANTI",
        border_color="#FFA726",
        text_color="#EF6C00",
    )
    erd.draw_cluster(
        (1370, 880, 2575, 1555),
        "KLUSTER 3: PENGAJUAN & DETAIL BANTUAN BARANG",
        border_color="#42A5F5",
        text_color="#1565C0",
    )

    # 1. Pengguna (1) -- Memiliki Akun -- (0..1) Donatur
    erd.draw_relationship(
        center=(605, 360),
        size=(156, 84),
        label="Memiliki\nAkun",
        lines=[
            {"pts": [(372, 360), (527, 360)], "dashed": False, "card": "1", "card_pos": (398, 338)},
            {"pts": [(683, 360), (838, 360)], "dashed": False, "card": "0..1", "card_pos": (802, 338)},
        ],
    )

    # 2. Donatur (1) -- Memicu Audit -- (N) Log Audit Akun
    erd.draw_relationship(
        center=(950, 505),
        size=(152, 80),
        label="Memicu\nAudit",
        lines=[
            {"pts": [(950, 399), (950, 465)], "dashed": False, "card": "1", "card_pos": (970, 422)},
            {"pts": [(950, 545), (950, 611)], "dashed": False, "card": "N", "card_pos": (970, 588)},
        ],
    )

    # 3. Kategori Donasi (1) -- Memiliki Donasi -- (N) Donasi Uang
    erd.draw_relationship(
        center=(1805, 345),
        size=(154, 82),
        label="Memiliki\nDonasi",
        lines=[
            {"pts": [(1655, 345), (1728, 345)], "dashed": False, "card": "1", "card_pos": (1678, 323)},
            {"pts": [(1882, 345), (1955, 345)], "dashed": False, "card": "N", "card_pos": (1930, 323)},
        ],
    )

    # 4. Donasi Uang (1) -- Memiliki Pembayaran -- (0..1) Transaksi Pembayaran
    erd.draw_relationship(
        center=(2065, 495),
        size=(168, 84),
        label="Memiliki\nPembayaran",
        lines=[
            {"pts": [(2065, 384), (2065, 453)], "dashed": False, "card": "1", "card_pos": (2085, 408)},
            {"pts": [(2065, 537), (2065, 606)], "dashed": False, "card": "0..1", "card_pos": (2094, 582)},
        ],
    )

    # 5. Cross-cluster: Pengguna (1) -- Memiliki Riwayat -- (N) Donasi Uang
    erd.draw_relationship(
        center=(1300, 104),
        size=(156, 76),
        label="Memiliki\nRiwayat",
        lines=[
            {
                "pts": [(345, 321), (345, 290), (715, 290), (715, 104), (1222, 104)],
                "dashed": True,
                "card": "1",
                "card_pos": (365, 302),
            },
            {
                "pts": [(1378, 104), (2135, 104), (2135, 306)],
                "dashed": True,
                "card": "N",
                "card_pos": (2155, 278),
            },
        ],
    )

    # 6. Cross-cluster: Donatur (1) -- Menyalurkan Donasi -- (N) Donasi Uang
    erd.draw_relationship(
        center=(1300, 560),
        size=(164, 82),
        label="Menyalurkan\nDonasi",
        lines=[
            {
                "pts": [(1045, 399), (1045, 435), (1205, 435), (1205, 560), (1218, 560)],
                "dashed": True,
                "card": "1",
                "card_pos": (1065, 418),
            },
            {
                "pts": [(1382, 560), (1910, 560), (1910, 368), (1955, 368)],
                "dashed": True,
                "card": "N",
                "card_pos": (1930, 390),
            },
        ],
    )

    # 7. Cross-cluster: Pengguna (1) -- Membukukan Pengeluaran -- (N) Pengeluaran Kas
    erd.draw_relationship(
        center=(700, 835),
        size=(168, 82),
        label="Membukukan\nPengeluaran",
        lines=[
            {
                "pts": [(350, 399), (350, 650), (700, 650), (700, 794)],
                "dashed": True,
                "card": "1",
                "card_pos": (370, 428),
            },
            {
                "pts": [(700, 876), (700, 945), (950, 945), (950, 1076)],
                "dashed": True,
                "card": "N",
                "card_pos": (970, 1048),
            },
        ],
    )

    # 8. Cross-cluster: Donatur (1) -- Mengajukan Bantuan -- (N) Pengajuan Bantuan
    erd.draw_relationship(
        center=(1300, 960),
        size=(162, 82),
        label="Mengajukan\nBantuan",
        lines=[
            {
                "pts": [(1000, 399), (1000, 470), (1175, 470), (1175, 835), (1300, 835), (1300, 919)],
                "dashed": True,
                "card": "1",
                "card_pos": (982, 452),
            },
            {
                "pts": [(1381, 960), (1560, 960), (1560, 1076)],
                "dashed": True,
                "card": "N",
                "card_pos": (1580, 1048),
            },
        ],
    )

    # 9. Pengajuan Bantuan (1) -- Memiliki Detail -- (N) Detail Bantuan
    erd.draw_relationship(
        center=(1815, 1115),
        size=(152, 82),
        label="Memiliki\nDetail",
        lines=[
            {"pts": [(1675, 1115), (1739, 1115)], "dashed": False, "card": "1", "card_pos": (1698, 1093)},
            {"pts": [(1891, 1115), (1953, 1115)], "dashed": False, "card": "N", "card_pos": (1930, 1093)},
        ],
    )

    # 10. Kategori Bantuan (1) -- Memiliki Item -- (N) Detail Bantuan
    erd.draw_relationship(
        center=(2065, 1255),
        size=(152, 80),
        label="Memiliki\nItem",
        lines=[
            {"pts": [(2065, 1154), (2065, 1215)], "dashed": False, "card": "N", "card_pos": (2085, 1180)},
            {"pts": [(2065, 1295), (2065, 1356)], "dashed": False, "card": "1", "card_pos": (2085, 1332)},
        ],
    )

    # 11. Kategori Pengeluaran (1) -- Memiliki Pengeluaran -- (N) Pengeluaran Kas
    erd.draw_relationship(
        center=(645, 1115),
        size=(168, 82),
        label="Memiliki\nPengeluaran",
        lines=[
            {"pts": [(462, 1115), (561, 1115)], "dashed": False, "card": "1", "card_pos": (488, 1093)},
            {"pts": [(729, 1115), (838, 1115)], "dashed": False, "card": "N", "card_pos": (812, 1093)},
        ],
    )

    # Entities in Kluster 1
    erd.draw_entity(
        center=(260, 360),
        size=(224, 78),
        title_id="Pengguna",
        table_name="users",
        attrs=[
            ((105, 238), (64, 27), "id (PK)", True),
            ((232, 218), (58, 27), "name", False),
            ((432, 228), (58, 27), "email", False),
            ((115, 485), (56, 27), "role", False),
            ((255, 510), (86, 27), "account_status", False),
        ],
    )

    erd.draw_entity(
        center=(950, 360),
        size=(224, 78),
        title_id="Donatur",
        table_name="donatur",
        attrs=[
            ((810, 228), (64, 27), "id (PK)", True),
            ((950, 214), (58, 27), "nama", False),
            ((1100, 228), (70, 27), "whatsapp", False),
            ((1138, 360), (56, 27), "jenis", False),
        ],
    )

    erd.draw_entity(
        center=(950, 650),
        size=(240, 78),
        title_id="Log Audit Akun",
        table_name="donor_account_audits",
        attrs=[
            ((800, 745), (64, 27), "id (PK)", True),
            ((945, 750), (60, 27), "action", False),
            ((1085, 745), (60, 27), "details", False),
        ],
    )

    # Entities in Kluster 2
    erd.draw_entity(
        center=(1545, 345),
        size=(220, 78),
        title_id="Kategori Donasi",
        table_name="kategori_donasi",
        attrs=[
            ((1470, 470), (64, 27), "id (PK)", True),
            ((1615, 470), (58, 27), "nama", False),
        ],
    )

    erd.draw_entity(
        center=(2065, 345),
        size=(220, 78),
        title_id="Donasi Uang",
        table_name="donasi",
        attrs=[
            ((1895, 218), (64, 27), "id (PK)", True),
            ((2030, 208), (56, 27), "kode", False),
            ((2285, 222), (64, 27), "nominal", False),
            ((2425, 288), (56, 27), "biaya", False),
            ((2435, 365), (60, 27), "sumber", False),
            ((2410, 442), (58, 27), "status", False),
        ],
    )

    erd.draw_entity(
        center=(2065, 645),
        size=(250, 78),
        title_id="Transaksi Pembayaran",
        table_name="payment_transactions",
        attrs=[
            ((1815, 744), (64, 27), "id (PK)", True),
            ((1980, 750), (66, 27), "order_id", False),
            ((2150, 750), (62, 27), "metode", False),
            ((2310, 744), (58, 27), "status", False),
        ],
    )

    # Entities in Kluster 3
    erd.draw_entity(
        center=(1560, 1115),
        size=(230, 78),
        title_id="Pengajuan Bantuan",
        table_name="bantuan",
        attrs=[
            ((1455, 1260), (64, 27), "id (PK)", True),
            ((1595, 1275), (56, 27), "kode", False),
            ((1495, 1372), (92, 27), "tanggal_diterima", False),
            ((1685, 1362), (58, 27), "status", False),
        ],
    )

    erd.draw_entity(
        center=(2065, 1115),
        size=(224, 78),
        title_id="Detail Bantuan",
        table_name="bantuan_items",
        attrs=[
            ((1930, 980), (64, 27), "id (PK)", True),
            ((2065, 970), (58, 27), "nama", False),
            ((2205, 980), (60, 27), "jumlah", False),
            ((2345, 1030), (60, 27), "satuan", False),
        ],
    )

    erd.draw_entity(
        center=(2065, 1395),
        size=(224, 78),
        title_id="Kategori Bantuan",
        table_name="kategori_bantuan",
        attrs=[
            ((1985, 1500), (64, 27), "id (PK)", True),
            ((2145, 1500), (58, 27), "nama", False),
        ],
    )

    # Entities in Kluster 4
    erd.draw_entity(
        center=(340, 1115),
        size=(244, 78),
        title_id="Kategori Pengeluaran",
        table_name="kategori_pengeluaran",
        attrs=[
            ((265, 988), (64, 27), "id (PK)", True),
            ((415, 988), (58, 27), "nama", False),
        ],
    )

    erd.draw_entity(
        center=(950, 1115),
        size=(224, 78),
        title_id="Pengeluaran Kas",
        table_name="pengeluaran",
        attrs=[
            ((1145, 1020), (64, 27), "id (PK)", True),
            ((1155, 1100), (56, 27), "kode", False),
            ((1145, 1180), (62, 27), "tanggal", False),
            ((875, 1252), (64, 27), "nominal", False),
            ((1025, 1252), (58, 27), "status", False),
        ],
    )

    erd.draw_entity(
        center=(340, 1395),
        size=(228, 76),
        title_id="Saldo Pembukaan",
        table_name="saldo_pembukaan",
        attrs=[
            ((120, 1395), (64, 27), "id (PK)", True),
            ((270, 1502), (84, 26), "tanggal_mulai", False),
            ((440, 1502), (64, 26), "nominal", False),
        ],
    )

    erd.draw_entity(
        center=(950, 1395),
        size=(224, 76),
        title_id="Pengaturan Panti",
        table_name="settings",
        attrs=[
            ((800, 1502), (64, 26), "id (PK)", True),
            ((950, 1502), (54, 26), "key", False),
            ((1095, 1502), (58, 26), "value", False),
        ],
    )

    out_path = OUT_DIR / "erd-chen-full.png"
    erd.im.save(out_path)
    # Also save to 07-relasi-akun-donasi.png and erd-donasi.png so any legacy script references stay updated
    shutil.copy2(out_path, OUT_DIR / "07-relasi-akun-donasi.png")
    shutil.copy2(out_path, PROP_DIR / "erd-donasi.png")
    if ARTIFACT_DIR.exists():
        shutil.copy2(out_path, ARTIFACT_DIR / "erd_an_nuur_2_chen.png")

    # Update .drawio file if it exists
    if DRAWIO_PATH.exists():
        tree = E.parse(str(DRAWIO_PATH))
        mxfile = tree.getroot()
        for diag in list(mxfile.findall("diagram")):
            if diag.get("name") in ("07-relasi-akun-donasi", "08-relasi-bantuan-kas", "07-erd-chen-full"):
                mxfile.remove(diag)
        # Insert at index 6 (after 06-activity-pengeluaran)
        mxfile.insert(6, erd.mx_diag)
        tree.write(str(DRAWIO_PATH), encoding="utf-8", xml_declaration=True, pretty_print=True)
        print("Updated drawio:", DRAWIO_PATH)

    return out_path


def update_docx(doc_path: Path, erd_img_path: Path):
    if not doc_path.exists():
        return
    d = Document(str(doc_path))
    ps = list(d.paragraphs)

    # Find Gambar 3.7 and Gambar 3.8 in the document
    idx_g37_img = None
    idx_g38_img = None
    for i, p in enumerate(ps):
        if p._p.xpath(".//a:blip") and i + 1 < len(ps):
            cap = ps[i + 1].text.strip()
            if cap.startswith("Gambar 3.7"):
                idx_g37_img = i
            elif cap.startswith("Gambar 3.8 ") and "bantuan" in cap.lower():
                idx_g38_img = i

    if idx_g37_img is not None:
        p_img = ps[idx_g37_img]
        p_img.clear()
        p_img.alignment = WD_ALIGN_PARAGRAPH.CENTER
        p_img.paragraph_format.first_line_indent = Cm(0)
        p_img.paragraph_format.keep_with_next = True
        p_img.paragraph_format.space_before = Pt(6)
        p_img.paragraph_format.space_after = Pt(3)
        p_img.add_run().add_picture(str(erd_img_path), width=Cm(14))

        p_cap = ps[idx_g37_img + 1]
        p_cap.text = "Gambar 3.7 Entity Relationship Diagram (ERD) Sistem Informasi Pengelolaan Donasi An-Nuur 2"
        for r in p_cap.runs:
            r.font.name = "Times New Roman"
            r.font.size = Pt(11)
            r.font.color.rgb = RGBColor(0, 0, 0)

    # If the second split relational diagram (Gambar 3.8 Relasi data bantuan dan pengeluaran) exists, remove it and renumber 3.9..3.12 -> 3.8..3.11
    if idx_g38_img is not None:
        # Remove image paragraph, caption paragraph, and "Sumber: Rancangan penulis" paragraph
        to_remove = [ps[idx_g38_img], ps[idx_g38_img + 1]]
        if idx_g38_img + 2 < len(ps) and ps[idx_g38_img + 2].text.strip().startswith("Sumber:"):
            to_remove.append(ps[idx_g38_img + 2])
        for rem_p in to_remove:
            rem_p._p.getparent().remove(rem_p._p)

        # Renumber subsequent Gambar 3.9 -> 3.8, 3.10 -> 3.9, 3.11 -> 3.10, 3.12 -> 3.11
        renumber_map = {
            "Gambar 3.9 ": "Gambar 3.8 ",
            "Gambar 3.10 ": "Gambar 3.9 ",
            "Gambar 3.11 ": "Gambar 3.10 ",
            "Gambar 3.12 ": "Gambar 3.11 ",
        }
        for p in d.paragraphs:
            for old_prefix, new_prefix in renumber_map.items():
                if p.text.startswith(old_prefix):
                    p.text = new_prefix + p.text[len(old_prefix) :]
                    for r in p.runs:
                        r.font.name = "Times New Roman"
                        r.font.size = Pt(11)
                        r.font.color.rgb = RGBColor(0, 0, 0)
                    break

    # Update prose paragraph explaining the ERD notation
    for p in d.paragraphs:
        if p.text.startswith("Notasi 1 : N menunjukkan") or p.text.startswith("Diagram disajikan menggunakan notasi Chen"):
            p.text = (
                "Diagram disajikan menggunakan notasi Chen yang dikelompokkan ke dalam empat kluster fungsional. "
                "Persegi panjang merepresentasikan entitas beserta nama tabel fisik dalam tanda kurung, elips "
                "merepresentasikan atribut utama dengan garis bawah pada atribut kunci utama (PK), dan belah ketupat "
                "merepresentasikan relasi antarentitas. Garis tegas menunjukkan relasi di dalam kluster yang sama, "
                "sedangkan garis putus-putus menunjukkan relasi lintas kluster. Notasi kardinalitas 1 : N menunjukkan "
                "satu data induk dapat berelasi dengan banyak data anak, sedangkan 1 : 0..1 menunjukkan hubungan "
                "opsional paling banyak satu data. Gambar memperlihatkan entitas dan relasi inti, sedangkan rincian "
                "tabel serta data pendukung dirangkum pada tabel berikut."
            )
            for r in p.runs:
                r.font.name = "Times New Roman"
                r.font.size = Pt(12)
                r.font.color.rgb = RGBColor(0, 0, 0)

    d.save(str(doc_path))
    print("Updated docx:", doc_path)


if __name__ == "__main__":
    img_path = build_erd()
    update_docx(DOC_MAIN, img_path)
    update_docx(DOC_UJIAN, img_path)
