const STORAGE_PREFIX = 'akademiata_nl_popup_';

function storageKey(id) {
    return STORAGE_PREFIX + id;
}

function readState(id) {
    try {
        const raw = window.localStorage.getItem(storageKey(id));
        if (!raw) {
            return null;
        }
        const data = JSON.parse(raw);
        return data && typeof data === 'object' ? data : null;
    } catch (e) {
        return null;
    }
}

function writeState(id, data) {
    try {
        window.localStorage.setItem(storageKey(id), JSON.stringify(data));
    } catch (e) {
        // ignore quota / private mode
    }
}

function isBlocked(id, rememberDays) {
    const state = readState(id);
    if (!state) {
        return false;
    }
    if (state.submitted) {
        return true;
    }
    if (!state.closedAt) {
        return false;
    }
    const days = Math.max(1, rememberDays || 14);
    return Date.now() - Number(state.closedAt) < days * 24 * 60 * 60 * 1000;
}

function lockScroll(on) {
    document.documentElement.classList.toggle('nl-popup-open', on);
    document.body.classList.toggle('nl-popup-open', on);
}

function openPopup(root) {
    if (!root || root.classList.contains('is-open')) {
        return;
    }
    root.hidden = false;
    root.classList.add('is-open');
    root.setAttribute('data-nl-open', '1');
    lockScroll(true);
    const closeBtn = root.querySelector('.nl-popup__close');
    if (closeBtn) {
        closeBtn.focus();
    }
}

function closePopup(root, remember) {
    if (!root) {
        return;
    }
    root.classList.remove('is-open');
    root.removeAttribute('data-nl-open');
    root.hidden = true;
    lockScroll(document.querySelector('.nl-popup.is-open') !== null);
    if (remember) {
        const id = root.getAttribute('data-nl-popup') || '';
        const prev = readState(id) || {};
        writeState(id, {
            submitted: !!prev.submitted,
            closedAt: Date.now(),
        });
    }
}

function markSubmitted(root) {
    const id = root.getAttribute('data-nl-popup') || '';
    writeState(id, { submitted: true, closedAt: Date.now() });
    const formWrap = root.querySelector('.nl-popup__form-wrap');
    const thanks = root.querySelector('.nl-popup__thanks');
    if (formWrap) {
        formWrap.hidden = true;
    }
    if (thanks) {
        thanks.hidden = false;
    }
}

function bindPopup(root) {
    const id = root.getAttribute('data-nl-popup') || '';
    const rememberDays = parseInt(root.getAttribute('data-nl-remember') || '14', 10);
    const delay = Math.max(0, parseInt(root.getAttribute('data-nl-delay') || '8', 10));
    const auto = root.getAttribute('data-nl-auto') === '1';

    root.querySelectorAll('[data-nl-close]').forEach((el) => {
        el.addEventListener('click', () => closePopup(root, true));
    });

    if (auto && !isBlocked(id, rememberDays)) {
        window.setTimeout(() => {
            if (!isBlocked(id, rememberDays) && !document.querySelector('.nl-popup.is-open')) {
                openPopup(root);
            }
        }, delay * 1000);
    }
}

document.addEventListener('click', (e) => {
    const trigger = e.target.closest ? e.target.closest('[data-nl-open]') : null;
    if (!trigger) {
        return;
    }
    const key = trigger.getAttribute('data-nl-open');
    const root = document.querySelector('.nl-popup[data-nl-popup="' + key + '"]');
    if (!root) {
        return;
    }
    e.preventDefault();
    openPopup(root);
});

document.addEventListener('keydown', (e) => {
    if (e.key !== 'Escape') {
        return;
    }
    const open = document.querySelector('.nl-popup.is-open');
    if (open) {
        closePopup(open, true);
    }
});

document.addEventListener('wpcf7mailsent', (event) => {
    const form = event.target;
    const root = form && form.closest ? form.closest('.nl-popup') : null;
    if (!root) {
        return;
    }
    markSubmitted(root);
});

document.addEventListener('wpcf7beforesubmit', (event) => {
    const form = event.target;
    if (!form || !form.closest || !form.closest('.nl-popup')) {
        return;
    }
    const btn = form.querySelector('.wpcf7-submit');
    if (btn && !btn.getAttribute('data-nl-label')) {
        btn.setAttribute('data-nl-label', btn.value);
        btn.value = 'Wysyłanie…';
        btn.disabled = true;
    }
});

['wpcf7mailsent', 'wpcf7mailfailed', 'wpcf7invalid', 'wpcf7spam', 'wpcf7failed', 'wpcf7aborted', 'wpcf7submit'].forEach((type) => {
    document.addEventListener(type, (event) => {
        const form = event.target;
        if (!form || !form.closest || !form.closest('.nl-popup')) {
            return;
        }
        const btn = form.querySelector('.wpcf7-submit');
        if (btn) {
            btn.disabled = false;
            btn.value = btn.getAttribute('data-nl-label') || btn.value;
        }
    });
});

function initNewsletterPopups() {
    document.querySelectorAll('.nl-popup').forEach(bindPopup);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initNewsletterPopups);
} else {
    initNewsletterPopups();
}
