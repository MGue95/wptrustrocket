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

		/* Touch / swipe / mouse drag */

		const drag = {
			active: false,
			startX: 0,
			startY: 0,
			currentX: 0,
			startTranslate: 0,
			dirLocked: false,
			isHorizontal: false
		};

		function getSlideWidthPct() {
			return 100 / getVisible();
		}

		function getCurrentTranslatePx() {
			return -(getSlideWidthPct() * current / 100) * track.scrollWidth;
		}

		function onDragStart(x, y) {
			drag.active = true;
			drag.startX = x;
			drag.startY = y;
			drag.currentX = x;
			drag.startTranslate = getCurrentTranslatePx();
			drag.dirLocked = false;
			drag.isHorizontal = false;
			track.classList.add('wptr-dragging');
		}

		function onDragMove(x, y, e) {
			if (!drag.active) return;
			const dx = x - drag.startX;
			const dy = y - drag.startY;

			if (!drag.dirLocked && (Math.abs(dx) > 5 || Math.abs(dy) > 5)) {
				drag.dirLocked = true;
				drag.isHorizontal = Math.abs(dx) > Math.abs(dy);
			}

			if (drag.dirLocked && !drag.isHorizontal) {
				onDragEnd();
				return;
			}

			if (drag.dirLocked && drag.isHorizontal && e.cancelable) {
				e.preventDefault();
			}

			drag.currentX = x;
			let translate = drag.startTranslate + dx;

			const maxT = 0;
			const minT = -(track.scrollWidth - container.offsetWidth);
			if (translate > maxT) {
				translate = maxT + (translate - maxT) * 0.3;
			} else if (translate < minT) {
				translate = minT + (translate - minT) * 0.3;
			}

			track.style.transform = 'translateX(' + translate + 'px)';
		}

		function onDragEnd() {
			if (!drag.active) return;
			drag.active = false;
			track.classList.remove('wptr-dragging');

			const dx = drag.currentX - drag.startX;
			const threshold = container.offsetWidth * 0.15;

			if (drag.isHorizontal && Math.abs(dx) > threshold) {
				dx < 0 ? goNext() : goPrev();
				resetAutoplay();
			} else {
				render();
			}
		}

		// Touch
		track.addEventListener('touchstart', e => {
			onDragStart(e.touches[0].clientX, e.touches[0].clientY);
		}, { passive: true });

		track.addEventListener('touchmove', e => {
			onDragMove(e.touches[0].clientX, e.touches[0].clientY, e);
		}, { passive: false });

		track.addEventListener('touchend', () => onDragEnd(), { passive: true });
		track.addEventListener('touchcancel', () => onDragEnd(), { passive: true });

		// Mouse drag
		track.addEventListener('mousedown', e => {
			if (e.button !== 0) return;
			e.preventDefault();
			onDragStart(e.clientX, e.clientY);
			drag.dirLocked = true;
			drag.isHorizontal = true;
		});

		document.addEventListener('mousemove', e => {
			if (!drag.active) return;
			e.preventDefault();
			onDragMove(e.clientX, e.clientY, e);
		});

		document.addEventListener('mouseup', () => onDragEnd());

		// Prevent link clicks after drag
		track.addEventListener('click', e => {
			if (Math.abs(drag.currentX - drag.startX) > 5) {
				e.preventDefault();
				e.stopPropagation();
			}
		}, true);

		// Prevent image drag ghost
		track.addEventListener('dragstart', e => e.preventDefault());

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

		container.addEventListener('mouseenter', () => {
			if (autoplayTimer) clearInterval(autoplayTimer);
		});
		container.addEventListener('mouseleave', () => {
			if (autoplayMs > 0) startAutoplay();
		});

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
