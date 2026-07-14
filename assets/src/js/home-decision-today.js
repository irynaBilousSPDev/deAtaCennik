(function () {
    const section = document.querySelector('.home-decision[data-countdown-target]');
    if (!section) {
        return;
    }

    const targetIso = section.getAttribute('data-countdown-target');
    if (!targetIso) {
        return;
    }

    const targetMs = Date.parse(targetIso);
    if (Number.isNaN(targetMs)) {
        return;
    }

    const pill = section.querySelector('.home-decision__timer-pill');
    const valueNodes = {
        days: section.querySelector('[data-unit="days"]'),
        hours: section.querySelector('[data-unit="hours"]'),
        minutes: section.querySelector('[data-unit="minutes"]'),
    };

    let lastParts = null;

    function pad(value) {
        return String(Math.max(0, value)).padStart(2, '0');
    }

    function getParts() {
        const diff = Math.max(0, targetMs - Date.now());
        const totalMinutes = Math.floor(diff / 60000);
        const days = Math.floor(totalMinutes / (60 * 24));
        const hours = Math.floor((totalMinutes % (60 * 24)) / 60);
        const minutes = totalMinutes % 60;

        return { days, hours, minutes };
    }

    function flashNode(node) {
        if (!node) {
            return;
        }

        node.classList.add('is-changing');
        window.setTimeout(function () {
            node.classList.remove('is-changing');
        }, 220);
    }

    function render(forceAnimation) {
        const parts = getParts();
        const entries = Object.keys(valueNodes);

        entries.forEach(function (key) {
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
            && (
                lastParts.days !== parts.days
                || lastParts.hours !== parts.hours
                || lastParts.minutes !== parts.minutes
            )
        ) {
            pill.classList.add('is-ticking');
            window.setTimeout(function () {
                pill.classList.remove('is-ticking');
            }, 350);
        }

        lastParts = parts;
    }

    render(false);

    window.setInterval(function () {
        render(true);
    }, 60000);

    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) {
            render(true);
        }
    });
}());
