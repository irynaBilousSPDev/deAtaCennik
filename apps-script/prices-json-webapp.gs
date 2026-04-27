/**
 * Akademia TA – Prices JSON Web App
 *
 * - doGet(): returns cached JSON fast
 * - onEdit(e): refreshes cache when spreadsheet edited (simple trigger)
 * - forceManualUpdate(): manual cache rebuild
 *
 * Web app:
 * - /exec                 -> cached fast
 * - /exec?force=1         -> bypass cache, rebuild now
 */
 
const CACHE_KEY = "ata_data_live";
const CACHE_TTL_SECONDS = 21600; // 6 hours
 
function doGet(e) {
  const cache = CacheService.getScriptCache();
 
  const force = !!(e && e.parameter && e.parameter.force === "1");
  if (!force) {
    const cached = cache.get(CACHE_KEY);
    if (cached != null) {
      return ContentService.createTextOutput(cached).setMimeType(
        ContentService.MimeType.JSON
      );
    }
  }
 
  const jsonString = JSON.stringify(generateJSON());
  cache.put(CACHE_KEY, jsonString, CACHE_TTL_SECONDS);
 
  return ContentService.createTextOutput(jsonString).setMimeType(
    ContentService.MimeType.JSON
  );
}
 
// Simple trigger: runs on user edits in the spreadsheet
function onEdit(e) {
  refreshCache_();
}
 
// Manual: click Run to force refresh
function forceManualUpdate() {
  refreshCache_();
}
 
function refreshCache_() {
  const cache = CacheService.getScriptCache();
  const jsonString = JSON.stringify(generateJSON());
  cache.put(CACHE_KEY, jsonString, CACHE_TTL_SECONDS);
}
 
// Helpers
function clean(val) {
  return val === undefined || val === null || val === "—" ? "" : String(val).trim();
}
 
function escHtml_(s) {
  return String(s)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}
 
function richToHtml_(rich) {
  if (!rich) return "";
  const text = rich.getText() || "";
  if (!text) return "";
 
  const runs = rich.getRuns();
  if (!runs || !runs.length) return clean(text);
 
  let out = "";
  for (const r of runs) {
    const t = escHtml_(r.getText() || "");
    const ts = r.getTextStyle ? r.getTextStyle() : null;
    const isBold = ts && typeof ts.isBold === "function" ? ts.isBold() : false;
    out += isBold ? `<strong>${t}</strong>` : t;
  }
 
  // Keep new lines
  return out.replace(/\r\n|\r|\n/g, "<br>");
}
 
function generateJSON() {
  const ss = SpreadsheetApp.getActiveSpreadsheet();
 
  const data = {
    SA: {},
    SA_EN: {},
    SA_ROWS: [],
    RAW: { pl: { wwa: { s: [], n: [] }, wro: { s: [], n: [] } }, en: { wwa: [], wro: [] } },
    UABY: { pl: {}, en: {} },
    PROMOS: []
  };
 
  // 🔗 SmartApply_URLs
  const sheetSA = ss.getSheetByName("🔗 SmartApply_URLs");
  if (sheetSA) {
    const rowsSA = sheetSA.getDataRange().getValues();
    // Some sheets have extra rows above the header (notes, blank rows, merged cells),
    // or the header may start far below row 1.
    // Find the first row that looks like a header.
    let headerRowIdx = -1;
    for (let i = 0; i < Math.min(250, rowsSA.length); i++) {
      const row = rowsSA[i] || [];
      const h = row.map(x => clean(x).toLowerCase());
      const hasLang = h.includes("lang");
      const hasKey = h.some(v => v.includes("klucz") && v.includes("smartapply"));
      const hasUrl = h.some(v => v.includes("url") && v.includes("smartapply"));
      if (hasLang && hasKey && hasUrl) { headerRowIdx = i; break; }
    }
    const header = (headerRowIdx >= 0) ? rowsSA[headerRowIdx].map(x => clean(x).toLowerCase()) : ((rowsSA && rowsSA.length) ? rowsSA[0].map(x => clean(x).toLowerCase()) : []);

    // Support BOTH formats:
    // 1) New format (your screenshot):
    //    Lang | Klucz SmartApply | ... | URL SmartApply
    //    where PL and EN have different keys + links.
    // 2) Old format:
    //    key | ... | URL_PL | URL_EN
    const idxLang = header.indexOf("lang");
    const idxKey = header.findIndex(h => h.includes("klucz") && h.includes("smartapply"));
    const idxUrl = header.findIndex(h => h.includes("url") && h.includes("smartapply"));
    const idxCity = header.findIndex(h => h.includes("miasto"));
    const idxDeg = header.findIndex(h => h.includes("stop"));
    const idxProg = header.findIndex(h => h.includes("kierunek"));
    const idxSpec = header.findIndex(h => h.includes("specjal"));

    const isNewFormat = idxLang >= 0 && idxKey >= 0 && idxUrl >= 0;

    const startRow = headerRowIdx >= 0 ? (headerRowIdx + 1) : 1;
    for (let i = startRow; i < rowsSA.length; i++) {
      if (isNewFormat) {
        const lang = clean(rowsSA[i][idxLang]).toLowerCase();
        const key = clean(rowsSA[i][idxKey]);
        const url = clean(rowsSA[i][idxUrl]);
        if (!key || !url) continue;
        if (lang === "en") data.SA_EN[key] = url;
        else data.SA[key] = url; // default to PL

        // Provide row-level data for robust matching (program tabs may not carry the right key).
        const cityRaw = idxCity >= 0 ? clean(rowsSA[i][idxCity]) : "";
        const city = cityRaw.toLowerCase().includes("wroc") ? "wro" : (cityRaw.toLowerCase().includes("warsz") ? "wwa" : "");
        const deg = idxDeg >= 0 ? parseInt(clean(rowsSA[i][idxDeg]), 10) || 0 : 0;
        const prog = idxProg >= 0 ? clean(rowsSA[i][idxProg]) : "";
        const spec = idxSpec >= 0 ? clean(rowsSA[i][idxSpec]) : "";

        data.SA_ROWS.push({
          lang: lang === "en" ? "en" : "pl",
          key: key,
          city: city,
          deg: deg,
          k: prog,
          s: spec,
          url: url
        });
        continue;
      }

      // If we couldn't detect the header, DO NOT assume old positional format.
      // Your new structure has data rows like: PL | 1_wwa_architektura | ... | https://smartapply...
      // We'll infer columns by content.
      const row = rowsSA[i] || [];

      const looksLikeLang = (v) => {
        const s = clean(v).toLowerCase();
        return s === "pl" || s === "en";
      };
      const looksLikeSmartApplyUrl = (v) => {
        const s = clean(v);
        return /^https?:\/\/smartapply\.akademiata\.pl\//i.test(s);
      };
      const looksLikeKey = (v) => {
        const s = clean(v);
        // e.g. "1_wwa_architektura", "2_wro_zarzadzanie-projektami"
        return /^\d+_(wwa|wro)_[a-z0-9-]+$/i.test(s);
      };

      // Try common new-format positions (Lang, Key, URL) by scanning the row.
      let lang = "";
      let key = "";
      let url = "";
      for (let c = 0; c < row.length; c++) {
        if (!lang && looksLikeLang(row[c])) lang = clean(row[c]).toLowerCase();
        if (!key && looksLikeKey(row[c])) key = clean(row[c]);
        if (!url && looksLikeSmartApplyUrl(row[c])) url = clean(row[c]);
      }

      if (lang && key && url) {
        if (lang === "en") data.SA_EN[key] = url;
        else data.SA[key] = url;
        continue;
      }

      // Final fallback: old positional (ONLY if values actually look like URLs).
      const posKey = clean(row[0]);
      const posPl = clean(row[2]);
      const posEn = clean(row[3]);
      if (posKey && (looksLikeSmartApplyUrl(posPl) || looksLikeSmartApplyUrl(posEn))) {
        if (posPl) data.SA[posKey] = posPl;
        if (posEn) data.SA_EN[posKey] = posEn;
      }
    }
  }
 
  // 🎓 Programy_PL
  const sheetPL = ss.getSheetByName("🎓 Programy_PL");
  if (sheetPL) {
    const rowsPL = sheetPL.getDataRange().getValues();
    for (let i = 1; i < rowsPL.length; i++) {
      const city = clean(rowsPL[i][0]).toLowerCase().includes("warszawa") ? "wwa" : "wro";
      const formStr = clean(rowsPL[i][1]).toLowerCase();
 
      const course = {
        k: clean(rowsPL[i][3]),
        s: clean(rowsPL[i][4]) || null,
        deg: parseInt(rowsPL[i][2], 10) || 1,
        r10: parseInt(rowsPL[i][5], 10) || 0,
        r12: parseInt(rowsPL[i][6], 10) || 0,
        rekr: parseInt(rowsPL[i][7], 10) || 0,
        wps: parseInt(rowsPL[i][8], 10) || 0,
        ps: clean(rowsPL[i][9]),
        ak: clean(rowsPL[i][10])
      };
 
      if (formStr.includes("stacjonarne") || formStr.includes("obie")) data.RAW.pl[city].s.push(course);
      if (formStr.includes("niestacjonarne") || formStr.includes("obie")) data.RAW.pl[city].n.push(course);
    }
  }
 
  // 🌍 Programy_EN
  const sheetEN = ss.getSheetByName("🌍 Programy_EN");
  if (sheetEN) {
    const rowsEN = sheetEN.getDataRange().getValues();
    for (let i = 1; i < rowsEN.length; i++) {
      const city = clean(rowsEN[i][0]).toLowerCase().includes("warszawa") ? "wwa" : "wro";
 
      const courseEN = {
        k: clean(rowsEN[i][2]),
        s: clean(rowsEN[i][3]) || null,
        deg: parseInt(rowsEN[i][1], 10) || 1,
        eu: { r: parseInt(rowsEN[i][4], 10) || 0, s: parseInt(rowsEN[i][5], 10) || 0 },
        ne: { r: parseInt(rowsEN[i][6], 10) || 0, s: parseInt(rowsEN[i][7], 10) || 0 },
        rekr: parseInt(rowsEN[i][8], 10) || 0,
        wps: parseInt(rowsEN[i][9], 10) || 0,
        ps: clean(rowsEN[i][10]),
        ak: clean(rowsEN[i][11])
      };
 
      data.RAW.en[city].push(courseEN);
    }
  }
 
  // 🇺🇦 Ceny_UABY
  const sheetUABY = ss.getSheetByName("🇺🇦 Ceny_UABY");
  if (sheetUABY) {
    const rowsUABY = sheetUABY.getDataRange().getValues();
    for (let i = 2; i < rowsUABY.length; i++) {
      const lang = clean(rowsUABY[i][0]).toLowerCase();
      if (lang !== "pl" && lang !== "en") continue;
 
      const k = clean(rowsUABY[i][1]);
      const d = String(parseInt(rowsUABY[i][2], 10) || 1);
 
      if (!data.UABY[lang][k]) data.UABY[lang][k] = {};
      data.UABY[lang][k][d] = {
        r: parseInt(rowsUABY[i][3], 10) || 0,
        s: parseInt(rowsUABY[i][4], 10) || 0,
        rekr: parseInt(rowsUABY[i][6], 10) || 0,
        apl: parseInt(rowsUABY[i][7], 10) || 0
      };
    }
  }
 
  // 🏷️ Promocje (WITH RICH TEXT -> HTML <strong>)
  const sheetPRO = ss.getSheetByName("🏷️ Promocje");
  if (sheetPRO) {
    const range = sheetPRO.getDataRange();
    const rowsPRO = range.getValues();
    const richPRO = range.getRichTextValues(); // IMPORTANT: includes bold runs
 
    for (let i = 1; i < rowsPRO.length; i++) {
      if (clean(rowsPRO[i][4]).toUpperCase() !== "TAK") continue;
 
      const cityVal = clean(rowsPRO[i][3]).toLowerCase();
      const cty = cityVal.includes("obie") ? "both" : (cityVal.includes("warszawa") ? "wwa" : "wro");
 
      const swRaw = clean(rowsPRO[i][10]);
      const sw = swRaw ? swRaw.split(",").map(x => x.trim()) : [];
 
      const tRabatu = clean(rowsPRO[i][8]).toLowerCase();
      const vRaw = clean(rowsPRO[i][9]);
      let val = 0;
 
      if (tRabatu === "fix" || tRabatu === "pct") {
        const match = vRaw.replace(/,/g, ".").match(/[-+]?\d*\.?\d+/);
        if (match) {
          val = parseFloat(match[0]);
          if (tRabatu === "pct" && val > 1) val = val / 100.0;
        }
      }
 
      const promoId = clean(rowsPRO[i][0]);
      const promo = {
        id: promoId,
        lng: clean(rowsPRO[i][1]).toLowerCase(),
        deg: parseInt(rowsPRO[i][2], 10) || 0,
        cty: cty,
        name: clean(rowsPRO[i][5]),
        tag: clean(rowsPRO[i][6]),
        // H (index 7) Opis skrócony (1 zdanie)
        short: richToHtml_(richPRO[i][7]),
        // L (index 11) Uwagi / warunki
        full: richToHtml_(richPRO[i][11]),
        sw: sw,
        isBonus: tRabatu === "bonus",
        disc: { t: tRabatu, v: val }
      };
 
      if (promoId === "grupie") {
        promo.so = [{ v: 200, l: "2–4 osoby (−200 zł)" }, { v: 400, l: "5+ osób (−400 zł)" }];
      } else if (promoId === "absolwent_pl") {
        promo.so = [{ v: 0.20, l: "Wynik standardowy (−20%)" }, { v: 0.30, l: "Wynik 5,0 / Wrocław (−30%)" }];
      } else if (promoId === "earlybirds") {
        promo.needRok = true;
      }
 
      data.PROMOS.push(promo);
    }
  }
 
  return data;
}

