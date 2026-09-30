(function () {
	'use strict';

	// Mobile menu
	var toggle = document.querySelector('.ram-menu-toggle');
	var menu = document.getElementById('ram-menu');
	if (toggle && menu) {
		toggle.addEventListener('click', function () {
			var open = toggle.getAttribute('aria-expanded') === 'true';
			toggle.setAttribute('aria-expanded', open ? 'false' : 'true');
			menu.classList.toggle('is-open', !open);
		});
	}

	// Elementor header: the top bar scrolls away while the header row sticks.
	var siteHeader = document.querySelector('.ram-site-header');
	var topbar = siteHeader && siteHeader.querySelector('.ramhf-topbar');
	if (topbar) {
		var setTopbar = function () { siteHeader.style.setProperty('--ram-topbar-h', topbar.offsetHeight + 'px'); };
		setTopbar();
		if ('ResizeObserver' in window) new ResizeObserver(setTopbar).observe(topbar);
		else window.addEventListener('resize', setTopbar);
	}

	// Elementor header: highlight the current page and label the menu.
	var navEl = document.querySelector('.ramhf-nav');
	if (navEl) {
		if (navEl.tagName === 'NAV' && !navEl.hasAttribute('aria-label')) navEl.setAttribute('aria-label', 'Main');
		var here = location.pathname.replace(/\/+$/, '') || '/';
		Array.prototype.forEach.call(navEl.querySelectorAll('a'), function (a) {
			if ((a.pathname.replace(/\/+$/, '') || '/') === here) {
				a.classList.add('is-active');
				a.setAttribute('aria-current', 'page');
			}
		});
	}

	// Home hero: vertical slider with three slides plus a looping clone of the first
	var slider = document.querySelector('.ram-slider, [data-ram-slider]');
	if (!slider) return;
	var viewport = slider.querySelector('.ram-slider-viewport, [data-slider-viewport]');
	var track = slider.querySelector('.ram-slider-track, [data-slider-track]');
	var dots = slider.querySelectorAll('.ram-dot, [data-slide]');
	// Only the slide containers (the Elementor editor adds its own overlay/handle elements).
	var slides = track.querySelectorAll(':scope > .ram-slide');
	if (!slides.length) slides = track.children;
	var count = slides.length; // 4
	var i = 0;
	var paused = false;
	var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	function measure() {
		viewport.style.height = (track.offsetHeight / count) + 'px';
	}

	function paint() {
		var active = i % (count - 1);
		for (var n = 0; n < dots.length; n++) {
			dots[n].style.background = n === active ? '#B08D57' : 'transparent';
			dots[n].setAttribute('aria-current', n === active ? 'true' : 'false');
		}
		for (var s = 0; s < count; s++) {
			var on = s === i;
			slides[s].inert = !on;
			slides[s].setAttribute('aria-hidden', on ? 'false' : 'true');
		}
	}

	function go(n, animate) {
		i = n;
		track.style.transition = animate === false || reduce ? 'none' : 'transform 1s cubic-bezier(0.65,0,0.35,1)';
		track.style.transform = 'translateY(-' + (i * (100 / count)) + '%)';
		paint();
		if (reduce && i === count - 1) go(0, false);
	}

	track.addEventListener('transitionend', function (e) {
		if (e.target !== track || i !== count - 1) return;
		go(0, false);
	});

	// Dots are Elementor containers: give them button semantics and keyboard support.
	Array.prototype.forEach.call(dots, function (dot, n) {
		dot.setAttribute('role', 'button');
		dot.setAttribute('tabindex', '0');
		if (!dot.getAttribute('aria-label')) dot.setAttribute('aria-label', 'Slide ' + (n + 1));
		dot.addEventListener('click', function () { go(n); });
		dot.addEventListener('keydown', function (e) {
			if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); go(n); }
		});
	});

	slider.addEventListener('mouseenter', function () { paused = true; });
	slider.addEventListener('mouseleave', function () { paused = false; });
	slider.addEventListener('focusin', function () { paused = true; });
	slider.addEventListener('focusout', function () { paused = false; });

	setInterval(function () {
		if (!paused && !document.hidden && i < count - 1) go(i + 1);
	}, 5000);

	if ('ResizeObserver' in window) new ResizeObserver(measure).observe(track);
	window.addEventListener('load', measure);
	measure();
	paint();
})();
