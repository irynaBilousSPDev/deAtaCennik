/**
 * Akademia TA – Prices JSON Web App
 *
 * Copy this ENTIRE file into Google Apps Script (Extensions → Apps Script).
 * Setup guide: apps-script/README.md
 *
 * - doGet(): returns cached JSON fast
 * - onEdit(e): refreshes cache when spreadsheet edited (simple trigger)
 * - forceManualUpdate(): manual cache rebuild
 *
 * Web app:
 * - /exec                 -> cached fast
 * - /exec?force=1         -> bypass cache, rebuild now
 *
 * @version 2026-09-25
 */
 
const CACHE_KEY = "ata_data_live";
const CACHE_TTL_SECONDS = 21600; // 6 hours
const CACHE_MAX_BYTES = 95000; // ScriptCache value limit is 100 KB

function putCacheSafe_(cache, key, value, ttl) {
  if (!value || value.length > CACHE_MAX_BYTES) return;
  try {
    cache.put(key, value, ttl);
  } catch (err) {
    // Payload too large or cache unavailable — serve JSON without caching.
  }
}

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
  putCacheSafe_(cache, CACHE_KEY, jsonString, CACHE_TTL_SECONDS);

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
  const ss = SpreadsheetApp.getActiveSpreadsheet();
  syncRecruitmentSheet_(ss);
  const cache = CacheService.getScriptCache();
  const jsonString = JSON.stringify(generateJSON());
  putCacheSafe_(cache, CACHE_KEY, jsonString, CACHE_TTL_SECONDS);
}
 
// Helpers
function clean(val) {
  return val === undefined || val === null || val === "—" ? "" : String(val).trim();
}

function parseEur_(val) {
  const s = clean(val).replace(/\s/g, "").replace(/€/g, "").replace(/,/g, ".");
  const m = s.match(/[-+]?\d*\.?\d+/);
  return m ? Math.round(parseFloat(m[0])) : 0;
}

function uabyStorageKey_(k, spec) {
  const kk = clean(k);
  const ss = clean(spec);
  if (!ss || ss === "—" || ss === "-") return kk;
  return kk + "|" + ss;
}

function parseTryb_(val) {
  const t = clean(val).toLowerCase();
  if (!t) return "s";
  // Niestacjonarne before Stacjonarne (substring trap).
  if (t === "n" || t.indexOf("niest") === 0 || t.includes("niestacjonarne") || t.includes("zaocz")) return "n";
  if (t === "s" || t.indexOf("stac") === 0 || t.includes("stacjonarne")) return "s";
  return "s";
}

/** Programy_PL Forma → one mode bucket (matches prices_generate_json.py). */
function parseFormaMode_(formStr) {
  const t = clean(formStr).toLowerCase();
  if (!t) return "s";
  if (t.includes("niest") || t === "n") return "n";
  if (t.includes("stac") || t === "s") return "s";
  return "s";
}

/** Column map for 🇺🇦 Ceny_UABY — standard layout: A Język, B Tryb, C Stopień, D Kierunek, E Specjalność. */
function mapUabyColumns_(headerRow) {
  const h = (headerRow || []).map(function (x) { return clean(x).toLowerCase(); });

  let idxLang = h.findIndex(function (x) { return x === "język" || x === "jezyk" || x === "lang"; });
  let idxTryb = h.findIndex(function (x) { return x === "tryb" || x.indexOf("tryb") >= 0; });
  let idxDeg = h.findIndex(function (x) { return x.indexOf("stop") >= 0; });
  let idxK = h.findIndex(function (x) { return x === "kierunek" || x.indexOf("kierunek") >= 0 || x === "program"; });
  let idxSpec = h.findIndex(function (x) { return x.indexOf("specjal") >= 0; });
  let idxAnn = h.findIndex(function (x) {
    return x.indexOf("opłata roczna") >= 0 || x.indexOf("oplata roczna") >= 0 || x.indexOf("annual") >= 0;
  });
  let idxSem = h.findIndex(function (x) {
    return x.indexOf("opłata semestralna") >= 0 || x.indexOf("oplata semestralna") >= 0 || x.indexOf("semester") >= 0;
  });
  let idxRekr = h.findIndex(function (x) { return x.indexOf("rekrutacyjna") >= 0 || x === "rekr"; });
  let idxApl = h.findIndex(function (x) { return x.indexOf("aplikacyjna") >= 0 || x === "apl"; });
  let idxAk = h.findIndex(function (x) { return x.indexOf("klucz") >= 0 && x.indexOf("smartapply") >= 0; });

  // Fixed positions when header row matches ATA sheet (B = Tryb).
  if (idxLang === 0 && h[1] && h[1].indexOf("tryb") >= 0) {
    if (idxTryb < 0) idxTryb = 1;
    if (idxDeg < 0 && h[2] && h[2].indexOf("stop") >= 0) idxDeg = 2;
    if (idxK < 0 && h[3] && h[3].indexOf("kierunek") >= 0) idxK = 3;
    if (idxSpec < 0 && h[4] && h[4].indexOf("specjal") >= 0) idxSpec = 4;
  }

  return {
    idxLang: idxLang,
    idxTryb: idxTryb,
    idxDeg: idxDeg,
    idxK: idxK,
    idxSpec: idxSpec,
    idxAnn: idxAnn,
    idxSem: idxSem,
    idxRekr: idxRekr,
    idxApl: idxApl,
    idxAk: idxAk
  };
}

function ensureUabyByMode_(slot) {
  if (!slot) return { byMode: {} };
  if (slot.byMode) return slot;
  if (slot.r !== undefined || slot.rekr !== undefined) return { byMode: { s: slot } };
  return { byMode: {} };
}

function findHeaderRow_(rows, requiredHints, maxScan) {
  const limit = Math.min(maxScan || 25, rows.length);
  for (let i = 0; i < limit; i++) {
    const h = (rows[i] || []).map(x => clean(x).toLowerCase());
    const ok = requiredHints.every(hint => h.some(cell => cell.includes(hint)));
    if (ok) return i;
  }
  return -1;
}

function parseUabySheet_(rows) {
  const out = { pl: {}, en: {}, rows: [] };
  if (!rows || !rows.length) return out;

  let headerRowIdx = findHeaderRow_(rows, ["język", "tryb", "kierunek", "stop"], 25);
  if (headerRowIdx < 0) {
    for (let r = 0; r < Math.min(8, rows.length); r++) {
      const probe = mapUabyColumns_(rows[r]);
      if (probe.idxLang >= 0 && probe.idxTryb >= 0 && probe.idxK >= 0 && probe.idxDeg >= 0) {
        headerRowIdx = r;
        break;
      }
    }
  }
  const startRow = headerRowIdx >= 0 ? headerRowIdx + 1 : 2;
  const headerRow = headerRowIdx >= 0 ? (rows[headerRowIdx] || []) : [];
  const cols = mapUabyColumns_(headerRow);
  const idxLang = cols.idxLang;
  const idxTryb = cols.idxTryb;
  const idxDeg = cols.idxDeg;
  const idxK = cols.idxK;
  const idxSpec = cols.idxSpec;
  const idxAnn = cols.idxAnn;
  const idxSem = cols.idxSem;
  const idxRekr = cols.idxRekr;
  const idxApl = cols.idxApl;
  const idxAk = cols.idxAk;

  const hasNewLayout = idxLang >= 0 && idxK >= 0 && idxDeg >= 0 && idxAnn >= 0;
  const hasTrybColumn = idxTryb >= 0;

  for (let i = startRow; i < rows.length; i++) {
    const row = rows[i] || [];
    let lang = "";
    let k = "";
    let spec = "";
    let deg = 1;
    let ann = 0;
    let sem = 0;
    let rekr = 20;
    let apl = 100;
    let ak = "";
    let modeKey = "s";

    if (hasNewLayout) {
      lang = clean(row[idxLang]).toLowerCase();
      k = clean(row[idxK]);
      spec = idxSpec >= 0 ? clean(row[idxSpec]) : "";
      if (idxTryb >= 0) modeKey = parseTryb_(row[idxTryb]);
      deg = parseInt(clean(row[idxDeg]), 10) || 1;
      ann = parseEur_(row[idxAnn]);
      sem = idxSem >= 0 ? parseEur_(row[idxSem]) : 0;
      if (idxRekr >= 0) rekr = parseEur_(row[idxRekr]) || 20;
      if (idxApl >= 0) apl = parseEur_(row[idxApl]) || 100;
      if (idxAk >= 0) ak = clean(row[idxAk]);
    } else if (!hasTrybColumn) {
      // Legacy layout (no Tryb column): Język | Kierunek | Stopień | roczna | semestralna | …
      lang = clean(row[0]).toLowerCase();
      k = clean(row[1]);
      deg = parseInt(clean(row[2]), 10) || 1;
      ann = parseEur_(row[3]);
      sem = parseEur_(row[4]);
      rekr = parseEur_(row[6]) || 20;
      apl = parseEur_(row[7]) || 100;
    } else {
      continue; // header row not recognized — skip row (do not treat column B as Kierunek)
    }

    if (lang !== "pl" && lang !== "en") continue;
    if (!k) continue;

    const storageKey = uabyStorageKey_(k, spec);
    const d = String(deg);
    if (!out[lang][storageKey]) out[lang][storageKey] = {};
    const fees = { r: ann, s: sem, rekr: rekr, apl: apl, ak: ak };
    out[lang][storageKey][d] = ensureUabyByMode_(out[lang][storageKey][d]);
    out[lang][storageKey][d].byMode[modeKey] = fees;

    out.rows.push({
      lang: lang,
      mode: modeKey,
      k: k,
      s: spec,
      deg: deg,
      fees: fees
    });
  }

  return out;
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
    UABY_ROWS: [],
    PROMOS: [],
    CLOSED: []
  };
 
  // 🔗 SmartApply_URLs
  const sheetSA = ss.getSheetByName("🔗 SmartApply_URLs");
  if (sheetSA) {
    const rowsSA = sheetSA.getDataRange().getValues();
    // Header may not be row 1 (notes / blanks above).
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

    // Lang | Klucz SmartApply | URL SmartApply, or legacy key | URL_PL | URL_EN.
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

        // Row match when program-tab key is missing.
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

      // No header: infer lang / key / URL from cell values.
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
        return /^\d+_(wwa|wro)_[a-z0-9-]+$/i.test(clean(v));
      };

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

      // Legacy: key | URL_PL | URL_EN.
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
      const modeKey = parseFormaMode_(rowsPL[i][1]);
 
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
 
      data.RAW.pl[city][modeKey].push(course);
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
  // Columns (new): Język | Tryb | Stopień | Kierunek | Specjalność | Opłata roczna | Opłata semestralna | Opłata rekrutacyjna | Opłata aplikacyjna | Klucz SmartApply
  // Rows with Specjalność use storage key "Kierunek|Specjalność" so the calculator can list each track separately.
  const sheetUABY = ss.getSheetByName("🇺🇦 Ceny_UABY");
  if (sheetUABY) {
    const parsedUaby = parseUabySheet_(sheetUABY.getDataRange().getValues());
    data.UABY = { pl: parsedUaby.pl, en: parsedUaby.en };
    data.UABY_ROWS = parsedUaby.rows || [];
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
 
      // Column M (index 12): optional expiry date (YYYY-MM-DD or DD.MM.YYYY).
      const expiresRaw = rowsPRO[i].length > 12 ? clean(rowsPRO[i][12]) : "";
      if (expiresRaw) {
        promo.expires = expiresRaw;
      }

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

  data.CLOSED = parseRecruitmentClosed_(ss);
 
  return data;
}

/** Fill Rekrutacja from Programy_PL / Programy_EN. Keeps OTWARTA / ZAMKNIĘTA. */
function syncRecruitmentSheet_(ss) {
  const lock = LockService.getScriptLock();
  if (!lock.tryLock(5000)) return;
  try {
    const programs = collectRecruitmentPrograms_(ss);
    const flags = readRecruitmentFlags_(ss.getSheetByName("Rekrutacja"));
    const values = [["Język", "Miasto", "Stopień", "Kierunek", "Status"]];
    programs.forEach(function (item) {
      values.push([
        item.lng,
        item.city,
        item.deg,
        item.k,
        recruitmentIsClosedFlag_(flags, item) ? "ZAMKNIĘTA" : "OTWARTA"
      ]);
    });

    let sheet = ss.getSheetByName("Rekrutacja");
    if (!sheet) sheet = ss.insertSheet("Rekrutacja");
    if (recruitmentGridEquals_(sheet.getDataRange().getValues(), values)) return;

    sheet.clear();
    sheet.getRange(1, 1, values.length, 5).setValues(values);
    sheet.setFrozenRows(1);
    sheet.getRange(1, 1, 1, 5).setFontWeight("bold");
    if (programs.length) {
      const rule = SpreadsheetApp.newDataValidation().requireValueInList(["OTWARTA", "ZAMKNIĘTA"], true).setAllowInvalid(false).build();
      sheet.getRange(2, 5, programs.length, 1).setDataValidation(rule);
    }
  } finally {
    lock.releaseLock();
  }
}

function collectRecruitmentPrograms_(ss) {
  const items = [];
  const seen = {};

  function add(lng, cityRaw, degRaw, kRaw) {
    const k = clean(kRaw);
    if (!k || k === "—") return;
    const city = recruitmentCityLabel_(cityRaw);
    if (!city) return;
    const deg = parseRecruitmentDegree_(degRaw) === 2 ? 2 : 1;
    const key = lng + "|" + city + "|" + deg + "|" + recruitmentNameKey_(k);
    if (seen[key]) return;
    seen[key] = true;
    items.push({ lng: lng, city: city, deg: deg, k: k });
  }

  const pl = ss.getSheetByName("🎓 Programy_PL");
  if (pl) {
    const rows = pl.getDataRange().getValues();
    for (let i = 1; i < rows.length; i++) add("PL", rows[i][0], rows[i][2], rows[i][3]);
  }

  const en = ss.getSheetByName("🌍 Programy_EN");
  if (en) {
    const rows = en.getDataRange().getValues();
    for (let i = 1; i < rows.length; i++) add("EN", rows[i][0], rows[i][1], rows[i][2]);
  }

  items.sort(function (a, b) {
    if (a.lng !== b.lng) return a.lng === "PL" ? -1 : 1;
    if (a.city !== b.city) return a.city < b.city ? -1 : 1;
    if (a.deg !== b.deg) return a.deg - b.deg;
    return String(a.k).localeCompare(String(b.k), "pl");
  });
  return items;
}

function readRecruitmentFlags_(sheet) {
  const flags = { exact: {}, both: {} };
  if (!sheet) return flags;
  const rows = sheet.getDataRange().getValues();
  let headerIdx = -1;
  for (let i = 0; i < Math.min(15, rows.length); i++) {
    const h = (rows[i] || []).map(function (x) { return clean(x).toLowerCase(); });
    if (h.some(function (v) { return v.indexOf("kierunek") >= 0; }) && h.some(function (v) { return v.indexOf("status") >= 0 || v.indexOf("zamkni") >= 0 || v.indexOf("closed") >= 0; })) {
      headerIdx = i;
      break;
    }
  }
  if (headerIdx < 0) return flags;

  const header = rows[headerIdx].map(function (x) { return clean(x).toLowerCase(); });
  const idxLang = header.findIndex(function (h) { return h.indexOf("język") >= 0 || h.indexOf("jezyk") >= 0 || h === "lang"; });
  const idxCity = header.findIndex(function (h) { return h.indexOf("miasto") >= 0; });
  const idxDeg = header.findIndex(function (h) { return h.indexOf("stop") >= 0; });
  const idxK = header.findIndex(function (h) { return h.indexOf("kierunek") >= 0; });
  const idxClosed = header.findIndex(function (h) { return h.indexOf("status") >= 0 || h.indexOf("zamkni") >= 0 || h.indexOf("closed") >= 0; });

  for (let i = headerIdx + 1; i < rows.length; i++) {
    const row = rows[i] || [];
    const k = clean(idxK >= 0 ? row[idxK] : "");
    const city = recruitmentCityLabel_(idxCity >= 0 ? row[idxCity] : "");
    if (!k || !city) continue;
    const langRaw = clean(idxLang >= 0 ? row[idxLang] : "").toLowerCase();
    const lng = (langRaw.indexOf("en") === 0 || langRaw.indexOf("ang") === 0) ? "EN" : "PL";
    const deg = parseRecruitmentDegree_(idxDeg >= 0 ? row[idxDeg] : "");
    const closed = recruitmentFlagIsClosed_(row[idxClosed]);
    const name = recruitmentNameKey_(k);
    if (deg === 0) {
      if (closed) flags.both[lng + "|" + city + "|" + name] = true;
      continue;
    }
    flags.exact[lng + "|" + city + "|" + deg + "|" + name] = closed;
  }
  return flags;
}

function recruitmentIsClosedFlag_(flags, item) {
  const name = recruitmentNameKey_(item.k);
  const exact = item.lng + "|" + item.city + "|" + item.deg + "|" + name;
  if (Object.prototype.hasOwnProperty.call(flags.exact, exact)) return flags.exact[exact];
  return !!flags.both[item.lng + "|" + item.city + "|" + name];
}

function recruitmentFlagIsClosed_(value) {
  const flag = clean(value).toUpperCase().replace(/Ę/g, "E");
  return flag === "ZAMKNIETA" || flag === "TAK" || flag === "TRUE" || flag === "YES" || flag === "1" || flag === "T";
}

function recruitmentCityLabel_(value) {
  const t = clean(value).toLowerCase();
  if (t.indexOf("wroc") >= 0) return "Wrocław";
  if (t.indexOf("warsz") >= 0) return "Warszawa";
  return "";
}

function recruitmentNameKey_(value) {
  return clean(value).toLowerCase().replace(/\s+/g, " ");
}

function recruitmentGridEquals_(current, next) {
  if (!current || current.length !== next.length) return false;
  for (let r = 0; r < next.length; r++) {
    for (let c = 0; c < 5; c++) {
      if (clean((current[r] || [])[c]) !== clean((next[r] || [])[c])) return false;
    }
  }
  return true;
}

/** Tab Rekrutacja: full kierunek list. Status ZAMKNIĘTA closes that language, city, and degree. */
function parseRecruitmentClosed_(ss) {
  const sheet = ss.getSheetByName("Rekrutacja");
  if (!sheet) return [];
  const rows = sheet.getDataRange().getValues();
  if (!rows || !rows.length) return [];

  let headerIdx = -1;
  for (let i = 0; i < Math.min(15, rows.length); i++) {
    const h = (rows[i] || []).map(x => clean(x).toLowerCase());
    const hasK = h.some(v => v.indexOf("kierunek") >= 0);
    const hasClosed = h.some(v => v.indexOf("status") >= 0 || v.indexOf("zamkni") >= 0 || v.indexOf("closed") >= 0);
    if (hasK && hasClosed) { headerIdx = i; break; }
  }
  if (headerIdx < 0) return [];

  const header = rows[headerIdx].map(x => clean(x).toLowerCase());
  const idxLang = header.findIndex(h => h.indexOf("język") >= 0 || h.indexOf("jezyk") >= 0 || h === "lang");
  const idxCity = header.findIndex(h => h.indexOf("miasto") >= 0);
  const idxDeg = header.findIndex(h => h.indexOf("stop") >= 0);
  const idxK = header.findIndex(h => h.indexOf("kierunek") >= 0);
  const idxClosed = header.findIndex(h => h.indexOf("status") >= 0 || h.indexOf("zamkni") >= 0 || h.indexOf("closed") >= 0);
  const out = [];

  for (let i = headerIdx + 1; i < rows.length; i++) {
    const row = rows[i] || [];
    if (!recruitmentFlagIsClosed_(row[idxClosed])) continue;

    const k = clean(idxK >= 0 ? row[idxK] : "");
    if (!k) continue;

    const cityRaw = clean(idxCity >= 0 ? row[idxCity] : "").toLowerCase();
    const city = cityRaw.indexOf("wroc") >= 0 ? "wro" : (cityRaw.indexOf("warsz") >= 0 ? "wwa" : "");
    if (!city) continue;

    const langRaw = clean(idxLang >= 0 ? row[idxLang] : "").toLowerCase();
    const lng = (langRaw.indexOf("en") === 0 || langRaw.indexOf("ang") === 0) ? "en" : "pl";

    out.push({
      lng: lng,
      city: city,
      deg: parseRecruitmentDegree_(idxDeg >= 0 ? row[idxDeg] : ""),
      k: k
    });
  }

  return out;
}

function parseRecruitmentDegree_(value) {
  const s = clean(value).toLowerCase();
  if (!s || s === "oba" || s === "both" || s === "1 i 2") return 0;
  if (s === "2" || s === "ii" || s.indexOf("ii") === 0 || s.indexOf("master") >= 0) return 2;
  if (s === "1" || s === "i" || s.indexOf("bachelor") >= 0) return 1;
  const n = parseInt(s, 10);
  return (n === 1 || n === 2) ? n : 0;
}

