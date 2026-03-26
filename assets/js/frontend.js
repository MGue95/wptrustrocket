/* ═══════════════════════════════════════════════
   WPTrustRocket – Frontend Slider
   ═══════════════════════════════════════════════ */

(function () {
	'use strict';

	function initSlider(container) {
		const track  = container.querySelector('.wptr-slider-track');
		const slides = container.querySelectorAll('.wptr-slider-slide');
		const prev   = container.querySelector('.wptr-slider-btn--prev');
		const next   = container.querySelector('.wptr-slider-btn--next');
		const dots   = container.querySelector('.wptr-slider-dots');

		if (!track || slides.length === 0) return;

		let current  = 0;
		let autoplayTimer = null;
		const autoplayMs = parseInt(container.dataset.autoplay || '0', 10);

		function getVisible() {
			const w = container.offsetWidth;
			if (w >= 900) return 3;
			if (w >= 600) return 2;
			return 1;
		}

		function getMaxIndex() {
			return Math.max(0, slides.length - getVisible());
		}

		function render() {
			const visible = getVisible();
			const pct = (100 / visible) * current;
			track.style.transform = 'translateX(-' + pct + '%)';
			updateDots();
		}

		/* Dots */

		function buildDots() {
			if (!dots) return;
			dots.innerHTML = '';
			const total = getMaxIndex() + 1;
			for (let i = 0; i < total; i++) {
				const dot = document.createElement('button');
				dot.className = 'wptr-slider-dot' + (i === current ? ' wptr-slider-dot--active' : '');
				dot.setAttribute('aria-label', 'Slide ' + (i + 1));
				dot.addEventListener('click', () => {
					current = i;
					render();
					resetAutoplay();
				});
				dots.appendChild(dot);
			}
		}

		function updateDots() {
			if (!dots) return;
			dots.querySelectorAll('.wptr-slider-dot').forEach((d, i) => {
				d.classList.toggle('wptr-slider-dot--active', i === current);
			});
		}

		/* Navigation */

		function goNext() {
			current = current < getMaxIndex() ? current + 1 : 0;
			render();
		}

		function goPrev() {
			current = current > 0 ? current - 1 : getMaxIndex();
			render();
		}

		if (prev) prev.addEventListener('click', () => { goPrev(); resetAutoplay(); });
		if (next) next.addEventListener('click', () => { goNext(); resetAutoplay(); });

		/* Touch / swipe */

		let startX = 0;
		let isDragging = false;

		track.addEventListener('touchstart', e => {
			startX = e.touches[0].clientX;
			isDragging = true;
		}, { passive: true });

		track.addEventListener('touchend', e => {
			if (!isDragging) return;
			isDragging = false;
			const diff = startX - e.changedTouches[0].clientX;
			if (Math.abs(diff) > 50) {
				diff > 0 ? goNext() : goPrev();
				resetAutoplay();
			}
		}, { passive: true });

		/* Autoplay */

		function startAutoplay() {
			if (autoplayMs > 0) {
				autoplayTimer = setInterval(goNext, autoplayMs);
			}
		}

		function resetAutoplay() {
			if (autoplayTimer) clearInterval(autoplayTimer);
			startAutoplay();
		}

		/* Keyboard */

		container.setAttribute('tabindex', '0');
		container.addEventListener('keydown', e => {
			if (e.key === 'ArrowLeft') { goPrev(); resetAutoplay(); }
			if (e.key === 'ArrowRight') { goNext(); resetAutoplay(); }
		});

		/* Resize */

		let resizeTimeout;
		window.addEventListener('resize', () => {
			clearTimeout(resizeTimeout);
			resizeTimeout = setTimeout(() => {
				if (current > getMaxIndex()) current = getMaxIndex();
				buildDots();
				render();
			}, 150);
		});

		/* Init */

		buildDots();
		render();
		startAutoplay();
	}

	/* Initialize all sliders on the page */

	function init() {
		document.querySelectorAll('.wptr-reviews--slider').forEach(initSlider);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}

})();
