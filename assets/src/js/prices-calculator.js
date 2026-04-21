export default function initPricesCalculator(_$, opts = {}) {
  const $ = _$ || window.jQuery;
  const content = document.getElementById('kalkulator-content');
  const loader = document.getElementById('ata-loader');
  const noteBot = document.getElementById('note-bot');
  const loaderShownAt = Date.now();
  const MIN_LOADER_MS = 350;

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
    render();
    setLoading(false);
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

  // Fetch from local file
  fetch(LOCAL_JSON_URL)
    .then(r => r.json())
    .then(localData => {
      applyData(localData);
      
      // Sync with Google API in background
      if (GOOGLE_API_URL) {
        fetch(withNoCache(GOOGLE_API_URL), { cache: 'no-store' })
          .then(r => r.json())
          .then(freshData => {
            // Only update UI if something actually changed.
            try {
              const freshJson = JSON.stringify(freshData);
              const freshHash = stableHash(freshJson);
              if (freshHash !== lastGoogleHash) {
                lastGoogleHash = freshHash;
                applyData(freshData);
              } else {
              }
            } catch (e) {
              applyData(freshData);
            }
          })
          .catch(err => {
            console.warn('Google API sync failed', err);
          });
      }
    })
    .catch(err => {
      if (GOOGLE_API_URL) {
        fetch(withNoCache(GOOGLE_API_URL), { cache: 'no-store' })
          .then(r => r.json())
          .then(freshData => {
            applyData(freshData);
          })
          .catch(e => console.error('Data source failed', e));
      } else {
        // No data sources available → stop loader (leave UI hidden)
        setLoading(false);
      }
    });

  // Event listeners
  function bindUI() {
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
  }

  function fmt(n) { return Math.round(n).toLocaleString('pl-PL'); }
  function normTxt(v) { return (v === undefined || v === null) ? '' : String(v).trim(); }
  function normSpec(v) {
    const s = normTxt(v);
    // In sheets/data, "—" often means "no specialization"
    return (s === '—' || s === '-') ? '' : s;
  }

  // Build program list with strict lang data sourcing
  function buildUnified() {
    const lang = window.lang;
    
    // Filter list if UABY is active
    if (window.uaby && window.city === 'wro') {
      const uabyData = window.UABY[lang] || {};
      const res = [];
      Object.keys(uabyData).forEach(courseName => {
        Object.keys(uabyData[courseName]).forEach(degree => {
          let ps = '', ak = '';
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
                  ps = list[i].ps; ak = list[i].ak; break;
               }
            }
          } else {
            const list = window.RAW.en.wro || [];
            for (let i = 0; i < list.length; i++) {
               if (list[i].deg === d && matchCourse(list[i].k)) {
                  ps = list[i].ps; ak = list[i].ak; break;
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
            ak: ak
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
    if (!u || window.lang === 'en' || u.uabyOnly) { if (mw) mw.style.display = 'none'; return; }
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
    
    const urlStr = (item.ps || '').trim();
    if (urlStr && urlStr !== '—') {
      bm.style.display = '';
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

  function render() {
    window.unified = buildUnified();
    const progCount = document.getElementById('prog-count');
    if (progCount) progCount.textContent = window.unified.length + ' opcji';
    if (!window.unified.length) return;

    buildSel();
    if (window.progIdx >= window.unified.length) window.progIdx = 0;
    const progSel = document.getElementById('prog-sel');
    if (progSel) progSel.value = window.progIdx;

    updateMB();
    const u = window.unified[window.progIdx], item = getItem();
    if (!item) return;
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
      
      let ph = '<div class="plans">';
      pids.forEach(pid => {
        const pp = getPP(pid, item, u);
        if (!pp) return;
        ph += '<div class="pc' + (window.plan === pid ? ' sel' : '') + '" onclick="window.plan=\'' + pid + '\'; window.render()"><div class="lbl">' + getPL(pid) + '</div><div class="pr">' + fmt(pp.pr) + '</div><div class="un">' + pp.un + '</div>' + (pp.sv ? '<div class="sv">' + pp.svl + '</div>' : '') + '</div>';
      });
      ph += '</div>';
      if (plansWrap) plansWrap.innerHTML = ph;
    }

    const elig = getElig(u), ps2 = document.getElementById('promos-section'), pi = document.getElementById('promos-inner');
    if (elig.length && !window.uaby) {
      if (ps2) ps2.style.display = '';
      let prH = '';
      elig.forEach(promo => {
        const isSel = window.selP[promo.id], isExp = window.expP[promo.id], canS = !isSel && canSel(promo.id);
        const sh = (promo.short || '').trim();
        const isGoodShort = /^rata\s*:|^rate\s*:/i.test(sh);
        prH += '<div class="promo-card' + (isSel ? ' sel' : '') + (!isSel && !canS ? ' dis' : '') + '"><div class="pc-head" onclick="window.togglePromo(\'' + promo.id + '\')"><div class="pc-chk"></div><div class="pc-info"><div class="pc-name">' + promo.name + '</div><div class="pc-short' + (isGoodShort ? ' good' : '') + '">' + promo.short + '</div></div><div class="pc-tag">' + (promo.tag || '') + '</div><button class="pc-arr' + (isExp ? ' open' : '') + '" onclick="window.toggleExp(\'' + promo.id + '\',event)">▾</button></div>' + (isExp ? '<div class="pc-body">' + promo.full + '</div>' : '') + '</div>';
      });
      if (pi) pi.innerHTML = prH;
    } else if (ps2) ps2.style.display = 'none';

    const ppS = getPP(window.plan, item, u), sb = document.getElementById('sum-box');
    if (ppS && sb) {
      const degL = u.deg === 1 ? (window.lang === 'pl' ? 'Studia I stopnia' : 'Bachelor studies') : (window.lang === 'pl' ? 'Studia II stopnia' : 'Master studies');
      const tsv = (window.lang === 'pl' && !window.uaby ? getEA(item.r12 * 12).disc : 0) + (ppS.sv || 0);
      sb.innerHTML = '<div class="sum"><div class="sl"><div class="sp">' + (u.s ? u.k + ' · ' + degL : degL) + '</div><div class="sn">' + (u.s || u.k) + '</div></div><div class="sr"><div class="sprice">' + fmt(ppS.pr) + ' ' + ppS.cur + '</div>' + (tsv > 0 ? '<div class="ssave">oszczędzasz ' + fmt(tsv) + ' ' + ppS.cur + '/rok</div>' : '') + '</div></div>';
      sb.style.display = '';
    } else if (sb) sb.style.display = 'none';

    const enrBox = document.getElementById('enr-box');
    if (enrBox) enrBox.style.display = '';

    const enrItems = document.getElementById('enr-items');
    if (enrItems) {
      if (window.uaby && window.city === 'wro') {
        const ub = (window.lang === 'pl' ? window.UABY.pl : window.UABY.en)[u.k]?.[u.deg];
        const r = ub?.rekr || 20, a = ub?.apl || 100;
        const admissionLbl = t('feeAdmission', 'Opłata rekrutacyjna');
        const applicationLbl = t('feeApplication', 'Opłata aplikacyjna');
        const totalLbl = t('feeTotal', 'Razem przy zapisie');
        enrItems.innerHTML = '<div class="ei"><div class="en">' + admissionLbl + '</div><div class="ev">' + fmt(r) + ' EUR</div></div><div class="ei"><div class="en">' + applicationLbl + '</div><div class="ev">' + fmt(a) + ' EUR</div></div><div class="ei"><div class="en">' + totalLbl + '</div><div class="ev">' + fmt(r + a) + ' EUR</div></div>';
      } else {
        const cur = window.lang === 'pl' ? ' PLN' : ' EUR';
        const admissionLbl = t('feeAdmission', 'Opłata rekrutacyjna');
        const entryLbl = t('feeEntry', 'Wpisowe');
        const totalLbl = t('feeTotal', 'Razem przy zapisie');
        enrItems.innerHTML = '<div class="ei"><div class="en">' + admissionLbl + '</div><div class="ev">' + fmt(item.rekr) + cur + '</div></div><div class="ei"><div class="en">' + entryLbl + '</div><div class="ev">' + fmt(item.wps) + cur + '</div></div><div class="ei"><div class="en">' + totalLbl + '</div><div class="ev">' + fmt(item.rekr + item.wps) + cur + '</div></div>';
      }
    }
  }
}