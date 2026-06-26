/**
 * Rankingi LP: scroll reveals + animated stat counters.
 */
function initLpRankingi() {
    const root = document.querySelector('.lp-rankingi');
    if (!root) {
        return;
    }

    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const reveals = root.querySelectorAll('.rank-reveal');

    if (reducedMotion) {
        reveals.forEach((el) => el.classList.add('in'));
    } else if ('IntersectionObserver' in window) {
        const revealObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) {
                    return;
                }
                entry.target.classList.add('in');
                revealObserver.unobserve(entry.target);
            });
        }, { threshold: 0.16 });

        reveals.forEach((el) => revealObserver.observe(el));
    } else {
        reveals.forEach((el) => el.classList.add('in'));
    }

    const fmt = (n) => n.toLocaleString('pl-PL');

    const runCounter = (el) => {
        const target = parseFloat(el.getAttribute('data-count'));
        const duration = 900;
        let start = null;

        const step = (timestamp) => {
            if (!start) {
                start = timestamp;
            }
            const progress = Math.min((timestamp - start) / duration, 1);
            const eased = 1 - (1 - progress) ** 3;
            el.firstChild.nodeValue = fmt(Math.round(target * eased));
            if (progress < 1) {
                requestAnimationFrame(step);
            }
        };

        requestAnimationFrame(step);
    };

    const counters = root.querySelectorAll('[data-count]');

    if (reducedMotion) {
        counters.forEach((el) => {
            el.firstChild.nodeValue = fmt(parseFloat(el.getAttribute('data-count')));
        });
        return;
    }

    if ('IntersectionObserver' in window) {
        const counterObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) {
                    return;
                }
                runCounter(entry.target);
                counterObserver.unobserve(entry.target);
            });
        }, { threshold: 0.5 });

        counters.forEach((el) => counterObserver.observe(el));
        return;
    }

    counters.forEach((el) => runCounter(el));
}

document.addEventListener('DOMContentLoaded', initLpRankingi);
