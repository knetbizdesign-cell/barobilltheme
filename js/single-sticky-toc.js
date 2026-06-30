// 우측 aside 전체 스크롤 따라 고정 (footer 위에서 정지) + fixed→absolute 전환 시 디졸브 (볼타 스타일)
// 위쪽 스크롤 시 깜빡임/사라짐 방지: 히스테리시스 + 상단 구간 보호
// 스크롤 성능: throttle로 레이아웃 읽기/쓰기 빈도 축소 (26년 국내 사이트 수준의 가벼운 스크롤)
(function() {
  var FADE_DISTANCE = 260;
  var FADE_EASE = 1.6;
  var HYSTERESIS = 60;  /* static↔fixed 경계에서 왓다갓다 시 전환 억제 */
  var rafScheduled = false;
  var lastMode = '';     /* 'static' | 'fixed' | 'absolute' */
  var THROTTLE_MS = 20;  /* 스크롤 시 최대 ~50fps로 갱신 (버벅임 방지) */
  var lastApplyTime = 0;

  document.addEventListener('DOMContentLoaded', function() {
    var aside = document.querySelector('.single-aside.sticky-group');
    if (!aside) return;
    var container = aside.parentElement;
    if (!container) return;
    var footer = document.querySelector('.site-footer');

    var startTop = 0;
    var origLeft = 0;
    var origWidth = 0;
    var relativeLeft = 0;

    function resetStyles() {
      aside.style.position = '';
      aside.style.top = '';
      aside.style.left = '';
      aside.style.width = '';
      aside.style.zIndex = '';
      aside.style.opacity = '';
      aside.style.transition = '';
    }

    function refreshMeasurements() {
      resetStyles();
      lastMode = 'static';
      var rect = aside.getBoundingClientRect();
      var containerRect = container.getBoundingClientRect();
      startTop = rect.top + window.pageYOffset;
      origLeft = rect.left;
      origWidth = aside.offsetWidth;
      relativeLeft = rect.left - containerRect.left;
    }

    function getBounds(scrollY) {
      var footerRect = footer ? footer.getBoundingClientRect() : null;
      var footerTop = footerRect ? scrollY + footerRect.top : document.body.scrollHeight;
      var containerRect = container.getBoundingClientRect();
      var containerTop = scrollY + containerRect.top;
      var containerBottom = containerTop + containerRect.height;
      /* 푸터와 겹치지 않도록 여유 120px, 빠른 스크롤 대비 여유 확보 */
      var safeAreaTop = Math.min(containerBottom, footerTop) - aside.offsetHeight - 120;
      return {
        containerTop: containerTop,
        safeAreaTop: Math.max(containerTop, safeAreaTop || 0),
        footerTop: footerTop,
      };
    }

    function applyScrollState() {
      rafScheduled = false;
      var scrollY = window.pageYOffset || document.documentElement.scrollTop;
      var viewportH = window.innerHeight || document.documentElement.clientHeight;
      var bounds = getBounds(scrollY);
      var fadeStart = bounds.safeAreaTop - FADE_DISTANCE;
      var fadeRange = bounds.safeAreaTop - fadeStart;
      if (fadeRange <= 0) fadeRange = 1;

      /* 상단 구간: 한 화면 위쪽에서는 절대 absolute/opacity 0 으로 넘어가지 않음 */
      var inTopZone = scrollY < viewportH * 0.6;
      var stickEnter = startTop + HYSTERESIS;
      var stickLeave = startTop - HYSTERESIS;
      /* 푸터 진입 전에 우측 영역 숨김: 푸터가 뷰포트 하단에서 150px 위에 오기 전에 전환 */
      var footerNearViewport = footer && (scrollY + viewportH) > (bounds.footerTop - 150);

      if (!inTopZone && (scrollY + 80 >= bounds.safeAreaTop || footerNearViewport)) {
        lastMode = 'absolute';
        aside.style.transition = 'opacity 0.28s ease-out';
        aside.style.position = 'absolute';
        aside.style.top = Math.max(0, bounds.safeAreaTop - bounds.containerTop) + 'px';
        aside.style.left = Math.max(0, relativeLeft) + 'px';
        aside.style.width = origWidth + 'px';
        aside.style.zIndex = '0';
        aside.style.opacity = '0';
      } else if (lastMode === 'fixed' ? scrollY > stickLeave : scrollY > stickEnter) {
        lastMode = 'fixed';
        aside.style.transition = '';
        aside.style.position = 'fixed';
        aside.style.top = '30px';
        aside.style.left = origLeft + 'px';
        aside.style.width = origWidth + 'px';
        aside.style.zIndex = '999';
        if (scrollY >= fadeStart && fadeStart < bounds.safeAreaTop) {
          var t = (scrollY - fadeStart) / fadeRange;
          t = Math.max(0, Math.min(1, t));
          var opacity = 1 - Math.pow(t, FADE_EASE);
          aside.style.opacity = String(opacity);
        } else {
          aside.style.opacity = '1';
        }
      } else {
        lastMode = 'static';
        resetStyles();
      }
    }

    function onScroll() {
      if (rafScheduled) return;
      rafScheduled = true;
      requestAnimationFrame(function() {
        rafScheduled = false;
        var now = typeof performance !== 'undefined' ? performance.now() : Date.now();
        if (now - lastApplyTime < THROTTLE_MS) return;
        lastApplyTime = now;
        applyScrollState();
      });
    }

    refreshMeasurements();
    applyScrollState();
    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', function() {
      refreshMeasurements();
      if (rafScheduled) return;
      rafScheduled = true;
      requestAnimationFrame(applyScrollState);
    });
  });
})();

// h2 => 목차 자동 추출 & 클릭 스크롤
(function() {
  function getHeaderOffset() {
    var header = document.querySelector('.site-header');
    /* .site-header 는 top:0 + padding-top(관리자바·safe-area) 로 뷰포트 상단부터 한 덩어리이므로
       #wpadminbar 높이를 또 더하면 이중 계산됨 */
    if (header && header.offsetHeight) {
      return header.offsetHeight + 24;
    }
    var v = getComputedStyle(document.documentElement).getPropertyValue('--header-height');
    if (v) {
      var num = parseInt(String(v).trim(), 10);
      if (!isNaN(num)) return num + 24;
    }
    return 96;
  }

  document.addEventListener('DOMContentLoaded', function() {
    var tocList = document.querySelector('.single-toc-list');
    var content = document.querySelector('.post-content');
    if (!tocList || !content) return;

    // 기존 목차 비움. 목차 순서(1,2,3…)와 본문 h2 순서를 반드시 일치시키기 위해 id를 순차 부여
    tocList.innerHTML = '';
    var h2s = content.querySelectorAll('h2');
    h2s.forEach(function(h2, i) {
      var id = 'section-toc-' + (i + 1);
      h2.id = id;
      var li = document.createElement('li');
      var a = document.createElement('a');
      a.href = '#' + id;
      a.innerText = h2.textContent || h2.innerText;
      li.appendChild(a);
      tocList.appendChild(li);
    });

    // 클릭 시 스크롤 이동 (smooth). GNB 세로 길이만큼 아래로 스크롤해 해당 제목이 헤더 아래에 보이도록
    tocList.querySelectorAll('a[href^="#"]').forEach(function(link) {
      link.addEventListener('click', function(e) {
        var target = document.getElementById(link.getAttribute('href').substring(1));
        if (target) {
          e.preventDefault();
          var headerOffset = getHeaderOffset();
          var scrollY = window.scrollY || window.pageYOffset;
          var targetTop = target.getBoundingClientRect().top + scrollY;
          var y = targetTop - headerOffset;
          if (y < 0) y = 0;
          window.scrollTo({ top: y, behavior: 'smooth' });
        }
      });
    });

    // 스크롤 위치에 따라 목차 항목에 .active 표시 (throttle로 레이아웃 읽기 최소화)
    var tocLinks = tocList.querySelectorAll('a[href^="#"]');
    var sectionIds = [];
    h2s.forEach(function(h2) { sectionIds.push(h2.id); });
    var tocRaf = null;
    var tocLastRun = 0;
    var TOC_THROTTLE_MS = 50;

    function setActiveToc() {
      tocRaf = null;
      var scrollY = window.scrollY || window.pageYOffset;
      var headerOffset = getHeaderOffset();
      var triggerLine = scrollY + headerOffset + 40;
      var current = 0;
      for (var i = 0; i < sectionIds.length; i++) {
        var el = document.getElementById(sectionIds[i]);
        if (!el) continue;
        var sectionTop = el.getBoundingClientRect().top + scrollY;
        if (sectionTop <= triggerLine) current = i;
      }
      tocLinks.forEach(function(link, i) {
        link.classList.toggle('active', i === current);
      });
    }
    function onScrollToc() {
      if (tocRaf) return;
      tocRaf = requestAnimationFrame(function() {
        var now = typeof performance !== 'undefined' ? performance.now() : Date.now();
        if (now - tocLastRun < TOC_THROTTLE_MS) {
          tocRaf = null;
          return;
        }
        tocLastRun = now;
        setActiveToc();
      });
    }
    setActiveToc();
    window.addEventListener('scroll', onScrollToc, { passive: true });
    window.addEventListener('resize', function() {
      if (tocRaf) cancelAnimationFrame(tocRaf);
      tocRaf = requestAnimationFrame(setActiveToc);
    });
  });
})();

// 읽기 진행률 UX: 0~100% (우측 영역 끝=본문 끝일 때 100%). 읽는 시간 영역은 항상 표시 (사라지지 않음)
(function() {
  var RAF = null;
  var lastPercent = -1;
  var progressLastRun = 0;
  var PROGRESS_THROTTLE_MS = 50;
  var ASIDE_FIXED_TOP = 30;

  function getProgress() {
    var article = document.querySelector('.single-post');
    var aside = document.querySelector('.single-aside.sticky-group');
    var scrollY = window.scrollY || window.pageYOffset;
    var viewportH = window.innerHeight || document.documentElement.clientHeight;
    if (!article) return { percent: 0, segment: 'start' };

    var rangeStart = 0;
    var articleTop = article.getBoundingClientRect().top + scrollY;
    var endEl = document.querySelector('.post-tags') || document.querySelector('.post-footer') || document.querySelector('.post-content');
    var contentEndDoc = 0;
    if (endEl) {
      var endRect = endEl.getBoundingClientRect();
      contentEndDoc = endRect.bottom + scrollY;
    } else {
      contentEndDoc = articleTop + article.offsetHeight;
    }
    var asideHeight = aside ? aside.offsetHeight : 320;
    var rangeEnd = contentEndDoc - ASIDE_FIXED_TOP - asideHeight;
    /* 본문이 짧거나 사이드바가 길면 rangeEnd가 너무 작아져 스크롤 조금에 100% 되는 것 방지 */
    var minRange = Math.max(viewportH, (contentEndDoc - articleTop) * 0.5);
    rangeEnd = Math.max(rangeEnd, rangeStart + minRange);
    var range = rangeEnd - rangeStart;
    var raw = (scrollY - rangeStart) / range;
    var percent = Math.round(Math.max(0, Math.min(100, raw * 100)));
    var segment = percent >= 100 ? '100' : percent >= 75 ? '75' : percent >= 50 ? '50' : percent >= 25 ? '25' : 'start';
    return { percent: percent, segment: segment };
  }

  function updateReadingProgress() {
    var block = document.querySelector('.single-reading-time');
    if (!block) return;
    var percentEl = block.querySelector('.single-reading-time__percent');
    var fillEl = block.querySelector('.single-reading-time__bar-fill');
    var msgEl = block.querySelector('.single-reading-time__message');
    var progressbar = block.querySelector('[role="progressbar"]');
    if (!percentEl || !fillEl || !msgEl) return;

    var p = getProgress();
    var percentToShow = p.percent >= 100 ? 100 : p.percent;
    if (percentToShow === lastPercent) return;
    lastPercent = percentToShow;

    percentEl.textContent = percentToShow + '% 읽는 중';
    if (fillEl) fillEl.style.width = percentToShow + '%';
    if (progressbar) progressbar.setAttribute('aria-valuenow', percentToShow);

    var key = 'data-msg-' + p.segment;
    var msg = msgEl.getAttribute(key);
    if (msg) msgEl.textContent = msg;
  }

  function onScrollReadingProgress() {
    if (RAF) return;
    RAF = requestAnimationFrame(function() {
      RAF = null;
      var now = typeof performance !== 'undefined' ? performance.now() : Date.now();
      if (now - progressLastRun < PROGRESS_THROTTLE_MS) return;
      progressLastRun = now;
      updateReadingProgress();
    });
  }

  document.addEventListener('DOMContentLoaded', function() {
    var block = document.querySelector('.single-reading-time');
    if (!block) return;
    updateReadingProgress();
    window.addEventListener('scroll', onScrollReadingProgress, { passive: true });
    window.addEventListener('resize', onScrollReadingProgress);
  });
})();


