export default function initPricesCalculator(_$, opts = {}) {
  const $ = _$ || window.jQuery;
  const content = document.getElementById('kalkulator-content');
  const loader = document.getElementById('ata-loader');
  const noteBot = document.getElementById('note-bot');
  const loaderShownAt = Date.now();
  const MIN_LOADER_MS = 350;

  // If calculator markup isn't on this page, do nothing.
  if (!content && !loader) return;

  const FIXED_KEY = (content && content.dataset && content.dataset.fixedKey) ? String(content.dataset.fixedKey).trim() : '';
  const FIXED_LANG = (content && content.dataset && content.dataset.fixedLang) ? String(content.dataset.fixedLang).trim() : '';
  const HIDE_MORE_BTN = !!(content && content.dataset && (content.dataset.hideMoreBtn === '1' || content.dataset.hideMoreBtn === 'true'));
  const emptyBox = document.getElementById('prices-empty');
  const PARSED_FIXED = FIXED_KEY ? parseFixedKey(FIXED_KEY) : null;

  function setLoading(isLoading) {
    if (isLoading) {
      if (loader) loader.style.display = '';
      if (content) content.style.display = 'none';
      return;
    }

    const elapsed = Date.now() - loaderShownAt;
    const delay = Math.max(0, MIN_LOADER_MS - elapsed);
    window.setTimeout(() => {
      if (loader) loader.style.display = 'none';
      if (content) content.style.display = '';
    }, delay);
  }

  // Start in loading state. If cache exists, we'll render immediately.
  setLoading(true);

  // URLs
  const LOCAL_JSON_URL = '/wp-content/themes/akademiata/prices.json'; 
  const GOOGLE_API_URL = opts.googleApiUrl || '';
  let lastGoogleHash = '';

  const I18N = (() => {
    const el = document.getElementById('prices-i18n');
    if (!el) return {};
    try { return JSON.parse(el.textContent || '{}') || {}; } catch (e) { return {}; }
  })();
  function t(key, fallback) { return (I18N && I18N[key]) ? I18N[key] : fallback; }

  function setEmptyState(isEmpty, reason = '') {
    if (!emptyBox) return;
    if (!isEmpty) {
      emptyBox.style.display = 'none';
      document.querySelectorAll('[data-hide-when-empty]').forEach(el => { el.style.display = ''; });
      return;
    }

    const titleEl = emptyBox.querySelector('[data-empty-title]');
    const textEl = emptyBox.querySelector('[data-empty-text]');
    const baseTitle = t('emptyTitle', 'Cennik w przygotowaniu');
    const baseText = t(
      'emptyText',
      'Wkrótce udostępnimy aktualny cennik dla tego programu. Jeśli chcesz, skontaktuj się z nami — chętnie pomożemy.'
    );

    if (titleEl) titleEl.textContent = baseTitle;
    // Keep this client-friendly (no technical keys shown).
    if (textEl) textEl.textContent = baseText;

    emptyBox.style.display = '';

    // Hide the rest of calculator blocks so the UI is just the message.
    document.querySelectorAll('[data-hide-when-empty]').forEach(el => { el.style.display = 'none'; });
  }
  
  // Init state
  window.SA = window.SA || {};
  window.SA_EN = window.SA_EN || {};
  window.RAW = window.RAW || { pl: { wwa: { s: [], n: [] }, wro: { s: [], n: [] } }, en: { wwa: [], wro: [] } };
  window.UABY = window.UABY || { pl: {}, en: {} };
  window.PROMOS = window.PROMOS || [];

  window.BASE = window.BASE || 'https://smartapply.akademiata.pl/pl/apply/';
  window.BASE_EN = window.BASE_EN || 'https://smartapply.akademiata.pl/en/apply/';

  window.city = window.city || 'wwa';
  window.lang = window.lang || 'pl';
  window.uaby = !!window.uaby;
  window.progIdx = Number.isFinite(window.progIdx) ? window.progIdx : 0;
  window.mode = window.mode || 's';
  window.plan = window.plan || 'r12';
  window.isEU = window.isEU !== undefined ? !!window.isEU : true;

  window.selP = window.selP || { jednorazowo: false };
  window.subP = window.subP || { grupie: 200, absolwent_pl: 0.20 };
  window.expP = window.expP || {};
  window.unified = window.unified || [];

  window.togglePromo = togglePromo;
  window.toggleExp = toggleExp;
  window.setSub = setSub;
  window.setCity = setCity;
  window.setLang = setLang;
  window.toggleUABY = toggleUABY;
  window.setMode = setMode;
  window.setEU = setEU;
  window.onProgChange = onProgChange;
  window.updateMB = updateMB;
  window.buildSel = buildSel;
  window.updateCTAs = updateCTAs;
  window.getPL = getPL;
  window.render = render;

  let isUIBound = false;

  // Apply fetched data
  function applyData(data) {
    if (!data || !data.RAW) return;
    window.SA = data.SA || {};
    window.SA_EN = data.SA_EN || {};
    window.RAW = data.RAW || window.RAW;
    window.UABY = data.UABY || window.UABY;
    window.PROMOS = data.PROMOS || [];
    window.BASE = data.BASE || window.BASE;
    window.BASE_EN = data.BASE_EN || window.BASE_EN;

    const uabyWrap = document.getElementById('uaby-wrap');
    const euWrap = document.getElementById('eu-wrap');
    
    if (uabyWrap) uabyWrap.style.display = window.city === 'wro' ? 'block' : 'none';
    if (euWrap) euWrap.style.display = (window.lang === 'en' && !window.uaby) ? 'block' : 'none';

    if (!isUIBound) {
      bindUI();
      isUIBound = true;
    }

    // Single-offer (fixed key) defaults
    if (FIXED_KEY) {
      const parsed = parseFixedKey(FIXED_KEY);
      if (parsed && parsed.city) window.city = parsed.city;
      if (FIXED_LANG) window.lang = FIXED_LANG;

      // Hide locked rows (city/lang/program) on single offer page.
      document.querySelectorAll('[data-prices-row="city"],[data-prices-row="lang"],[data-prices-row="program"]').forEach(el => {
        el.style.display = 'none';
      });
    }
    render();
    setLoading(false);
  }

  function parseFixedKey(key) {
    // Expected: "<deg>_<city>_<slug>" e.g. "1_wwa_architektura"
    const m = String(key || '').trim().toLowerCase().match(/^(\d+)_([a-z]{3})_(.+)$/);
    if (!m) return null;
    return { deg: parseInt(m[1], 10), city: m[2], slug: m[3] };
  }

  function matchItemByFixedKey(item, fixedKey, parsed) {
    if (!item || !fixedKey) return false;

    // Preferred: if the data includes the key (future-proof for your sheets column)
    const candidateKey = (item.lk || item.key || item.logical_key || item.logical_sync_key || item.sync_key || '').toString().trim().toLowerCase();
    if (candidateKey && candidateKey === fixedKey.toLowerCase()) return true;

    // Backward-compat / testing support:
    // Some sheets currently store the logical key in the "Klucz SmartApply" column (mapped to `ak` in JSON).
    // Allow matching by `ak` as well, but note this can affect SmartApply CTA mapping.
    const akKey = (item.ak || '').toString().trim().toLowerCase();
    if (akKey && akKey === fixedKey.toLowerCase()) return true;

    // Fallback: match by degree + slug in program URL (ps)
    if (!parsed) return false;
    if (Number(item.deg) !== Number(parsed.deg)) return false;
    const ps = (item.ps || '').toString().trim().toLowerCase();
    if (!ps) return false;

    // `ps` can be either:
    // - a full URL containing "/<slug>/"
    // - a plain slug (because `prices_generate_json.py` extracts last segment)
    if (ps === parsed.slug) return true;
    return ps.includes('/' + parsed.slug + '/');
  }

  function stableHash(str) {
    // djb2-ish, fast + stable across sessions
    let h = 5381;
    for (let i = 0; i < str.length; i++) h = ((h << 5) + h) ^ str.charCodeAt(i);
    // force unsigned + short string
    return String(h >>> 0);
  }

  function withNoCache(url) {
    if (!url) return url;
    const sep = url.includes('?') ? '&' : '?';
    return url + sep + '_=' + Date.now();
  }

  // Data loading strategy:
  // If `googleApiUrl` is provided, treat Google Apps Script as the ONLY source of truth.
  // (No local `prices.json` fetch; avoids stale/cached local data.)
  // If `googleApiUrl` is missing, fall back to local `prices.json`.

  function fetchGoogleOnly() {
    return fetch(withNoCache(GOOGLE_API_URL), { cache: 'no-store' })
      .then(r => r.json())
      .then(freshData => {
        try {
          lastGoogleHash = stableHash(JSON.stringify(freshData));
        } catch (e) {}
        applyData(freshData);
      })
      .catch(err => {
        console.warn('Google API failed', err);
        // Keep UI client-friendly: stop loader and render empty-state (render() will handle it)
        setLoading(false);
        try { setEmptyState(true); } catch (e) {}
      });
  }

  function fetchLocalOnly() {
    return fetch(LOCAL_JSON_URL)
      .then(r => r.json())
      .then(localData => applyData(localData))
      .catch(err => {
        console.error('Local data source failed', err);
        setLoading(false);
        try { setEmptyState(true); } catch (e) {}
      });
  }

  if (GOOGLE_API_URL) fetchGoogleOnly();
  else fetchLocalOnly();

  // Event listeners
  function bindUI() {
    function updatePlansHint(plansEl) {
      const hintEl = document.querySelector('[data-plans-hint]');
      if (!hintEl || !plansEl) return;
      const maxLeft = Math.max(0, plansEl.scrollWidth - plansEl.clientWidth);
      const atEnd = (plansEl.scrollLeft || 0) >= (maxLeft - 1);
      const atStart = (plansEl.scrollLeft || 0) <= 8;
      const rightTxt = hintEl.getAttribute('data-hint-right') || 'Przesuń →';
      const leftTxt = hintEl.getAttribute('data-hint-left') || '← Przesuń';

      // Slider-like behavior:
      // - when user reaches the end, lock direction to "left"
      // - keep showing "← Przesuń" and keep scrolling left until reaching the start
      // - at the start, unlock back to "right"
      if (atEnd) hintEl.dataset.lockDir = 'left';
      if (atStart) hintEl.dataset.lockDir = '';
      const locked = (hintEl.dataset.lockDir || '') === 'left';

      if (locked && !atStart) {
        hintEl.dataset.dir = 'left';
        hintEl.textContent = leftTxt;
        return;
      }

      if (atEnd) {
        hintEl.dataset.dir = 'left';
        hintEl.textContent = leftTxt;
      } else {
        hintEl.dataset.dir = 'right';
        hintEl.textContent = rightTxt;
      }
    }

    function nudgePlans(dir) {
      const wrap = document.getElementById('plans-wrap');
      const plans = wrap ? wrap.querySelector('.plans') : null;
      if (!plans) return;

      const firstCard = plans.querySelector('.pc');
      const gap = 14;
      const delta = firstCard ? (firstCard.getBoundingClientRect().width + gap) : 280;
      const maxLeft = Math.max(0, plans.scrollWidth - plans.clientWidth);
      const curLeft = plans.scrollLeft || 0;
      const nextLeft = dir === 'left'
        ? Math.max(0, curLeft - delta)
        : Math.min(maxLeft, curLeft + delta);

      // If no movement possible, just update hint state.
      if (Math.abs(nextLeft - curLeft) < 1) {
        updatePlansHint(plans);
        return;
      }

      try {
        plans.scrollTo({ left: nextLeft, top: 0, behavior: 'smooth' });
      } catch (e) {
        plans.scrollLeft = nextLeft;
      }

      // Update hint after scrolling.
      window.setTimeout(() => updatePlansHint(plans), 220);
    }

    if ($ && $.fn && typeof $.fn.on === 'function') {
      $(document)
        .on('click', '#city-row .seg-btn', function () {
          window.setCity(this.getAttribute('data-val'));
        })
        .on('click', '#lang-row .seg-btn', function () {
          window.setLang(this.getAttribute('data-val'));
        })
        .on('click', '#uaby-row', function () {
          window.toggleUABY();
        })
        .on('change', '#prog-sel', function () {
          window.progIdx = parseInt(this.value, 10);
          window.onProgChange();
        })
        .on('click', '#eu-row .pill', function () {
          const val = this.getAttribute('data-val');
          if (!val) return;
          window.setEU(val === 'eu' || val === 'true');
        })
        .on('click', '[data-plans-hint]', function () {
          const dir = (this && this.dataset && this.dataset.dir) ? this.dataset.dir : 'right';
          nudgePlans(dir);
        });
      // Ensure EU pills initial state is correct when UI binds.
      $('#eu-row .pill').each(function () {
        const val = this.getAttribute('data-val');
        this.classList.toggle('on', (val === 'eu' || val === 'true') === window.isEU);
      });
      return;
    }

    // Fallback (no jQuery)
    const cityRow = document.getElementById('city-row');
    if (cityRow) {
      cityRow.addEventListener('click', (e) => {
        const btn = e.target && e.target.closest ? e.target.closest('.seg-btn') : null;
        if (!btn) return;
        window.setCity(btn.getAttribute('data-val'));
      });
    }

    const langRow = document.getElementById('lang-row');
    if (langRow) {
      langRow.addEventListener('click', (e) => {
        const btn = e.target && e.target.closest ? e.target.closest('.seg-btn') : null;
        if (!btn) return;
        window.setLang(btn.getAttribute('data-val'));
      });
    }

    const uabyRow = document.getElementById('uaby-row');
    if (uabyRow) uabyRow.addEventListener('click', () => window.toggleUABY());

    const progSel = document.getElementById('prog-sel');
    if (progSel) {
      progSel.addEventListener('change', function () {
        window.progIdx = parseInt(this.value, 10);
        window.onProgChange();
      });
    }
    // Mobile: native <select> is overlayed and captures taps (CSS).

    const euRow = document.getElementById('eu-row');
    if (euRow) {
      document.querySelectorAll('#eu-row .pill').forEach(b => {
        const val = b.getAttribute('data-val');
        b.classList.toggle('on', (val === 'eu' || val === 'true') === window.isEU);
      });

      euRow.addEventListener('click', (e) => {
        const btn = e.target && e.target.closest ? e.target.closest('.pill') : null;
        if (!btn) return;
        const val = btn.getAttribute('data-val');
        if (!val) return;
        window.setEU(val === 'eu' || val === 'true');
      });
    }

    // Mobile/tablet: tap "Przesuń →" to nudge plans scroller.
    document.querySelectorAll('[data-plans-hint]').forEach(el => {
      el.addEventListener('click', () => {
        const dir = (el && el.dataset && el.dataset.dir) ? el.dataset.dir : 'right';
        nudgePlans(dir);
      });
    });
  }

  function fmt(n) { return Math.round(n).toLocaleString('pl-PL'); }
  function setTextAll(root, selector, text) {
    if (!root) return;
    const els = root.querySelectorAll(selector);
    if (!els || !els.length) return;
    els.forEach(el => { el.textContent = text; });
  }
  function buildPlanCardFallback() {
    const card = document.createElement('div');
    card.className = 'pc';

    const mobileHead = document.createElement('div');
    mobileHead.className = 'pc-h pc-h--mobile';
    mobileHead.innerHTML = `
      <div class="pc-ic" aria-hidden="true"></div>
      <div class="lbl" data-plan-label></div>
      <div class="pc-disc" data-plan-disc style="display:none"></div>
    `.trim();

    const lblDesktop = document.createElement('div');
    lblDesktop.className = 'lbl pc-lbl--desktop';
    lblDesktop.setAttribute('data-plan-label-desktop', '');

    const priceDesktop = document.createElement('div');
    priceDesktop.className = 'pc-price--desktop';
    priceDesktop.innerHTML = `
      <span class="pr pc-pr--desktop" data-plan-price-desktop></span>
      <span class="pc-unit pc-unit--desktop" data-plan-unit-desktop></span>
    `.trim();

    const priceMobile = document.createElement('div');
    priceMobile.className = 'pc-price pc-price--mobile';
    priceMobile.innerHTML = `
      <span class="pr" data-plan-price></span>
      <span class="pc-unit" data-plan-unit></span>
    `.trim();

    const sv = document.createElement('div');
    sv.className = 'sv';
    sv.setAttribute('data-plan-sv', '');
    sv.style.display = 'none';

    const pick = document.createElement('div');
    pick.className = 'pc-pick';
    pick.setAttribute('data-plan-pick', '');
    pick.style.display = 'none';
    pick.textContent = 'Najczęściej wybierany';

    card.appendChild(mobileHead);
    card.appendChild(lblDesktop);
    card.appendChild(priceDesktop);
    card.appendChild(priceMobile);
    card.appendChild(sv);
    card.appendChild(pick);
    return card;
  }
  function getCurSymbol(cur) {
    if (cur === 'PLN') return 'zł';
    if (cur === 'EUR') return '€';
    return cur || '';
  }
  function inferPercentPill(txt) {
    const m = String(txt || '').match(/([−-]\s*\d{1,2}\s*%)/);
    return m ? m[1].replace(/\s+/g, '') : '';
  }
  function normTxt(v) { return (v === undefined || v === null) ? '' : String(v).trim(); }
  function normSpec(v) {
    const s = normTxt(v);
    // In sheets/data, "—" often means "no specialization"
    return (s === '—' || s === '-') ? '' : s;
  }
  function decodeHtmlEntities(str) {
    // Google Sheets/API sometimes returns escaped HTML like &lt;strong&gt;...&lt;/strong&gt;.
    if (!str) return '';
    const el = document.createElement('textarea');
    el.innerHTML = String(str);
    return el.value;
  }
  function formatPromoHtml(str) {
    // Supports:
    // - **bold** / __bold__
    // - escaped <strong>/<b> from sheets
    // - newlines -> <br>
    const raw = decodeHtmlEntities(str);
    const normalizedTags = raw
      // Normalize <b> to <strong> so styling is consistent.
      .replace(/<\s*\/\s*b\s*>/gi, '</strong>')
      .replace(/<\s*b(\s+[^>]*)?>/gi, '<strong$1>');

    const withBold = normalizedTags
      .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
      .replace(/__(.+?)__/g, '<strong>$1</strong>');

    // If the source contains no explicit bold markers/tags, rich-text bold from Sheets is lost.
    // Fallback: highlight common "important" fragments in PL promos (dates, amounts, UWAGA).
    let out = withBold;
    const hasExplicitBold = /<\s*strong\b/i.test(out);
    if (!hasExplicitBold) {
      out = out
        // "UWAGA:" label
        .replace(/\b(UWAGA)\s*:/gi, '<strong>$1:</strong>')
        // dd.mm.yyyy
        .replace(/\b(\d{1,2}\.\d{1,2}\.\d{4})\b/g, '<strong>$1</strong>')
        // "do 10 marca", "lub 10 marca" etc.
        .replace(/\b(do|lub)\s+(\d{1,2})\s+([A-Za-zĄąĆćĘęŁłŃńÓóŚśŹźŻż]+)\b/gi, '$1 <strong>$2 $3</strong>')
        // amounts like "1 000 zł", "1000 zł", "200 zł"
        .replace(/\b(\d{1,3}(?:\s?\d{3})*)\s*(zł|PLN|EUR|€)\b/g, '<strong>$1 $2</strong>')
        // percentages like "5%" or "−10%"
        .replace(/([−-]?\s*\d{1,2})\s*%/g, '<strong>$1%</strong>');
    }

    return out.replace(/\r\n|\r|\n/g, '<br>');
  }
  function setPromoCardBody(el, show) {
    // IMPORTANT: promo body markup lives in the HTML <template>.
    // Do not overwrite innerHTML here, only toggle visibility.
    if (!el) return;
    el.style.display = show ? '' : 'none';
  }
  function isDiscountValuePromo(promo) {
    if (!promo) return false;
    const txt = [promo.short, promo.full].filter(Boolean).join(' ');
    return /Wartość rabatu/i.test(txt);
  }
  function inferSubOptions(promo) {
    if (!promo) return [];
    // Promos that describe "discount value (PLN or %)" should not render pc-subopts buttons,
    // even if the data includes explicit suboptions.
    if (isDiscountValuePromo(promo)) return [];
    if (Array.isArray(promo.so) && promo.so.length) return promo.so;

    // Try to infer two-option promos from text (tag/short/full).
    const txt = [promo.tag, promo.short, promo.full].filter(Boolean).join(' ');
    if (!txt) return [];

    // If pct: allow 0.20 lub 0.30 OR 20% lub 30%
    if (promo.disc && promo.disc.t === 'pct') {
      const vals = new Set();
      // decimals like 0.20
      (txt.match(/\b0\.\d{1,3}\b/g) || []).forEach(s => {
        const n = parseFloat(s);
        if (Number.isFinite(n) && n > 0 && n < 1) vals.add(n);
      });
      // percentages like 20%
      (txt.match(/\b\d{1,2}\s*%/g) || []).forEach(s => {
        const n = parseFloat(s.replace('%', '').trim());
        if (Number.isFinite(n) && n > 0 && n < 100) vals.add(n / 100);
      });
      const arr = Array.from(vals).sort((a, b) => a - b);
      if (arr.length >= 2) {
        const a = arr[0], b = arr[arr.length - 1];
        return [
          { v: a, l: `−${Math.round(a * 100)}%` },
          { v: b, l: `−${Math.round(b * 100)}%` },
        ];
      }
      return [];
    }

    // If fix: pick distinct integers (e.g. 200, 400) from text
    if (promo.disc && promo.disc.t === 'fix') {
      const vals = new Set();
      (txt.match(/\b\d{1,3}(?:\s?\d{3})*\b/g) || []).forEach(s => {
        const n = parseInt(s.replace(/\s/g, ''), 10);
        if (Number.isFinite(n) && n > 0) vals.add(n);
      });
      const arr = Array.from(vals).sort((a, b) => a - b);
      if (arr.length >= 2) {
        const a = arr[0], b = arr[arr.length - 1];
        return [
          { v: a, l: `−${fmt(a)} zł` },
          { v: b, l: `−${fmt(b)} zł` },
        ];
      }
    }
    return [];
  }
  function getEndOfCurrentMonthPL() {
    const now = new Date();
    const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);
    const dd = String(lastDay.getDate()).padStart(2, '0');
    const mm = String(lastDay.getMonth() + 1).padStart(2, '0');
    const yyyy = String(lastDay.getFullYear());
    return `${dd}.${mm}.${yyyy}`;
  }

  // Build program list with strict lang data sourcing
  function buildUnified() {
    const lang = window.lang;

    // Single-offer mode: lock the program list to the post key.
    if (FIXED_KEY) {
      const parsed = parseFixedKey(FIXED_KEY);
      const city = (parsed && parsed.city) ? parsed.city : window.city;
      const deg = (parsed && Number.isFinite(parsed.deg)) ? parsed.deg : null;

      if (lang === 'en') {
        const list = (window.RAW.en && window.RAW.en[city]) || [];
        const hit = list.find(it => matchItemByFixedKey(it, FIXED_KEY, parsed) && (deg ? Number(it.deg) === Number(deg) : true));
        if (!hit) return [];
        return [Object.assign({}, hit, { modes: ['s'], dn: hit.s ? (hit.k + ' — ' + hit.s) : hit.k })];
      }

      const sl = window.RAW.pl[city] ? (window.RAW.pl[city].s || []) : [];
      const nl = window.RAW.pl[city] ? (window.RAW.pl[city].n || []) : [];

      const sHit = sl.find(it => matchItemByFixedKey(it, FIXED_KEY, parsed) && (deg ? Number(it.deg) === Number(deg) : true));
      const nHit = nl.find(it => matchItemByFixedKey(it, FIXED_KEY, parsed) && (deg ? Number(it.deg) === Number(deg) : true));

      const rep = sHit || nHit;
      if (!rep) return [];

      const modes = [];
      if (sHit) modes.push('s');
      if (nHit) modes.push('n');

      return [Object.assign({}, rep, { modes: modes.length ? modes : ['s'], dn: rep.s ? (rep.k + ' — ' + rep.s) : rep.k })];
    }
    
    // Filter list if UABY is active
    if (window.uaby && window.city === 'wro') {
      const uabyData = window.UABY[lang] || {};
      const res = [];
      Object.keys(uabyData).forEach(courseName => {
        Object.keys(uabyData[courseName]).forEach(degree => {
          let ps = '', ak = '';
          let rekr = 0, wps = 0;
          const cleanName = courseName.trim().toLowerCase();
          const d = parseInt(degree, 10);
          
          const matchCourse = (origK) => {
             const kName = (origK || '').trim().toLowerCase();
             return kName === cleanName || 'en' + kName === cleanName || kName === 'en' + cleanName || cleanName.replace(/^en\s*/, '') === kName;
          };

          // Strict source separation based on language
          if (lang === 'pl') {
            const list = (window.RAW.pl.wro && window.RAW.pl.wro.s) || [];
            for (let i = 0; i < list.length; i++) {
               if (list[i].deg === d && matchCourse(list[i].k)) {
                  ps = list[i].ps;
                  ak = list[i].ak;
                  // Enrollment fees should stay PLN for PL flow even in UABY view.
                  rekr = Number(list[i].rekr || 0);
                  wps = Number(list[i].wps || 0);
                  break;
               }
            }
          } else {
            const list = window.RAW.en.wro || [];
            for (let i = 0; i < list.length; i++) {
               if (list[i].deg === d && matchCourse(list[i].k)) {
                  ps = list[i].ps;
                  ak = list[i].ak;
                  // Keep these for completeness (EN may render different fee block).
                  rekr = Number(list[i].rekr || 0);
                  wps = Number(list[i].wps || 0);
                  break;
               }
            }
          }

          res.push({
            k: courseName,
            deg: d,
            modes: ['s'],
            dn: courseName,
            uabyOnly: true,
            ps: ps,
            ak: ak,
            rekr: rekr,
            wps: wps
          });
        });
      });
      return res;
    }

    // Default build for standard view
    if (lang === 'en') {
      const er = (window.RAW.en && window.RAW.en[window.city]) || [], res = [], seen = {};
      er.forEach(it => {
        const gk = it.deg + '|' + (it.k || '').trim().toLowerCase();
        if (!seen[gk]) {
          seen[gk] = 1;
          res.push(Object.assign({}, it, { modes: ['s'], dn: it.k }));
        }
        if (it.s && !seen[gk + '|' + (it.s || '').trim().toLowerCase()]) {
          seen[gk + '|' + (it.s || '').trim().toLowerCase()] = 1;
          res.push(Object.assign({}, it, { modes: ['s'], dn: it.k + ' — ' + it.s }));
        }
      });
      return res;
    }

    const sl = window.RAW.pl[window.city] ? window.RAW.pl[window.city].s || [] : [];
    const nl = window.RAW.pl[window.city] ? window.RAW.pl[window.city].n || [] : [];
    const groups = {}, gOrd = [];
    
    sl.concat(nl).forEach(it => {
      const cleanK = (it.k || '').trim().toLowerCase();
      const gk = it.deg + '|' + cleanK;
      if (!groups[gk]) { groups[gk] = { deg: it.deg, k: it.k, cleanK: cleanK, rep: it, specs: [] }; gOrd.push(gk); }
      const sp = normSpec(it.s);
      if (sp) {
        let dup = false;
        const cleanS = sp.trim().toLowerCase();
        for (let i = 0; i < groups[gk].specs.length; i++) { 
            if (normSpec(groups[gk].specs[i].s).trim().toLowerCase() === cleanS) { dup = true; break; } 
        }
        if (!dup) groups[gk].specs.push(it);
      }
    });
    
    const res = [];
    gOrd.forEach(gk => {
      const g = groups[gk];
      const inS = sl.some(x => x.deg === g.deg && (x.k || '').trim().toLowerCase() === g.cleanK);
      const inN = nl.some(x => x.deg === g.deg && (x.k || '').trim().toLowerCase() === g.cleanK);
      const modes = [];
      if (inS) modes.push('s');
      if (inN) modes.push('n');
      res.push(Object.assign({}, g.rep, { s: null, modes: modes, dn: g.k }));
      
      g.specs.forEach(sp => {
        const sm = [];
        const cleanSpS = (sp.s || '').trim().toLowerCase();
        if (sl.some(x => x.deg === sp.deg && (x.k || '').trim().toLowerCase() === g.cleanK && (x.s || '').trim().toLowerCase() === cleanSpS)) sm.push('s');
        if (nl.some(x => x.deg === sp.deg && (x.k || '').trim().toLowerCase() === g.cleanK && (x.s || '').trim().toLowerCase() === cleanSpS)) sm.push('n');
        res.push(Object.assign({}, sp, { modes: sm, dn: sp.k + ' — ' + sp.s }));
      });
    });
    return res;
  }

  function getItem() {
    const u = window.unified[window.progIdx];
    if (!u) return null;
    if (window.lang === 'en' || u.uabyOnly) return u;

    // Single-offer mode: always match the exact row by key + mode.
    // This avoids incorrect matches when multiple rows share the same course name/degree.
    if (FIXED_KEY) {
      const list = (window.RAW.pl[window.city] && window.RAW.pl[window.city][window.mode]) || [];
      for (let i = 0; i < list.length; i++) {
        if (matchItemByFixedKey(list[i], FIXED_KEY, PARSED_FIXED)) return list[i];
      }
      // If no exact match, return null so empty-state can show.
      return null;
    }

    const list = (window.RAW.pl[window.city] && window.RAW.pl[window.city][window.mode]) || [];
    const uK = (u.k || '').trim().toLowerCase();
    const uS = normSpec(u.s).trim().toLowerCase();
    for (let i = 0; i < list.length; i++) {
      if (list[i].deg === u.deg && (list[i].k || '').trim().toLowerCase() === uK && normSpec(list[i].s).trim().toLowerCase() === uS) return list[i];
    }
    // Fallback: if specialization row isn't present in RAW, match by degree+course name.
    for (let i = 0; i < list.length; i++) {
      if (list[i].deg === u.deg && (list[i].k || '').trim().toLowerCase() === uK) return list[i];
    }
    return u;
  }

  function getEA(bAnn) {
    const ids = Object.keys(window.selP).filter(id => window.selP[id] && id !== 'jednorazowo');
    if (!ids.length) return { eff: bAnn, disc: 0, active: false };
    const ap = window.PROMOS.filter(p => window.selP[p.id] && !p.isBonus);
    let pp = null;
    for (let i = 0; i < ap.length; i++) { if (ap[i].disc.t === 'pct') { pp = ap[i]; break; } }
    if (pp) {
      const pct = pp.id === 'absolwent_pl' ? window.subP.absolwent_pl : pp.disc.v;
      const d = Math.round(bAnn * pct);
      return { eff: bAnn - d, disc: d, active: true };
    }
    let tot = 0;
    ap.forEach(p => { tot += p.id === 'grupie' ? window.subP.grupie : (p.disc.v || 0); });
    return { eff: Math.max(0, bAnn - tot), disc: tot, active: true };
  }

  function getPP(pid, item, u) {
    if (window.uaby && window.city === 'wro') {
      const tbl = window.lang === 'pl' ? window.UABY.pl : window.UABY.en, ub = tbl && tbl[u.k] && tbl[u.k][u.deg];
      if (!ub) return null;
      if (pid === 'rok') return { pr: ub.r, un: 'EUR / rok', cur: 'EUR' };
      if (pid === 'sem') return { pr: ub.s, un: 'EUR / semestr', cur: 'EUR' };
      return null;
    }

    if (window.lang === 'en') {
      const pr = window.isEU ? item.eu : item.ne, eb = window.selP['earlybirds'], ab = window.selP['absolwent_en'];
      if (pid === 'rok') {
        if (!pr || !pr.r || pr.r <= 0) return null;
        if (eb) {
          const de = Math.round(pr.r * .1);
          return { pr: Math.round(pr.r * .9), un: 'EUR / rok', cur: 'EUR', sv: de, svl: 'Early Birds −' + fmt(de) + ' EUR' };
        }
        if (ab) {
          const da = Math.round(pr.r * .2);
          return { pr: Math.round(pr.r * .8), un: 'EUR / rok', cur: 'EUR', sv: da, svl: '−' + fmt(da) + ' EUR' };
        }
        return { pr: pr.r, un: 'EUR / rok', cur: 'EUR' };
      }
      if (pid === 'sem') {
        if (!pr || !pr.s || pr.s <= 0) return null;
        if (eb) return { pr: pr.s, un: 'EUR / semestr (Early Birds requires annual plan)', cur: 'EUR' };
        if (ab) {
          const ds = Math.round(pr.s * .2);
          return { pr: Math.round(pr.s * .8), un: 'EUR / semestr', cur: 'EUR', sv: ds, svl: '−' + fmt(ds) + ' EUR' };
        }
        return { pr: pr.s, un: 'EUR / semestr', cur: 'EUR' };
      }
      return null;
    }

    const bon = window.selP['jednorazowo'];

    // IMPORTANT: Use exact sheet columns for installment plans:
    // - r12 => R12 column (12 payments)
    // - r10 => R10 column (10 payments)
    // Discounts should apply to the total amount for the selected plan.
    if (pid === 'r12') {
      if (!item.r12 || item.r12 <= 0) return null;
      const baseTot = Math.round(item.r12 * 12);
      const ea = getEA(baseTot);
      return { pr: Math.round(ea.eff / 12), un: 'zł / ratę (12 rat)', cur: 'PLN' };
    }
    if (pid === 'r10') {
      if (!item.r10 || item.r10 <= 0) return null;
      const baseTot = Math.round(item.r10 * 10);
      const ea = getEA(baseTot);
      return { pr: Math.round(ea.eff / 10), un: 'zł / ratę (10 rat)', cur: 'PLN' };
    }

    // For upfront payment variants, use annual total based on R12 column.
    const bAnn = (item.r12 || 0) * 12;
    const ea = getEA(bAnn);
    if (pid === 'sem') {
      if (item.r12 <= 0) return null;
      if (bon) {
        const sd = Math.round(ea.eff / 2 * .05);
        return { pr: Math.round(ea.eff / 2 * .95), un: 'zł za semestr (−5%)', cur: 'PLN', sv: sd, svl: 'oszczędzasz ' + fmt(sd) + ' zł' };
      }
      return { pr: Math.round(ea.eff / 2), un: 'zł za semestr', cur: 'PLN' };
    }
    if (pid === 'rok') {
      if (item.r12 <= 0) return null;
      if (bon) {
        const rd = Math.round(ea.eff * .10);
        return { pr: Math.round(ea.eff * .90), un: 'zł za rok (−10%)', cur: 'PLN', sv: rd, svl: 'oszczędzasz ' + fmt(rd) + ' zł' };
      }
      return { pr: ea.eff, un: 'zł za rok', cur: 'PLN' };
    }
    return null;
  }

  function getElig(u) {
    return window.PROMOS.filter(pr => pr.lng === window.lang && (!pr.deg || pr.deg === u.deg) && (pr.cty === 'both' || pr.cty === window.city) && (!window.uaby || window.city !== 'wro'));
  }

  function canSel(id) {
    const pr = window.PROMOS.find(p => p.id === id);
    if (!pr) return true;
    const cur = Object.keys(window.selP).filter(i => window.selP[i] && i !== id);
    return !cur.some(oid => pr.sw.indexOf(oid) < 0);
  }

  function togglePromo(id) {
    if (window.selP[id]) window.selP[id] = false;
    else {
      const pr = window.PROMOS.find(p => p.id === id);
      if (!pr) return;
      Object.keys(window.selP).forEach(oid => { if (window.selP[oid] && pr.sw.indexOf(oid) < 0 && oid !== id) window.selP[oid] = false; });
      window.selP[id] = true;
    }
    render();
  }

  function toggleExp(id, ev) { if (ev) ev.stopPropagation(); window.expP[id] = !window.expP[id]; render(); }

  function setSub(pid, v) { window.subP[pid] = v; render(); }

  function setCity(c) {
    window.city = c;
    window.progIdx = 0;
    window.selP = { jednorazowo: false };
    window.uaby = false;
    document.getElementById('uaby-row')?.classList.remove('on');
    document.getElementById('uaby-chk')?.classList.remove('on');
    const uWrap = document.getElementById('uaby-wrap');
    if (uWrap) uWrap.style.display = c === 'wro' ? 'block' : 'none';
    const euWrap = document.getElementById('eu-wrap');
    if (euWrap) euWrap.style.display = (window.lang === 'en' && !window.uaby) ? 'block' : 'none';
    document.querySelectorAll('#city-row .seg-btn').forEach(b => b.classList.toggle('on', b.getAttribute('data-val') === c));
    render();
  }

  function setLang(l) {
    window.lang = l;
    window.progIdx = 0;
    window.selP = { jednorazowo: false };
    window.plan = l === 'pl' ? 'r12' : 'rok';
    document.querySelectorAll('#lang-row .seg-btn').forEach(b => b.classList.toggle('on', b.getAttribute('data-val') === l));
    const euWrap = document.getElementById('eu-wrap');
    if (euWrap) euWrap.style.display = (l === 'en' && !window.uaby) ? 'block' : 'none';
    render();
  }

  function toggleUABY() {
    window.uaby = !window.uaby;
    window.progIdx = 0;
    window.selP = { jednorazowo: false };
    document.getElementById('uaby-row')?.classList.toggle('on', window.uaby);
    document.getElementById('uaby-chk')?.classList.toggle('on', window.uaby);
    const euWrap = document.getElementById('eu-wrap');
    if (euWrap) euWrap.style.display = (window.lang === 'en' && !window.uaby) ? 'block' : 'none';
    render();
  }

  function setMode(m) { window.mode = m; updateMB(); render(); }
  function setEU(v) { window.isEU = v; document.querySelectorAll('#eu-row .pill').forEach(b => b.classList.toggle('on', (b.getAttribute('data-val') === 'eu' || b.getAttribute('data-val') === 'true') === v)); render(); }
  function onProgChange() { window.selP = { jednorazowo: false }; updateMB(); render(); }

  function updateMB() {
    const u = window.unified[window.progIdx], mw = document.getElementById('mode-wrap');
    // Hide "Forma studiów" if:
    // - no program
    // - EN (no stacjonarne/niestacjonarne split here)
    // - UABY-only program
    // - only 1 mode available (no choice)
    if (!u || window.lang === 'en' || u.uabyOnly || !Array.isArray(u.modes) || u.modes.length <= 1) {
      if (mw) mw.style.display = 'none';
      // Still keep window.mode consistent when there's exactly one mode.
      if (u && Array.isArray(u.modes) && u.modes.length === 1) window.mode = u.modes[0];
      return;
    }
    if (mw) mw.style.display = 'block';
    if (u.modes.indexOf(window.mode) < 0) window.mode = u.modes[0];
    const mr = document.getElementById('mode-row');
    if (!mr) return;
    mr.innerHTML = '';
    u.modes.forEach(m => {
      const btn = document.createElement('button');
      btn.className = 'pill' + (window.mode === m ? ' on' : '');
      btn.textContent = m === 's' ? 'Stacjonarne' : 'Niestacjonarne';
      btn.onclick = () => setMode(m);
      mr.appendChild(btn);
    });
  }

  function buildSel() {
    const ps = document.getElementById('prog-sel');
    if (!ps) return;
    ps.innerHTML = '';
    [1, 2].forEach(deg => {
      const items = window.unified.filter(x => x.deg === deg);
      if (!items.length) return;
      const g = document.createElement('optgroup');
      g.label = window.lang === 'pl' ? (deg === 1 ? 'Studia I stopnia' : 'Studia II stopnia') : (deg === 1 ? 'Bachelor / BSc' : 'Master / MA');
      items.forEach(it => {
        const o = document.createElement('option');
        o.value = window.unified.indexOf(it);
        o.textContent = it.dn;
        g.appendChild(o);
      });
      ps.appendChild(g);
    });
  }

  // Fallback support for English Links
  function updateCTAs(item) {
    const bm = document.getElementById('btn-more'), ba = document.getElementById('btn-apply');
    if (!bm || !ba) return;

    if (HIDE_MORE_BTN) {
      bm.style.display = 'none';
    }
    
    const urlStr = (item.ps || '').trim();
    if (urlStr && urlStr !== '—') {
      if (!HIDE_MORE_BTN) bm.style.display = '';
      const enDeg = item.deg === 1 ? 'bachelor' : 'master';
      const plDeg = item.deg === 1 ? 'studia-1-stopnia' : 'studia-2-stopnia';
      bm.href = urlStr.startsWith('http') ? urlStr : (window.lang === 'en' ? 'https://akademiata.pl/en/offer/' + enDeg + '/' : 'https://akademiata.pl/oferta/' + plDeg + '/') + urlStr + '/';
      bm.textContent = t('ctaMore', 'Więcej o programie →');
    } else bm.style.display = 'none';

    const rawAk = (item.ak || '').trim();
    let saVal = null;
    
    // Strict fallback: if raw link is present in EN data, use it directly if mapping fails
    if (rawAk && rawAk !== '—') {
      saVal = window.lang === 'en' ? (window.SA_EN[rawAk] || rawAk) : (window.SA[rawAk] || rawAk);
    }

    if (saVal) {
      ba.style.display = '';
      ba.href = saVal.startsWith('http') ? saVal : (window.lang === 'en' ? window.BASE_EN : window.BASE) + saVal;
      ba.textContent = t('ctaApply', 'Zapisz się →');
    } else {
      ba.style.display = 'none';
    }
  }

  function getPL(pid) { return pid === 'r12' ? '12 rat miesięcznych' : pid === 'r10' ? '10 rat miesięcznych' : pid === 'sem' ? 'Semestr z góry' : 'Rok z góry'; }

  function updatePriceFromBanner(pp, pid) {
    const wrap = document.getElementById('priseScroll');
    if (!wrap) return;

    const strong = wrap.querySelector('strong');
    if (!strong) return;

    if (!pp || !pid) return;

    const isEn = window.lang === 'en';
    const fromTxt = isEn ? 'from' : 'już od';

    // Unit formatting aligned with existing banner expectations:
    // - PL installments are monthly ("zł/mies.")
    // - EN installs typically show "/month"
    let unit = pp.cur || '';
    if (pid === 'r12' || pid === 'r10') {
      if (pp.cur === 'PLN') unit = 'zł/mies.';
      else if (pp.cur === 'EUR') unit = isEn ? '€/month' : 'EUR/mies.';
    } else {
      // Keep currency only for upfront variants.
      if (pp.cur === 'PLN') unit = 'zł';
      if (pp.cur === 'EUR') unit = '€';
    }

    strong.textContent = `${fromTxt} ${fmt(pp.pr)} ${unit}`.trim();
  }

  function render() {
    window.unified = buildUnified();
    const progCount = document.getElementById('prog-count');
    if (progCount) progCount.textContent = window.unified.length + ' opcji';
    if (!window.unified.length) {
      // Show friendly empty state instead of leaving UI with blanks.
      setEmptyState(true);

      // Developer diagnostics (do not show in UI).
      try {
        console.warn('[PricesCalculator] No matching data for calculator', {
          fixedKey: FIXED_KEY || null,
          fixedLang: FIXED_LANG || null,
          city: window.city || null,
          lang: window.lang || null,
        });
      } catch (e) {}

      const plansWrap = document.getElementById('plans-wrap');
      if (plansWrap) plansWrap.innerHTML = '';
      document.getElementById('promos-section')?.style && (document.getElementById('promos-section').style.display = 'none');
      document.getElementById('sum-box')?.style && (document.getElementById('sum-box').style.display = 'none');
      document.getElementById('enr-box')?.style && (document.getElementById('enr-box').style.display = 'none');
      document.getElementById('btn-apply')?.style && (document.getElementById('btn-apply').style.display = 'none');
      document.getElementById('mode-wrap')?.style && (document.getElementById('mode-wrap').style.display = 'none');
      document.getElementById('uaby-wrap')?.style && (document.getElementById('uaby-wrap').style.display = 'none');
      return;
    }

    setEmptyState(false);

    if (!FIXED_KEY) buildSel();
    if (window.progIdx >= window.unified.length) window.progIdx = 0;
    const progSel = document.getElementById('prog-sel');
    if (progSel) {
      if (FIXED_KEY) {
        progSel.innerHTML = '';
        const o = document.createElement('option');
        o.value = '0';
        o.textContent = window.unified[0].dn || window.unified[0].k || '';
        progSel.appendChild(o);
        progSel.value = '0';
        window.progIdx = 0;
      } else {
        progSel.value = window.progIdx;
      }
    }

    updateMB();
    const u = window.unified[window.progIdx], item = getItem();
    if (!item) {
      setEmptyState(true);
      try {
        console.warn('[PricesCalculator] No matching RAW row for current selection', {
          fixedKey: FIXED_KEY || null,
          city: window.city || null,
          mode: window.mode || null,
          lang: window.lang || null,
        });
      } catch (e) {}
      return;
    }
    updateCTAs(item);

    let pids = window.lang === 'pl' ? ['r12', 'r10', 'sem', 'rok'] : ['rok', 'sem'];
    if (window.uaby && window.city === 'wro') pids = window.lang === 'en' ? ['rok'] : ['rok', 'sem'];

    let validPids = pids.filter(pid => getPP(pid, item, u) !== null);
    if (!validPids.includes(window.plan) && validPids.length > 0) window.plan = validPids[0];

    const plansWrap = document.getElementById('plans-wrap');
    let planHeader = plansWrap && plansWrap.previousElementSibling && plansWrap.previousElementSibling.classList.contains('sec') ? plansWrap.previousElementSibling : null;
    if (!planHeader) {
      document.querySelectorAll('.sec').forEach(el => {
        if (el.textContent.includes('Wariant') || el.textContent.includes('Payment')) planHeader = el;
      });
    }

    if (window.uaby && validPids.length <= 1) {
      if (plansWrap) plansWrap.style.display = 'none';
      if (planHeader) planHeader.style.display = 'none';
    } else {
      if (plansWrap) plansWrap.style.display = '';
      if (planHeader) planHeader.style.display = '';

      const tpl = document.getElementById('plan-card-template');
      if (!plansWrap) return;
      // Preserve horizontal scroll position across re-renders (promos/plan changes call render()).
      const prevPlansEl = plansWrap.querySelector('.plans');
      const prevScrollLeft = prevPlansEl ? prevPlansEl.scrollLeft : 0;
      const shouldPreserveScroll = window.matchMedia ? window.matchMedia('(max-width: 820px)').matches : false;

      plansWrap.innerHTML = '';

      const plansEl = document.createElement('div');
      plansEl.className = 'plans';

      pids.forEach(pid => {
        const pp = getPP(pid, item, u);
        if (!pp) return;

        // Use template if present; otherwise fallback to legacy markup.
        let card = null;
        if (tpl && 'content' in tpl && tpl.content && tpl.content.firstElementChild) {
          card = tpl.content.firstElementChild.cloneNode(true);
        } else {
          card = buildPlanCardFallback();
        }

        card.classList.toggle('sel', window.plan === pid);
        card.setAttribute('data-plan-id', pid);
        card.onclick = () => { window.plan = pid; window.render(); };

        const label = getPL(pid);
        setTextAll(card, '[data-plan-label]', label);
        setTextAll(card, '[data-plan-label-desktop]', label);

        const price = fmt(pp.pr);
        setTextAll(card, '[data-plan-price]', price);
        setTextAll(card, '[data-plan-price-desktop]', price);

        const unitEl = card.querySelector('[data-plan-unit]');
        const unitDesktopEl = card.querySelector('[data-plan-unit-desktop]');
        const unitTxt = (() => {
          if (pid === 'r12' || pid === 'r10') {
            return pp.cur === 'PLN' ? 'zł/m-c' : (pp.cur === 'EUR' ? '€/month' : '');
          }
          return getCurSymbol(pp.cur);
        })();
        if (unitEl) unitEl.textContent = unitTxt;
        if (unitDesktopEl) unitDesktopEl.textContent = unitTxt;

        const svEl = card.querySelector('[data-plan-sv]');
        if (svEl) {
          if (pp.sv && pp.svl) {
            svEl.textContent = pp.svl;
            svEl.style.display = '';
          } else {
            svEl.textContent = '';
            svEl.style.display = 'none';
          }
        }

        const pickEl = card.querySelector('[data-plan-pick]');
        const isPick = window.lang === 'pl' && pid === 'r12';
        if (pickEl) pickEl.style.display = isPick ? 'block' : 'none';

        const discEl = card.querySelector('[data-plan-disc]');
        if (discEl) {
          const pill = inferPercentPill(pp.un);
          if (pill) {
            discEl.textContent = pill;
            discEl.style.display = '';
          } else {
            discEl.textContent = '';
            discEl.style.display = 'none';
          }
        }

        plansEl.appendChild(card);
      });

      plansWrap.appendChild(plansEl);

      // Restore scroll, then keep selected card in view.
      if (shouldPreserveScroll) {
        plansEl.scrollLeft = prevScrollLeft;
        const sel = plansEl.querySelector('.pc.sel');
        if (sel && typeof sel.scrollIntoView === 'function') {
          try { sel.scrollIntoView({ block: 'nearest', inline: 'nearest' }); } catch (e) {}
        }
        // Sync hint label with current scroll position.
        try { window.setTimeout(() => updatePlansHint(plansEl), 0); } catch (e) {}

        // Update hint as user scrolls.
        plansEl.addEventListener('scroll', () => {
          updatePlansHint(plansEl);
        }, { passive: true });
      }
    }

    const elig = getElig(u), ps2 = document.getElementById('promos') || document.getElementById('promos-section'), pi = document.getElementById('promos-inner');
    if (elig.length && !window.uaby) {
      if (ps2) ps2.style.display = '';
      const tpl = document.getElementById('promo-card-template');
      if (pi) pi.innerHTML = '';
      elig.forEach(promo => {
        const isSel = window.selP[promo.id];
        const isExp = window.expP[promo.id];
        const canS = !isSel && canSel(promo.id);
        const sh = (promo.short || '').trim();
        const isGoodShort = /^rata\s*:|^rate\s*:/i.test(sh);

        if (!pi || !tpl || !('content' in tpl)) return;
        const card = tpl.content.firstElementChild.cloneNode(true);

        card.classList.toggle('sel', !!isSel);
        card.classList.toggle('open', !!isExp);
        card.classList.toggle('dis', !isSel && !canS);

        const head = card.querySelector('.pc-head');
        if (head) head.setAttribute('onclick', `window.togglePromo('${promo.id}')`);

        const nameEl = card.querySelector('[data-promo-name]');
        if (nameEl) nameEl.textContent = promo.name || '';

        const shortEl = card.querySelector('[data-promo-short]');
        if (shortEl) {
          shortEl.classList.toggle('good', isGoodShort);
          shortEl.innerHTML = formatPromoHtml(promo.short);
        }

        const tagEl = card.querySelector('[data-promo-tag]');
        if (tagEl) tagEl.textContent = promo.tag || '';

        const arr = card.querySelector('[data-promo-arr]');
        if (arr) {
          arr.classList.toggle('open', !!isExp);
          arr.setAttribute('onclick', `window.toggleExp('${promo.id}',event)`);
        }

        const body = card.querySelector('[data-promo-body]');
        setPromoCardBody(body, !!isExp);
        if (body && isExp) {
          const bodyText = body.querySelector('[data-promo-body-text]');
          if (bodyText) bodyText.innerHTML = formatPromoHtml(promo.full);

          const subWrap = body.querySelector('[data-promo-subopts]');
          const subOpts = inferSubOptions(promo);
          // Only render sub-option buttons for promos that are truly user-configurable
          // (i.e. we have a dedicated entry in window.subP for that promo id).
          // This prevents accidental pc-subopts for fixed-value promos if data contains stray `so`.
          const isConfigurablePromo = !!(window.subP && Object.prototype.hasOwnProperty.call(window.subP, promo.id));
          const shouldShowSubopts = !!isSel && isConfigurablePromo && subOpts.length >= 2;

          if (subWrap) {
            subWrap.innerHTML = '';
            if (shouldShowSubopts) {
              subWrap.style.display = '';
              subOpts.slice(0, 5).forEach(so => {
                const b = document.createElement('button');
                b.type = 'button';
                b.className = 'pc-so' + (window.subP[promo.id] === so.v ? ' on' : '');
                b.textContent = so.l || String(so.v);
                b.onclick = (ev) => {
                  if (ev) ev.stopPropagation();
                  window.setSub(promo.id, so.v);
                };
                subWrap.appendChild(b);
              });
            } else {
              subWrap.style.display = 'none';
            }
          }
        } else if (body) {
          // When collapsed, clear dynamic content but keep skeleton nodes.
          const bodyText = body.querySelector('[data-promo-body-text]');
          if (bodyText) bodyText.innerHTML = '';
          const subWrap = body.querySelector('[data-promo-subopts]');
          if (subWrap) { subWrap.innerHTML = ''; subWrap.style.display = 'none'; }
        }

        pi.appendChild(card);
      });
    } else if (ps2) ps2.style.display = 'none';

    const ppS = getPP(window.plan, item, u), sb = document.getElementById('sum-box');
    if (ppS && sb) {
      const degL = u.deg === 1 ? (window.lang === 'pl' ? 'Studia I stopnia' : 'Bachelor studies') : (window.lang === 'pl' ? 'Studia II stopnia' : 'Master studies');
      const tsv = (window.lang === 'pl' && !window.uaby ? getEA(item.r12 * 12).disc : 0) + (ppS.sv || 0);
      // Design: header line should always be "KIERUNEK · POZIOM" (course name from column D).
      const spLine = (u.k ? String(u.k) : '') + (degL ? ' · ' + degL : '');
      const snLine = (u.s || u.k || '');
      const spEl = sb.querySelector('[data-sum-sp]');
      const snEl = sb.querySelector('[data-sum-sn]');
      const priceEl = sb.querySelector('[data-sum-price]');
      const saveEl = sb.querySelector('[data-sum-save]');

      if (spEl) spEl.textContent = spLine;
      if (snEl) snEl.textContent = snLine;
      if (priceEl) priceEl.textContent = fmt(ppS.pr) + ' ' + ppS.cur;

      if (saveEl) {
        if (tsv > 0) {
          saveEl.textContent = 'oszczędzasz ' + fmt(tsv) + ' ' + ppS.cur + '/rok';
          saveEl.style.display = '';
        } else {
          saveEl.textContent = '';
          saveEl.style.display = 'none';
        }
      }
      sb.style.display = '';

      // Mobile "WYBRANY KIERUNEK" card: title + meta (use the same sl line as sum-box).
      const mobileTitleEl = document.querySelector('[data-prog-mobile-title]');
      const mobileMetaEl = document.querySelector('[data-prog-mobile-meta]');
      if (mobileTitleEl) mobileTitleEl.textContent = (u && u.k) ? String(u.k) : (snLine || '—');
      if (mobileMetaEl) {
        // Always show "Forma studiów" in the select meta on PL mobile.
        // If the program has only one mode, reflect that single mode even when mode selector is hidden.
        const effectiveMode = (u && Array.isArray(u.modes) && u.modes.length === 1) ? u.modes[0] : window.mode;
        const modeLabel =
          window.lang === 'pl'
            ? (effectiveMode === 'n' ? 'Niestacjonarne' : 'Stacjonarne')
            : '';
        const meta = [degL, modeLabel].filter(Boolean).join(' · ');
        mobileMetaEl.textContent = meta || spLine || '—';
      }
    } else if (sb) sb.style.display = 'none';

    // Update the "CENA / już od" banner on single offer header (if present).
    if (ppS) updatePriceFromBanner(ppS, window.plan);

    const enrBox = document.getElementById('enr-box');
    if (enrBox) enrBox.style.display = '';

    const enrItems = document.getElementById('enr-items');
    if (enrItems) {
      // UABY checkbox: show UABY enrollment fees (EUR) only when checked.
      if (window.uaby && window.city === 'wro') {
        const ub = (window.lang === 'pl' ? window.UABY.pl : window.UABY.en)[u.k]?.[u.deg];
        const r = ub?.rekr || 20, a = ub?.apl || 100;
        const admissionLbl = t('feeAdmission', 'Opłata rekrutacyjna');
        const applicationLbl = t('feeApplication', 'Opłata aplikacyjna');
        const totalLbl = t('feeTotal', 'Razem przy zapisie');
        enrItems.innerHTML = '<div class="ei"><div class="en">' + admissionLbl + '</div><div class="ev">' + fmt(r) + ' EUR</div></div><div class="ei"><div class="en">' + applicationLbl + '</div><div class="ev">' + fmt(a) + ' EUR</div></div><div class="ei"><div class="en">' + totalLbl + '</div><div class="ev">' + fmt(r + a) + ' EUR</div></div>';
      } else {
        // Ensure PLN/standard skeleton exists (EN/UABY branches may overwrite innerHTML).
        const hasSkeleton = !!enrItems.querySelector('[data-enr-value="admission"]');
        if (!hasSkeleton) {
          enrItems.innerHTML = `
            <div class="ei" data-enr-item="admission">
              <div class="en" data-enr-label="admission">—</div>
              <div class="ev" data-enr-value="admission">—</div>
            </div>

            <div class="ei ei--promo" data-enr-item="entry">
              <div class="en" data-enr-label="entry">—</div>
              <div class="ev" data-enr-value="entry">—</div>
              <div class="eb" data-enr-badge="entry" style="display:none">
                <span class="eb-ic" aria-hidden="true">⏰</span>
                <span data-enr-badge-text="entry"></span>
              </div>
            </div>

            <div class="ei ei--total" data-enr-item="total">
              <div class="en" data-enr-label="total">—</div>
              <div class="ev" data-enr-value="total">—</div>
              <div class="es" data-enr-savings style="display:none"></div>
            </div>
          `.trim();
        }

        const cur = window.lang === 'pl' ? ' PLN' : ' EUR';
        const admissionLbl = t('feeAdmission', 'Opłata rekrutacyjna');
        const entryLbl = t('feeEntry', 'Wpisowe');
        const totalLbl = t('feeTotal', 'Razem przy zapisie');
        if (window.lang === 'pl') {
          const promoEntry = 0;
          const regularTotal = (item.rekr || 0) + (item.wps || 0);
          const promoTotal = (item.rekr || 0) + promoEntry;
          const savings = Math.max(0, regularTotal - promoTotal);
          const validTo = getEndOfCurrentMonthPL();
          // Prefer updating the static HTML skeleton (page-template-prices.php).
          const admissionLabelEl = enrItems.querySelector('[data-enr-label="admission"]');
          const entryLabelEl = enrItems.querySelector('[data-enr-label="entry"]');
          const totalLabelEl = enrItems.querySelector('[data-enr-label="total"]');
          const admissionValEl = enrItems.querySelector('[data-enr-value="admission"]');
          const entryValEl = enrItems.querySelector('[data-enr-value="entry"]');
          const totalValEl = enrItems.querySelector('[data-enr-value="total"]');
          const entryBadgeEl = enrItems.querySelector('[data-enr-badge="entry"]');
          const entryBadgeTextEl = enrItems.querySelector('[data-enr-badge-text="entry"]');
          const savingsEl = enrItems.querySelector('[data-enr-savings]');

          if (admissionLabelEl) admissionLabelEl.textContent = admissionLbl;
          if (entryLabelEl) entryLabelEl.textContent = entryLbl;
          if (totalLabelEl) totalLabelEl.textContent = totalLbl;

          if (admissionValEl) admissionValEl.textContent = fmt(item.rekr) + cur;
          if (entryValEl) entryValEl.textContent = fmt(promoEntry) + cur;
          if (totalValEl) totalValEl.textContent = fmt(promoTotal) + cur;

          if (entryBadgeTextEl) entryBadgeTextEl.textContent = 'do ' + validTo;
          if (entryBadgeEl) entryBadgeEl.style.display = '';

          if (savingsEl) {
            if (savings > 0) {
              savingsEl.innerHTML = 'zamiast ' + fmt(regularTotal) + ' PLN — oszczędzasz <strong>' + fmt(savings) + ' zł</strong>';
              savingsEl.style.display = '';
            } else {
              savingsEl.textContent = '';
              savingsEl.style.display = 'none';
            }
          }
        } else {
          // EN: update skeleton if present (so switching back to PL works).
          const admissionLabelEl = enrItems.querySelector('[data-enr-label="admission"]');
          const entryLabelEl = enrItems.querySelector('[data-enr-label="entry"]');
          const totalLabelEl = enrItems.querySelector('[data-enr-label="total"]');
          const admissionValEl = enrItems.querySelector('[data-enr-value="admission"]');
          const entryValEl = enrItems.querySelector('[data-enr-value="entry"]');
          const totalValEl = enrItems.querySelector('[data-enr-value="total"]');
          const entryBadgeEl = enrItems.querySelector('[data-enr-badge="entry"]');
          const savingsEl = enrItems.querySelector('[data-enr-savings]');

          if (admissionLabelEl) admissionLabelEl.textContent = admissionLbl;
          if (entryLabelEl) entryLabelEl.textContent = entryLbl;
          if (totalLabelEl) totalLabelEl.textContent = totalLbl;

          if (admissionValEl) admissionValEl.textContent = fmt(item.rekr) + cur;
          if (entryValEl) entryValEl.textContent = fmt(item.wps) + cur;
          if (totalValEl) totalValEl.textContent = fmt(item.rekr + item.wps) + cur;

          if (entryBadgeEl) entryBadgeEl.style.display = 'none';
          if (savingsEl) { savingsEl.textContent = ''; savingsEl.style.display = 'none'; }
        }
      }
    }
  }
}