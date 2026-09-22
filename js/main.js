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

        // 워드프레스 REST API endpoint (운영/로컬 경로 차이 대응)
        const WP_API_URL = (window.borobillHeroSettings && borobillHeroSettings.restUrl)
            ? String(borobillHeroSettings.restUrl)
            : '/wp-json/wp/v2/';

        // 기본 이미지 경로 (운영/로컬 경로 차이 대응)
        const BOROBILL_DEFAULT_THUMB = (function () {
            const el = document.querySelector('link[href*="/themes/borobill_theme/"], script[src*="/themes/borobill_theme/"]');
            const src = el ? (el.href || el.src) : '';
            const i = src.indexOf('/themes/borobill_theme/');
            return i > -1
                ? src.slice(0, i) + '/themes/borobill_theme/images/default.png'
                : '/wp-content/themes/borobill_theme/images/default.png';
        })();

        // 대표 이미지: 원본(수 MB) 대신 축소본을 사용
        function getThumbUrl(post) {
            const media = post._embedded
                && post._embedded['wp:featuredmedia']
                && post._embedded['wp:featuredmedia'][0];
            if (!media) return BOROBILL_DEFAULT_THUMB;
            const sizes = media.media_details && media.media_details.sizes;
            if (sizes) {
                const pick = sizes.borobill_featured || sizes.medium_large || sizes.large || sizes.medium;
                if (pick && pick.source_url) return pick.source_url;
            }
            return media.source_url || BOROBILL_DEFAULT_THUMB;
        }

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
                if (window.matchMedia && window.matchMedia('(min-width: 1001px)').matches) {
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
            const $paginationCurrent = $wrapper.find('.hero-carousel-pagination__current');
            const $paginationWrap = $wrapper.find('.hero-carousel-pagination__counter');
            const $paginationPrev = $wrapper.find('[data-hero-pagination-prev]');
            const $paginationNext = $wrapper.find('[data-hero-pagination-next]');
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
                return { r: (num >> 16) & 255, g: (num >> 8) & 255, b: num & 255, a: 1 };
            }

            function parseCssColor(input) {
                const raw = String(input || '').trim();
                if (!raw) return null;
                const asHex = hexToRgb(raw);
                if (asHex) return asHex;
                const mRgb = raw.match(/^rgba?\(\s*([0-9.]+)\s*,\s*([0-9.]+)\s*,\s*([0-9.]+)(?:\s*,\s*([0-9.]+))?\s*\)$/i);
                if (!mRgb) return null;
                return {
                    r: Math.round(Number(mRgb[1])),
                    g: Math.round(Number(mRgb[2])),
                    b: Math.round(Number(mRgb[3])),
                    a: (typeof mRgb[4] === 'undefined' || mRgb[4] === '') ? 1 : clamp(Number(mRgb[4]), 0, 1),
                };
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
                const btnColor = String($active.data('btn-color') || '').trim();
                const btnTextColor = String($active.data('btn-text-color') || '').trim() || '#ffffff';
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
                }

                $wrapper.get(0).style.setProperty('--hero-btn-color', btnTextColor);

                const applyHeroButtonColors = (rgb, opacity) => {
                    if (!rgb) return;
                    const a = clamp(Number(opacity), 0, 1);
                    const hoverA = Math.min(1, a + 0.08);
                    const border = lighten(rgb, 0.12);
                    const borderHover = lighten(rgb, 0.18);
                    const btnHover = darkenToLowLuminance(rgb, 0.12);
                    $wrapper.get(0).style.setProperty('--hero-btn-bg', rgbaToCss(rgb, a));
                    $wrapper.get(0).style.setProperty('--hero-btn-bg-hover', rgbaToCss(btnHover, hoverA));
                    $wrapper.get(0).style.setProperty('--hero-btn-border', `rgba(${border.r}, ${border.g}, ${border.b}, ${Math.min(1, a * 0.6)})`);
                    $wrapper.get(0).style.setProperty('--hero-btn-border-hover', `rgba(${borderHover.r}, ${borderHover.g}, ${borderHover.b}, ${Math.min(1, hoverA * 0.75)})`);
                };

                if (btnColor) {
                    const parsed = parseCssColor(btnColor);
                    if (parsed) {
                        applyHeroButtonColors(parsed, typeof parsed.a === 'number' ? parsed.a : 1);
                    }
                } else if (bgColor) {
                    const rgb = hexToRgb(bgColor);
                    if (rgb) {
                        // 요청사항:
                        // - 기본 버튼은 배경색 기반으로 "저명도 + 약간 더 선명한(채도↑)" 딥톤
                        // - hover는 더 어둡게
                        const btnBase = boostSaturation(darkenToLowLuminance(rgb, 0.52), 0.14);
                        const btnHover = boostSaturation(darkenToLowLuminance(rgb, 0.64), 0.16);
                        const border = lighten(btnBase, 0.12);
                        const borderHover = lighten(btnBase, 0.18);
                        const a = 0.97;
                        const hoverA = 1;
                        $wrapper.get(0).style.setProperty('--hero-btn-bg', rgbaToCss(btnBase, a));
                        $wrapper.get(0).style.setProperty('--hero-btn-bg-hover', rgbaToCss(btnHover, hoverA));
                        $wrapper.get(0).style.setProperty('--hero-btn-border', `rgba(${border.r}, ${border.g}, ${border.b}, ${Math.min(1, a * 0.6)})`);
                        $wrapper.get(0).style.setProperty('--hero-btn-border-hover', `rgba(${borderHover.r}, ${borderHover.g}, ${borderHover.b}, ${Math.min(1, hoverA * 0.75)})`);
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

                if ($paginationCurrent.length) {
                    $paginationCurrent.text(String(idx + 1));
                    const tot = $slides.length;
                    if ($paginationWrap.length && tot) {
                        $paginationWrap.attr('aria-label', '슬라이드 ' + (idx + 1) + ' / ' + tot);
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

            function openActiveHeroBannerLink() {
                const $active = $slides.eq(current);
                const btnEnabledRaw = $active.data('btn-enabled');
                const btnEnabled = String(typeof btnEnabledRaw === 'undefined' ? '1' : btnEnabledRaw) !== '0';
                const btnUrl = String($active.data('btn-url') || '').trim();

                if (!(btnEnabled && btnUrl)) {
                    return false;
                }

                const win = window.open(btnUrl, '_blank', 'noopener,noreferrer');
                if (win) {
                    win.opener = null;
                }
                return true;
            }

            let heroBannerIgnoreClick = false;

            $wrapper.off('click.borobillHeroBannerTap').on('click.borobillHeroBannerTap', function (e) {
                if (heroBannerIgnoreClick) {
                    heroBannerIgnoreClick = false;
                    return;
                }
                if ($(e.target).closest('.hero-carousel-pagination, [data-hero-next], a, button').length) {
                    return;
                }
                openActiveHeroBannerLink();
            });

            $paginationPrev.off('click.borobillHeroPagination').on('click.borobillHeroPagination', function () {
                applySlide(current - 1);
            });

            $paginationNext.off('click.borobillHeroPagination').on('click.borobillHeroPagination', function () {
                applySlide(current + 1);
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

                    heroBannerIgnoreClick = true;

                    if (dx < 0) {
                        applySlide(current + 1);
                    } else {
                        applySlide(current - 1);
                    }
                });

            startAuto();
        })();

        // --- 카테고리 추천 아티클 모바일 슬라이더 (≤1000px) --- //
        (function initCategoryHeroCarousel() {
            const $shell = $('.layout--category .category-hero-carousel-shell');
            const $track = $shell.find('#category-hero-carousel-track');
            const $slides = $shell.find('.category-hero-carousel-slide');
            const $prev = $shell.find('[data-category-hero-prev]');
            const $next = $shell.find('[data-category-hero-next]');
            const $counterCur = $shell.find('.category-hero-carousel-pagination__current');
            const $counterWrap = $shell.find('.category-hero-carousel-pagination__counter');

            if (!$shell.length || !$track.length || !$slides.length) {
                return;
            }

            const mqMobile = window.matchMedia ? window.matchMedia('(max-width: 1000px)') : null;

            function isMobileViewport() {
                return mqMobile ? mqMobile.matches : window.innerWidth <= 1000;
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
        let gnbLabelByCatId = {}; // {categoryId: gnbMenuTitle}
        let listSortOrder = 'latest'; // 'latest' | 'recommended' — 카테고리 서브페이지 전체게시글 정렬
        let postViewMode = 'list'; // 'photo' | 'list' | 'card' — 카테고리 전체게시글 보기 방식
        let feedObserver = null;
        let feedSentinelEl = null;
        let lastIsMobileFeed = null;
        let isFeedLoadingMore = false;

        const rawFilterItems = $filter.length ? $filter.data('filter-items') : null;
        const rawRootCatIds = $filter.length ? $filter.data('root-cat-ids') : null;
        const $indexGnbBadgeLabels = $('#borobill-index-gnb-badge-labels');

        function parseIndexGnbData($el, attrName) {
            if (!$el.length) {
                return null;
            }
            const raw = $el.attr(attrName);
            if (!raw) {
                return null;
            }
            try {
                return JSON.parse(raw);
            } catch (e) {
                return null;
            }
        }

        const rawGnbBadgeLabels = parseIndexGnbData($indexGnbBadgeLabels, 'data-gnb-badge-labels');
        const indexGnbRootIds = (parseIndexGnbData($indexGnbBadgeLabels, 'data-gnb-root-ids') || [])
            .map((id) => Number(id))
            .filter((id) => !Number.isNaN(id));
        const isIndexListPage = $indexGnbBadgeLabels.length > 0;
        const isCategoryRootListPage = $('.layout--category[data-category-root-page="1"]').length > 0;
        const showFeedListTags = false;
        customFilterItems = parseFilterItems(rawFilterItems);
        rootCategoryIds = parseNumericArray(rawRootCatIds);

        const filterScope = $filter.length ? String($filter.data('filter-scope') || '') : '';
        if (filterScope === 'global' && customFilterItems.length) {
            filterOrder = customFilterItems
                .map(item => item.label || item.name || '')
                .filter(name => name.length > 0);
        }

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

        function buildIndexArticleTagsBlock(post, maxTags) {
            if (!showFeedListTags) {
                return '';
            }
            const limit = Number.isFinite(maxTags) && maxTags > 0 ? maxTags : 3;
            const tagNames = getEmbeddedTermNames(post, 'post_tag').slice(0, limit);
            if (!tagNames.length) {
                return '';
            }
            const tagsHtml = tagNames.map((name) => (
                `<span class="article-tag" draggable="false"><span class="article-tag__text"># ${escapeHtml(name)}</span></span>`
            )).join('');
            return `<div class="article-tags article-tags--index" aria-label="태그">${tagsHtml}</div>`;
        }

        // --- 데이터 fetch --- //
        // content 제외 + embed 최소화로 운영 전송량/TTFB 감소
        const POST_LIST_FIELDS = 'id,date,link,status,title,excerpt,categories,borobill_subtitle,borobill_views,_links';

        // 주소의 ?tag= 값을 태그 ID로 바꿔 둔다 (한 번만 조회)
        let activeTagId = null;

        async function resolveActiveTag() {
            const name = new URLSearchParams(location.search).get('tag');
            if (!name) { return null; }

            try {
                const res = await fetch(`${WP_API_URL}tags?per_page=100&_fields=id,name`);
                if (!res.ok) { return null; }
                const list = await res.json();
                const hit = Array.isArray(list)
                    ? list.find((t) => t.name === name)
                    : null;
                return hit ? hit.id : null;
            } catch (e) {
                return null;
            }
        }

        function buildPostsUrl(page, perPage, categoryIds) {
            const catParam = (Array.isArray(categoryIds) && categoryIds.length)
                ? `&categories=${categoryIds.join(',')}`
                : '';
            const tagParam = activeTagId ? `&tags=${activeTagId}` : '';
            return `${WP_API_URL}posts?per_page=${perPage}&page=${page}&_embed=wp:featuredmedia,wp:term&_fields=${POST_LIST_FIELDS}${catParam}${tagParam}`;
        }

        async function fetchPostsPage(page, perPage, categoryIds) {
            const res = await fetch(buildPostsUrl(page, perPage, categoryIds));
            if (!res.ok) {
                return { data: [], totalPages: 0 };
            }
            const data = await res.json();
            const totalPages = parseInt(res.headers.get('X-WP-TotalPages') || '0', 10) || 0;
            return {
                data: Array.isArray(data) ? data : [],
                totalPages: Number.isNaN(totalPages) ? 0 : totalPages,
            };
        }

        async function fetchAllPosts(categoryIds) {
            const collected = [];
            let page = 1;
            let totalPages = 0;

            while (true) {
                const { data, totalPages: pages } = await fetchPostsPage(page, 100, categoryIds);
                if (data.length) {
                    collected.push(...data);
                }
                if (!totalPages && pages > 0) {
                    totalPages = pages;
                }
                if ((totalPages && page >= totalPages) || data.length < 100) {
                    break;
                }
                page += 1;
            }

            return collected;
        }

        function collectSubtreeIdsFromCategories(rootIds, categories) {
            const byParent = {};
            (categories || []).forEach((cat) => {
                const id = Number(cat && cat.id);
                const parent = Number(cat && cat.parent) || 0;
                if (Number.isNaN(id)) return;
                if (!byParent[parent]) byParent[parent] = [];
                byParent[parent].push(id);
            });
            const out = [];
            const walk = (id) => {
                out.push(id);
                (byParent[id] || []).forEach(walk);
            };
            (rootIds || []).forEach((id) => {
                const n = Number(id);
                if (!Number.isNaN(n)) walk(n);
            });
            return Array.from(new Set(out));
        }

        function applyAllowedCategoryFilter(posts) {
            if (!allowedCategoryIds.length) {
                return posts;
            }
            const allowedSet = new Set(allowedCategoryIds);
            return posts.filter((post) => (
                post.categories && post.categories.some((cid) => allowedSet.has(cid))
            ));
        }

        function finishListBootstrap() {
            gnbLabelByCatId = buildGnbLabelByCatId();
            rootLabelByCatId = buildRootLabelMap();

            const initialGroup = $filter.length ? String($filter.data('initial-group') || '').trim() : '';
            if (initialGroup && filterGroupMap[initialGroup]) {
                nowGroup = initialGroup;
                currentCatGroupIDs = filterGroupMap[nowGroup] || filterGroupMap['all'];
            }

            renderFilter();
            bindInlineSearch();
            bindAllPostsSort();
            bindPostViewMode();
            renderFeatured();
            renderList();

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
        }

        fetch(WP_API_URL + 'categories?per_page=100')
            .then((res) => res.json())
            .then(async (cats) => {
            allCategories = Array.isArray(cats) ? cats : [];
            categoryChildrenMap = buildChildrenMap();
            categoryGroups = buildCategoryGroups();
            initializeFilterState();

            // 카테고리 페이지: 서버에서 해당 트리만 조회 (전체 카탈로그 다운로드 방지)
            const fetchCatIds = rootCategoryIds.length
                ? collectSubtreeIdsFromCategories(rootCategoryIds, allCategories)
                : [];

            // 1) 첫 화면(6개)만 먼저 받아 스켈레톤 즉시 제거
            activeTagId = await resolveActiveTag();
            const firstPage = await fetchPostsPage(1, DEFAULT_LIST_PER_PAGE, fetchCatIds);
            allPosts = applyAllowedCategoryFilter(firstPage.data);
            finishListBootstrap();

            // 2) 나머지 전체는 백그라운드 — 필터/검색/페이지네이션용 데이터만 채움
            //    목록 카드 DOM은 다시 그리지 않음(두 번째 등장 효과 방지)
            fetchAllPosts(fetchCatIds).then((posts) => {
                allPosts = applyAllowedCategoryFilter(posts);
                refreshListPaginationOnly({ silent: true });
            }).catch(() => {});

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

        function buildGnbLabelByCatId() {
            const map = {};
            customFilterItems.forEach(item => {
                const label = item.label || item.name || '';
                if (!label) {
                    return;
                }
                let ids = [];
                if (Array.isArray(item.cat_ids) && item.cat_ids.length) {
                    ids = item.cat_ids;
                } else if (Array.isArray(item.category_ids) && item.category_ids.length) {
                    ids = item.category_ids;
                } else if (item.id) {
                    ids = [item.id];
                }
                ids.forEach(id => {
                    const numId = Number(id);
                    if (!Number.isNaN(numId)) {
                        map[numId] = label;
                    }
                });
            });

            if (rawGnbBadgeLabels && typeof rawGnbBadgeLabels === 'object') {
                Object.keys(rawGnbBadgeLabels).forEach((key) => {
                    const numId = Number(key);
                    const label = String(rawGnbBadgeLabels[key] || '');
                    if (!Number.isNaN(numId) && label) {
                        map[numId] = label;
                    }
                });
            }

            return map;
        }

        /** index: GNB 1차(헤더) 메뉴명 — 카테고리 트리를 올라가며 매칭 */
        function getIndexGnbRootLabelForPost(post) {
            if (!rawGnbBadgeLabels || !indexGnbRootIds.length || !post || !post.categories || !post.categories.length) {
                return '';
            }

            const rootIdSet = new Set(indexGnbRootIds);

            for (const cid of post.categories) {
                let currentId = Number(cid);
                const guard = new Set();

                while (currentId && !Number.isNaN(currentId) && !guard.has(currentId)) {
                    guard.add(currentId);

                    if (rootIdSet.has(currentId)) {
                        return gnbLabelByCatId[currentId] || '';
                    }

                    const mapped = gnbLabelByCatId[currentId];
                    if (mapped) {
                        return mapped;
                    }

                    const cat = allCategories.find(c => c.id === currentId);
                    if (!cat || !cat.parent) {
                        break;
                    }
                    currentId = Number(cat.parent);
                }
            }

            return '';
        }

        /** 리스트/추천 카드 뱃지용: GNB 메뉴명 우선, 없으면 카테고리명 */
        function getDisplayCategoryLabelForPost(post) {
            if (!post || !post.categories || !post.categories.length) return '';

            if (rawGnbBadgeLabels) {
                const indexRootLabel = getIndexGnbRootLabelForPost(post);
                if (indexRootLabel) {
                    return indexRootLabel;
                }
            }

            function findGnbLabelForCategoryId(cid) {
                if (rawGnbBadgeLabels) {
                    let currentId = Number(cid);
                    const guard = new Set();
                    while (currentId && !Number.isNaN(currentId) && !guard.has(currentId)) {
                        guard.add(currentId);
                        const gnbLabel = gnbLabelByCatId[currentId];
                        if (gnbLabel) {
                            return gnbLabel;
                        }
                        const cat = allCategories.find(c => c.id === currentId);
                        if (!cat || !cat.parent) {
                            break;
                        }
                        currentId = Number(cat.parent);
                    }
                    return '';
                }

                return gnbLabelByCatId[cid] || '';
            }

            for (const cid of post.categories) {
                const gnbLabel = findGnbLabelForCategoryId(cid);
                if (gnbLabel) {
                    return gnbLabel;
                }
            }

            if (rawGnbBadgeLabels) {
                return getRootLabelForPost(post);
            }

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

        // --- 추천 게시물 렌더링 (관리자 미지정 시 조회수 추천순 3개) --- //
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
            // PHP(recommended.php)가 이미 렌더한 경우 REST 전체 로드 결과를 덮어쓰지 않음
            if ($featured.length && $featured.attr('data-php-rendered') === '1' && $featured.children().length) {
                return;
            }
            const getViews = (p) => (p && (p.borobill_views != null ? p.borobill_views : (p.meta && p.meta._bb_views))) || 0;
            let recHTML = '';
            let picked = [...allPosts]
                .filter(p => p.status === 'publish')
                .sort((a, b) => (getViews(b) - getViews(a)) || (new Date(b.date) - new Date(a.date)))
                .slice(0, 3);

            for (let post of picked) {
                let thumb = getThumbUrl(post);
                let cat = escapeHtml(getDisplayCategoryLabelForPost(post));
                const subPlain = getPostSubtitlePlain(post);
                const subBlock = subPlain
                    ? `<p class="featured-subdesc"><a class="featured-subdesc__link" href="${escapeAttr(post.link)}">${escapeHtml(subPlain)}</a></p>`
                    : '';
                recHTML += `<article class="featured-card featured-card--feed">
<a href="${post.link}" class="featured-thumb-wrap">
    <img src="${thumb}" class="featured-thumb" alt="${post.title.rendered.replace(/<[^>]*>?/gm, '')}" loading="lazy" decoding="async" />
</a>
<div class="featured-meta article-meta">
    <span class="badge badge-small">${cat}</span>
    <span class="meta-date">${formatDate(post.date)}</span>
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
                    const badge = stripHtml(getRootLabelForPost(post) || '');
                    const tagNames = getEmbeddedTermNames(post, 'post_tag').join(' ');
                    const categoryNames = getEmbeddedTermNames(post, 'category').join(' ');

                    const hayNorm = normalizeForSearch(
                        [title, subtitle, badge, excerpt, tagNames, categoryNames].join(' ')
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

        function applyPostViewMode() {
            if (!$list.length || !$('.post-view-mode').length) {
                return;
            }

            $list.removeClass('post-list--view-photo post-list--view-list post-list--view-card');
            if (postViewMode === 'photo' || postViewMode === 'list' || postViewMode === 'card') {
                $list.addClass('post-list--view-' + postViewMode);
            }
        }

        function bindPostViewMode() {
            const $viewWrap = $('.post-view-mode');
            if (!$viewWrap.length) {
                return;
            }

            const $initialActive = $viewWrap.find('.post-view-mode__btn.is-active').first();
            if ($initialActive.length) {
                const initialMode = String($initialActive.data('view-mode') || '');
                if (initialMode === 'photo' || initialMode === 'list' || initialMode === 'card') {
                    postViewMode = initialMode;
                }
            }

            applyPostViewMode();

            $viewWrap.off('click.borobillViewMode').on('click.borobillViewMode', '.post-view-mode__btn', function () {
                const mode = String($(this).data('view-mode') || '');
                if (mode !== 'photo' && mode !== 'list' && mode !== 'card') {
                    return;
                }
                if (mode === postViewMode) {
                    return;
                }

                postViewMode = mode;
                $viewWrap.find('.post-view-mode__btn').removeClass('is-active').attr('aria-pressed', 'false');
                $(this).addClass('is-active').attr('aria-pressed', 'true');
                applyPostViewMode();
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
            // 모바일: 기본은 페이지네이션 숨김(무한 피드) / "전체"에서는 페이지네이션 허용
            if (isMobileFeed() && String(nowGroup) !== 'all') {
                $pagination.removeClass('pagination--hero').empty();
                return;
            }
            if (totalPages <= 1) {
                $pagination.removeClass('pagination--hero').empty();
                return;
            }

            // 페이지 번호는 최대 5개, 나머지는 줄임표
            function getPageItems(current, total) {
                if (total <= 5) {
                    const all = [];
                    for (let i = 1; i <= total; i++) all.push(i);
                    return all;
                }
                if (current <= 3) {
                    return [1, 2, 3, 4, 'ellipsis', total];
                }
                if (current >= total - 2) {
                    return [1, 'ellipsis', total - 3, total - 2, total - 1, total];
                }
                return [1, 'ellipsis', current - 1, current, current + 1, 'ellipsis', total];
            }

            $pagination.removeClass('pagination--hero');
            let html = '';
            html += `<button class="page-btn nav prev" type="button" data-action="prev" aria-label="이전 페이지" ${page <= 1 ? 'disabled' : ''}>
                <span class="page-icon page-icon--prev" aria-hidden="true"></span>
            </button>`;

            getPageItems(page, totalPages).forEach((item) => {
                if (item === 'ellipsis') {
                    html += `<span class="page-ellipsis" aria-hidden="true">…</span>`;
                    return;
                }
                const isActive = item === page;
                html += `<button class="page-btn${isActive ? ' active' : ''}" type="button" data-page="${item}" ${isActive ? 'aria-current="page"' : ''} aria-label="${item} 페이지">${item}</button>`;
            });

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
            return false;
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

        function refreshListPaginationOnly(options) {
            const silent = !!(options && options.silent);
            const filtered = getFilteredSortedPosts();
            const perPage = getListPerPage();
            const totalPages = Math.max(1, Math.ceil(filtered.length / perPage));
            const page = Math.min(Math.max(1, currentViewPage), totalPages);
            currentViewPage = page;
            renderPagination(totalPages, page);
            if (silent) {
                // scroll-reveal 재등장 애니메이션 방지
                $pagination.addClass('is-visible reveal');
                $pagination.find('.reveal').addClass('is-visible');
            }
            lastIsMobileFeed = isMobileFeed();
            if (isMobileInfiniteFeedMode()) {
                setupMobileFeedObserver(totalPages);
            } else {
                teardownMobileFeedObserver();
            }
        }

        function renderList() {
            const filtered = getFilteredSortedPosts();
            const total = filtered.length;
            const perPage = getListPerPage();
            const totalPages = Math.max(1, Math.ceil(total / perPage));
            const page = Math.min(Math.max(1, currentViewPage), totalPages);
            const sliced = filtered.slice((page - 1) * perPage, page * perPage);

            let listHTML = '';
            for (let post of sliced) {
                let thumb = getThumbUrl(post);
                const cat = escapeHtml(getDisplayCategoryLabelForPost(post));
                const listSubPlain = getPostSubtitlePlain(post);
                const plainTitle = post.title.rendered.replace(/<[^>]*>?/gm, '');
                const listSubBlock = listSubPlain
                    ? `<p class="article-subtitle">${escapeHtml(listSubPlain)}</p>`
                    : '';
                const listTagsBlock = buildIndexArticleTagsBlock(post, 3);
                listHTML += `<a href="${escapeAttr(post.link)}" class="article-card__link" aria-label="${escapeAttr(plainTitle)}">
<article class="article-card article-card--feed">
<div class="article-info">
    <div class="article-meta">
        <span class="badge badge-small">${cat}</span>
        <span class="meta-date">${formatDate(post.date)}</span>
    </div>
    <h3 class="article-title">${post.title.rendered}</h3>
    ${listSubBlock}
    ${listTagsBlock}
</div>
<div class="article-thumb-wrap">
    <img src="${thumb}" class="article-thumb" alt="${escapeAttr(plainTitle)}" loading="lazy" decoding="async" />
</div>
</article>
</a>`;
            }
            // 초기 스켈레톤 제거(레이아웃 안정화 후 실제 콘텐츠로 교체)
            $list.removeClass('post-list--skeleton').attr('aria-busy', 'false');
            $list.html(listHTML);
            applyPostViewMode();
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

        // --- 하단 배너 멀티슬라이드 캐러셀 --- //
        (function initBottomCtaCarousel() {
            const $section = $('[data-bottom-cta-carousel]');
            if (!$section.length) return;

            const $carousel = $section.find('.bottom-cta-carousel');
            const $slides   = $carousel.children('.bottom-cta-slide');
            if (!$carousel.length || $slides.length < 2) return;

            const settings = (window.borobillBottomBannerSettings && typeof window.borobillBottomBannerSettings === 'object')
                ? window.borobillBottomBannerSettings
                : {};
            const autoDelaySec  = Number(settings.autoDelay);
            const durationSec   = Number(settings.duration);
            const effect        = String(settings.effect || 'default');
            const autoDelayMs   = Number.isFinite(autoDelaySec) ? Math.max(0, autoDelaySec * 1000) : 6000;

            if (Number.isFinite(durationSec) && durationSec > 0) {
                $carousel.get(0).style.setProperty('--cta-duration', `${durationSec}s`);
            }

            // overflow clip 은 캐러셀, transform 은 내부 트랙 (같은 요소에 같이 쓰면 밀렸다가 되돌아감)
            let $track = $carousel.children('.bottom-cta-track');
            if (!$track.length) {
                $slides.wrapAll('<div class="bottom-cta-track"></div>');
                $track = $carousel.children('.bottom-cta-track');
            }
            const $trackSlides = $track.children('.bottom-cta-slide');
            const $paginationPrev = $section.find('[data-bottom-cta-pagination-prev]');
            const $paginationNext = $section.find('[data-bottom-cta-pagination-next]');

            // 효과 클래스
            $carousel.addClass('is-carousel');
            if (effect === 'smooth') {
                $carousel.addClass('effect-smooth');
            } else if (effect === 'bounce') {
                $carousel.addClass('effect-bounce');
            }

            let current = 0;
            const total = $trackSlides.length;

            function applySlide(nextIndex) {
                const idx = ((nextIndex % total) + total) % total;
                current = idx;
                $track.get(0).style.transform = `translateX(${-idx * 100}%)`;
                $trackSlides.removeClass('is-active').attr('aria-hidden', 'true');
                $trackSlides.eq(idx).addClass('is-active').attr('aria-hidden', 'false');
            }

            // 초기 동기화
            applySlide(current);

            let timer = null;
            function stopAuto() {
                if (timer) { window.clearInterval(timer); timer = null; }
            }
            function startAuto() {
                if (!autoDelayMs) return;
                stopAuto();
                timer = window.setInterval(() => applySlide(current + 1), autoDelayMs);
            }

            function go(delta) {
                applySlide(current + delta);
                startAuto();
            }

            $paginationPrev.off('click.bottomCtaPag').on('click.bottomCtaPag', function (e) {
                e.preventDefault();
                go(-1);
            });
            $paginationNext.off('click.bottomCtaPag').on('click.bottomCtaPag', function (e) {
                e.preventDefault();
                go(1);
            });

            $section.off('mouseenter.bottomCta focusin.bottomCta').on('mouseenter.bottomCta focusin.bottomCta', stopAuto);
            $section.off('mouseleave.bottomCta focusout.bottomCta').on('mouseleave.bottomCta focusout.bottomCta', startAuto);

            document.addEventListener('visibilitychange', function () {
                if (document.hidden) { stopAuto(); } else { startAuto(); }
            });

            startAuto();
        })();

    });
})(jQuery);
