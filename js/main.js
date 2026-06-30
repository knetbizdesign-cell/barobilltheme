// borobill_theme JS: 메인페이지 추천/카테고리/리스트/페이지네이션 sixshop 스타일 개선
(function($) {
    'use strict';

    $(document).ready(function() {
        // --- 테마(라이트/다크): localStorage + html[data-theme] ---
        (function borobillColorScheme() {
            var STORAGE_KEY = 'borobill-theme';

            function getTheme() {
                var t = document.documentElement.getAttribute('data-theme');
                return t === 'dark' ? 'dark' : 'light';
            }

            function applyTheme(theme) {
                if (theme !== 'dark' && theme !== 'light') {
                    theme = 'light';
                }
                document.documentElement.setAttribute('data-theme', theme);
                try {
                    localStorage.setItem(STORAGE_KEY, theme);
                } catch (e) {}
                var dark = theme === 'dark';
                var label = dark ? '라이트 모드로 전환' : '다크 모드로 전환';
                $('.borobill-theme-toggle').each(function() {
                    $(this).attr('aria-pressed', dark ? 'true' : 'false');
                    $(this).attr('aria-label', label);
                });
            }

            applyTheme(getTheme());

            $(document).on('click', '.borobill-theme-toggle', function(e) {
                e.preventDefault();
                applyTheme(getTheme() === 'dark' ? 'light' : 'dark');
            });
        })();

        // 워드프레스 REST API endpoint
        const WP_API_URL = '/wordpress/wp-json/wp/v2/';
        // DOM elements
        const $featured = $('#featured-posts'); // 추천 게시물 영역
        const $filter = $('#category-filter');  // 카테고리 필터 버튼 영역
        const $list = $('#post-list');          // 게시글 리스트 영역
        const $pagination = $('#post-pagination'); // 페이지네이션 영역

        // --- Header: mobile nav toggle (안전한 최소 기능) --- //
        const $mobileToggle = $('.header-mobile-toggle');
        const $mobileNav = $('.header-mobile-nav');
        const $siteHeader = $('.site-header');
        const $searchToggle = $('.header-search-toggle');
        const $mobileSearch = $('.header-mobile-search');
        const $mobileSearchInput = $('#header-mobile-search-input');
        const $mobileSearchCancel = $('.header-mobile-search-cancel');
        let lockedScrollY = 0;
        let isNavScrollLocked = false;

        function restoreWindowScroll() {
            if (!isNavScrollLocked) {
                return;
            }
            // 스크롤이 움직이려고 하면 즉시 원위치(스크롤바는 유지)
            if ((window.scrollY || 0) !== lockedScrollY) {
                window.scrollTo(0, lockedScrollY);
            }
        }

        function onGlobalWheel(e) {
            if (!isNavScrollLocked) return;
            const nav = $mobileNav.get(0);
            if (nav && nav.contains(e.target)) {
                return; // 메뉴 내부는 스크롤 허용
            }
            e.preventDefault();
        }

        function onGlobalTouchMove(e) {
            if (!isNavScrollLocked) return;
            const nav = $mobileNav.get(0);
            if (nav && nav.contains(e.target)) {
                return;
            }
            e.preventDefault();
        }

        function setMobileNavOpen(open) {
            if (!$mobileToggle.length || !$mobileNav.length) {
                return;
            }
        $mobileNav.toggleClass('is-open', open);
            $('body').toggleClass('mobile-nav-open', open);
            $('html').toggleClass('mobile-nav-open', open);
            $mobileToggle.attr('aria-expanded', open ? 'true' : 'false');
            $mobileToggle.attr('aria-label', open ? '메뉴 닫기' : '메뉴 열기');
        $mobileToggle.toggleClass('is-open', open);

            // 페이지 스크롤 잠금(스크롤바는 유지, 실제 이동만 차단)
            if (open) {
                lockedScrollY = window.scrollY || 0;
                isNavScrollLocked = true;
                // 스크롤 복원 루프
                restoreWindowScroll();
                $(window).off('scroll.borobillNavLock').on('scroll.borobillNavLock', restoreWindowScroll);
                document.addEventListener('wheel', onGlobalWheel, { passive: false });
                document.addEventListener('touchmove', onGlobalTouchMove, { passive: false });
            } else {
                isNavScrollLocked = false;
                $(window).off('scroll.borobillNavLock');
                document.removeEventListener('wheel', onGlobalWheel, { passive: false });
                document.removeEventListener('touchmove', onGlobalTouchMove, { passive: false });
                lockedScrollY = 0;
            }
        }

        function setHeaderSearchOpen(open) {
            if (!$siteHeader.length) {
                return;
            }
            // 검색 열면 메뉴는 닫기
            if (open) {
                setMobileNavOpen(false);
            }
            $siteHeader.toggleClass('is-search-open', open);
            $searchToggle.attr('aria-expanded', open ? 'true' : 'false');
            $searchToggle.attr('aria-label', open ? '검색 닫기' : '검색 열기');

            if (open && $mobileSearchInput.length) {
                setTimeout(() => $mobileSearchInput.trigger('focus'), 0);
            }
        }

        // --- Global: prevent nested scroll containers from hijacking wheel --- //
        // 요청: 사이트 내 "영역 안 스크롤"이 휠/트랙패드 스크롤을 먹지 않게
        // (단, 모바일 메뉴/검색 모달처럼 의도된 스크롤 UI는 예외)
        (function preventNestedScrollHijack() {
            function isExcludedTarget(t) {
                if (!t || typeof t.closest !== 'function') return false;
                return Boolean(
                    t.closest('.search-modal') ||
                    t.closest('.header-mobile-nav') ||
                    t.closest('[data-allow-inner-scroll="1"]')
                );
            }

            function isScrollableEl(el) {
                if (!el || el === document.body || el === document.documentElement) return false;
                try {
                    const style = window.getComputedStyle(el);
                    const oy = String(style.overflowY || '');
                    const ox = String(style.overflowX || '');
                    const canY = (oy === 'auto' || oy === 'scroll') && (el.scrollHeight - el.clientHeight) > 1;
                    const canX = (ox === 'auto' || ox === 'scroll') && (el.scrollWidth - el.clientWidth) > 1;
                    return canY || canX;
                } catch (e) {
                    return false;
                }
            }

            document.addEventListener('wheel', function (e) {
                // 모바일 메뉴가 열려있을 땐 기존 잠금 로직이 담당
                if (isNavScrollLocked) return;
                if (document.body.classList.contains('search-modal-open')) return;
                if (document.body.classList.contains('mobile-nav-open')) return;

                const target = e.target;
                if (isExcludedTarget(target)) return;

                // 가장 가까운 "스크롤 가능한" 조상 요소가 있으면, 휠은 페이지로만 전달
                let el = target && target.nodeType === 1 ? target : null;
                while (el && el !== document.body && el !== document.documentElement) {
                    if (isScrollableEl(el)) {
                        // 내부 스크롤 이동 차단
                        e.preventDefault();
                        const dy = Number(e.deltaY) || 0;
                        if (dy) {
                            window.scrollBy(0, dy);
                        }
                        return;
                    }
                    el = el.parentElement;
                }
            }, { passive: false });
        })();

        if ($mobileToggle.length && $mobileNav.length) {
            $mobileToggle.off('click.borobillMobileNav').on('click.borobillMobileNav', function () {
                setMobileNavOpen(!$mobileNav.hasClass('is-open'));
            });

            // 메뉴 항목 클릭 시 닫기 (모바일 UX)
            $mobileNav.off('click.borobillMobileNavLink').on('click.borobillMobileNavLink', 'a', function () {
                setMobileNavOpen(false);
            });

            // ESC로 닫기
            $(document).off('keydown.borobillMobileNav').on('keydown.borobillMobileNav', function (e) {
                if (e.key === 'Escape') {
                    setMobileNavOpen(false);
                    setHeaderSearchOpen(false);
                }
            });

            // 데스크톱 전환 시 열려있으면 닫기
            $(window).off('resize.borobillMobileNav').on('resize.borobillMobileNav', function () {
                // 1025px 이상: 헤더 검색은 사용하지 않음 → 닫기
                if (window.matchMedia && window.matchMedia('(min-width: 1025px)').matches) {
                    setHeaderSearchOpen(false);
                }

                // 데스크톱(중앙 GNB) 이상에서는 햄버거 메뉴도 닫기
                if (window.matchMedia && window.matchMedia('(min-width: 1181px)').matches) {
                    setMobileNavOpen(false);
                }

                // 모바일에서 화면이 줄어들 때 카테고리 탭이 왼쪽으로 "밀려 숨는" 현상 방지
                if (window.matchMedia && window.matchMedia('(max-width: 768px)').matches) {
                    const filterEl = $filter && $filter.length ? $filter.get(0) : null;
                    if (filterEl) {
                        filterEl.scrollLeft = 0;
                    }
                }
            });
        }

        // --- Header: search toggle (모바일) --- //
        // 검색 모달(search-modal.js)이 로드되어 있으면 모달로 열고,
        // 아닌 경우에만 기존 인라인 검색 사용 (하위 호환)
        if ($searchToggle.length && $mobileSearch.length) {
            $searchToggle.off('click.borobillHeaderSearch').on('click.borobillHeaderSearch', function () {
                if (window.SearchModal) {
                    // 검색 모달로 대체 (data-open-search가 처리)
                    return;
                }
                setHeaderSearchOpen(!$siteHeader.hasClass('is-search-open'));
            });

            $mobileSearchCancel.off('click.borobillHeaderSearchCancel').on('click.borobillHeaderSearchCancel', function () {
                setHeaderSearchOpen(false);
            });
        }

        // --- Hero carousel (메인 상단) --- //
        (function initHeroCarousel() {
            const $wrapper = $('.hero-wrapper');
            const $carousel = $wrapper.find('.hero-carousel');
            const $slides = $carousel.find('.hero-slide');
            const $badge = $wrapper.find('.hero-badge');
            const $title = $wrapper.find('.hero-title');
            const $dots = $wrapper.find('.hero-indicators .dot');
            const $slideCounterCurrent = $wrapper.find('.hero-slide-counter__current');
            const $nextBtn = $wrapper.find('[data-hero-next]');
            const $nextBtnLabel = $nextBtn.find('.hero-next-btn__label');

            if (!$wrapper.length || !$carousel.length || $slides.length < 2) {
                return;
            }

            const settings = (window.borobillHeroSettings && typeof window.borobillHeroSettings === 'object')
                ? window.borobillHeroSettings
                : {};
            const autoDelaySec = Number(settings.autoDelay);
            const durationSec = Number(settings.duration);
            const autoDelayMs = Number.isFinite(autoDelaySec) ? Math.max(0, autoDelaySec * 1000) : 0;

            if (Number.isFinite(durationSec) && durationSec > 0) {
                $wrapper.get(0).style.setProperty('--hero-duration', `${durationSec}s`);
            }

            function clamp(n, min, max) {
                return Math.max(min, Math.min(max, n));
            }

            function hexToRgb(hex) {
                const raw = String(hex || '').trim();
                const m = raw.match(/^#?([0-9a-f]{3}|[0-9a-f]{6})$/i);
                if (!m) return null;
                let h = m[1];
                if (h.length === 3) {
                    h = h.split('').map(ch => ch + ch).join('');
                }
                const num = parseInt(h, 16);
                return { r: (num >> 16) & 255, g: (num >> 8) & 255, b: num & 255 };
            }

            function rgbToCss(rgb) {
                return `rgb(${clamp(rgb.r, 0, 255)}, ${clamp(rgb.g, 0, 255)}, ${clamp(rgb.b, 0, 255)})`;
            }

            function rgbaToCss(rgb, a) {
                const alpha = clamp(Number(a), 0, 1);
                return `rgba(${clamp(rgb.r, 0, 255)}, ${clamp(rgb.g, 0, 255)}, ${clamp(rgb.b, 0, 255)}, ${alpha})`;
            }

            function darkenToLowLuminance(rgb, ratioBlack) {
                const t = clamp(Number(ratioBlack), 0, 1);
                return {
                    r: Math.round(rgb.r * (1 - t)),
                    g: Math.round(rgb.g * (1 - t)),
                    b: Math.round(rgb.b * (1 - t)),
                };
            }

            function lighten(rgb, amount) {
                const t = clamp(Number(amount), 0, 1);
                return {
                    r: Math.round(rgb.r + (255 - rgb.r) * t),
                    g: Math.round(rgb.g + (255 - rgb.g) * t),
                    b: Math.round(rgb.b + (255 - rgb.b) * t),
                };
            }

            function rgbToHsl(rgb) {
                const r = clamp(rgb.r, 0, 255) / 255;
                const g = clamp(rgb.g, 0, 255) / 255;
                const b = clamp(rgb.b, 0, 255) / 255;

                const max = Math.max(r, g, b);
                const min = Math.min(r, g, b);
                const d = max - min;

                let h = 0;
                let s = 0;
                const l = (max + min) / 2;

                if (d !== 0) {
                    s = d / (1 - Math.abs(2 * l - 1));
                    switch (max) {
                        case r:
                            h = ((g - b) / d) % 6;
                            break;
                        case g:
                            h = (b - r) / d + 2;
                            break;
                        default:
                            h = (r - g) / d + 4;
                            break;
                    }
                    h = h * 60;
                    if (h < 0) h += 360;
                }

                return { h, s, l };
            }

            function hslToRgb(hsl) {
                const h = ((Number(hsl.h) % 360) + 360) % 360;
                const s = clamp(Number(hsl.s), 0, 1);
                const l = clamp(Number(hsl.l), 0, 1);

                const c = (1 - Math.abs(2 * l - 1)) * s;
                const x = c * (1 - Math.abs(((h / 60) % 2) - 1));
                const m = l - c / 2;

                let rp = 0, gp = 0, bp = 0;
                if (h < 60) {
                    rp = c; gp = x; bp = 0;
                } else if (h < 120) {
                    rp = x; gp = c; bp = 0;
                } else if (h < 180) {
                    rp = 0; gp = c; bp = x;
                } else if (h < 240) {
                    rp = 0; gp = x; bp = c;
                } else if (h < 300) {
                    rp = x; gp = 0; bp = c;
                } else {
                    rp = c; gp = 0; bp = x;
                }

                return {
                    r: Math.round((rp + m) * 255),
                    g: Math.round((gp + m) * 255),
                    b: Math.round((bp + m) * 255),
                };
            }

            function boostSaturation(rgb, amount) {
                const hsl = rgbToHsl(rgb);
                hsl.s = clamp(hsl.s + Number(amount), 0, 1);
                return hslToRgb(hsl);
            }

            let current = 0;
            const activeIdx = $slides.toArray().findIndex(el => el.classList.contains('is-active'));
            if (activeIdx >= 0) {
                current = activeIdx;
            }

            function applySlide(nextIndex) {
                const total = $slides.length;
                const idx = ((nextIndex % total) + total) % total;
                current = idx;

                $carousel.get(0).style.transform = `translateX(${-idx * 100}%)`;
                $slides.removeClass('is-active').eq(idx).addClass('is-active');

                const $active = $slides.eq(idx);
                const badgeText = String($active.data('badge') || '');
                const titleHtml = String($active.data('title-html') || '');
                const bgColor = String($active.data('bg-color') || '');
                const btnText = String($active.data('btn-text') || '게시글 바로가기');
                const btnEnabledRaw = $active.data('btn-enabled');
                const btnEnabled = String(typeof btnEnabledRaw === 'undefined' ? '1' : btnEnabledRaw) !== '0';
                const gradBottom = String($active.data('grad-bottom') || '');
                const gradEnabledRaw = $active.data('grad-enabled');
                const gradEnabled = String(typeof gradEnabledRaw === 'undefined' ? (gradBottom ? '1' : '0') : gradEnabledRaw) !== '0';

                if ($badge.length) $badge.text(badgeText);
                if ($title.length) $title.html(titleHtml);

                if ($nextBtn.length) {
                    if (!btnEnabled) {
                        $nextBtn.addClass('is-hidden').attr('aria-hidden', 'true').attr('tabindex', '-1');
                    } else {
                        $nextBtn.removeClass('is-hidden').removeAttr('aria-hidden').removeAttr('tabindex');
                        if ($nextBtnLabel.length) {
                            $nextBtnLabel.text(btnText);
                        }
                    }
                }

                if (bgColor) {
                    $wrapper.get(0).style.setProperty('--hero-bg-color', bgColor);
                    const rgb = hexToRgb(bgColor);
                    if (rgb) {
                        // 요청사항:
                        // - 기본 버튼은 배경색 기반으로 "저명도 + 약간 더 선명한(채도↑)" 딥톤
                        // - hover는 더 어둡게
                        const btnBase = boostSaturation(darkenToLowLuminance(rgb, 0.52), 0.14);
                        const btnHover = boostSaturation(darkenToLowLuminance(rgb, 0.64), 0.16);
                        const border = lighten(btnBase, 0.12);
                        const borderHover = lighten(btnBase, 0.18);
                        // blur/레이어감이 살아나도록 rgba(반투명)로 적용
                        $wrapper.get(0).style.setProperty('--hero-btn-bg', rgbaToCss(btnBase, 0.78));
                        $wrapper.get(0).style.setProperty('--hero-btn-bg-hover', rgbaToCss(btnHover, 0.88));
                        $wrapper.get(0).style.setProperty('--hero-btn-border', `rgba(${border.r}, ${border.g}, ${border.b}, 0.55)`);
                        $wrapper.get(0).style.setProperty('--hero-btn-border-hover', `rgba(${borderHover.r}, ${borderHover.g}, ${borderHover.b}, 0.7)`);
                    }
                }
                // 그라데이션: 값이 비면 레이어 자체를 끔(기본값으로 돌아가지 않게)
                if (gradEnabled && gradBottom) {
                    $wrapper.get(0).style.setProperty('--hero-grad-bottom', gradBottom);
                    $wrapper.get(0).style.setProperty('--hero-grad-opacity', '1');
                } else {
                    $wrapper.get(0).style.setProperty('--hero-grad-bottom', 'rgba(0,0,0,0)');
                    $wrapper.get(0).style.setProperty('--hero-grad-opacity', '0');
                }

                if ($dots.length) {
                    $dots.removeClass('is-active').removeAttr('aria-current');
                    $dots.eq(idx).addClass('is-active').attr('aria-current', 'true');
                }

                if ($slideCounterCurrent.length) {
                    $slideCounterCurrent.text(String(idx + 1));
                    const $ctr = $slideCounterCurrent.closest('.hero-slide-counter');
                    const tot = $slides.length;
                    if ($ctr.length && tot) {
                        $ctr.attr('aria-label', '슬라이드 ' + (idx + 1) + ' / ' + tot);
                    }
                }
            }

            // 초기 상태 동기화
            applySlide(current);

            $nextBtn.off('click.borobillHeroNext').on('click.borobillHeroNext', function () {
                const $active = $slides.eq(current);
                const btnEnabledRaw = $active.data('btn-enabled');
                const btnEnabled = String(typeof btnEnabledRaw === 'undefined' ? '1' : btnEnabledRaw) !== '0';
                const btnUrl = String($active.data('btn-url') || '').trim();

                // URL이 있으면 이동, 없으면 다음 슬라이드
                if (btnEnabled && btnUrl) {
                    // 새 창(새 탭)으로 열기
                    const win = window.open(btnUrl, '_blank', 'noopener,noreferrer');
                    if (win) {
                        win.opener = null;
                    }
                    return;
                }

                applySlide(current + 1);
            });

            $wrapper.off('click.borobillHeroDot').on('click.borobillHeroDot', '.hero-indicators .dot', function () {
                const idx = Number($(this).data('index'));
                if (!Number.isFinite(idx)) return;
                applySlide(idx);
            });

            let timer = null;
            function stopAuto() {
                if (timer) {
                    window.clearInterval(timer);
                    timer = null;
                }
            }
            function startAuto() {
                if (!autoDelayMs || $slides.length < 2) return;
                stopAuto();
                timer = window.setInterval(() => applySlide(current + 1), autoDelayMs);
            }

            $wrapper.off('mouseenter.borobillHero focusin.borobillHero').on('mouseenter.borobillHero focusin.borobillHero', stopAuto);
            $wrapper.off('mouseleave.borobillHero focusout.borobillHero').on('mouseleave.borobillHero focusout.borobillHero', startAuto);

            document.addEventListener('visibilitychange', function () {
                if (document.hidden) {
                    stopAuto();
                } else {
                    startAuto();
                }
            });

            $(document).off('keydown.borobillHero').on('keydown.borobillHero', function (e) {
                const activeEl = document.activeElement;
                if (!activeEl || !$wrapper.get(0).contains(activeEl)) {
                    return;
                }
                if (e.key === 'ArrowRight') {
                    applySlide(current + 1);
                } else if (e.key === 'ArrowLeft') {
                    applySlide(current - 1);
                }
            });

            /** 태블릿·모바일(≤1024px): 좌우 스와이프로 이전/다음 슬라이드 */
            const heroSwipeMq = window.matchMedia ? window.matchMedia('(max-width: 1024px)') : null;
            let heroTouchStartX = null;
            let heroTouchStartY = null;
            const HERO_SWIPE_MIN_PX = 48;

            function isHeroSwipeViewport() {
                return heroSwipeMq ? heroSwipeMq.matches : window.innerWidth <= 1024;
            }

            $wrapper
                .off('touchstart.borobillHeroSwipe')
                .on('touchstart.borobillHeroSwipe', function (e) {
                    if (!isHeroSwipeViewport() || !e.originalEvent || !e.originalEvent.touches || e.originalEvent.touches.length !== 1) {
                        return;
                    }
                    const t = e.originalEvent.touches[0];
                    heroTouchStartX = t.clientX;
                    heroTouchStartY = t.clientY;
                });

            $wrapper
                .off('touchend.borobillHeroSwipe')
                .on('touchend.borobillHeroSwipe', function (e) {
                    if (heroTouchStartX === null || heroTouchStartY === null) {
                        return;
                    }
                    if (!isHeroSwipeViewport() || !e.originalEvent || !e.originalEvent.changedTouches || e.originalEvent.changedTouches.length !== 1) {
                        heroTouchStartX = null;
                        heroTouchStartY = null;
                        return;
                    }
                    const t = e.originalEvent.changedTouches[0];
                    const dx = t.clientX - heroTouchStartX;
                    const dy = t.clientY - heroTouchStartY;
                    heroTouchStartX = null;
                    heroTouchStartY = null;

                    if (Math.abs(dx) < HERO_SWIPE_MIN_PX) {
                        return;
                    }
                    /* 세로 스크롤 위주 제스처는 무시 */
                    if (Math.abs(dy) >= Math.abs(dx) * 0.85) {
                        return;
                    }

                    if (dx < 0) {
                        applySlide(current + 1);
                    } else {
                        applySlide(current - 1);
                    }
                });

            startAuto();
        })();

        // --- 카테고리 추천 아티클 모바일 슬라이더 (<768px) --- //
        (function initCategoryHeroCarousel() {
            const $shell = $('.layout--category .category-hero-carousel-shell');
            const $track = $shell.find('#category-hero-carousel-track');
            const $slides = $shell.find('.category-hero-carousel-slide');
            const $prev = $shell.find('[data-category-hero-prev]');
            const $next = $shell.find('[data-category-hero-next]');
            const $counterCur = $shell.find('.category-hero-carousel-counter__current');
            const $counterWrap = $shell.find('.category-hero-carousel-counter');

            if (!$shell.length || !$track.length || !$slides.length) {
                return;
            }

            const mqMobile = window.matchMedia ? window.matchMedia('(max-width: 767px)') : null;

            function isMobileViewport() {
                return mqMobile ? mqMobile.matches : window.innerWidth <= 767;
            }

            let current = 0;
            const total = $slides.length;

            function clampIdx(i) {
                return ((i % total) + total) % total;
            }

            function applySlide(nextIndex) {
                current = clampIdx(nextIndex);
                $track.css('transform', 'translateX(' + (-current * 100) + '%)');
                $slides.removeClass('is-active').eq(current).addClass('is-active');
                if ($counterCur.length) {
                    $counterCur.text(String(current + 1));
                }
                if ($counterWrap.length && total) {
                    $counterWrap.attr(
                        'aria-label',
                        '슬라이드 ' + (current + 1) + ' / ' + total
                    );
                }
            }

            applySlide(0);

            $prev.off('click.borobillCatHero').on('click.borobillCatHero', function () {
                applySlide(current - 1);
            });
            $next.off('click.borobillCatHero').on('click.borobillCatHero', function () {
                applySlide(current + 1);
            });

            let touchStartX = null;
            let touchStartY = null;
            const SWIPE_MIN_PX = 44;

            $shell
                .off('touchstart.borobillCatHeroSwipe')
                .on('touchstart.borobillCatHeroSwipe', function (e) {
                    if (!isMobileViewport() || !e.originalEvent || !e.originalEvent.touches || e.originalEvent.touches.length !== 1) {
                        return;
                    }
                    const t = e.originalEvent.touches[0];
                    touchStartX = t.clientX;
                    touchStartY = t.clientY;
                });

            $shell
                .off('touchend.borobillCatHeroSwipe')
                .on('touchend.borobillCatHeroSwipe', function (e) {
                    if (touchStartX === null || touchStartY === null) {
                        return;
                    }
                    if (!isMobileViewport() || !e.originalEvent || !e.originalEvent.changedTouches || e.originalEvent.changedTouches.length !== 1) {
                        touchStartX = null;
                        touchStartY = null;
                        return;
                    }
                    const t = e.originalEvent.changedTouches[0];
                    const dx = t.clientX - touchStartX;
                    const dy = t.clientY - touchStartY;
                    touchStartX = null;
                    touchStartY = null;

                    if (Math.abs(dx) < SWIPE_MIN_PX) {
                        return;
                    }
                    if (Math.abs(dy) >= Math.abs(dx) * 0.85) {
                        return;
                    }

                    if (dx < 0) {
                        applySlide(current + 1);
                    } else {
                        applySlide(current - 1);
                    }
                });
        })();

        // 한 번에 보여줄 리스트 게시글 수
        // - 기본: 6개
        // - "전체" 버튼 선택 시도 동일하게 6개 (요청)
        const DEFAULT_LIST_PER_PAGE = 6;
        const ALL_LIST_PER_PAGE = 6;
        const MOBILE_BREAKPOINT_QUERY = '(max-width: 768px)';

        // 전역 변수 (state)
        let allPosts = [];
        let allCategories = [];
        let categoryGroups = {}; // {parentID: [하위포함id들]}
        let categoryChildrenMap = {}; // {parentId: [childId...]}
        let filterOrder = ['초보사업자','세무·비즈니스','사업자 뉴스룸','바로빌 가이드','고객사례·인사이트'];
        let currentCatGroupIDs = [];
        let currentViewPage = 1;
        let nowGroup = 'all';
        let currentSearchKeyword = '';
        let filterConfig = [];
        let filterGroupMap = {};
        let allowedCategoryIds = [];
        let customFilterItems = [];
        let rootCategoryIds = [];
        let rootLabelByCatId = {}; // {categoryId: rootLabel}
        let listSortOrder = 'latest'; // 'latest' | 'recommended' — 카테고리 서브페이지 전체게시글 정렬
        let feedObserver = null;
        let feedSentinelEl = null;
        let lastIsMobileFeed = null;
        let isFeedLoadingMore = false;

        const rawFilterItems = $filter.length ? $filter.data('filter-items') : null;
        const rawRootCatIds = $filter.length ? $filter.data('root-cat-ids') : null;
        customFilterItems = parseFilterItems(rawFilterItems);
        rootCategoryIds = parseNumericArray(rawRootCatIds);

        function stripHtml(input) {
            const s = String(input || '');
            return s.replace(/<[^>]*>?/gm, ' ').replace(/\s+/g, ' ').trim();
        }

        function escapeHtml(input) {
            return String(input ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function escapeAttr(input) {
            return String(input ?? '').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        }

        function normalizeForSearch(input) {
            // 공백/구두점 차이는 무시하고 의미 단위로 매칭되게 처리
            // 예) "초보사업자" === "초보 사업자"
            return String(input || '')
                .toLowerCase()
                .replace(/<[^>]*>?/gm, ' ')
                .replace(/[\s\u00A0]+/g, '') // whitespace + nbsp
                .replace(/[·•\u00B7\.\,\!\?\:\;\(\)\[\]\{\}"'`~@#$%^&*\-_=+\\\/|<>]/g, '');
        }

        function getEmbeddedTerms(post, taxonomy) {
            const groups = post && post._embedded && post._embedded['wp:term'] ? post._embedded['wp:term'] : null;
            if (!Array.isArray(groups)) {
                return [];
            }
            const out = [];
            groups.forEach((group) => {
                if (!Array.isArray(group)) {
                    return;
                }
                group.forEach((term) => {
                    if (!term || !term.name) {
                        return;
                    }
                    if (taxonomy && term.taxonomy !== taxonomy) {
                        return;
                    }
                    out.push({
                        name: String(term.name),
                        link: term.link ? String(term.link) : '',
                    });
                });
            });
            return out;
        }

        function getEmbeddedTermNames(post, taxonomy) {
            const groups = post && post._embedded && post._embedded['wp:term'] ? post._embedded['wp:term'] : null;
            if (!Array.isArray(groups)) {
                return [];
            }
            const out = [];
            groups.forEach((group) => {
                if (!Array.isArray(group)) {
                    return;
                }
                group.forEach((term) => {
                    if (!term || !term.name) {
                        return;
                    }
                    if (taxonomy && term.taxonomy !== taxonomy) {
                        return;
                    }
                    out.push(String(term.name));
                });
            });
            return out;
        }

        // --- 데이터 fetch --- //
        async function fetchAllPosts() {
            const collected = [];
            let page = 1;
            let totalPages = 0;

            while (true) {
                const url = `${WP_API_URL}posts?per_page=100&page=${page}&_embed`;
                const res = await fetch(url);
                if (!res.ok) {
                    break;
                }
                const data = await res.json();
                if (Array.isArray(data) && data.length) {
                    collected.push(...data);
                }

                if (!totalPages) {
                    const headerTotalPages = parseInt(res.headers.get('X-WP-TotalPages') || '0', 10);
                    if (!Number.isNaN(headerTotalPages) && headerTotalPages > 0) {
                        totalPages = headerTotalPages;
                    }
                }

                if ((totalPages && page >= totalPages) || !Array.isArray(data) || data.length < 100) {
                    break;
                }
                page += 1;
            }

            return collected;
        }

        Promise.all([
            fetch(WP_API_URL+'categories?per_page=100').then(res=>res.json()),
            fetchAllPosts(),
        ]).then(([cats, posts])=>{
            allCategories = cats;
            allPosts = posts;
            categoryChildrenMap = buildChildrenMap();
            categoryGroups = buildCategoryGroups();
            initializeFilterState();
            rootLabelByCatId = buildRootLabelMap();

            // 카테고리 서브페이지: 현재 카테고리(GNB 하위)만 리스트에 노출되도록 초기 그룹 적용
            const initialGroup = $filter.length ? String($filter.data('initial-group') || '').trim() : '';
            if (initialGroup && filterGroupMap[initialGroup]) {
                nowGroup = initialGroup;
                currentCatGroupIDs = filterGroupMap[nowGroup] || filterGroupMap['all'];
            }

            if (allowedCategoryIds.length) {
                const allowedSet = new Set(allowedCategoryIds);
                allPosts = allPosts.filter(post=>{
                    return post.categories && post.categories.some(cid=>allowedSet.has(cid));
                });
            }

            renderFilter();
            bindInlineSearch();
            bindAllPostsSort();
            renderFeatured();
            renderList();

            // 모바일/데스크톱 전환 시 목록 모드 전환
            $(window).off('resize.borobillFeed').on('resize.borobillFeed', function () {
                const nowMobile = isMobileFeed();
                if (lastIsMobileFeed === null) {
                    lastIsMobileFeed = nowMobile;
                    return;
                }
                if (nowMobile !== lastIsMobileFeed) {
                    lastIsMobileFeed = nowMobile;
                    currentViewPage = 1;
                    teardownMobileFeedObserver();
                    renderList();
                }
            });

        });

        // --- 카테고리 트리 구하기 --- //
        function buildChildrenMap() {
            const map = {};
            (allCategories || []).forEach(c => {
                const pid = Number(c && typeof c.parent !== 'undefined' ? c.parent : 0);
                const id = Number(c && typeof c.id !== 'undefined' ? c.id : NaN);
                if (Number.isNaN(id)) {
                    return;
                }
                if (!map[pid]) {
                    map[pid] = [];
                }
                map[pid].push(id);
            });
            return map;
        }

        function getSubtreeIds(rootId) {
            const out = [];
            const stack = [Number(rootId)];
            const visited = new Set();
            while (stack.length) {
                const id = stack.pop();
                if (Number.isNaN(id) || visited.has(id)) {
                    continue;
                }
                visited.add(id);
                out.push(id);
                const children = categoryChildrenMap[id] || [];
                for (let i = children.length - 1; i >= 0; i--) {
                    stack.push(children[i]);
                }
            }
            return out;
        }

        function buildCategoryGroups() {
            let groups = {};
            for (let cname of filterOrder) {
                let parent = allCategories.find(c=>c.name===cname);
                if (!parent) continue;
                let ids = [parent.id];
                let children = allCategories.filter(c=>c.parent===parent.id);
                function subtree(cat) {
                    let direct = allCategories.filter(c=>c.parent===cat.id)
                    for (let sub of direct) {
                        ids.push(sub.id);
                        subtree(sub);
                    }
                }
                subtree(parent);
                groups[parent.id] = ids;
            }
            groups['all'] = allCategories.map(c=>c.id);
            return groups;
        }

        // 메인 뱃지는 "상위 카테고리(버튼명)"로 통일하기 위한 매핑
        function buildRootLabelMap() {
            const map = {};
            for (let cname of filterOrder) {
                const parent = allCategories.find(c => c.name === cname);
                if (!parent) continue;
                const ids = categoryGroups[parent.id] ? categoryGroups[parent.id].slice() : [parent.id];
                ids.forEach(id => { map[id] = parent.name; });
            }
            return map;
        }

        function getRootLabelForPost(post) {
            if (!post || !post.categories || !post.categories.length) {
                return '';
            }
            // post.categories 안에서 rootLabel 매핑되는 첫 카테고리 기준
            for (const cid of post.categories) {
                const label = rootLabelByCatId[cid];
                if (label) return label;
            }
            // fallback: 첫 카테고리명
            return getCategoryName(post.categories[0]);
        }

        /** 리스트/추천 카드 뱃지용: 실제 카테고리명(하위 우선). GNB 직계 자식이 있으면 그 이름, 없으면 루트명 */
        function getDisplayCategoryLabelForPost(post) {
            if (!post || !post.categories || !post.categories.length) return '';
            const rootIds = filterOrder.map(name => {
                const p = allCategories.find(c => c.name === name);
                return p ? p.id : null;
            }).filter(id => id != null);
            for (const cid of post.categories) {
                const cat = allCategories.find(c => c.id === cid);
                if (!cat) continue;
                if (rootIds.includes(cat.parent)) return cat.name;
            }
            return getRootLabelForPost(post);
        }

        // --- 필터 버튼 렌더링 --- //
        function renderFilter() {
            if ( !$filter.length ) {
                return;
            }

            let html = '';
            html += `<button class="filter-item${nowGroup==='all'?' active':''}" data-group="all" tabindex="0">전체</button>`;
            for (let item of filterConfig) {
                const key = item.key;
                html += `<button class="filter-item${nowGroup===key?' active':''}" data-group="${key}" tabindex="0">${item.label}</button>`;
            }

            $filter.html(html);
            // 모바일에서는 항상 좌측부터 보이게(초기/재렌더 시 scrollLeft 리셋)
            if (window.matchMedia && window.matchMedia('(max-width: 768px)').matches) {
                const filterEl = $filter.get(0);
                if (filterEl) {
                    filterEl.scrollLeft = 0;
                }
            }
            $filter.off('click').on('click','.filter-item',function(){
                $filter.find('.filter-item').removeClass('active');
                $(this).addClass('active');
                let group = $(this).data('group');
                nowGroup = group+'';
                currentCatGroupIDs = filterGroupMap[nowGroup] || filterGroupMap['all'];
                currentViewPage = 1;
                teardownMobileFeedObserver();
                renderList();
            });
            // keydown으로도 이동 가능하도록
            $filter.off('keydown').on('keydown','.filter-item',function(e){
                if(e.key==='Enter'||e.key===' '){ $(this).trigger('click'); }
            });

            currentCatGroupIDs = filterGroupMap[nowGroup] || filterGroupMap['all'];
        }

        function initializeFilterState() {
            if (rootCategoryIds.length) {
                const expanded = [];
                rootCategoryIds.forEach(id => {
                    expanded.push(...getSubtreeIds(id));
                });
                allowedCategoryIds = Array.from(new Set(expanded));
            } else {
                allowedCategoryIds = (categoryGroups['all'] ? categoryGroups['all'].slice() : []);
            }
            filterGroupMap = { all: allowedCategoryIds.slice() };
            filterConfig = buildFilterConfig();
            currentCatGroupIDs = filterGroupMap[nowGroup] || filterGroupMap['all'];
        }

        function buildFilterConfig() {
            const configs = [];
            if ( customFilterItems.length ) {
                customFilterItems.forEach(item => {
                    const keyCandidate = item.slug || item.key || item.label || item.name || item.id;
                    if ( ! keyCandidate ) {
                        return;
                    }
                    const key = String(keyCandidate);
                    const label = item.label || item.name || key;
                    let ids = [];

                    if ( Array.isArray( item.cat_ids ) && item.cat_ids.length ) {
                        ids = item.cat_ids;
                    } else if ( Array.isArray( item.category_ids ) && item.category_ids.length ) {
                        ids = item.category_ids;
                    } else if ( item.id ) {
                        ids = [ item.id ];
                    }

                    ids = ids.map(id => Number(id)).filter(id => !Number.isNaN(id));
                    if ( ! ids.length ) {
                        return;
                    }

                    // 커스텀 필터(루트의 직계 자식 탭): 해당 카테고리의 하위(손자)까지 포함
                    const expanded = [];
                    ids.forEach(id => {
                        expanded.push(...getSubtreeIds(id));
                    });
                    filterGroupMap[key] = Array.from(new Set(expanded));
                    configs.push( { key, label } );
                } );
            } else {
                for (let cname of filterOrder) {
                    let parent = allCategories.find(c=>c.name===cname);
                    if ( ! parent ) {
                        continue;
                    }
                    const key = String(parent.id);
                    const ids = categoryGroups[parent.id] ? categoryGroups[parent.id].slice() : [parent.id];
                    filterGroupMap[key] = ids;
                    configs.push({
                        key,
                        label: parent.name,
                    });
                }
            }

            return configs;
        }

        // --- 추천 게시물 렌더링 (최신 3개) --- //
        function getPostSubtitlePlain(post) {
            if (!post) {
                return '';
            }
            const raw = post.borobill_subtitle != null && post.borobill_subtitle !== ''
                ? String(post.borobill_subtitle)
                : (post.meta && post.meta._borobill_subtitle != null ? String(post.meta._borobill_subtitle) : '');
            return stripHtml(raw).trim();
        }

        function renderFeatured() {
            let recHTML = '';
            let picked = [...allPosts]
                .filter(p=>p.status==='publish')
                .sort((a,b)=>(new Date(b.date) - new Date(a.date)))
                .slice(0,3);

            const getViews = (p) => (p && (p.borobill_views != null ? p.borobill_views : (p.meta && p.meta._bb_views))) || 0;
            for (let post of picked) {
                let thumb = post._embedded && post._embedded['wp:featuredmedia'] ? post._embedded['wp:featuredmedia'][0].source_url : '/wordpress/wp-content/themes/borobill_theme/images/default.png';
                let cat = escapeHtml(getDisplayCategoryLabelForPost(post));
                const views = formatViewCount(getViews(post));
                const subPlain = getPostSubtitlePlain(post);
                const subBlock = subPlain
                    ? `<p class="featured-subdesc"><a class="featured-subdesc__link" href="${escapeAttr(post.link)}">${escapeHtml(subPlain)}</a></p>`
                    : '';
                recHTML += `<article class="featured-card featured-card--feed">
<a href="${post.link}" class="featured-thumb-wrap">
    <img src="${thumb}" class="featured-thumb" alt="${post.title.rendered.replace(/<[^>]*>?/gm, '')}" />
</a>
<div class="featured-meta article-meta">
    <span class="badge badge-small">${cat}</span>
    <span class="meta-date">${formatDate(post.date)}</span>
    <span class="featured-views">
        <span class="featured-views__label">조회수</span>
        <span class="featured-views__count">${views}</span>
    </span>
</div>
<h3 class="featured-title"><a href="${post.link}">${post.title.rendered}</a></h3>
${subBlock}
</article>`;
            }
            $featured.html(recHTML);
        }

        // --- 리스트+페이지네이션 렌더링 --- //
        function getFilteredSortedPosts() {
            let filtered = allPosts
                .filter(post => post && post.status === 'publish')
                .filter(post => {
                    if (!currentCatGroupIDs || !currentCatGroupIDs.length) {
                        return true;
                    }
                    return post.categories && post.categories.some(cid => currentCatGroupIDs.includes(cid));
                })
                .filter(post => {
                    const qNorm = normalizeForSearch(currentSearchKeyword);
                    if (!qNorm) {
                        return true;
                    }

                    const title = stripHtml(post?.title?.rendered || '');
                    const subtitle = stripHtml(post?.borobill_subtitle || post?.meta?._borobill_subtitle || '');
                    const excerpt = stripHtml(post?.excerpt?.rendered || '');
                    const content = stripHtml(post?.content?.rendered || '');
                    const badge = stripHtml(getRootLabelForPost(post) || '');
                    const tagNames = getEmbeddedTermNames(post, 'post_tag').join(' ');
                    const categoryNames = getEmbeddedTermNames(post, 'category').join(' ');

                    const hayNorm = normalizeForSearch(
                        [title, subtitle, badge, excerpt, content, tagNames, categoryNames].join(' ')
                    );
                    return hayNorm.includes(qNorm);
                });

            // 최신순: 대카테고리 기준 최신 업데이트(날짜) 순 / 추천순: 조회수(borobill_views) 순
            const getViews = (p) => (p && (p.borobill_views != null ? p.borobill_views : (p.meta && p.meta._bb_views))) || 0;
            if (listSortOrder === 'recommended') {
                filtered.sort((a, b) => (getViews(b) - getViews(a)) || (new Date(b.date) - new Date(a.date)));
            } else {
                filtered.sort((a, b) => (new Date(b.date) - new Date(a.date)));
            }

            return filtered;
        }

        function bindAllPostsSort() {
            const $sortWrap = $('.all-posts-sort');
            if (!$sortWrap.length) {
                return;
            }
            $sortWrap.off('click.borobillSort').on('click.borobillSort', '.sort-btn', function () {
                const sort = $(this).data('sort');
                if (sort !== 'latest' && sort !== 'recommended') {
                    return;
                }
                listSortOrder = sort;
                $sortWrap.find('.sort-btn').removeClass('active').attr('aria-pressed', 'false');
                $(this).addClass('active').attr('aria-pressed', 'true');
                currentViewPage = 1;
                teardownMobileFeedObserver();
                renderList();
            });
        }

        function bindInlineSearch() {
            const $form = $('.category-subfilter-search[data-inline-search="1"]');
            if (!$form.length) {
                return;
            }
            const $input = $form.find('input[type="search"]');
            if (!$input.length) {
                return;
            }

            currentSearchKeyword = String($input.val() || '');

            $form.off('submit.borobillInlineSearch').on('submit.borobillInlineSearch', function (e) {
                e.preventDefault();
                currentSearchKeyword = String($input.val() || '');
                currentViewPage = 1;
                teardownMobileFeedObserver();
                renderList();
            });

            // 입력값이 비워지면 즉시 전체로 복귀
            $input.off('input.borobillInlineSearch').on('input.borobillInlineSearch', function () {
                if (String(this.value || '').trim() !== '') {
                    return;
                }
                currentSearchKeyword = '';
                currentViewPage = 1;
                teardownMobileFeedObserver();
                renderList();
            });
        }

        function renderPagination(totalPages, page) {
            // 모바일: 기본은 페이지네이션 숨김(무한 피드) / "전체"에서는 페이지네이션 사용
            if (isMobileFeed() && String(nowGroup) !== 'all') {
                $pagination.empty();
                return;
            }
            if (totalPages <= 1) {
                $pagination.empty();
                return;
            }

            // 요청: 페이지 번호는 6개까지만 노출 (일반적인 그룹형 프론트엔드 페이지네이션)
            const windowSize = Math.min(6, totalPages);
            const groupIndex = Math.floor((page - 1) / windowSize);
            const start = groupIndex * windowSize + 1;
            const end = Math.min(totalPages, start + windowSize - 1);

            let html = '';
            html += `<button class="page-btn nav prev" type="button" data-action="prev" aria-label="이전 페이지" ${page <= 1 ? 'disabled' : ''}>
                <span class="page-icon page-icon--prev" aria-hidden="true"></span>
            </button>`;

            for (let i = start; i <= end; i++) {
                const isActive = i === page;
                html += `<button class="page-btn${isActive ? ' active' : ''}" type="button" data-page="${i}" ${isActive ? 'aria-current="page"' : ''} aria-label="${i} 페이지">${i}</button>`;
            }

            html += `<button class="page-btn nav next" type="button" data-action="next" aria-label="다음 페이지" ${page >= totalPages ? 'disabled' : ''}>
                <span class="page-icon page-icon--next" aria-hidden="true"></span>
            </button>`;

            $pagination.html(html);
        }

        function isMobileFeed() {
            return window.matchMedia && window.matchMedia(MOBILE_BREAKPOINT_QUERY).matches;
        }

        function getListPerPage() {
            return String(nowGroup) === 'all' ? ALL_LIST_PER_PAGE : DEFAULT_LIST_PER_PAGE;
        }

        function isMobileInfiniteFeedMode() {
            return isMobileFeed() && String(nowGroup) !== 'all';
        }

        function ensureMobileFeedSentinel() {
            if (!$list.length) {
                feedSentinelEl = null;
                return null;
            }
            if (feedSentinelEl && document.contains(feedSentinelEl)) {
                return feedSentinelEl;
            }
            let $sentinel = $('#post-list-sentinel');
            if (!$sentinel.length) {
                $sentinel = $('<div id="post-list-sentinel" aria-hidden="true" style="height:1px;"></div>');
                $sentinel.insertAfter($list);
            }
            feedSentinelEl = $sentinel.get(0);
            return feedSentinelEl;
        }

        function teardownMobileFeedObserver() {
            if (feedObserver) {
                try { feedObserver.disconnect(); } catch (e) {}
            }
            feedObserver = null;
            isFeedLoadingMore = false;
        }

        function setupMobileFeedObserver(totalPages) {
            if (!isMobileInfiniteFeedMode()) {
                teardownMobileFeedObserver();
                return;
            }

            const sentinel = ensureMobileFeedSentinel();
            if (!sentinel || typeof IntersectionObserver === 'undefined') {
                return;
            }

            teardownMobileFeedObserver();
            feedObserver = new IntersectionObserver((entries) => {
                const entry = entries && entries[0];
                if (!entry || !entry.isIntersecting) {
                    return;
                }
                if (isFeedLoadingMore) {
                    return;
                }
                if (currentViewPage >= totalPages) {
                    return;
                }
                isFeedLoadingMore = true;
                currentViewPage = currentViewPage + 1;
                renderList();
                // 렌더 직후 연속 트리거 방지
                setTimeout(() => {
                    isFeedLoadingMore = false;
                }, 120);
            }, { root: null, rootMargin: '300px 0px', threshold: 0.01 });

            feedObserver.observe(sentinel);
        }

        function renderList() {
            const filtered = getFilteredSortedPosts();
            const total = filtered.length;
            const perPage = getListPerPage();
            const totalPages = Math.max(1, Math.ceil(total / perPage));
            const page = Math.min(Math.max(1, currentViewPage), totalPages);
            const sliced = isMobileInfiniteFeedMode()
                ? filtered.slice(0, page * perPage) // 모바일: 누적 노출(무한 피드)
                : filtered.slice((page - 1) * perPage, page * perPage); // 데스크톱/태블릿 + (모바일 전체): 페이지별 노출

            const getViews = (p) => (p && (p.borobill_views != null ? p.borobill_views : (p.meta && p.meta._bb_views))) || 0;
            let listHTML = '';
            for (let post of sliced) {
                let thumb = post._embedded && post._embedded['wp:featuredmedia'] ? post._embedded['wp:featuredmedia'][0].source_url : '/wordpress/wp-content/themes/borobill_theme/images/default.png';
                const cat = escapeHtml(getDisplayCategoryLabelForPost(post));
                const views = formatViewCount(getViews(post));
                const listSubPlain = getPostSubtitlePlain(post);
                const listSubBlock = listSubPlain
                    ? `<p class="article-subtitle"><a class="article-subtitle__link" href="${escapeAttr(post.link)}">${escapeHtml(listSubPlain)}</a></p>`
                    : '';
                listHTML += `<article class="article-card article-card--feed">
<div class="article-info">
    <div class="article-meta">
        <span class="badge badge-small">${cat}</span>
        <span class="meta-date">${formatDate(post.date)}</span>
        <span class="featured-views">
            <span class="featured-views__label">조회수</span>
            <span class="featured-views__count">${views}</span>
        </span>
    </div>
    <h3 class="article-title"><a href="${post.link}">${post.title.rendered}</a></h3>
    ${listSubBlock}
</div>
<div class="article-thumb-wrap">
    <a href="${post.link}">
        <img src="${thumb}" class="article-thumb" alt="${post.title.rendered.replace(/<[^>]*>?/gm, '')}" />
    </a>
</div>
</article>`;
            }
            // 초기 스켈레톤 제거(레이아웃 안정화 후 실제 콘텐츠로 교체)
            $list.removeClass('post-list--skeleton').attr('aria-busy', 'false');
            $list.html(listHTML);
            renderPagination(totalPages, page);
            lastIsMobileFeed = isMobileFeed();
            if (isMobileInfiniteFeedMode()) {
                setupMobileFeedObserver(totalPages);
            } else {
                teardownMobileFeedObserver();
            }
        }

        // 페이지네이션 이벤트(버튼 클릭)
        $pagination.off('click.borobillPagination').on('click.borobillPagination', '.page-btn', function (e) {
            e.preventDefault();

            const $btn = $(this);
            if ($btn.is('[disabled]')) {
                return;
            }

            const filtered = getFilteredSortedPosts();
            const perPage = getListPerPage();
            const totalPages = Math.max(1, Math.ceil(filtered.length / perPage));

            const action = String($btn.data('action') || '');
            const pageValue = Number($btn.data('page'));

            if (action === 'prev') {
                currentViewPage = Math.max(1, currentViewPage - 1);
            } else if (action === 'next') {
                currentViewPage = Math.min(totalPages, currentViewPage + 1);
            } else if (!Number.isNaN(pageValue) && pageValue > 0) {
                currentViewPage = pageValue;
            }

            renderList();
        });
        // --- 유틸 함수 --- //
        function formatDate(dtstr) {
            let d = new Date(dtstr);
            return `${d.getFullYear()}.${String(d.getMonth()+1).padStart(2,'0')}.${String(d.getDate()).padStart(2,'0')}`;
        }

        function escapeHtml(str) {
            if (str == null || str === '') {
                return '';
            }
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        function formatViewCount(n) {
            const v = Number(n);
            if (!Number.isFinite(v) || v < 0) {
                return '0';
            }
            try {
                return v.toLocaleString('ko-KR');
            } catch (e) {
                return String(v);
            }
        }
        function getCategoryName(cid) {
            let cat = allCategories.find(c=>c.id===cid);
            return cat ? cat.name : '';
        }

        function parseFilterItems(raw) {
            if (!raw) {
                return [];
            }
            if (Array.isArray(raw)) {
                return raw;
            }
            if (typeof raw === 'string') {
                try {
                    const parsed = JSON.parse(raw);
                    if (Array.isArray(parsed)) {
                        return parsed;
                    }
                } catch (e) {
                    return [];
                }
            }
            return [];
        }

        function parseNumericArray(raw) {
            if (!raw) {
                return [];
            }

            let values = [];
            if (Array.isArray(raw)) {
                values = raw;
            } else if (typeof raw === 'string') {
                try {
                    const parsed = JSON.parse(raw);
                    if (Array.isArray(parsed)) {
                        values = parsed;
                    } else {
                        values = raw.split(',').map(v=>v.trim()).filter(v=>v.length);
                    }
                } catch (e) {
                    values = raw.split(',').map(v=>v.trim()).filter(v=>v.length);
                }
            } else {
                return [];
            }

            return values.map(value => parseInt(value, 10)).filter(id => !Number.isNaN(id));
        }
    });
})(jQuery);
