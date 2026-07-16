/**
 * Compact offer countdown + scroll-down word pairs (bachelor/master test).
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
	const pairCount = Math.max(1, parseInt(root.getAttribute('data-pair-count'), 10) || 2);
	const reels = Array.from(root.querySelectorAll('.offer-start-timer__reel'));
	const valueNodes = {
		days: root.querySelector('[data-unit="days"]'),
		hours: root.querySelector('[data-unit="hours"]'),
		minutes: root.querySelector('[data-unit="minutes"]'),
		seconds: root.querySelector('[data-unit="seconds"]'),
	};

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

	if (!reels.length || pairCount < 2 || reduce) {
		return;
	}

	// Duplicate first word so the reel can always scroll downward.
	reels.forEach((reel) => {
		const first = reel.querySelector('.offer-start-timer__word');
		if (first) {
			reel.appendChild(first.cloneNode(true));
		}
	});

	let index = 0;
	const stepCount = pairCount; // 0 -> 1 -> 2(clone of 0) -> reset to 0

	function setReel(indexValue, animate) {
		reels.forEach((reel) => {
			reel.style.transition = animate ? '' : 'none';
			reel.style.transform = `translateY(calc(-${indexValue} * 1.15em))`;
		});
	}

	setReel(0, false);

	window.setInterval(() => {
		index += 1;
		setReel(index, true);

		if (index >= stepCount) {
			window.setTimeout(() => {
				index = 0;
				setReel(0, false);
			}, 580);
		}
	}, 3200);
}
