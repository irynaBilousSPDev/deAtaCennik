/**
 * Compact offer countdown + typewriter phrase swap (bachelor/master test).
 */
export default function initOfferStartTimer() {
	const root = document.querySelector('.offer-start-timer[data-countdown-ts]');
	if (!root) {
		return;
	}

	const targetTs = parseInt(root.getAttribute('data-countdown-ts'), 10);
	if (!Number.isFinite(targetTs) || targetTs <= 0) {
		return;
	}

	const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	const valueNodes = {
		days: root.querySelector('[data-unit="days"]'),
		hours: root.querySelector('[data-unit="hours"]'),
		minutes: root.querySelector('[data-unit="minutes"]'),
		seconds: root.querySelector('[data-unit="seconds"]'),
	};
	const typeNode = root.querySelector('.offer-start-timer__type-text');

	let phrases = [];
	try {
		phrases = JSON.parse(root.getAttribute('data-phrases') || '[]');
	} catch (e) {
		phrases = [];
	}
	if (!Array.isArray(phrases) || !phrases.length) {
		phrases = ['start studiów', 'pierwszego października'];
	}

	function pad(value) {
		const n = Math.max(0, Math.floor(Number(value) || 0));
		return String(n).padStart(2, '0');
	}

	function getParts() {
		const diff = Math.max(0, targetTs * 1000 - Date.now());
		const totalSeconds = Math.floor(diff / 1000);
		return {
			days: Math.floor(totalSeconds / 86400),
			hours: Math.floor((totalSeconds % 86400) / 3600),
			minutes: Math.floor((totalSeconds % 3600) / 60),
			seconds: totalSeconds % 60,
		};
	}

	function renderCountdown() {
		const parts = getParts();
		Object.keys(valueNodes).forEach((key) => {
			const node = valueNodes[key];
			if (!node) {
				return;
			}
			const next = pad(parts[key]);
			if (node.textContent !== next) {
				node.textContent = next;
			}
		});
	}

	renderCountdown();
	window.setInterval(renderCountdown, 1000);

	if (!typeNode) {
		return;
	}

	if (reduce) {
		typeNode.textContent = phrases[0];
		return;
	}

	let phraseIndex = 0;
	let charIndex = 0;
	let deleting = false;
	let pauseUntil = 0;

	function tickTypewriter(now) {
		if (now < pauseUntil) {
			window.requestAnimationFrame(tickTypewriter);
			return;
		}

		const phrase = phrases[phraseIndex] || '';

		if (!deleting && charIndex < phrase.length) {
			charIndex += 1;
			typeNode.textContent = phrase.slice(0, charIndex);
			pauseUntil = now + 55;
		} else if (!deleting && charIndex >= phrase.length) {
			deleting = true;
			pauseUntil = now + 1800;
		} else if (deleting && charIndex > 0) {
			charIndex -= 1;
			typeNode.textContent = phrase.slice(0, charIndex);
			pauseUntil = now + 32;
		} else {
			deleting = false;
			phraseIndex = (phraseIndex + 1) % phrases.length;
			pauseUntil = now + 320;
		}

		window.requestAnimationFrame(tickTypewriter);
	}

	window.requestAnimationFrame(tickTypewriter);
}
