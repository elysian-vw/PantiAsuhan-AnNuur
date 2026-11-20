from pathlib import Path
import shutil
from lxml import etree as E
from docx import Document
from docx.shared import Pt, Cm, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH

from generate_chen_erd import build_erd

WORKSPACE_PROP_DIR = Path(r"D:/apps/laragon/www/projectTA/Proposal LA Web Mobile")
POLTEKK_PROP_DIR = Path(r"D:/poltekk/SEMESTER 5/PROPOSAL LAPORAN AKHIR/Proposal LA Web Mobile")
POLTEKK_UJIAN_DOC = Path(r"D:/poltekk/SEMESTER 5/PROPOSAL LAPORAN AKHIR/UJIAN PROPOSAL/Proposal LA.docx")

BASE_DOC = WORKSPACE_PROP_DIR / "Sistem Informasi Pengelolaan Donasi (draft1).docx"
BASE_DRAWIO = WORKSPACE_PROP_DIR / "Diagram_Proposal_LA_An_Nuur_2.drawio"
ERD_IMG = Path(r"d:/apps/laragon/www/projectTA/tmp/proposal-la/revision-20261024/erd-chen-full.png")


def apply_erd_to_latest_doc():
    # Make a backup of BASE_DOC before modifying
    backup = Path(r"d:/apps/laragon/www/projectTA/tmp/proposal-la/revision-20261024/draft1-before-erd-update.docx")
    if not backup.exists():
        shutil.copy2(BASE_DOC, backup)

    d = Document(str( backup ))
    ps = list(d.paragraphs)

    # Verify Gambar 3.1 Use case is intact
    for i, p in enumerate(ps):
        if p.text.startswith("Gambar 3.1"):
            print("Verified Use Case caption:", p.text)

    # Find Gambar 3.5 (Relasi data akun dan donasi) and Gambar 3.6 (Relasi data bantuan dan pengeluaran)
    idx_erd1_img = None
    idx_erd2_img = None
    for i, p in enumerate(ps):
        if p._p.xpath(".//a:blip") and i + 1 < len(ps):
            cap = ps[i + 1].text.strip()
            if cap.startswith("Gambar 3.5") and "Relasi data" in cap:
                idx_erd1_img = i
            elif cap.startswith("Gambar 3.6") and "Relasi data" in cap:
                idx_erd2_img = i

    if idx_erd1_img is not None:
        p_img = ps[idx_erd1_img]
        p_img.clear()
        p_img.alignment = WD_ALIGN_PARAGRAPH.CENTER
        p_img.paragraph_format.first_line_indent = Cm(0)
        p_img.paragraph_format.keep_with_next = True
        p_img.paragraph_format.space_before = Pt(6)
        p_img.paragraph_format.space_after = Pt(3)
        p_img.add_run().add_picture(str(ERD_IMG), width=Cm(14))

        p_cap = ps[idx_erd1_img + 1]
        p_cap.text = "Gambar 3.5 Entity Relationship Diagram (ERD) Sistem Informasi Pengelolaan Donasi An-Nuur 2"
        for r in p_cap.runs:
            r.font.name = "Times New Roman"
            r.font.size = Pt(11)
            r.font.color.rgb = RGBColor(0, 0, 0)

    if idx_erd2_img is not None:
        to_remove = [ps[idx_erd2_img], ps[idx_erd2_img + 1]]
        if idx_erd2_img + 2 < len(ps) and ps[idx_erd2_img + 2].text.strip().startswith("Sumber:"):
            to_remove.append(ps[idx_erd2_img + 2])
        for rem_p in to_remove:
            rem_p._p.getparent().remove(rem_p._p)

        renumber_map = {
            "Gambar 3.7 ": "Gambar 3.6 ",
            "Gambar 3.8 ": "Gambar 3.7 ",
            "Gambar 3.9 ": "Gambar 3.8 ",
            "Gambar 3.10 ": "Gambar 3.9 ",
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
                "tabel inti dan pendukung dirangkum pada Tabel 3.6."
            )
            for r in p.runs:
                r.font.name = "Times New Roman"
                r.font.size = Pt(12)
                r.font.color.rgb = RGBColor(0, 0, 0)

    # Save to all active report files so all copies are synchronized with the latest Use Case + new Chen ERD
    targets = [
        WORKSPACE_PROP_DIR / "Sistem Informasi Pengelolaan Donasi (draft1).docx",
        WORKSPACE_PROP_DIR / "Proposal_LA_Donasi_Web_Mobile_An_Nuur_2_rev1.docx",
        WORKSPACE_PROP_DIR / "Sistem Informasi Pengelolaan Donasi Berbasis Web dan Mobile pada Panti Asuhan NU A1.docx",
        WORKSPACE_PROP_DIR / "Sistem Informasi Pengelolaan Donasi Berbasis Web dan Mobile pada Panti Asuhan NU An.docx",
        WORKSPACE_PROP_DIR / "Proposal_LA_Donasi_Web_Mobile_An_Nuur_2.docx",
        POLTEKK_PROP_DIR / "Proposal_LA_Donasi_Web_Mobile_An_Nuur_2.docx",
        POLTEKK_UJIAN_DOC,
    ]
    d.save(str(targets[0]))
    for t in targets[1:]:
        if t.parent.exists():
            shutil.copy2(targets[0], t)
            print("Synced docx:", t)


def update_drawio_files():
    # Read erd diagram element from POLTEKK drawio or rebuild
    from generate_chen_erd import ChenERD
    # Let's parse WORKSPACE_PROP_DIR / Diagram_Proposal_LA_An_Nuur_2.drawio which has 01-use-case-sistem-terpadu
    backup_drawio = Path(r"d:/apps/laragon/www/projectTA/tmp/proposal-la/revision-20261024/drawio-before-erd-update.drawio")
    if not backup_drawio.exists():
        shutil.copy2(BASE_DRAWIO, backup_drawio)

    tree_ws = E.parse(str(backup_drawio))
    mx_ws = tree_ws.getroot()

    # Get the 07-erd-chen-full diagram element from POLTEKK drawio
    tree_pol = E.parse(str(POLTEKK_PROP_DIR / "Diagram_Proposal_LA_An_Nuur_2.drawio"))
    erd_diag = None
    for diag in tree_pol.getroot().findall("diagram"):
        if diag.get("name") == "07-erd-chen-full":
            erd_diag = diag
            erd_diag.set("name", "05-erd-chen-full")
            break

    if erd_diag is not None:
        for diag in list(mx_ws.findall("diagram")):
            if diag.get("name") in ("05-relasi-akun-donasi", "06-relasi-bantuan-kas", "05-erd-chen-full"):
                mx_ws.remove(diag)
        mx_ws.insert(4, erd_diag)
        tree_ws.write(str(BASE_DRAWIO), encoding="utf-8", xml_declaration=True, pretty_print=True)
        shutil.copy2(BASE_DRAWIO, POLTEKK_PROP_DIR / "Diagram_Proposal_LA_An_Nuur_2.drawio")
        print("Synced drawio with 01-use-case-sistem-terpadu and 05-erd-chen-full")


if __name__ == "__main__":
    apply_erd_to_latest_doc()
    update_drawio_files()
