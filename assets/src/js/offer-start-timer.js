/**
 * Compact offer countdown + seamless scroll-down word loop.
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

	// Clone first pair at the end → seamless loop (always scrolls down).
	reels.forEach((reel) => {
		const first = reel.querySelector('.offer-start-timer__word');
		if (first) {
			reel.appendChild(first.cloneNode(true));
		}
	});

	let index = 0;
	let busy = false;
	const lineH = () => {
		const line = root.querySelector('.offer-start-timer__line');
		return line ? line.getBoundingClientRect().height : 16;
	};

	function setReel(indexValue, animate) {
		const y = -(indexValue * lineH());
		reels.forEach((reel) => {
			if (!animate) {
				reel.style.transition = 'none';
			} else {
				reel.style.transition = '';
			}
			reel.style.transform = `translateY(${y}px)`;
		});
	}

	function advance() {
		if (busy) {
			return;
		}
		busy = true;
		index += 1;
		setReel(index, true);

		const lead = reels[0];
		const onEnd = (event) => {
			if (event.propertyName !== 'transform') {
				return;
			}
			lead.removeEventListener('transitionend', onEnd);

			if (index >= pairCount) {
				index = 0;
				setReel(0, false);
				// Force reflow so next loop can animate again.
				void lead.offsetHeight;
				reels.forEach((reel) => {
					reel.style.transition = '';
				});
			}
			busy = false;
		};

		lead.addEventListener('transitionend', onEnd);
	}

	setReel(0, false);
	window.setInterval(advance, 3400);
}
