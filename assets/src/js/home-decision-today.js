(function () {
    const sections = document.querySelectorAll('.home-decision[data-countdown-ts]');
    if (!sections.length) {
        return;
    }

    function pad(value) {
        return String(Math.max(0, value)).padStart(2, '0');
    }

    function getParts(targetMs) {
        const diff = Math.max(0, targetMs - Date.now());
        const totalSeconds = Math.floor(diff / 1000);
        const days = Math.floor(totalSeconds / 86400);
        const hours = Math.floor((totalSeconds % 86400) / 3600);
        const minutes = Math.floor((totalSeconds % 3600) / 60);
        const seconds = totalSeconds % 60;

        return { days, hours, minutes, seconds };
    }

    function flashNode(node) {
        if (!node) {
            return;
        }

        node.classList.add('is-changing');
        window.setTimeout(function () {
            node.classList.remove('is-changing');
        }, 180);
    }

    function initCountdown(section) {
        const targetTs = parseInt(section.getAttribute('data-countdown-ts'), 10);
        if (!Number.isFinite(targetTs) || targetTs <= 0) {
            return null;
        }

        const targetMs = targetTs * 1000;
        const pill = section.querySelector('.home-decision__timer-pill');
        const valueNodes = {
            days: section.querySelector('[data-unit="days"]'),
            hours: section.querySelector('[data-unit="hours"]'),
            minutes: section.querySelector('[data-unit="minutes"]'),
            seconds: section.querySelector('[data-unit="seconds"]'),
        };

        let lastParts = null;

        function render(forceAnimation) {
            const parts = getParts(targetMs);

            Object.keys(valueNodes).forEach(function (key) {
                const node = valueNodes[key];
                if (!node) {
                    return;
                }

                const nextValue = pad(parts[key]);
                if (node.textContent !== nextValue) {
                    node.textContent = nextValue;
                    if (forceAnimation) {
                        flashNode(node);
                    }
                }
            });

            if (
                forceAnimation
                && pill
                && lastParts
                && JSON.stringify(lastParts) !== JSON.stringify(parts)
            ) {
                pill.classList.add('is-ticking');
                window.setTimeout(function () {
                    pill.classList.remove('is-ticking');
                }, 220);
            }

            lastParts = parts;
        }

        render(false);

        return render;
    }

    function copyText(text) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            return navigator.clipboard.writeText(text);
        }

        return new Promise(function (resolve, reject) {
            const input = document.createElement('textarea');
            input.value = text;
            input.setAttribute('readonly', '');
            input.style.position = 'absolute';
            input.style.left = '-9999px';
            document.body.appendChild(input);
            input.select();

            try {
                document.execCommand('copy');
                document.body.removeChild(input);
                resolve();
            } catch (error) {
                document.body.removeChild(input);
                reject(error);
            }
        });
    }

    function getShareUrl(channel, url, text) {
        const encodedUrl = encodeURIComponent(url);
        const encodedText = encodeURIComponent(text);

        switch (channel) {
            case 'whatsapp':
                return 'https://wa.me/?text=' + encodedText;
            case 'messenger':
                return 'https://www.facebook.com/dialog/send?link=' + encodedUrl + '&redirect_uri=' + encodedUrl;
            case 'telegram':
                return 'https://t.me/share/url?url=' + encodedUrl + '&text=' + encodedText;
            default:
                return '';
        }
    }

    function initShare(section) {
        const toggle = section.querySelector('[data-share-toggle]');
        const menu = section.querySelector('[data-share-menu]');
        const toast = section.querySelector('[data-share-toast]');
        const url = section.getAttribute('data-share-url') || '';
        const text = section.getAttribute('data-share-text') || url;
        const copiedLabel = section.getAttribute('data-share-copied') || '';

        if (!toggle || !menu || !url) {
            return;
        }

        const nativeButton = menu.querySelector('[data-share-native]');
        if (nativeButton && typeof navigator.share !== 'function') {
            nativeButton.hidden = true;
        }

        let toastTimer = null;

        function setMenuOpen(isOpen) {
            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            menu.hidden = !isOpen;
            section.classList.toggle('is-share-open', isOpen);
        }

        function showToast(message) {
            if (!toast || !message) {
                return;
            }

            toast.textContent = message;
            toast.hidden = false;

            if (toastTimer) {
                window.clearTimeout(toastTimer);
            }

            toastTimer = window.setTimeout(function () {
                toast.hidden = true;
            }, 2600);
        }

        toggle.addEventListener('click', function (event) {
            event.stopPropagation();
            const isOpen = toggle.getAttribute('aria-expanded') === 'true';
            setMenuOpen(!isOpen);
        });

        menu.addEventListener('click', function (event) {
            event.stopPropagation();
        });

        document.addEventListener('click', function (event) {
            if (!section.contains(event.target)) {
                setMenuOpen(false);
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                setMenuOpen(false);
            }
        });

        menu.querySelectorAll('[data-share-channel]').forEach(function (button) {
            button.addEventListener('click', function () {
                const channel = button.getAttribute('data-share-channel');
                const mode = button.getAttribute('data-share-mode');

                if (mode === 'copy') {
                    copyText(text)
                        .then(function () {
                            showToast(copiedLabel);
                        })
                        .catch(function () {
                            showToast(copiedLabel);
                        });
                    return;
                }

                if (mode === 'native' && typeof navigator.share === 'function') {
                    navigator.share({
                        title: document.title,
                        text: text,
                        url: url,
                    }).catch(function () {
                        // User cancelled — no toast.
                    });
                    return;
                }

                const shareUrl = getShareUrl(channel, url, text);
                if (shareUrl) {
                    window.open(shareUrl, '_blank', 'noopener,noreferrer');
                }
            });
        });
    }

    const renderers = [];
    sections.forEach(function (section) {
        const render = initCountdown(section);
        if (render) {
            renderers.push(render);
        }
        initShare(section);
    });

    if (renderers.length) {
        window.setInterval(function () {
            renderers.forEach(function (render) {
                render(true);
            });
        }, 1000);

        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) {
                renderers.forEach(function (render) {
                    render(true);
                });
            }
        });
    }
}());
