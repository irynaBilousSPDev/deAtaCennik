# Prices calculator – Google Sheets + Apps Script

This folder is the **source of truth** for the script that powers the Akademia TA prices calculator on the website.

| File | Purpose |
|------|---------|
| `prices-json-webapp.gs` | Full Apps Script — copy **entire file** into Google Sheets → Extensions → Apps Script |
| `README.md` | Setup and maintenance (this document) |

The WordPress theme loads JSON from the deployed web app URL (see [Connect WordPress](#6-connect-wordpress)).

---

## What the script does

1. Reads pricing data from named tabs in one Google Spreadsheet.
2. Builds one JSON object (`SA`, `RAW`, `UABY`, `PROMOS`, …).
3. Serves it via a public **Web app** URL (`doGet`).
4. Caches the JSON for 6 hours when under ~95 KB (larger payloads are served without cache — Script Cache limit is 100 KB per key).
5. Refreshes on sheet edit (`onEdit`) or manually (`forceManualUpdate`).

---

## Required spreadsheet tabs

Tab names must match **exactly** (including emojis):

| Tab | Required | Row 1 |
|-----|----------|--------|
| `🔗 SmartApply_URLs` | Yes | Header row (script auto-detects if lower) |
| `🎓 Programy_PL` | Yes | Header in row 1, data from row 2 |
| `🌍 Programy_EN` | Yes | Header in row 1, data from row 2 |
| `🇺🇦 Ceny_UABY` | Yes (Wrocław UABY) | Header row with column names (see below) |
| `🏷️ Promocje` | Yes | Header in row 1, data from row 2 |

---

## Column layouts (reference)

### `🔗 SmartApply_URLs`

**Recommended format:**

| Lang | Klucz SmartApply | … | URL SmartApply | Miasto | Stopień | Kierunek | Specjalność |
|------|------------------|---|----------------|--------|---------|----------|-------------|
| PL / EN | `1_wro_informatyka` | … | `https://smartapply.akademiata.pl/...` | Wrocław | 1 | Informatyka | — |

- Header row is detected automatically (looks for `Lang`, `Klucz SmartApply`, `URL SmartApply`).
- Extra note rows above the header are OK.

### `🎓 Programy_PL`

| A Miasto | B Tryb | C Stopień | D Kierunek | E Specjalność | F r10 | G r12 | H rekr | I wps | J ps | K ak |
|----------|--------|-----------|------------|---------------|-------|-------|--------|-------|------|------|
| Warszawa / Wrocław | Stacjonarne / Niestacjonarne / Obie | 1 / 2 | … | — or name | PLN | PLN | PLN | PLN | key | key |

### `🌍 Programy_EN`

| A Miasto | B Stopień | C Kierunek | D Specjalność | E EU rok | F EU sem | G non-EU rok | H non-EU sem | I rekr | J wps | K ps | L ak |

### `🇺🇦 Ceny_UABY` (updated layout)

| Col | A | **B** | C | D | E | F | … |
|-----|---|-------|---|---|---|---|---|
| Header | Język | **Tryb** | Stopień | Kierunek | Specjalność | Opłata roczna (EUR) | … |
| Values | PL / EN | **Stacjonarne** / **Niestacjonarne** | 1 / 2 | Informatyka | — or track | `2 250 €` | … |

**Column B must be `Tryb`** — the script reads Stacjonarne / Niestacjonarne from this column (not from column D).

- **Specjalność** empty or `—` → one program line (e.g. `Informatyka`).
- **With specjalność** → separate line in calculator (e.g. `Informatyka — Inżynieria oprogramowania`).
- Fees may include spaces and `€`; the script parses them.
- Optional instruction row above the header is OK; the script finds the header row.

### `🏷️ Promocje`

| ID | Język | Min. stopień | Miasto | Aktywna | Nazwa | Tag | Opis skrócony | Typ rabatu | Wartość | Łączy się z | Uwagi |
|----|-------|--------------|--------|---------|-------|-----|---------------|------------|---------|-------------|-------|

- **Aktywna** = `TAK` to include the promo.
- Bold text in cells is preserved in JSON (`<strong>`).

---

## 1. New Google account – create the spreadsheet

1. Sign in to the Google account that will own the pricing sheet.
2. **Google Drive → New → Google Sheets**.
3. Name it e.g. `Akademia TA – Cennik`.
4. Create the five tabs listed above (rename sheets; copy emojis from this doc).
5. Add header rows and paste your data (or copy from an existing workbook).

---

## 2. Install Apps Script

1. Open the spreadsheet.
2. **Extensions → Apps Script**.
3. Delete any default code in `Code.gs`.
4. Open `apps-script/prices-json-webapp.gs` from this repository.
5. **Select all → Copy → Paste** into `Code.gs` in Google.
6. **File → Save** (or Ctrl+S). Project name e.g. `ATA Prices JSON`.

---

## 3. Authorize and test

1. In Apps Script, select function **`forceManualUpdate`** in the toolbar dropdown.
2. Click **Run**.
3. First run: **Review permissions → Allow** (Google account, spreadsheet access).
4. **View → Execution log** — should finish without errors.
5. Optional: select **`generateJSON`**, Run, check log (no output is normal).

If execution fails, check tab names and that `🇺🇦 Ceny_UABY` has a proper header row (`Język`, `Kierunek`, `Stopień`, …).

---

## 4. Deploy as web app

1. Apps Script → **Deploy → New deployment**.
2. Click the gear next to “Select type” → **Web app**.
3. Settings:
   - **Description**: e.g. `Prices JSON v1`
   - **Execute as**: **Me**
   - **Who has access**: **Anyone** (required for the public website)
4. **Deploy** → copy the **Web app URL** (ends with `/exec`).
5. Test in browser:
   - `https://script.google.com/macros/s/XXXX/exec` → JSON
   - `.../exec?force=1` → rebuild cache and return fresh JSON

**After code changes:** Deploy → **Manage deployments** → pencil icon → **Version: New version** → Deploy.  
The `/exec` URL stays the same.

---

## 5. Enable automatic refresh on edit

The script includes `onEdit(e)` (simple trigger).

1. Edit any cell in the spreadsheet and save.
2. First time: Google may ask to authorize the script again for triggers.
3. Cache updates within a few seconds (max TTL 6 hours anyway).

Manual refresh anytime: run **`forceManualUpdate`** in Apps Script, or open `.../exec?force=1`.

---

## 6. Connect WordPress

In the theme, the web app URL is set in `configure/js-css.php`:

```php
wp_localize_script('name-main-js', 'akademiataPrices', [
    'googleApiUrl' => 'https://script.google.com/macros/s/YOUR_DEPLOYMENT_ID/exec',
]);
```

1. Replace `YOUR_DEPLOYMENT_ID` with your deployment URL from step 4.
2. Deploy the theme to the server.
3. Open the **Prices** page → calculator should load data from Google (not local `prices.json`).

**Without `googleApiUrl`:** the calculator falls back to `/wp-content/themes/akademiata/prices.json` (use `prices_generate_json.py` to generate it).

---

## 7. Local JSON backup (optional)

From the project root (Python 3 + pandas):

```bash
pip install pandas openpyxl requests
python prices_generate_json.py --sheet "https://docs.google.com/spreadsheets/d/1hwGls1Dpxq36gxm_XtHr9DDUYGBipyC9hWPOfo_LZS0/edit" --out prices.json
```

Output can be saved as `prices.json` in the theme folder for offline/fallback use.

---

## 8. Checklist after changes

| Step | Action |
|------|--------|
| 1 | Edit sheet data |
| 2 | Run `forceManualUpdate` or wait for `onEdit` |
| 3 | Open `.../exec?force=1` and spot-check JSON (`UABY`, `RAW`, …) |
| 4 | Hard-refresh Prices page on the site (Ctrl+F5) |
| 5 | Test: city, language, UABY checkbox (Wrocław), specjalności |

---

## 9. Troubleshooting

| Problem | What to check |
|---------|----------------|
| Empty calculator | Web app URL in `js-css.php`; browser Network tab for JSON request |
| Wrong UABY prices | `🇺🇦 Ceny_UABY` headers; use **this** script version (not the old `for (i = 2)` loop) |
| Missing specjalności | Specjalność column filled; not only kierunek rows |
| `parseInt("2 250 €")` = 2 | Old script — replace with `parseUabySheet_` from `prices-json-webapp.gs` |
| Stale data | `?force=1` or `forceManualUpdate`; cache TTL 6h |
| Permission errors | Re-run `forceManualUpdate` and accept scopes |
| Promos without bold | Promocje tab must use rich text; script uses `getRichTextValues()` |

---

## 10. Copying to another Google account (summary)

1. **File → Make a copy** of the spreadsheet (or export/import).
2. New account: **Extensions → Apps Script** → paste `prices-json-webapp.gs`.
3. **Deploy → New deployment** (new URL).
4. Put the **new** `/exec` URL in WordPress `configure/js-css.php`.
5. Run **`forceManualUpdate`** once.

---

## JSON output shape (short)

```json
{
  "SA": { "1_wwa_architektura": "https://smartapply..." },
  "SA_EN": { },
  "SA_ROWS": [ ],
  "RAW": { "pl": { "wwa": { "s": [], "n": [] }, "wro": { ... } }, "en": { ... } },
  "UABY": {
    "pl": {
      "Informatyka": { "1": { "r": 2250, "s": 1300, "rekr": 20, "apl": 100 } },
      "Informatyka|Inżynieria oprogramowania": { "1": { ... } }
    },
    "en": { }
  },
  "PROMOS": [ ]
}
```

---

## Related theme files

- `assets/src/js/prices-calculator.js` – calculator logic
- `template-parts/prices/calculator.php` – markup + regulamin URLs
- `configure/js-css.php` – `googleApiUrl`
- `prices_generate_json.py` – Excel/Sheets → `prices.json`

After editing JS/SCSS: `npm run build` in the theme directory.
