/**
 * LP O Uczelni — sticky subnav offset + scroll-spy active state.
 */
export default function initLpOUczelni() {
	const root = document.querySelector('.lp-page.lp-o-uczelni');
	if (!root) {
		return;
	}

	const nav = root.querySelector('.subnav');
	const siteHeader = document.querySelector('.site-header');
	const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	const links = nav ? Array.from(nav.querySelectorAll('a[href^="#"]')) : [];
	const map = links
		.map((a) => {
			const href = a.getAttribute('href');
			const el = href && href.length > 1 ? document.querySelector(href) : null;
			return el ? { a, el } : null;
		})
		.filter(Boolean);

	let scrollSpyLocked = false;
	let scrollSpyTimer = null;
	let forcedNavLink = null;

	function updateStickyMetrics() {
		const headerH = siteHeader ? Math.round(siteHeader.getBoundingClientRect().height) : 0;
		root.style.setProperty('--oucz-header-offset', `${headerH}px`);
		const navH = nav ? nav.offsetHeight : 0;
		root.style.setProperty('--oucz-scroll-offset', `${headerH + navH + 12}px`);
	}

	function getAnchorMarker() {
		if (nav) {
			return nav.getBoundingClientRect().bottom + 12;
		}
		if (siteHeader) {
			return siteHeader.getBoundingClientRect().bottom + 12;
		}
		return 12;
	}

	function scrollActiveLinkIntoView(activeA) {
		const container = activeA.closest('.wrap') || (nav && nav.querySelector('.wrap'));
		if (!container) {
			return;
		}
		const containerRect = container.getBoundingClientRect();
		const linkRect = activeA.getBoundingClientRect();
		if (linkRect.left < containerRect.left + 4) {
			container.scrollLeft -= containerRect.left - linkRect.left + 8;
		} else if (linkRect.right > containerRect.right - 4) {
			container.scrollLeft += linkRect.right - containerRect.right + 8;
		}
	}

	function setActiveLink(activeA) {
		if (!nav) {
			return;
		}
		links.forEach((a) => a.classList.remove('is-active'));
		if (!activeA) {
			return;
		}
		activeA.classList.add('is-active');
		scrollActiveLinkIntoView(activeA);
	}

	function releaseScrollSpy(activeLink) {
		scrollSpyLocked = false;
		forcedNavLink = null;
		if (activeLink) {
			const href = activeLink.getAttribute('href');
			const el = href && href.charAt(0) === '#' ? document.querySelector(href) : null;
			if (el) {
				const rect = el.getBoundingClientRect();
				const marker = getAnchorMarker();
				if (Math.abs(rect.top - marker) <= 80) {
					setActiveLink(activeLink);
					return;
				}
			}
		}
		onSubnavScroll();
	}

	function lockScrollSpy(activeLink) {
		scrollSpyLocked = true;
		forcedNavLink = activeLink || null;
		clearTimeout(scrollSpyTimer);
		let finished = false;
		const waitMs = reduce ? 120 : 1600;

		function finish() {
			if (finished) {
				return;
			}
			finished = true;
			clearTimeout(scrollSpyTimer);
			releaseScrollSpy(activeLink);
		}

		scrollSpyTimer = setTimeout(finish, waitMs);
		if (!reduce && 'onscrollend' in window) {
			window.addEventListener('scrollend', finish, { once: true });
		}
	}

	function onSubnavScroll() {
		if (!nav || scrollSpyLocked) {
			return;
		}
		if (forcedNavLink) {
			setActiveLink(forcedNavLink);
			return;
		}
		const marker = getAnchorMarker();
		let cur = null;
		let bestTop = -Infinity;
		map.forEach((m) => {
			const rect = m.el.getBoundingClientRect();
			const docTop = rect.top + window.scrollY;
			if (rect.top <= marker + 2 && docTop > bestTop) {
				bestTop = docTop;
				cur = m;
			}
		});
		if (cur) {
			setActiveLink(cur.a);
		}
	}

	function scrollToAnchor(el, activeLink) {
		if (activeLink) {
			setActiveLink(activeLink);
		}
		const rect = el.getBoundingClientRect();
		const y = Math.max(0, window.scrollY + rect.top - getAnchorMarker());
		lockScrollSpy(activeLink);
		window.scrollTo({ top: y, behavior: reduce ? 'auto' : 'smooth' });
	}

	updateStickyMetrics();
	window.addEventListener('resize', updateStickyMetrics);

	if (nav) {
		window.addEventListener('scroll', onSubnavScroll, { passive: true });
		onSubnavScroll();
	}

	root.addEventListener('click', (e) => {
		const a = e.target.closest ? e.target.closest('a[href^="#"]') : null;
		if (!a || !root.contains(a)) {
			return;
		}
		const href = a.getAttribute('href');
		if (!href || href.length < 2) {
			return;
		}
		const el = document.querySelector(href);
		if (!el) {
			return;
		}
		e.preventDefault();
		const activeLink = nav && nav.contains(a) ? a : null;
		scrollToAnchor(el, activeLink);
		if (window.history && window.history.pushState) {
			window.history.pushState(null, '', href);
		}
	});

	if (location.hash) {
		const el = document.querySelector(location.hash);
		if (el && root.contains(el)) {
			const activeLink = links.find((a) => a.getAttribute('href') === location.hash) || null;
			requestAnimationFrame(() => {
				updateStickyMetrics();
				scrollToAnchor(el, activeLink);
			});
		}
	}
}
