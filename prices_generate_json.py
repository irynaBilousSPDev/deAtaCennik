"""
Generate calculator JSON for the Prices page from a Google Sheet (xlsx export).

Dependencies:
  pip install pandas openpyxl requests

Usage:
  python prices_generate_json.py --sheet "https://docs.google.com/spreadsheets/d/<ID>/edit#gid=0"
  python prices_generate_json.py --sheet "<ID>" --out kalkulator-data.json

This script matches your current sheet tabs (with emojis):
  - 🔗 SmartApply_URLs
  - 🎓 Programy_PL
  - 🌍 Programy_EN
  - 🇺🇦 Ceny_UABY   (row 1 is instructional text, skipped)
  - 🏷️ Promocje

Output JSON (top-level keys) for `assets/src/js/prices-calculator.js`:
  BASE, BASE_EN, SA, SA_EN, RAW, UABY, PROMOS
"""

from __future__ import annotations

import argparse
import io
import json
import re
import sys
from typing import Any, Dict, List, Optional, Tuple

import pandas as pd
import requests


DEFAULT_BASE = "https://smartapply.akademiata.pl/pl/apply/"
DEFAULT_BASE_EN = "https://smartapply.akademiata.pl/en/apply/"

TAB_SMARTAPPLY = "🔗 SmartApply_URLs"
TAB_PL = "🎓 Programy_PL"
TAB_EN = "🌍 Programy_EN"
TAB_UABY = "🇺🇦 Ceny_UABY"
TAB_PROMOS = "🏷️ Promocje"

def _safe_print(msg: str) -> None:
    """
    Windows consoles can fail printing emojis (cp1250/cp852). Avoid crashing.
    """
    try:
        print(msg)
    except UnicodeEncodeError:
        print(msg.encode(sys.stdout.encoding or "utf-8", errors="replace").decode(sys.stdout.encoding or "utf-8", errors="replace"))


def _sheet_id_from_input(sheet: str) -> str:
    sheet = sheet.strip()
    # Full URL
    m = re.search(r"/spreadsheets/d/([a-zA-Z0-9-_]+)", sheet)
    if m:
        return m.group(1)
    # Raw ID
    if re.fullmatch(r"[a-zA-Z0-9-_]{20,}", sheet):
        return sheet
    raise ValueError("Invalid Google Sheet input. Provide full URL or sheet ID.")


def _xlsx_export_url(sheet_id: str) -> str:
    return f"https://docs.google.com/spreadsheets/d/{sheet_id}/export?format=xlsx"


def _download_xlsx_bytes(url: str, timeout_s: int = 60) -> bytes:
    r = requests.get(url, timeout=timeout_s)
    r.raise_for_status()
    return r.content


def _norm_str(v: Any) -> str:
    if v is None:
        return ""
    if isinstance(v, float) and pd.isna(v):
        return ""
    return str(v).strip()


def _to_int(v: Any, default: int = 0) -> int:
    s = _norm_str(v)
    if s == "":
        return default
    # allow "1 200" "1,200" "1200.0"
    s = s.replace(" ", "").replace(",", ".")
    try:
        return int(float(s))
    except Exception:
        return default


def _detect_city_key(v: str) -> str:
    v = v.lower()
    if "warsz" in v or "wwa" in v:
        return "wwa"
    if "wroc" in v or "wro" in v:
        return "wro"
    # fallback
    return "wwa"


def _detect_mode_key(v: str) -> str:
    v = v.lower()
    if "niest" in v or v in {"n", "niestacjonarne"}:
        return "n"
    # IMPORTANT: check "niest" before "stac" because "niestacjonarne" contains "stac"
    if "stac" in v or v in {"s", "stacjonarne"}:
        return "s"
    # fallback
    return "s"


def _read_sheet(xls: pd.ExcelFile, name: str) -> Optional[pd.DataFrame]:
    if name not in xls.sheet_names:
        _safe_print(f"Warning: missing tab '{name}', skipping.")
        return None
    df = pd.read_excel(xls, name)
    return df.fillna("")

def _extract_slug(url: Any) -> str:
    """Extract last URL segment for page slug matching the JS logic."""
    url = _norm_str(url)
    if not url or "akademiata.pl" not in url:
        return ""
    parts = [p for p in url.split("/") if p]
    return parts[-1] if parts else ""


def _parse_programy_pl(df: pd.DataFrame) -> Dict[str, Dict[str, List[Dict[str, Any]]]]:
    """
    Produces RAW.pl.<city>.<mode> arrays.

    Expected columns (case-sensitive, but we also try small aliases):
      - Miasto
      - Forma
      - Kierunek
      - Specjalność
      - Stopień
      - R10 (rata 10-mies.)
      - R12 (rata 12-mies.)
      - Opłata rekrutacyjna
      - Wpisowe
      - URL strony ATA (pełny link)   (can be slug or full URL)
      - Klucz SmartApply
      - Klucz (Google Sheets / logical_sync_key)   (optional but recommended)
    """

    # Column aliases (match your exact headers, with tolerant fallbacks)
    col = {
        "miasto": next((c for c in df.columns if c.lower() == "miasto"), None),
        "forma": next((c for c in df.columns if c.lower() == "forma"), None),
        "kierunek": next((c for c in df.columns if c.lower() == "kierunek"), None),
        "spec": next((c for c in df.columns if c.lower().startswith("specjal")), None),
        "stopien": next((c for c in df.columns if c.lower().startswith("stop")), None),
        "r10": next((c for c in df.columns if "r10" in c.lower()), None),
        "r12": next((c for c in df.columns if "r12" in c.lower()), None),
        "rekr": next((c for c in df.columns if "rekrut" in c.lower()), None),
        "wps": next((c for c in df.columns if c.lower().startswith("wpis")), None),
        "ps": next((c for c in df.columns if "url" in c.lower() and "ata" in c.lower()), None),
        "ak": next((c for c in df.columns if "klucz smartapply" in c.lower() or "smartapply" in c.lower()), None),
        # Logical key used to match single offer pages (e.g. 1_wwa_architektura)
        "lk": next(
            (
                c
                for c in df.columns
                if "klucz" in c.lower()
                and "smartapply" not in c.lower()
                and ("google" in c.lower() or "sheets" in c.lower() or "logical" in c.lower() or "sync" in c.lower())
            ),
            None,
        ),
    }

    missing = [k for k, v in col.items() if v is None and k in {"miasto", "forma", "kierunek", "stopien"}]
    if missing:
        raise ValueError(f"Programy_PL: missing required columns: {', '.join(missing)}")

    out: Dict[str, Dict[str, List[Dict[str, Any]]]] = {"wwa": {"s": [], "n": []}, "wro": {"s": [], "n": []}}

    for _, row in df.iterrows():
        city_key = _detect_city_key(_norm_str(row[col["miasto"]]))
        mode_key = _detect_mode_key(_norm_str(row[col["forma"]]))

        spec = _norm_str(row[col["spec"]]) if col["spec"] else ""
        course = {
            "k": _norm_str(row[col["kierunek"]]),
            "s": spec if spec != "" else None,
            "deg": _to_int(row[col["stopien"]], 0),
            "r10": _to_int(row[col["r10"]], 0) if col["r10"] else 0,
            "r12": _to_int(row[col["r12"]], 0) if col["r12"] else 0,
            "rekr": _to_int(row[col["rekr"]], 0) if col["rekr"] else 0,
            "wps": _to_int(row[col["wps"]], 0) if col["wps"] else 0,
            # JS expects slug, not full URL
            "ps": _extract_slug(row[col["ps"]]) if col["ps"] else "",
            "ak": _norm_str(row[col["ak"]]) if col["ak"] else "",
            "lk": _norm_str(row[col["lk"]]) if col["lk"] else "",
        }

        # Skip empty rows
        if not course["k"] or not course["deg"]:
            continue

        out[city_key][mode_key].append(course)

    return out


def _parse_programy_en(df: pd.DataFrame) -> Dict[str, List[Dict[str, Any]]]:
    """
    Produces RAW.en.<city> arrays (English programs).

    Expected columns (examples; adjust your sheet headers if needed):
      - City
      - Program
      - Specialization
      - Degree
      - EU Annual
      - EU Semester
      - Non-EU Annual
      - Non-EU Semester
      - Recruitment fee
      - Enrollment fee (or Wpisowe)
      - ATA page slug (or full url)
      - SmartApply key
    """

    # Match your EN headers (with tolerant fallbacks)
    col = {
        "city": next((c for c in df.columns if c.lower() == "miasto" or c.lower() == "city"), None),
        "k": next((c for c in df.columns if c.lower() == "kierunek" or c.lower() == "program"), None),
        "s": next((c for c in df.columns if c.lower().startswith("specjal") or "special" in c.lower()), None),
        "deg": next((c for c in df.columns if c.lower().startswith("stop") or c.lower() == "degree"), None),
        "eu_r": next((c for c in df.columns if "eu/cis" in c.lower() and "rok" in c.lower()), None),
        "eu_s": next((c for c in df.columns if "eu/cis" in c.lower() and "semestr" in c.lower()), None),
        "ne_r": next((c for c in df.columns if "non-eu" in c.lower() and "rok" in c.lower()), None),
        "ne_s": next((c for c in df.columns if "non-eu" in c.lower() and "semestr" in c.lower()), None),
        "rekr": next((c for c in df.columns if "opłata rekrutacyjna" in c.lower() or "recruit" in c.lower()), None),
        "wps": next((c for c in df.columns if c.lower().startswith("wpis") or "enroll" in c.lower()), None),
        "ps": next((c for c in df.columns if "url" in c.lower() and "ata" in c.lower()), None),
        "ak": next((c for c in df.columns if "klucz smartapply" in c.lower() or "smartapply" in c.lower()), None),
        "lk": next(
            (
                c
                for c in df.columns
                if "klucz" in c.lower()
                and "smartapply" not in c.lower()
                and ("google" in c.lower() or "sheets" in c.lower() or "logical" in c.lower() or "sync" in c.lower())
            ),
            None,
        ),
    }

    if col["city"] is None or col["k"] is None or col["deg"] is None:
        raise ValueError("Programy_EN: missing required columns (City/Program/Degree).")

    out: Dict[str, List[Dict[str, Any]]] = {"wwa": [], "wro": []}
    for _, row in df.iterrows():
        city_key = _detect_city_key(_norm_str(row[col["city"]]))
        spec = _norm_str(row[col["s"]]) if col["s"] else ""

        item = {
            "k": _norm_str(row[col["k"]]),
            "s": spec if spec != "" else None,
            "deg": _to_int(row[col["deg"]], 0),
            "eu": {"r": _to_int(row[col["eu_r"]], 0) if col["eu_r"] else 0, "s": _to_int(row[col["eu_s"]], 0) if col["eu_s"] else 0},
            "ne": {"r": _to_int(row[col["ne_r"]], 0) if col["ne_r"] else 0, "s": _to_int(row[col["ne_s"]], 0) if col["ne_s"] else 0},
            "rekr": _to_int(row[col["rekr"]], 0) if col["rekr"] else 0,
            "wps": _to_int(row[col["wps"]], 0) if col["wps"] else 0,
            "ps": _extract_slug(row[col["ps"]]) if col["ps"] else "",
            "ak": _norm_str(row[col["ak"]]) if col["ak"] else "",
            "lk": _norm_str(row[col["lk"]]) if col["lk"] else "",
        }
        if not item["k"] or not item["deg"]:
            continue
        out[city_key].append(item)

    return out


def _parse_sa_map(df: pd.DataFrame) -> Dict[str, str]:
    raise NotImplementedError("Not used for the current emoji-based sheet.")


def _parse_uaby(df: pd.DataFrame) -> Dict[str, Dict[str, Dict[int, Dict[str, int]]]]:
    # Matches your headers:
    #   Język, Kierunek, Stopień, Opłata roczna (EUR), Opłata semestralna (EUR)
    lang_col = next((c for c in df.columns if c.lower() in {"język", "jezyk", "lang"}), None)
    prog_col = next((c for c in df.columns if c.lower() == "kierunek" or c.lower() == "program"), None)
    deg_col = next((c for c in df.columns if c.lower().startswith("stop")), None)
    ann_col = next((c for c in df.columns if "opłata roczna" in c.lower() or "annual" in c.lower()), None)
    sem_col = next((c for c in df.columns if "opłata semestralna" in c.lower() or "semester" in c.lower()), None)
    if not (lang_col and prog_col and deg_col and ann_col and sem_col):
        raise ValueError("Ceny_UABY: missing required columns.")

    out: Dict[str, Dict[str, Dict[int, Dict[str, int]]]] = {"pl": {}, "en": {}}
    for _, row in df.iterrows():
        lng = _norm_str(row[lang_col]).lower() or "pl"
        if lng not in out:
            continue
        prog = _norm_str(row[prog_col])
        deg = _to_int(row[deg_col], 0)
        if not prog or not deg:
            continue
        ann = _to_int(row[ann_col], 0)
        sem = _to_int(row[sem_col], 0) if sem_col else 0

        out[lng].setdefault(prog, {})
        out[lng][prog][deg] = {"r": ann, "s": sem}
    return out


def _parse_promos(df: pd.DataFrame) -> List[Dict[str, Any]]:
    """
    Promos are the most custom part. This parser expects the JSON-ish fields
    already prepared in the sheet (or simple columns).

    Minimal expected columns:
      - id, lng, deg, cty, name, tag, short, full
    Optional:
      - sw (comma-separated ids)
      - isBonus (true/false)
      - disc_t (pct/fix/bonus)
      - disc_v (number)
      - needRok (true/false)
      - so (JSON string for suboptions) OR columns so_1_v/so_1_l etc.
    """
    # Matches your headers:
    #   Aktywna, ID, Język, Min. stopień (0=oba), Miasto,
    #   Nazwa, Tag skrócony, Opis skrócony (1 zdanie), Uwagi / warunki,
    #   Łączy się z (ID po przecinku), Typ rabatu (fix/pct/bonus), Wartość rabatu (PLN lub %)
    col_active = next((c for c in df.columns if c.lower() == "aktywna"), None)
    col_id = next((c for c in df.columns if c.lower() == "id"), None)
    col_lang = next((c for c in df.columns if c.lower() in {"język", "jezyk", "lng"}), None)
    col_deg = next((c for c in df.columns if "min. stopień" in c.lower() or "min. stopien" in c.lower()), None)
    col_city = next((c for c in df.columns if c.lower() == "miasto"), None)
    col_name = next((c for c in df.columns if c.lower() == "nazwa"), None)
    col_tag = next((c for c in df.columns if "tag" in c.lower()), None)
    col_short = next((c for c in df.columns if "opis skrócony" in c.lower() or "opis skrocony" in c.lower()), None)
    col_full = next((c for c in df.columns if "uwagi" in c.lower()), None)
    col_sw = next((c for c in df.columns if "łączy się z" in c.lower() or "laczy sie z" in c.lower()), None)
    col_disc_t = next((c for c in df.columns if "typ rabatu" in c.lower()), None)
    col_disc_v = next((c for c in df.columns if "wartość rabatu" in c.lower() or "wartosc rabatu" in c.lower()), None)

    required_cols = [col_active, col_id, col_lang, col_deg, col_city, col_name, col_tag, col_short, col_full, col_disc_t, col_disc_v]
    if any(c is None for c in required_cols):
        raise ValueError("Promocje: missing required columns (check headers).")

    promos: List[Dict[str, Any]] = []
    for _, row in df.iterrows():
        if _norm_str(row[col_active]).upper() != "TAK":
            continue

        pid = _norm_str(row[col_id])
        if not pid:
            continue

        city_val = _norm_str(row[col_city]).lower()
        if "obie" in city_val:
            cty = "both"
        elif "warsz" in city_val:
            cty = "wwa"
        elif "wroc" in city_val:
            cty = "wro"
        else:
            cty = "both"

        sw_raw = _norm_str(row[col_sw]) if col_sw else ""
        sw = [x.strip() for x in sw_raw.split(",") if x.strip()] if sw_raw else []

        disc_t = _norm_str(row[col_disc_t]).lower() or "fix"
        disc_v_raw = _norm_str(row[col_disc_v])
        disc_v: float = 0.0
        if disc_t in {"fix", "pct"} and disc_v_raw:
            nums = re.findall(r"[-+]?\d*\.\d+|\d+", disc_v_raw.replace(",", "."))
            if nums:
                disc_v = float(nums[0])
                if disc_t == "pct" and disc_v > 1:
                    disc_v = disc_v / 100.0

        promo: Dict[str, Any] = {
            "id": pid,
            "lng": _norm_str(row[col_lang]).lower(),
            "deg": _to_int(row[col_deg], 0),
            "cty": cty,
            "name": _norm_str(row[col_name]),
            "tag": _norm_str(row[col_tag]),
            "short": _norm_str(row[col_short]),
            "full": _norm_str(row[col_full]),
            "sw": sw,
            "isBonus": disc_t == "bonus",
            "disc": {"t": disc_t, "v": disc_v},
        }

        # Optional behavior flag (required by your calculator logic for Early Birds)
        if pid == "earlybirds":
            promo["needRok"] = True

        # Optional sub-options: try to parse from columns if present; otherwise provide safe defaults
        if pid == "grupie":
            promo["so"] = [{"v": 200, "l": "2–4 osoby (−200 zł)"}, {"v": 400, "l": "5+ osób (−400 zł)"}]
        elif pid == "absolwent_pl":
            promo["so"] = [{"v": 0.20, "l": "Wynik standardowy (−20%)"}, {"v": 0.30, "l": "Wynik 5,0 / Wrocław (−30%)"}]

        promos.append(promo)

    return promos


def generate_json(sheet: str, out_path: str) -> None:
    sheet_id = _sheet_id_from_input(sheet)
    export_url = _xlsx_export_url(sheet_id)

    _safe_print("Downloading data from Google Sheets...")
    try:
        content = _download_xlsx_bytes(export_url)
        xls = pd.ExcelFile(io.BytesIO(content))
    except Exception as e:
        raise RuntimeError(
            f"Error reading file from Google Sheets: {e}\n"
            f"Make sure sharing is set to 'Anyone with the link can view'.\n"
            f"Export URL: {export_url}"
        ) from e

    _safe_print("Download successful! Processing data...")

    data: Dict[str, Any] = {
        "BASE": DEFAULT_BASE,
        "BASE_EN": DEFAULT_BASE_EN,
        "SA": {},
        "SA_EN": {},
        "RAW": {
            "pl": {"wwa": {"s": [], "n": []}, "wro": {"s": [], "n": []}},
            "en": {"wwa": [], "wro": []},
        },
        "UABY": {"pl": {}, "en": {}},
        "PROMOS": [],
    }

    # --- 🔗 SmartApply_URLs ---
    df = _read_sheet(xls, TAB_SMARTAPPLY)
    if df is not None:
        try:
            # Support BOTH formats:
            # 1) New format (2026): Lang | Klucz SmartApply | ... | URL SmartApply
            #    - PL and EN use different keys and full URLs.
            # 2) Old format: Klucz (ak) | URL PL (SmartApply) | URL EN (SmartApply)

            cols_lower = {c.lower(): c for c in df.columns}
            lang_col = cols_lower.get("lang")
            url_col = next((c for c in df.columns if "url" in c.lower() and "smartapply" in c.lower()), None)
            key_col_new = next((c for c in df.columns if "klucz" in c.lower() and "smartapply" in c.lower()), None)

            is_new = bool(lang_col and url_col and key_col_new)

            if is_new:
                for _, row in df.iterrows():
                    lng = _norm_str(row.get(lang_col, "")).lower()
                    k = _norm_str(row.get(key_col_new, ""))
                    url = _norm_str(row.get(url_col, ""))
                    if not k or not url:
                        continue
                    if lng == "en":
                        data["SA_EN"][k] = url
                    else:
                        data["SA"][k] = url
                _safe_print("Parsed: SmartApply_URLs (new format)")
            else:
                key_col = "Klucz (ak)"
                pl_col = "URL PL (SmartApply)"
                en_col = "URL EN (SmartApply)"
                for _, row in df.iterrows():
                    k = _norm_str(row.get(key_col, ""))
                    if not k:
                        continue
                    url_pl = _norm_str(row.get(pl_col, ""))
                    url_en = _norm_str(row.get(en_col, ""))
                    if url_pl:
                        data["SA"][k] = url_pl.replace(DEFAULT_BASE, "")
                    if url_en:
                        data["SA_EN"][k] = url_en.replace(DEFAULT_BASE_EN, "")
                _safe_print("Parsed: SmartApply_URLs (old format)")
        except Exception as e:
            _safe_print(f"Warning: SmartApply_URLs parse failed: {e}")

    # --- 🎓 Programy_PL ---
    df = _read_sheet(xls, TAB_PL)
    if df is not None:
        try:
            data["RAW"]["pl"] = _parse_programy_pl(df)
            _safe_print("Parsed: Programy_PL")
        except Exception as e:
            _safe_print(f"Warning: Programy_PL parse failed: {e}")

    # --- 🌍 Programy_EN ---
    df = _read_sheet(xls, TAB_EN)
    if df is not None:
        try:
            data["RAW"]["en"] = _parse_programy_en(df)
            _safe_print("Parsed: Programy_EN")
        except Exception as e:
            _safe_print(f"Warning: Programy_EN parse failed: {e}")

    # --- 🇺🇦 Ceny_UABY (skip first row with instructions) ---
    if TAB_UABY in xls.sheet_names:
        try:
            df = pd.read_excel(xls, TAB_UABY, skiprows=1).fillna("")
            data["UABY"] = _parse_uaby(df)
            _safe_print("Parsed: Ceny_UABY")
        except Exception as e:
            _safe_print(f"Warning: Ceny_UABY parse failed: {e}")
    else:
        _safe_print("Warning: missing tab 'Ceny_UABY', skipping.")

    # --- 🏷️ Promocje ---
    df = _read_sheet(xls, TAB_PROMOS)
    if df is not None:
        try:
            data["PROMOS"] = _parse_promos(df)
            _safe_print("Parsed: Promocje")
        except Exception as e:
            _safe_print(f"Warning: Promocje parse failed: {e}")

    with open(out_path, "w", encoding="utf-8") as f:
        json.dump(data, f, ensure_ascii=False, indent=2)

    _safe_print(f"Success! Generated {out_path}")


def main() -> None:
    ap = argparse.ArgumentParser()
    ap.add_argument("--sheet", required=True, help="Google Sheet URL or sheet ID")
    ap.add_argument("--out", default="kalkulator-data.json", help="Output JSON path")
    args = ap.parse_args()

    generate_json(args.sheet, args.out)


if __name__ == "__main__":
    main()

