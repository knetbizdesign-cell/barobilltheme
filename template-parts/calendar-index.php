<?php
/**
 * 세무 일정 — 캘린더형 레이아웃
 * 사용: category.php (borobill_get_category_layout() === 'calendar')
 */

if ( ! function_exists( 'borobill_calendar_events' ) ) {
    return;
}

$cal_kinds   = borobill_calendar_kinds();
$cal_today   = current_time( 'Y-m-d' );
$cal_year    = (int) substr( $cal_today, 0, 4 );
$cal_month   = (int) substr( $cal_today, 5, 2 );
$cal_events  = borobill_calendar_events( $cal_year, $cal_month );
$cal_pool = borobill_calendar_guide_pool( 200 );

// 기본 목록: 이번 달 남은 일정만
$cal_list = array();
foreach ( $cal_events as $e ) {
    if ( $e['date'] >= $cal_today ) {
        $e['mo']    = $cal_month;
        $cal_list[] = $e;
    }
}

// 이번 달이 통째로 지났으면 그 달 전체를 보여준다
if ( ! $cal_list ) {
    foreach ( $cal_events as $e ) {
        $e['mo']    = $cal_month;
        $cal_list[] = $e;
    }
}

$cal_past_count = 0;
foreach ( $cal_events as $e ) {
    if ( $e['date'] < $cal_today ) {
        $cal_past_count++;
    }
}

// 오늘 기준 바로 다음에 다가올 날짜
$cal_next_date = '';
foreach ( $cal_list as $e ) {
    if ( $e['date'] >= $cal_today ) {
        $cal_next_date = $e['date'];
        break;
    }
}

// 같은 날짜끼리 묶기
$cal_groups = array();
foreach ( $cal_list as $e ) {
    $cal_groups[ $e['date'] ][] = $e;
}

// 다음 일정의 항목에 맞춰 관련 글을 고른다
$cal_first_kind = '';
foreach ( $cal_list as $e ) {
    if ( $e['date'] === $cal_next_date ) {
        $cal_first_kind = $e['k'];
        break;
    }
}
$cal_related   = borobill_calendar_pick_guides( $cal_pool, $cal_first_kind, 3 );
$cal_today_ts = strtotime( $cal_today );
?>
<section class="section section-list section-calendar">
    <div class="section-inner">
        <div class="taxcal" id="taxcal">

            <div class="taxcal-body">

                <div class="taxcal-ym">
                    <button type="button" class="taxcal-ym__btn" id="taxcal-prev" aria-label="이전 달">
                        <span class="taxcal-ym__arrow taxcal-ym__arrow--prev" aria-hidden="true"></span>
                    </button>
                    <span class="taxcal-ym__label" id="taxcal-label"><?php echo esc_html( $cal_year . '년 ' . $cal_month . '월' ); ?></span>
                    <button type="button" class="taxcal-ym__btn" id="taxcal-next" aria-label="다음 달">
                        <span class="taxcal-ym__arrow taxcal-ym__arrow--next" aria-hidden="true"></span>
                    </button>
                </div>

                <div class="taxcal-grid">
                    <table>
                        <thead>
                            <tr>
                                <th>일</th><th>월</th><th>화</th><th>수</th><th>목</th><th>금</th><th>토</th>
                            </tr>
                        </thead>
                        <tbody id="taxcal-grid"></tbody>
                    </table>
                </div>

                <aside class="taxcal-aside">
                    <div class="taxcal-panel">
                        <h3 class="taxcal-panel__title">항목별 보기</h3>
                        <ul class="taxcal-kinds" id="taxcal-kinds"></ul>
                    </div>
                </aside>

            </div>

            <h3 class="taxcal-sec" id="taxcal-listtitle"><?php echo esc_html( $cal_month . '월 일정 자세히 보기' ); ?></h3>

            <div class="taxcal-list" id="taxcal-list">
                <?php if ( empty( $cal_list ) ) : ?>
                    <p class="taxcal-list__empty">등록된 일정이 없습니다.</p>
                <?php else : ?>
                    <?php foreach ( $cal_groups as $g_date => $g_list ) : ?>
                        <div class="taxcal-group<?php echo ( $g_date === $cal_next_date ) ? ' is-next' : ''; ?>">
                            <?php foreach ( $g_list as $e ) : ?>
                                <?php
                                $diff = (int) floor( ( strtotime( $e['date'] ) - $cal_today_ts ) / DAY_IN_SECONDS );
                                if ( $diff < 0 ) {
                                    $dd = '마감';
                                } elseif ( 0 === $diff ) {
                                    $dd = 'D-DAY';
                                } else {
                                    $dd = 'D-' . $diff;
                                }
                                ?>
                                <div class="taxcal-row<?php echo ( $diff < 0 ) ? ' is-past' : ''; ?>">
                                    <span class="taxcal-row__date">
                                        <span><?php echo esc_html( $e['mo'] . '월' ); ?></span>
                                        <b><?php echo (int) $e['day']; ?></b>
                                    </span>
                                    <span class="taxcal-row__body">
                                        <span class="taxcal-row__title">
                                            <span class="taxcal-row__kind" data-k="<?php echo esc_attr( $e['k'] ); ?>"><?php echo esc_html( $cal_kinds[ $e['k'] ] ); ?></span><?php echo esc_html( $e['t'] ); ?>
                                        </span>
                                        <span class="taxcal-row__note"><?php echo esc_html( $e['w'] ); ?></span>
                                    </span>
                                    <span class="taxcal-row__dday"><?php echo esc_html( $dd ); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="taxcal-more" id="taxcal-morewrap"<?php echo $cal_past_count ? '' : ' hidden'; ?>>
                <button type="button" id="taxcal-more">지난 일정 보기</button>
            </div>

            <?php if ( ! empty( $cal_related ) ) : ?>
                <div class="taxcal-rel">
                    <h3 class="taxcal-sec">일정과 함께 보면 좋은 글</h3>
                    <div class="post-list post-list--view-card taxcal-relist" id="taxcal-rellist">
                        <?php foreach ( $cal_related as $r ) : ?>
                            <a href="<?php echo esc_url( $r['u'] ); ?>" class="article-card__link" aria-label="<?php echo esc_attr( $r['t'] ); ?>">
                                <article class="article-card article-card--feed">
                                    <div class="article-info">
                                        <div class="article-meta">
                                            <?php if ( ! empty( $r['cat'] ) ) : ?>
                                                <span class="badge badge-small"><?php echo esc_html( $r['cat'] ); ?></span>
                                            <?php endif; ?>
                                            <span class="meta-date"><?php echo esc_html( $r['dt'] ); ?></span>
                                        </div>
                                        <h3 class="article-title"><?php echo esc_html( $r['t'] ); ?></h3>
                                        <?php if ( ! empty( $r['sub'] ) ) : ?>
                                            <p class="article-subtitle"><?php echo esc_html( $r['sub'] ); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="article-thumb-wrap">
                                        <img src="<?php echo esc_url( $r['i'] ); ?>" class="article-thumb" alt="<?php echo esc_attr( $r['t'] ); ?>" loading="lazy" />
                                    </div>
                                </article>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</section>

<script type="application/json" id="taxcal-data"><?php
echo wp_json_encode( array(
    'kinds' => $cal_kinds,
    'items' => borobill_calendar_get_items(),
    'today' => $cal_today,
    'pool'  => $cal_pool,
    'words' => borobill_calendar_kind_keywords(),
    'hols'  => borobill_calendar_holidays(),
) );
?></script>

<script>
(function () {
    var root = document.getElementById('taxcal');
    var box  = document.getElementById('taxcal-data');
    if (!root || !box) { return; }

    var DATA;
    try { DATA = JSON.parse(box.textContent); } catch (e) { return; }

    var KINDS = DATA.kinds || {};
    var ITEMS = DATA.items || [];
    var HOLS  = DATA.hols  || {};

    var t = String(DATA.today).split('-');
    var TODAY = new Date(+t[0], +t[1] - 1, +t[2]);

    var cur      = new Date(TODAY.getFullYear(), TODAY.getMonth(), 1);
    var off      = {};   // 체크 해제된 항목
    var selDay   = null;
    var showPast = false;

    var $ = function (id) { return document.getElementById(id); };
    var grid     = $('taxcal-grid');
    var list     = $('taxcal-list');
    var kindBox  = $('taxcal-kinds');
    var moreWrap = $('taxcal-morewrap');
    var moreBtn  = $('taxcal-more');

    function escapeHtml(s) {
        return String(s).replace(/[&<>"]/g, function (c) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' })[c];
        });
    }

    function pad(n) { return (n < 10 ? '0' : '') + n; }
    function lastDay(y, m) { return new Date(y, m + 1, 0).getDate(); }

    function resolveAll(y, m) {
        var prefix = y + '-' + pad(m + 1) + '-';
        var out = [];

        ITEMS.forEach(function (it) {
            if (String(it.d).indexOf(prefix) !== 0) { return; }
            out.push({
                k: KINDS[it.k] ? it.k : 'wht',
                t: it.t,
                w: it.w,
                mo: m + 1,
                day: parseInt(String(it.d).slice(8, 10), 10),
                ymd: it.d
            });
        });

        return out.sort(function (a, b) { return a.day - b.day; });
    }

    function resolve(y, m) {
        return resolveAll(y, m).filter(function (e) { return !off[e.k]; });
    }

    function dday(ymd) {
        var p = ymd.split('-');
        var diff = Math.round((new Date(+p[0], +p[1] - 1, +p[2]) - TODAY) / 86400000);
        if (diff < 0)   { return { txt: '마감', past: true }; }
        if (diff === 0) { return { txt: 'D-DAY', past: false }; }
        return { txt: 'D-' + diff, past: false };
    }

    /* 기본 목록: 이 달 남은 일정만 */
    function defaultList(y, m) {
        return resolve(y, m).filter(function (e) { return e.ymd >= DATA.today; });
    }

    function renderKinds() {
        var allOn = true;
        Object.keys(KINDS).forEach(function (k) { if (off[k]) { allOn = false; } });

        var html = '<li><button type="button" class="taxcal-kind taxcal-kind--all' + (allOn ? ' is-on' : '')
                 + '" data-kind="" aria-pressed="' + (allOn ? 'true' : 'false') + '">'
                 + '<span class="taxcal-kind__label">전체</span>'
                 + '<span class="taxcal-kind__check" aria-hidden="true"></span></button></li>';

        Object.keys(KINDS).forEach(function (k) {
            var on = !off[k];
            html += '<li><button type="button" class="taxcal-kind' + (on ? ' is-on' : '')
                  + '" data-kind="' + k + '" data-k="' + k + '" aria-pressed="' + (on ? 'true' : 'false') + '">'
                  + '<span class="taxcal-kind__label">' + escapeHtml(KINDS[k]) + '</span>'
                  + '<span class="taxcal-kind__check" aria-hidden="true"></span></button></li>';
        });

        kindBox.innerHTML = html;
    }

    function rowHtml(e) {
        var dd = dday(e.ymd);
        return '<div class="taxcal-row' + (dd.past ? ' is-past' : '') + '">'
             + '<span class="taxcal-row__date"><span>' + e.mo + '월</span><b>' + e.day + '</b></span>'
             + '<span class="taxcal-row__body">'
             + '<span class="taxcal-row__title"><span class="taxcal-row__kind" data-k="' + e.k + '">'
             + escapeHtml(KINDS[e.k]) + '</span>' + escapeHtml(e.t) + '</span>'
             + '<span class="taxcal-row__note">' + escapeHtml(e.w) + '</span>'
             + '</span>'
             + '<span class="taxcal-row__dday">' + dd.txt + '</span>'
             + '</div>';
    }

    function render() {
        var y = cur.getFullYear(), m = cur.getMonth();
        var events = resolve(y, m);

        renderKinds();

        /* 달력 */
        var first    = new Date(y, m, 1).getDay();
        var last     = lastDay(y, m);
        var prevLast = lastDay(y, m - 1 < 0 ? 11 : m - 1);
        var cells = [], i, tail = 1;

        for (i = first - 1; i >= 0; i--) { cells.push({ n: prevLast - i, out: true }); }
        for (i = 1; i <= last; i++)      { cells.push({ n: i, out: false }); }
        while (cells.length % 7)         { cells.push({ n: tail++, out: true }); }

        var html = '';
        for (i = 0; i < cells.length; i += 7) {
            html += '<tr>';
            cells.slice(i, i + 7).forEach(function (c, idx) {
                var edge = (idx === 0 ? ' taxcal-day--sun' : idx === 6 ? ' taxcal-day--sat' : '');

                if (c.out) {
                    html += '<td><div class="taxcal-day taxcal-day--out' + edge + '">'
                         + '<span class="taxcal-day__n">' + c.n + '</span>'
                         + '<span class="taxcal-day__dots"></span></div></td>';
                    return;
                }

                var hit = events.filter(function (e) { return e.day === c.n; });
                var ymd = y + '-' + ('0' + (m + 1)).slice(-2) + '-' + ('0' + c.n).slice(-2);
                var hol = HOLS[ymd] || '';
                var cls = 'taxcal-day' + edge;
                if (hol)        { cls += ' taxcal-day--hol'; }
                if (hit.length) { cls += ' taxcal-day--has'; }
                if (y === TODAY.getFullYear() && m === TODAY.getMonth() && c.n === TODAY.getDate()) { cls += ' taxcal-day--today'; }
                if (selDay === c.n) { cls += ' is-active'; }

                html += '<td><button type="button" class="' + cls + '" data-day="' + c.n + '"'
                     + (hol ? ' title="' + hol.replace(/"/g, '&quot;') + '"' : '') + '>'
                     + '<span class="taxcal-day__n">' + c.n + '</span>'
                     + '<span class="taxcal-day__dots">'
                     + hit.slice(0, 3).map(function (e) { return '<span class="taxcal-dot" data-k="' + e.k + '"></span>'; }).join('')
                     + '</span></button></td>';
            });
            html += '</tr>';
        }
        grid.innerHTML = html;

        $('taxcal-label').textContent = y + '년 ' + (m + 1) + '월';
        $('taxcal-listtitle').textContent = (m + 1) + '월 일정 자세히 보기';

        /* 리스트 */
        var pastCount = events.filter(function (e) { return dday(e.ymd).past; }).length;
        var shown, markNext;

        if (selDay !== null) {
            // 날짜를 고른 상태 — 강조 없이 그 날 일정만
            shown = events.filter(function (e) { return e.day === selDay; });
            markNext = false;
        } else if (showPast) {
            shown = events;
            markNext = true;
        } else {
            shown = defaultList(y, m);
            markNext = true;
            if (!shown.length) { shown = events; }
        }

        var nextYmd = '';
        if (markNext) {
            for (var n = 0; n < shown.length; n++) {
                if (shown[n].ymd >= DATA.today) { nextYmd = shown[n].ymd; break; }
            }
        }

        if (shown.length) {
            var groups = [];
            shown.forEach(function (e) {
                if (!groups.length || groups[groups.length - 1].ymd !== e.ymd) {
                    groups.push({ ymd: e.ymd, list: [] });
                }
                groups[groups.length - 1].list.push(e);
            });

            list.innerHTML = groups.map(function (g) {
                return '<div class="taxcal-group' + (g.ymd === nextYmd ? ' is-next' : '') + '">'
                     + g.list.map(rowHtml).join('')
                     + '</div>';
            }).join('');
        } else {
            list.innerHTML = '<p class="taxcal-list__empty">'
                + (selDay !== null ? '이 날에는 신고·납부 기한이 없습니다.' : '선택한 항목의 일정이 없습니다.')
                + '</p>';
        }

        /* 하단 버튼 */
        if (selDay !== null) {
            moreWrap.hidden = false;
            moreBtn.textContent = (m + 1) + '월 전체 일정 보기';
        } else if (pastCount) {
            moreWrap.hidden = false;
            moreBtn.textContent = showPast ? '지난 일정 접기' : '지난 일정 보기';
        } else {
            moreWrap.hidden = true;
        }
    }

    $('taxcal-prev').addEventListener('click', function () {
        cur.setMonth(cur.getMonth() - 1); selDay = null; showPast = false; render();
    });

    $('taxcal-next').addEventListener('click', function () {
        cur.setMonth(cur.getMonth() + 1); selDay = null; showPast = false; render();
    });

    moreBtn.addEventListener('click', function () {
        if (selDay !== null) { selDay = null; } else { showPast = !showPast; }
        render();
    });

    grid.addEventListener('click', function (e) {
        var b = e.target.closest('[data-day]');
        if (!b) { return; }
        var d = parseInt(b.getAttribute('data-day'), 10);
        selDay = (selDay === d) ? null : d;
        render();
    });

    kindBox.addEventListener('click', function (e) {
        var b = e.target.closest('[data-kind]');
        if (!b) { return; }
        var v = b.getAttribute('data-kind');

        if (v === '') {
            var allOn = true;
            Object.keys(KINDS).forEach(function (k) { if (off[k]) { allOn = false; } });
            off = {};
            if (allOn) { Object.keys(KINDS).forEach(function (k) { off[k] = true; }); }
        } else {
            if (off[v]) { delete off[v]; } else { off[v] = true; }
        }

        selDay = null; showPast = false;
        render();
    });

    render();
})();
</script>