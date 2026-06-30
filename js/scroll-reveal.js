/**
 * Scroll Reveal – IntersectionObserver 기반 fade+translate-up
 *
 * 대상: .reveal 클래스가 붙은 요소
 *   → 뷰포트에 진입하면 .is-visible 추가
 *
 * 동적으로 생성되는 콘텐츠(JS 렌더링 카드 등)도
 * MutationObserver로 자동 감지하여 reveal을 적용한다.
 */
(function () {
    'use strict';

    // prefers-reduced-motion 사용자면 즉시 표시
    var prefersReduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ── 대상 셀렉터 ── */
    // 섹션 단위
    var SECTION_SELECTORS = [
        '.hero-section',
        '.section-featured',
        '.section-filter',
        '.section-list--category',
        '.section-bottom-cta',
        // 페이지네이션도 스크롤 시 자연스럽게 등장
        '.pagination',
        '.site-footer',
    ];

    // 개별 카드/아이템 단위 (부모에 reveal-stagger가 붙으면 자식으로 동작)
    var CARD_SELECTORS = [
        '.featured-card',
        '.article-card',
    ];

    // 스태거 부모 (자식들에 순차 딜레이)
    var STAGGER_PARENTS = [
        '.featured-grid',
        '#post-list',
    ];

    /* ── IntersectionObserver ── */
    var observer;
    // 2026 트렌드: 더 빠르고 미세한 진입 트리거
    var THRESHOLD = 0.12;               // 12% 보이면 트리거
    var ROOT_MARGIN = '0px 0px -10% 0px'; // 하단을 10% 줄여, 화면 하단에 붙기 전에 자연스럽게 트리거

    function initObserver() {
        if (typeof IntersectionObserver === 'undefined') {
            // fallback: 모두 즉시 보이기
            showAll();
            return;
        }

        observer = new IntersectionObserver(onIntersect, {
            root: null,
            rootMargin: ROOT_MARGIN,
            threshold: THRESHOLD,
        });

        applyRevealClasses();
        observeAll();
    }

    function onIntersect(entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                // 같은 프레임에 reveal/is-visible이 같이 적용되면 transition이 안 보일 수 있어 1프레임 뒤 적용
                requestAnimationFrame(function () {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target); // 한 번만
                });
            }
        });
    }

    /* ── reveal 클래스 자동 부여 ── */
    function applyRevealClasses() {
        // 섹션
        SECTION_SELECTORS.forEach(function (sel) {
            document.querySelectorAll(sel).forEach(function (el) {
                if (!el.classList.contains('reveal')) {
                    el.classList.add('reveal');
                }
            });
        });

        // 스태거 부모에 클래스 부여 + 자식 카드에 reveal
        STAGGER_PARENTS.forEach(function (sel) {
            document.querySelectorAll(sel).forEach(function (parent) {
                if (!parent.classList.contains('reveal-stagger')) {
                    parent.classList.add('reveal-stagger');
                }
                CARD_SELECTORS.forEach(function (cardSel) {
                    parent.querySelectorAll(cardSel).forEach(function (card) {
                        if (!card.classList.contains('reveal')) {
                            card.classList.add('reveal');
                        }
                    });
                });
            });
        });
    }

    function observeAll() {
        document.querySelectorAll('.reveal:not(.is-visible)').forEach(function (el) {
            observer.observe(el);
        });
    }

    function showAll() {
        document.querySelectorAll('.reveal').forEach(function (el) {
            el.classList.add('is-visible');
        });
    }

    /* ── MutationObserver: JS 동적 렌더링 대응 ── */
    function watchDynamicContent() {
        if (typeof MutationObserver === 'undefined') return;

        var mo = new MutationObserver(function (mutations) {
            var hasNew = false;
            mutations.forEach(function (m) {
                if (m.addedNodes && m.addedNodes.length) hasNew = true;
            });
            if (hasNew) {
                applyRevealClasses();
                observeAll();
            }
        });

        // 관찰 대상: 메인 콘텐츠 영역
        var targets = document.querySelectorAll('.layout, .layout--single, .layout--category, body');
        targets.forEach(function (t) {
            mo.observe(t, { childList: true, subtree: true });
        });
    }

    /* ── Boot ── (지연 실행으로 첫 페인트/스크롤 방해 최소화) */
    function boot() {
        if (prefersReduced) {
            applyRevealClasses();
            showAll();
            return;
        }
        initObserver();
        watchDynamicContent();
    }

    function scheduleBoot() {
        if (typeof requestIdleCallback !== 'undefined') {
            requestIdleCallback(function () { boot(); }, { timeout: 120 });
        } else {
            setTimeout(boot, 0);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', scheduleBoot);
    } else {
        scheduleBoot();
    }
})();
