<?php
/**
 * 세무 사전 — 인덱스형 레이아웃
 * 사용: category.php (borobill_get_category_layout() === 'glossary')
 * 최종 정리: 2026-09-14
 */

$g_term = get_queried_object();
if ( ! $g_term instanceof WP_Term ) {
    return;
}

$g_posts = get_posts( array(
    'post_type'   => 'post',
    'post_status' => 'publish',
    'numberposts' => -1,
    'cat'         => (int) $g_term->term_id,
    'orderby'     => 'title',
    'order'       => 'ASC',
) );

// 초성별로 묶기
$g_groups = array();
foreach ( $g_posts as $g_p ) {
    $g_key = borobill_hangul_initial( $g_p->post_title );
    if ( ! isset( $g_groups[ $g_key ] ) ) {
        $g_groups[ $g_key ] = array();
    }
    $g_groups[ $g_key ][] = $g_p;
}

$g_top    = borobill_get_glossary_top_ids( (int) $g_term->term_id, 5, 3 );
$g_guides = borobill_get_guide_posts_for_glossary( 300 );
?>
<section class="section section-list section-glossary">
    <div class="section-inner">
        <div class="glossary" id="glossary">

            <div class="glossary-search">
                <svg class="glossary-search__icon" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/>
                    <path d="M20 20l-3.5-3.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
                <input type="search" id="glossary-q" class="glossary-search__input"
                       placeholder="찾고 싶은 세무 용어를 입력하세요" aria-label="세무 용어 검색">
            </div>

            <div class="glossary-jamo" role="group" aria-label="자음 필터">
                <?php foreach ( borobill_glossary_jamo_order() as $g_j ) : ?>
                    <?php
                    $g_has = ! empty( $g_groups[ $g_j ] );
                    // A-Z · # 는 해당 용어가 있을 때만 보여준다
                    if ( in_array( $g_j, array( 'A-Z', '#' ), true ) && ! $g_has ) {
                        continue;
                    }
                    ?>
                    <button type="button"
                            class="glossary-jamo__btn<?php echo $g_has ? '' : ' is-empty'; ?>"
                            data-jamo="<?php echo esc_attr( $g_j ); ?>"
                            aria-pressed="false"
                            <?php disabled( ! $g_has ); ?>><?php echo esc_html( $g_j ); ?></button>
                <?php endforeach; ?>
            </div>

            <div class="glossary-body">

                <div class="glossary-main">
                    <div class="glossary-list" id="glossary-list">
                        <?php if ( empty( $g_posts ) ) : ?>
                            <p class="glossary-empty">등록된 용어가 없습니다.</p>
                        <?php else : ?>
                            <?php foreach ( borobill_glossary_jamo_order() as $g_j ) : ?>
                                <?php if ( empty( $g_groups[ $g_j ] ) ) : continue; endif; ?>
                                <section class="glossary-group" data-jamo="<?php echo esc_attr( $g_j ); ?>">
                                    <h2 class="glossary-group__head"><?php echo esc_html( $g_j ); ?></h2>
                                    <?php foreach ( $g_groups[ $g_j ] as $g_p ) : ?>
                                        <?php
                                        $g_desc = trim( $g_p->post_excerpt );
                                        $g_body = trim( $g_p->post_content );
                                        if ( '' === $g_desc ) {
                                            $g_desc = wp_trim_words( wp_strip_all_tags( $g_p->post_content ), 40, '…' );
                                        }
                                        ?>
                                        <article class="glossary-item"
                                                 id="term-<?php echo (int) $g_p->ID; ?>"
                                                 data-jamo="<?php echo esc_attr( $g_j ); ?>">
                                            <h3 class="glossary-item__title">
                                                <button type="button" class="glossary-item__head" aria-expanded="false">
                                                    <span class="glossary-item__term"><?php echo esc_html( $g_p->post_title ); ?></span>
                                                    <span class="glossary-item__desc"><?php echo esc_html( $g_desc ); ?></span>
                                                    <span class="glossary-item__chev" aria-hidden="true"></span>
                                                </button>
                                            </h3>
                                            <?php if ( '' !== $g_body ) : ?>
                                                <div class="glossary-item__body" hidden>
                                                    <?php echo wp_kses_post( borobill_glossary_render_body( $g_body ) ); ?>
                                                </div>
                                            <?php endif; ?>
                                        </article>
                                    <?php endforeach; ?>
                                </section>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <p class="glossary-empty" id="glossary-empty" hidden>찾는 용어가 없습니다.</p>

                    <div class="pagination glossary-pagination" id="glossary-pagination"></div>
                </div>

                <aside class="glossary-aside">

                    <?php if ( ! empty( $g_top ) ) : ?>
                        <div class="glossary-panel">
                            <h3 class="glossary-panel__title">많이 찾는 용어</h3>
                            <ol class="glossary-top">
                                <?php foreach ( $g_top as $g_i => $g_tid ) : ?>
                                    <li>
                                        <span class="glossary-top__rank"><?php echo (int) ( $g_i + 1 ); ?></span>
                                        <a class="glossary-top__link" href="#term-<?php echo (int) $g_tid; ?>"
                                           data-target="term-<?php echo (int) $g_tid; ?>"><?php echo esc_html( get_the_title( $g_tid ) ); ?></a>
                                    </li>
                                <?php endforeach; ?>
                            </ol>
                        </div>
                    <?php endif; ?>

                    <?php if ( ! empty( $g_guides ) ) : ?>
                        <div class="glossary-panel glossary-panel--related" id="glossary-related-panel">
                            <h3 class="glossary-panel__title" id="glossary-related-title">추천 글</h3>
                            <ul class="glossary-related" id="glossary-related">
                                <?php
                                // 크롤러가 읽을 수 있도록 서버에서 먼저 3건을 그린다. 이후 JS가 상황에 맞게 교체한다.
                                $g_seed = array_slice( $g_guides, 0, 3 );
                                foreach ( $g_seed as $g_g ) :
                                    ?>
                                    <li>
                                        <a class="glossary-related__link" href="<?php echo esc_url( $g_g['u'] ); ?>">
                                            <span class="glossary-related__thumb"><img src="<?php echo esc_url( $g_g['i'] ); ?>" alt="" loading="lazy"></span>
                                            <span class="glossary-related__title"><?php echo esc_html( $g_g['t'] ); ?></span>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <script type="application/json" id="glossary-guides"><?php echo wp_json_encode( $g_guides ); ?></script>
                    <?php endif; ?>

                </aside>

            </div>
        </div>
    </div>
</section>

<script>
(function () {
    var root = document.getElementById('glossary');
    if (!root) { return; }

    var PER_PAGE = 12;
    var SNIPPET_AFTER  = 24;
    var SNIPPET_BEFORE = 12;
    var RELATED_COUNT  = 3;

    var q        = document.getElementById('glossary-q');
    var listEl   = document.getElementById('glossary-list');
    var empty    = document.getElementById('glossary-empty');
    var pager    = document.getElementById('glossary-pagination');
    var related  = document.getElementById('glossary-related');
    var relTitle = document.getElementById('glossary-related-title');
    var items    = Array.prototype.slice.call(root.querySelectorAll('.glossary-item'));
    var groups   = Array.prototype.slice.call(root.querySelectorAll('.glossary-group'));
    var tabs     = Array.prototype.slice.call(root.querySelectorAll('.glossary-jamo__btn'));

    var guides   = [];
    var guideBox = document.getElementById('glossary-guides');
    if (guideBox) {
        try { guides = JSON.parse(guideBox.textContent) || []; } catch (e) { guides = []; }
    }
    var popular = guides.slice().sort(function (a, b) { return (b.v || 0) - (a.v || 0); });

    items.forEach(function (el) {
        el._term = el.querySelector('.glossary-item__term').textContent;
        el._desc = el.querySelector('.glossary-item__desc').textContent;
    });

    var firstTab = tabs.filter(function (b) { return !b.disabled; })[0];
    var DEFAULT_JAMO = firstTab ? firstTab.getAttribute('data-jamo') : 'all';

    var jamo           = DEFAULT_JAMO;
    var page           = 1;
    var hlTimer        = null;
    var currentQuery   = '';
    var currentKeyword = '';

    function syncPerPage() {
        var wide = !window.matchMedia || window.matchMedia('(min-width: 768px)').matches;
        var next = wide ? 12 : 6;
        if (next !== PER_PAGE) { PER_PAGE = next; return true; }
        return false;
    }

    function escapeHtml(s) {
        return String(s).replace(/[&<>"]/g, function (c) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' })[c];
        });
    }

    /* ── 사이드바: 추천 글 / 관련 글 ──── */

    function findGuides(keyword) {
        var k = String(keyword || '').trim().toLowerCase();
        if (!k) { return null; }

        var hit = guides.filter(function (g) {
            return (g.t + ' ' + g.d).toLowerCase().indexOf(k) > -1;
        }).sort(function (a, b) { return (b.v || 0) - (a.v || 0); });

        return hit.length ? hit.slice(0, RELATED_COUNT) : null;
    }

    function renderRelated() {
        if (!related || !relTitle) { return; }

        var open    = root.querySelector('.glossary-item.is-open');
        var keyword = '';
        var hit     = null;

        // 1순위: 검색 중이면 검색어 기준을 그대로 유지 (용어를 펼쳐도 안 바뀜)
        if (currentKeyword) {
            keyword = currentKeyword;
            hit     = findGuides(keyword);
        }

        // 2순위: 검색 중이 아니거나, 검색어로 찾은 글이 없으면 펼친 용어 기준
        if (!hit && open) {
            keyword = open._term;
            hit     = findGuides(keyword);
        }

        var list = hit || popular.slice(0, RELATED_COUNT);

        relTitle.textContent = hit ? (keyword + ' 관련 글') : '추천 글';

        related.innerHTML = list.map(function (g) {
            return '<li><a class="glossary-related__link" href="' + escapeHtml(g.u) + '">'
                 + '<span class="glossary-related__thumb"><img src="' + escapeHtml(g.i || '') + '" alt="" loading="lazy"></span>'
                 + '<span class="glossary-related__title">' + escapeHtml(g.t) + '</span>'
                 + '</a></li>';
        }).join('');
    }

    /* ── 검색 ──────────────────────────── */

    var WORD_CHAR = /[0-9A-Za-z\uac00-\ud7a3]/;

    function wordStartIndex(text, query) {
        var lower = String(text).toLowerCase();
        var lq    = String(query).toLowerCase();
        var i     = 0;
        if (!lq) { return -1; }
        while (true) {
            var p = lower.indexOf(lq, i);
            if (p === -1) { return -1; }
            if (p === 0 || !WORD_CHAR.test(text.charAt(p - 1))) { return p; }
            i = p + 1;
        }
    }

    function highlight(text, query) {
        if (!query) { return escapeHtml(text); }
        var p = String(text).toLowerCase().indexOf(String(query).toLowerCase());
        if (p === -1) { return escapeHtml(text); }
        return escapeHtml(text.slice(0, p))
             + '<mark class="glossary-hl">' + escapeHtml(text.slice(p, p + query.length)) + '</mark>'
             + escapeHtml(text.slice(p + query.length));
    }

    function snippetOf(el) {
        var text = el._desc;
        if (currentQuery) {
            var p = String(text).toLowerCase().indexOf(currentQuery.toLowerCase());
            if (p > SNIPPET_AFTER) {
                el._snipped = true;
                return '… ' + text.slice(Math.max(0, p - SNIPPET_BEFORE));
            }
        }
        el._snipped = false;
        return text;
    }

    function setDescHtml(el, open) {
        el.querySelector('.glossary-item__desc').innerHTML = highlight(open ? el._desc : snippetOf(el), currentQuery);
    }
    function setBody(el, open) {
        var body = el.querySelector('.glossary-item__body');
        if (body) { body.hidden = !open; }
    }

    /* 펼친 항목이 있으면 목록 전체에 표시 → 나머지 카드를 흐리게 */
    function syncFocus() {
        var open = root.querySelector('.glossary-item.is-open');
        root.classList.toggle('is-focusing', !!open);
        reserveDrawerSpace(open);
    }

    /* 펼친 내용은 떠 있어서 자리를 차지하지 않는다.
       마지막 줄에서 열면 아래 CTA를 덮으므로, 삐져나온 만큼 목록 아래에 자리를 만든다. */
    function reserveDrawerSpace(el) {
        if (!listEl) { return; }
        listEl.style.paddingBottom = '';

        if (!el || window.innerWidth < 768) { return; }

        var body = el.querySelector('.glossary-item__body');
        if (!body || body.hidden) { return; }

        var over = body.getBoundingClientRect().bottom - listEl.getBoundingClientRect().bottom;
        if (over > 0) {
            listEl.style.paddingBottom = Math.ceil(over + 20) + 'px';
        }
    }

    function closeAll(except) {
        Array.prototype.forEach.call(root.querySelectorAll('.glossary-item.is-open'), function (el) {
            if (el === except) { return; }
            el.classList.remove('is-open');
            setDescHtml(el, false);
            setBody(el, false);
            var h = el.querySelector('.glossary-item__head');
            if (h) { h.setAttribute('aria-expanded', 'false'); }
        });
        syncFocus();
    }

    function getMatched() {
        var v = (q ? q.value : '').trim();

        if (v) {
            var lv    = v.toLowerCase();
            var exact = items.filter(function (el) { return el._term.trim().toLowerCase() === lv; });
            if (exact.length) { return { list: exact, query: '' }; }

            return {
                list: items.filter(function (el) {
                    return wordStartIndex(el._term, v) > -1 || wordStartIndex(el._desc, v) > -1;
                }),
                query: v
            };
        }

        return {
            list: items.filter(function (el) {
                return jamo === 'all' || el.getAttribute('data-jamo') === jamo;
            }),
            query: ''
        };
    }

    function markOverflow() {
        items.forEach(function (el) {
            if (el.hidden) { return; }
            var desc = el.querySelector('.glossary-item__desc');
            var head = el.querySelector('.glossary-item__head');
            if (!desc || !head) { return; }

            var wasOpen = el.classList.contains('is-open');
            el.classList.remove('is-open');
            setDescHtml(el, false);

            var over    = desc.scrollHeight > desc.clientHeight + 1;
            var hasBody = !!el.querySelector('.glossary-item__body');
            el.classList.toggle('has-more', over || hasBody || !!el._snipped);

            if (wasOpen && el.classList.contains('has-more')) {
                el.classList.add('is-open');
                setDescHtml(el, true);
                setBody(el, true);
                head.setAttribute('aria-expanded', 'true');
            } else {
                setBody(el, false);
                head.setAttribute('aria-expanded', 'false');
            }
        });
        syncFocus();
    }

    function buildPages(matched) {
        var blocks = [];
        matched.forEach(function (el) {
            var j = el.getAttribute('data-jamo');
            if (!blocks.length || blocks[blocks.length - 1].jamo !== j) {
                blocks.push({ jamo: j, list: [] });
            }
            blocks[blocks.length - 1].list.push(el);
        });

        var pages = [], cur = [];
        blocks.forEach(function (b) {
            var i = 0;
            while (i < b.list.length) {
                var room = PER_PAGE - cur.length;
                if (room <= 0) { pages.push(cur); cur = []; room = PER_PAGE; }
                if (i === 0 && cur.length && b.list.length > room) { pages.push(cur); cur = []; room = PER_PAGE; }
                var take = Math.min(room, b.list.length - i);
                cur = cur.concat(b.list.slice(i, i + take));
                i += take;
            }
        });
        if (cur.length) { pages.push(cur); }
        return pages.length ? pages : [[]];
    }

    function pageItems(current, total) {
        if (total <= 7) {
            var all = [];
            for (var i = 1; i <= total; i++) { all.push(i); }
            return all;
        }
        if (current <= 4) { return [1, 2, 3, 4, 5, 'ellipsis', total]; }
        if (current >= total - 3) { return [1, 'ellipsis', total - 4, total - 3, total - 2, total - 1, total]; }
        return [1, 'ellipsis', current - 1, current, current + 1, 'ellipsis', total];
    }

    function renderPager(total) {
        if (!pager) { return; }
        if (total <= 1) { pager.innerHTML = ''; return; }

        var html = '<button class="page-btn nav prev" type="button" data-action="prev" aria-label="이전 페이지"' + (page <= 1 ? ' disabled' : '') + '>'
                 + '<span class="page-icon page-icon--prev" aria-hidden="true"></span></button>';

        pageItems(page, total).forEach(function (item) {
            if (item === 'ellipsis') { html += '<span class="page-ellipsis" aria-hidden="true">…</span>'; return; }
            var on = (item === page);
            html += '<button class="page-btn' + (on ? ' active' : '') + '" type="button" data-page="' + item + '"'
                  + (on ? ' aria-current="page"' : '') + ' aria-label="' + item + ' 페이지">' + item + '</button>';
        });

        html += '<button class="page-btn nav next" type="button" data-action="next" aria-label="다음 페이지"' + (page >= total ? ' disabled' : '') + '>'
              + '<span class="page-icon page-icon--next" aria-hidden="true"></span></button>';

        pager.innerHTML = html;
    }

    function render() {
        syncPerPage();

        var res = getMatched();
        currentQuery   = res.query;
        currentKeyword = (q ? q.value : '').trim();

        var pages = buildPages(res.list);
        var total = pages.length;

        if (page > total) { page = total; }
        if (page < 1) { page = 1; }

        var visible = pages[page - 1] || [];

        items.forEach(function (el) { el.hidden = true; });
        visible.forEach(function (el) {
            el.hidden = false;
            el.querySelector('.glossary-item__term').innerHTML = highlight(el._term, currentQuery);
        });

        groups.forEach(function (g) {
            g.hidden = !g.querySelector('.glossary-item:not([hidden])');
        });

        if (empty) { empty.hidden = res.list.length !== 0; }

        renderPager(res.list.length === 0 ? 0 : total);
        markOverflow();
        renderRelated();
    }

    function setJamo(value) {
        jamo = value;
        tabs.forEach(function (b) {
            var on = (b.getAttribute('data-jamo') === value);
            b.classList.toggle('is-active', on);
            b.setAttribute('aria-pressed', on ? 'true' : 'false');
        });
    }

    function clearHighlight() {
        if (hlTimer) { clearTimeout(hlTimer); hlTimer = null; }
        Array.prototype.forEach.call(root.querySelectorAll('.glossary-top__link.is-current'), function (el) { el.classList.remove('is-current'); });
        Array.prototype.forEach.call(root.querySelectorAll('.glossary-item.is-highlight'), function (el) { el.classList.remove('is-highlight'); });
    }

    function scrollToTop() {
        var y = root.getBoundingClientRect().top + window.pageYOffset - 100;
        window.scrollTo({ top: y, behavior: 'smooth' });
    }

    // 자음 탭
    tabs.forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (btn.disabled) { return; }
            clearHighlight();
            if (q) { q.value = ''; }
            setJamo(btn.getAttribute('data-jamo'));
            page = 1;
            render();
        });
    });

    /* 검색어 기록 — 타이핑이 멈춘 뒤에만, 같은 말은 방문당 한 번만 */
    var logTimer = null;
    var logged   = Object.create(null);

    function logSearch(kw, count) {
        kw = String(kw || '').trim().replace(/\s+/g, ' ');
        if (kw.length < 2 || kw.length > 50 || logged[kw]) { return; }
        logged[kw] = true;
        try {
            fetch(<?php echo wp_json_encode( esc_url_raw( rest_url( 'borobill/v1/search-log' ) ) ); ?>, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ keyword: kw, results: count | 0, source: 'glossary' }),
                keepalive: true
            }).catch(function () {});
        } catch (e) {}
    }

    // 검색
    if (q) {
        q.addEventListener('input', function () {
            clearHighlight();
            var has = !!q.value.trim();
            setJamo(has ? '' : DEFAULT_JAMO);
            page = 1;
            render();

            if (logTimer) { clearTimeout(logTimer); }
            if (has) {
                var kw = q.value.trim();
                logTimer = setTimeout(function () {
                    logSearch(kw, getMatched().list.length);
                }, 1500);
            }
        });
    }

    // 페이지 이동
    if (pager) {
        pager.addEventListener('click', function (e) {
            var btn = e.target.closest('.page-btn');
            if (!btn || btn.disabled) { return; }

            var total = buildPages(getMatched().list).length;
            var act   = btn.getAttribute('data-action');

            if (act === 'prev') { page = Math.max(1, page - 1); }
            else if (act === 'next') { page = Math.min(total, page + 1); }
            else { page = parseInt(btn.getAttribute('data-page'), 10) || 1; }

            clearHighlight();
            render();
            scrollToTop();
        });
    }

    root.addEventListener('click', function (e) {
        var head = e.target.closest('.glossary-item__head');
        if (head) {
            var item = head.closest('.glossary-item');
            if (!item || !item.classList.contains('has-more')) { return; }

            var willOpen = !item.classList.contains('is-open');
            closeAll(item);

            if (willOpen) { item.classList.add('is-open'); }
            else { item.classList.remove('is-open'); }

            head.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
            setDescHtml(item, willOpen);
            setBody(item, willOpen);
            syncFocus();

            renderRelated();
            return;
        }

        var top = e.target.closest('.glossary-top__link');
        if (!top) { return; }

        e.preventDefault();
        var target = document.getElementById(top.getAttribute('data-target'));
        if (!target) { return; }

        clearHighlight();
        if (q) { q.value = ''; }
        setJamo(target.getAttribute('data-jamo') || DEFAULT_JAMO);

        var pages = buildPages(getMatched().list);
        page = 1;
        for (var pi = 0; pi < pages.length; pi++) {
            if (pages[pi].indexOf(target) > -1) { page = pi + 1; break; }
        }
        render();

        top.classList.add('is-current');
        target.classList.add('is-highlight');
        target.scrollIntoView({ block: 'center', behavior: 'smooth' });

        closeAll(target);
        if (target.classList.contains('has-more')) {
            target.classList.add('is-open');
            setDescHtml(target, true);
            setBody(target, true);
            var th = target.querySelector('.glossary-item__head');
            if (th) { th.setAttribute('aria-expanded', 'true'); }
        }
        syncFocus();

        renderRelated();
        hlTimer = setTimeout(clearHighlight, 2500);
    });

    /* 카드 바깥을 누르거나 Esc를 누르면 닫고 흐림도 푼다 */
    function closeIfOpen() {
        if (!root.classList.contains('is-focusing')) { return; }
        closeAll();
        renderRelated();
    }

    document.addEventListener('click', function (e) {
        if (e.target.closest('.glossary-item, .glossary-top__link')) { return; }
        closeIfOpen();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' || e.key === 'Esc') { closeIfOpen(); }
    });

    window.addEventListener('resize', function () {
        if (syncPerPage()) { page = 1; render(); } else { markOverflow(); }
    });

    syncPerPage();
    setJamo(DEFAULT_JAMO);
    render();
})();
</script>