/**
 * SearchModal – bolta.io/insight-style 실시간 검색 모달
 *
 * 재사용 가능한 독립 모듈. 외부 의존성: jQuery(선택), WordPress REST API.
 * 트리거: [data-open-search] 속성이 있는 아무 버튼/링크 클릭, 또는
 *         SearchModal.open() 직접 호출.
 */
(function () {
    'use strict';

    /* ------------------------------------------------------------------ */
    /* Config                                                              */
    /* ------------------------------------------------------------------ */
    const API_BASE =
        (typeof borobillSearchModal !== 'undefined' && borobillSearchModal.apiBase)
            ? borobillSearchModal.apiBase
            : '/wordpress/wp-json/wp/v2/';
    const PER_PAGE = 10;
    const DEBOUNCE_MS = 300;

    /* ------------------------------------------------------------------ */
    /* DOM refs                                                            */
    /* ------------------------------------------------------------------ */
    let $modal, $backdrop, $container, $input, $clearBtn, $cancelBtn,
        $body, $stateInitial, $stateLoading, $stateNoResults, $stateResults;
    let $tagBadges, $popularTags;
    let triggerEl = null;       // 모달을 연 트리거 요소 (포커스 복귀용)
    let debounceTimer = null;
    let abortCtrl = null;       // fetch abort controller
    let lastQuery = '';
    let isOpen = false;
    let cachedSearchPosts = null;          // 최신순 게시글 캐시
    let cachedSearchPostsPromise = null;   // 중복 fetch 방지
    let selectedTags = [];                  // 자주 찾는 검색어 태그 (최대 3개)
    const MAX_POPULAR_TAGS = 3;

    /* ------------------------------------------------------------------ */
    /* Init (DOMContentLoaded)                                             */
    /* ------------------------------------------------------------------ */
    document.addEventListener('DOMContentLoaded', init);

    function init() {
        $modal         = document.getElementById('search-modal');
        if (!$modal) return;

        $backdrop      = $modal.querySelector('.search-modal__backdrop');
        $container     = $modal.querySelector('.search-modal__container');
        $input         = document.getElementById('search-modal-input');
        $clearBtn      = $modal.querySelector('.search-modal__clear');
        $cancelBtn     = $modal.querySelector('.search-modal__cancel');
        $body          = $modal.querySelector('.search-modal__body');
        $stateInitial  = $modal.querySelector('[data-state="initial"]');
        $stateLoading  = $modal.querySelector('[data-state="loading"]');
        $stateNoResults= $modal.querySelector('[data-state="no-results"]');
        $stateResults  = $modal.querySelector('[data-state="results"]');
        $tagBadges     = document.getElementById('search-modal-tag-badges');
        $popularTags   = document.getElementById('search-modal-popular-tags');
        $modalScrollBody = $modal.querySelector('.search-modal__body');

        // 자주 찾는 검색어: 태그 클릭 (최대 3개, 중복 선택 가능)
        if ($popularTags) {
            $popularTags.addEventListener('click', function (e) {
                var btn = e.target.closest('.search-modal__tag');
                if (!btn) return;
                e.preventDefault();
                var keyword = (btn.getAttribute('data-keyword') || '').trim();
                if (!keyword) return;
                var idx = selectedTags.indexOf(keyword);
                if (idx !== -1) {
                    selectedTags.splice(idx, 1);
                } else {
                    if (selectedTags.length >= MAX_POPULAR_TAGS) {
                        updateSearchPlaceholder(true);
                        return;
                    }
                    selectedTags.push(keyword);
                }
                updateSearchPlaceholder(false);
                updatePopularTagStates();
                renderTagBadges();
                refreshSearch();
            });
        }

        // 뱃지 X 클릭으로 태그 제거 (이벤트 위임)
        if ($tagBadges) {
            $tagBadges.addEventListener('click', function (e) {
                var removeBtn = e.target.closest('.search-modal__badge-remove');
                if (!removeBtn) return;
                var keyword = (removeBtn.getAttribute('data-keyword') || '').trim();
                var idx = selectedTags.indexOf(keyword);
                if (idx !== -1) {
                    selectedTags.splice(idx, 1);
                    updateSearchPlaceholder(false);
                    updatePopularTagStates();
                    renderTagBadges();
                    refreshSearch();
                }
            });
        }

        // 트리거 버튼 연결: [data-open-search] 속성이 있는 모든 요소
        document.addEventListener('click', function (e) {
            const trigger = e.target.closest('[data-open-search]');
            if (trigger) {
                e.preventDefault();
                e.stopPropagation();
                open(trigger);
            }
        });

        // 닫기: 배경 클릭
        $backdrop.addEventListener('click', close);

        // 닫기: 취소 버튼
        $cancelBtn.addEventListener('click', close);

        // 닫기: ESC
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && isOpen) {
                close();
            }
        });

        // 입력 이벤트 → 실시간 검색
        $input.addEventListener('input', onInput);

        // 클리어 버튼
        $clearBtn.addEventListener('click', function () {
            $input.value = '';
            $clearBtn.hidden = true;
            lastQuery = '';
            refreshSearch();
            $input.focus();
        });

        // 키보드 탐색 (ArrowDown/ArrowUp)
        $input.addEventListener('keydown', onInputKeydown);
    }

    /* ------------------------------------------------------------------ */
    /* Open / Close                                                        */
    /* ------------------------------------------------------------------ */
    var savedScrollY = 0;
    var isScrollLocked = false;
    var $modalScrollBody = null;

    function restoreWindowScroll() {
        if (!isScrollLocked) return;
        if ((window.scrollY || 0) !== savedScrollY) {
            window.scrollTo(0, savedScrollY);
        }
    }

    function isModalScrollableTarget(target) {
        if (!$modalScrollBody || !target || typeof target.closest !== 'function') {
            return false;
        }
        return $modalScrollBody.contains(target);
    }

    function onSearchModalWheel(e) {
        if (!isScrollLocked) return;
        if (isModalScrollableTarget(e.target)) return;
        e.preventDefault();
    }

    function onSearchModalTouchMove(e) {
        if (!isScrollLocked) return;
        if (isModalScrollableTarget(e.target)) return;
        e.preventDefault();
    }

    function lockPageScroll() {
        savedScrollY = window.scrollY || window.pageYOffset || 0;
        isScrollLocked = true;
        restoreWindowScroll();
        window.addEventListener('scroll', restoreWindowScroll, { passive: true });
        document.addEventListener('wheel', onSearchModalWheel, { passive: false });
        document.addEventListener('touchmove', onSearchModalTouchMove, { passive: false });
    }

    function unlockPageScroll() {
        if (!isScrollLocked) return;
        isScrollLocked = false;
        window.removeEventListener('scroll', restoreWindowScroll);
        document.removeEventListener('wheel', onSearchModalWheel);
        document.removeEventListener('touchmove', onSearchModalTouchMove);
    }

    function open(trigger) {
        if (isOpen) return;
        isOpen = true;
        triggerEl = trigger || document.activeElement;

        $modal.hidden = false;
        // 스크롤 잠금: body position:fixed 는 헤더·본문 간격이 튀므로, 이동 없이 휠·터치만 차단
        document.documentElement.classList.add('search-modal-open');
        document.body.classList.add('search-modal-open');
        lockPageScroll();

        // 강제 리플로우 → 트랜지션 적용
        void $modal.offsetHeight;
        $modal.classList.add('is-open');

        // 자주 찾는 검색어 초기화
        selectedTags = [];
        updateSearchPlaceholder(false);
        updatePopularTagStates();
        renderTagBadges();

        // 포커스
        setTimeout(function () {
            $input.focus();
        }, 60);

        // 기존 입력이 있으면 다시 검색
        if ($input.value.trim()) {
            onInput();
        }
    }

    function close() {
        if (!isOpen) return;
        isOpen = false;

        $modal.classList.remove('is-open');

        // 트랜지션 완료 후 hidden
        setTimeout(function () {
            $modal.hidden = true;
            document.documentElement.classList.remove('search-modal-open');
            document.body.classList.remove('search-modal-open');
            unlockPageScroll();

            // 포커스 복귀
            if (triggerEl && typeof triggerEl.focus === 'function') {
                triggerEl.focus();
            }
            triggerEl = null;
        }, 260);

        // 진행 중인 fetch 취소
        if (abortCtrl) {
            abortCtrl.abort();
            abortCtrl = null;
        }
    }

    /* ------------------------------------------------------------------ */
    /* State management                                                    */
    /* ------------------------------------------------------------------ */
    function showState(state) {
        [$stateInitial, $stateLoading, $stateNoResults, $stateResults].forEach(function (el) {
            if (el) el.hidden = true;
        });
        var target = $modal.querySelector('[data-state="' + state + '"]');
        if (target) target.hidden = false;
    }

    function updateSearchPlaceholder(limitReached) {
        if (!$input) return;
        $input.placeholder = (limitReached || selectedTags.length > 0)
            ? '3개까지 선택 가능합니다.'
            : '검색어를 입력해주세요';
    }

    function updatePopularTagStates() {
        if (!$popularTags) return;
        var btns = $popularTags.querySelectorAll('.search-modal__tag');
        btns.forEach(function (btn) {
            var kw = (btn.getAttribute('data-keyword') || '').trim();
            btn.classList.toggle('is-selected', selectedTags.indexOf(kw) !== -1);
        });
    }

    function renderTagBadges() {
        if (!$tagBadges) return;
        var html = '';
        selectedTags.forEach(function (keyword) {
            html += '<span class="search-modal__badge">';
            html += '<span class="search-modal__badge-text">' + escapeHTML(keyword) + '</span>';
            html += '<button type="button" class="search-modal__badge-remove" data-keyword="' + escapeAttr(keyword) + '" aria-label="태그 제거">×</button>';
            html += '</span>';
        });
        $tagBadges.innerHTML = html;
    }

    /** 검색어 또는 태그가 있으면 실시간 검색, 없으면 초기 상태 */
    function refreshSearch() {
        var query = ($input.value || '').trim();
        if (!query && !selectedTags.length) {
            lastQuery = '';
            if (abortCtrl) { abortCtrl.abort(); abortCtrl = null; }
            showState('initial');
            return;
        }
        lastQuery = query;
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function () {
            doSearch(query, selectedTags);
        }, query ? DEBOUNCE_MS : 0);
    }

    /* ------------------------------------------------------------------ */
    /* Input / Search                                                      */
    /* ------------------------------------------------------------------ */
    function onInput() {
        var query = ($input.value || '').trim();
        $clearBtn.hidden = !query;
        refreshSearch();
    }

    /** 검색어 또는 태그(1개라도 포함) 기준 실시간 검색 */
    async function doSearch(query, tags) {
        if (abortCtrl) abortCtrl.abort();
        abortCtrl = new AbortController();
        showState('loading');

        try {
            var posts = await ensureSearchPosts(abortCtrl.signal);
            var qNorm = query ? normalizeForSearch(query) : '';
            var tagNorms = (tags && tags.length) ? tags.map(function (t) { return normalizeForSearch(t); }) : [];

            var matched = posts.filter(function (post) {
                var hay = String(post && post.__bbSearchHayNorm || '');
                var matchQuery = qNorm ? hay.indexOf(qNorm) !== -1 : false;
                var matchTag = tagNorms.length ? tagNorms.some(function (n) { return n && hay.indexOf(n) !== -1; }) : false;
                return matchQuery || matchTag;
            });

            matched.sort(function (a, b) {
                return (b.__bbSortTs || 0) - (a.__bbSortTs || 0);
            });

            if (!matched.length) {
                showState('no-results');
                return;
            }
            renderResults(matched.slice(0, PER_PAGE), query || '');
            showState('results');
        } catch (err) {
            if (err.name === 'AbortError') return;
            console.error('[SearchModal]', err);
            showState('no-results');
        }
    }

    async function ensureSearchPosts(signal) {
        if (cachedSearchPosts) return cachedSearchPosts;
        if (cachedSearchPostsPromise) return cachedSearchPostsPromise;

        cachedSearchPostsPromise = (async function () {
            try {
                var collected = [];
                var page = 1;
                var maxPages = 3; // 최신 글 기준으로 충분한 범위 (필요 시 증가)
                var fields = 'id,date,link,title,excerpt,borobill_subtitle,_links';
                while (page <= maxPages) {
                    var url = API_BASE + 'posts?per_page=100&page=' + page
                        + '&_embed=wp:featuredmedia,wp:term&_fields=' + fields
                        + '&orderby=date&order=desc';
                    var res = await fetch(url, { signal: signal });
                    if (!res.ok) break;
                    var data = await res.json();
                    if (Array.isArray(data) && data.length) {
                        collected = collected.concat(data);
                    }
                    if (!Array.isArray(data) || data.length < 100) break;
                    page += 1;
                }

                // 검색용 정규화 문자열을 미리 계산 (제목/서브타이틀/뱃지(카테고리)/요약/태그 — 본문 HTML 제외)
                collected.forEach(function (post) {
                    var title = post && post.title ? stripHTML(post.title.rendered || '') : '';
                    var subtitle = (post && (post.borobill_subtitle || (post.meta && post.meta._borobill_subtitle))) || '';
                    var excerpt = post && post.excerpt ? stripHTML(post.excerpt.rendered || '') : '';
                    var tagNames = getEmbeddedTermNames(post, 'post_tag').join(' ');
                    var catNames = getEmbeddedTermNames(post, 'category').join(' ');
                    post.__bbSearchHayNorm = normalizeForSearch([title, subtitle, excerpt, tagNames, catNames].join(' '));
                    post.__bbSortTs = post && post.date ? (new Date(post.date)).getTime() : 0;
                });

                cachedSearchPosts = collected;
                cachedSearchPostsPromise = null;
                return cachedSearchPosts;
            } catch (e) {
                cachedSearchPostsPromise = null;
                throw e;
            }
        })();

        return cachedSearchPostsPromise;
    }

    /* ------------------------------------------------------------------ */
    /* Render results                                                       */
    /* ------------------------------------------------------------------ */
    function renderResults(posts, query) {
        var html = '';
        posts.forEach(function (post, idx) {
            var title    = post.title ? post.title.rendered : '';
            var excerpt  = post.excerpt ? post.excerpt.rendered : '';
            var link     = post.link || '#';

            // 썸네일
            var thumb = '';
            if (post._embedded && post._embedded['wp:featuredmedia'] &&
                post._embedded['wp:featuredmedia'][0]) {
                thumb = post._embedded['wp:featuredmedia'][0].source_url || '';
            }

            // 카테고리
            var catName = '';
            if (post._embedded && post._embedded['wp:term'] &&
                post._embedded['wp:term'][0] && post._embedded['wp:term'][0][0]) {
                catName = post._embedded['wp:term'][0][0].name || '';
            }

            // 날짜
            var dateStr = formatDate(post.date);

            // 검색어 하이라이트
            var titleHL = highlightText(stripHTML(title), query);
            var snippet  = makeSnippet(stripHTML(excerpt), query, 80);
            var snippetHL = highlightText(snippet, query);

            html += '<li class="search-modal__item" role="option" data-index="' + idx + '">';
            html += '<a href="' + escapeAttr(link) + '" class="search-modal__link">';

            if (thumb) {
                html += '<div class="search-modal__thumb"><img src="' + escapeAttr(thumb) + '" alt="" loading="lazy" /></div>';
            }

            html += '<div class="search-modal__text">';
            if (catName) {
                html += '<span class="search-modal__cat">' + escapeHTML(catName) + '</span>';
            }
            html += '<strong class="search-modal__title">' + titleHL + '</strong>';
            if (snippetHL) {
                html += '<p class="search-modal__snippet">' + snippetHL + '</p>';
            }
            html += '<span class="search-modal__date">' + escapeHTML(dateStr) + '</span>';
            html += '</div></a></li>';
        });

        $stateResults.innerHTML = html;
    }

    /* ------------------------------------------------------------------ */
    /* Keyboard navigation in results                                      */
    /* ------------------------------------------------------------------ */
    function onInputKeydown(e) {
        if (!isOpen) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            focusResultItem(0);
        }
    }

    // 결과 목록 내 키보드 이동 (이벤트 위임)
    document.addEventListener('keydown', function (e) {
        if (!isOpen) return;
        var item = e.target.closest('.search-modal__item');
        if (!item) return;

        var items = $stateResults ? Array.from($stateResults.querySelectorAll('.search-modal__item')) : [];
        var idx = items.indexOf(item);
        if (idx < 0) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (idx + 1 < items.length) focusResultItem(idx + 1);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (idx === 0) {
                $input.focus();
            } else {
                focusResultItem(idx - 1);
            }
        } else if (e.key === 'Enter') {
            var link = item.querySelector('a');
            if (link) link.click();
        }
    });

    function focusResultItem(idx) {
        if (!$stateResults) return;
        var items = $stateResults.querySelectorAll('.search-modal__item');
        if (items[idx]) {
            items[idx].setAttribute('tabindex', '-1');
            items[idx].focus();
        }
    }

    /* ------------------------------------------------------------------ */
    /* Utility helpers                                                      */
    /* ------------------------------------------------------------------ */
    function stripHTML(html) {
        var tmp = document.createElement('div');
        tmp.innerHTML = html;
        return (tmp.textContent || tmp.innerText || '').trim();
    }

    function normalizeForSearch(input) {
        return String(input || '')
            .toLowerCase()
            .replace(/<[^>]*>?/gm, ' ')
            .replace(/[\s\u00A0]+/g, '')
            .replace(/[·•\u00B7\.\,\!\?\:\;\(\)\[\]\{\}"'`~@#$%^&*\-_=+\\\/|<>]/g, '');
    }

    function getEmbeddedTermNames(post, taxonomy) {
        var groups = post && post._embedded && post._embedded['wp:term'] ? post._embedded['wp:term'] : null;
        if (!Array.isArray(groups)) return [];
        var out = [];
        groups.forEach(function (group) {
            if (!Array.isArray(group)) return;
            group.forEach(function (term) {
                if (!term || !term.name) return;
                if (taxonomy && term.taxonomy !== taxonomy) return;
                out.push(String(term.name));
            });
        });
        return out;
    }

    function highlightText(text, query) {
        if (!query) return escapeHTML(text);
        // 한글 자모 분리를 고려한 정규식
        var escaped = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        var re = new RegExp('(' + escaped + ')', 'gi');
        return escapeHTML(text).replace(re, '<mark class="search-modal__mark">$1</mark>');
    }

    function makeSnippet(text, query, radius) {
        if (!text) return '';
        var lower = text.toLowerCase();
        var qLower = (query || '').toLowerCase();
        var idx = lower.indexOf(qLower);
        if (idx < 0) return text.slice(0, radius * 2);
        var start = Math.max(0, idx - radius);
        var end = Math.min(text.length, idx + query.length + radius);
        var snippet = '';
        if (start > 0) snippet += '...';
        snippet += text.slice(start, end);
        if (end < text.length) snippet += '...';
        return snippet;
    }

    function escapeHTML(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function escapeAttr(str) {
        return String(str).replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function formatDate(dtstr) {
        if (!dtstr) return '';
        var d = new Date(dtstr);
        if (isNaN(d.getTime())) return '';
        return d.getFullYear() + '.' +
               String(d.getMonth() + 1).padStart(2, '0') + '.' +
               String(d.getDate()).padStart(2, '0');
    }

    /* ------------------------------------------------------------------ */
    /* Public API (window.SearchModal)                                     */
    /* ------------------------------------------------------------------ */
    window.SearchModal = {
        open: open,
        close: close,
    };
})();
