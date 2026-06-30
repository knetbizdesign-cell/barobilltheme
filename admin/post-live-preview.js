(function ($) {
    'use strict';

    $(function () {
        var AUTO_REFRESH_DEBOUNCE_MS = 900;
        var AUTOSAVE_THROTTLE_MS = 2500;
        var isPostEditor = $('body').hasClass('post-php') || $('body').hasClass('post-new-php');
        if (!isPostEditor) {
            return;
        }

        // 타이틀/서브타이틀/고유주소를 하나의 카드로 묶기
        var $titleDiv = $('#titlediv');
        var $subtitleField = $('.borobill-subtitle-field').first();
        if ($titleDiv.length && !$subtitleField.closest('#borobill-post-header-card').length) {
            var $slugInTitle = $titleDiv.find('#edit-slug-box').first();
            if (!$slugInTitle.length) {
                $slugInTitle = $('#edit-slug-box').first();
            }

            var $headerCard = $(
                '<section id="borobill-post-header-card" class="borobill-post-header-card">' +
                    '<div class="borobill-post-header-card__row borobill-post-header-card__row--title">' +
                        '<div class="borobill-post-header-card__label">타이틀</div>' +
                        '<div class="borobill-post-header-card__content borobill-post-header-card__title-content"></div>' +
                    '</div>' +
                    '<div class="borobill-post-header-card__row borobill-post-header-card__row--subtitle">' +
                        '<div class="borobill-post-header-card__label">서브타이틀</div>' +
                        '<div class="borobill-post-header-card__content borobill-post-header-card__subtitle-content"></div>' +
                    '</div>' +
                    '<div class="borobill-post-header-card__row borobill-post-header-card__row--slug">' +
                        '<div class="borobill-post-header-card__label">고유주소</div>' +
                        '<div class="borobill-post-header-card__content borobill-post-header-card__slug-content"></div>' +
                    '</div>' +
                '</section>'
            );

            $titleDiv.before($headerCard);
            $headerCard.find('.borobill-post-header-card__title-content').append($titleDiv);
            if ($subtitleField.length) {
                $headerCard.find('.borobill-post-header-card__subtitle-content').append($subtitleField);
            }
            if ($slugInTitle.length) {
                $headerCard.find('.borobill-post-header-card__slug-content').append($slugInTitle);
            } else {
                $headerCard.find('.borobill-post-header-card__row--slug').hide();
            }
        }

        var $postDivRich = $('#postdivrich');
        if (!$postDivRich.length || $('#borobill-editor-preview-wrap').length) {
            return;
        }

        var $wrap = $('<div id="borobill-editor-preview-wrap" class="borobill-editor-preview-wrap"></div>');
        $postDivRich.before($wrap);
        $wrap.append($postDivRich);

        var STORAGE_KEY = 'borobill_preview_collapsed';

        var $preview = $(
            '<section id="borobill-live-preview" class="borobill-live-preview">' +
                '<div class="borobill-live-preview__head">' +
                    '<strong>실시간 미리보기(저장본)</strong>' +
                    '<div class="borobill-live-preview__actions">' +
                        '<a class="button button-small borobill-open-link" href="#" target="_blank" rel="noopener noreferrer">새 창으로 보기</a>' +
                        '<button type="button" class="button button-small borobill-preview-refresh">새로고침</button>' +
                        '<button type="button" class="button button-small borobill-preview-collapse" aria-label="미리보기 접기" title="글쓰기 영역 넓히기">접기</button>' +
                    '</div>' +
                '</div>' +
                '<p class="borobill-live-preview__notice">본문에 h2로 작성한 내용이 index 개요로 표기됩니다.</p>' +
                '<div class="borobill-live-preview__body">' +
                    '<iframe title="게시글 미리보기" class="borobill-live-preview__iframe" src="about:blank"></iframe>' +
                    '<div class="borobill-live-preview__empty" style="display:none;">게시글 URL이 아직 없습니다. 초안 저장 후 확인하세요.</div>' +
                '</div>' +
            '</section>'
        );

        var $toggleStrip = $(
            '<div class="borobill-preview-toggle-strip" style="display:none;" aria-hidden="true">' +
                '<button type="button" class="button borobill-preview-expand" aria-label="미리보기 펼치기">미리보기 보기</button>' +
            '</div>'
        );

        $wrap.append($preview);
        $wrap.append($toggleStrip);

        function setPreviewCollapsed(collapsed) {
            if (collapsed) {
                $wrap.addClass('is-preview-collapsed');
                $preview.attr('aria-hidden', 'true');
                $toggleStrip.show().attr('aria-hidden', 'false');
            } else {
                $wrap.removeClass('is-preview-collapsed');
                $preview.attr('aria-hidden', 'false');
                $toggleStrip.hide().attr('aria-hidden', 'true');
            }
            try {
                localStorage.setItem(STORAGE_KEY, collapsed ? '1' : '0');
            } catch (e) {}
        }

        function isPreviewCollapsed() {
            return $wrap.hasClass('is-preview-collapsed');
        }

        $preview.on('click', '.borobill-preview-collapse', function (e) {
            e.preventDefault();
            setPreviewCollapsed(true);
        });

        $toggleStrip.on('click', '.borobill-preview-expand', function (e) {
            e.preventDefault();
            setPreviewCollapsed(false);
        });

        var saved = false;
        try {
            saved = localStorage.getItem(STORAGE_KEY) === '1';
        } catch (e) {}
        if (saved) {
            setPreviewCollapsed(true);
        }

        var autoRefreshTimer = null;
        var lastAutosaveAt = 0;

        function getPreviewUrl() {
            var permalink = $('#sample-permalink a').attr('href') || '';
            var previewBtn = $('#post-preview').attr('href') || '';
            var url = permalink || previewBtn;

            function isLikelyHome(u) {
                try {
                    var p = new URL(u, window.location.origin);
                    var path = (p.pathname || '').replace(/\/+$/, '') || '/';
                    var hasMeaningfulQuery = Array.from(p.searchParams.keys()).some(function (k) {
                        return k && k !== '_bbpv';
                    });
                    return path === '/' && !hasMeaningfulQuery;
                } catch (e) {
                    return false;
                }
            }

            if (!url) {
                // permalink/preview 버튼 링크가 아직 없으면 post_ID로 fallback
                var pid0 = parseInt($('#post_ID').val() || '0', 10);
                if (pid0 > 0) {
                    url = '/?p=' + pid0;
                } else {
                    return '';
                }
            }

            try {
                var parsed = new URL(url, window.location.origin);
                var status = ($('#post_status').val() || '').toString();

                // 일부 환경에서 preview 버튼 href가 메인으로 잡히는 이슈 방지: post_ID 기반으로 강제
                if (isLikelyHome(parsed.toString())) {
                    var pid = parseInt($('#post_ID').val() || '0', 10);
                    if (pid > 0) {
                        parsed = new URL('/?p=' + pid, window.location.origin);
                    } else {
                        // 새 글 추가 화면에서 ID를 못 얻으면 메인은 절대 노출하지 않음
                        return '';
                    }
                }

                if (status !== 'publish') {
                    parsed.searchParams.set('preview', 'true');
                }
                // 실시간 미리보기 iframe용: 프론트에서 관리자 바 숨김
                parsed.searchParams.set('borobill_preview_frame', '1');
                // 스타일/본문 변경 즉시 반영되도록 캐시 무효화
                parsed.searchParams.set('_bbpv', String(Date.now()));
                return parsed.toString();
            } catch (e) {
                return url;
            }
        }

        function maybeTriggerAutosave() {
            // 클래식 에디터/워드프레스 기본 autosave가 있으면, 입력 즉시 저장 → iframe에서 바로 반영
            var now = Date.now();
            if (now - lastAutosaveAt < AUTOSAVE_THROTTLE_MS) return;

            try {
                if (window.wp && wp.autosave && wp.autosave.server && typeof wp.autosave.server.triggerSave === 'function') {
                    lastAutosaveAt = now;
                    wp.autosave.server.triggerSave();
                }
            } catch (e) {
                // ignore
            }
        }

        function hasDraftContent() {
            var title = ($('#title').val() || '').toString().trim();
            var subtitle = ($('#borobill_subtitle').val() || '').toString().trim();
            if (title || subtitle) return true;

            // TinyMCE 우선
            try {
                if (window.tinymce) {
                    var ed = tinymce.get('content');
                    if (ed && typeof ed.getContent === 'function') {
                        var txt = (ed.getContent({ format: 'text' }) || '').toString().trim();
                        if (txt) return true;
                    }
                }
            } catch (e) {}

            // Textarea fallback
            var content = ($('#content').val() || '').toString().trim();
            return !!content;
        }

        function refreshPreview() {
            // 새 글 추가 화면에서 처음 진입 시: 메인 대신 비어있는 상태로 유지
            if (!hasDraftContent()) {
                var $iframe0 = $preview.find('.borobill-live-preview__iframe');
                var $empty0 = $preview.find('.borobill-live-preview__empty');
                var $open0 = $preview.find('.borobill-open-link');
                $iframe0.hide().attr('src', 'about:blank');
                $open0.hide();
                $empty0
                    .text('작성 시작하면 미리보기가 표시됩니다. (자동저장 후 반영)')
                    .show();
                return;
            }

            var url = getPreviewUrl();
            var $iframe = $preview.find('.borobill-live-preview__iframe');
            var $empty = $preview.find('.borobill-live-preview__empty');
            var $open = $preview.find('.borobill-open-link');

            if (!url) {
                $iframe.hide();
                $empty.show();
                $open.hide();
                return;
            }

            $empty.hide();
            $iframe.show().attr('src', url);
            $open.attr('href', url).show();
        }

        function scheduleAutoRefresh() {
            clearTimeout(autoRefreshTimer);
            autoRefreshTimer = setTimeout(function () {
                maybeTriggerAutosave();
                // autosave 반영 시간을 약간 주고 갱신
                setTimeout(refreshPreview, 450);
            }, AUTO_REFRESH_DEBOUNCE_MS);
        }

        $preview.on('click', '.borobill-preview-refresh', function (e) {
            e.preventDefault();
            refreshPreview();
        });

        // 저장/업데이트 직후 프리뷰 새로고침
        $(document).on('click', '#publish, #save-post', function () {
            setTimeout(refreshPreview, 1200);
        });

        // 입력 변경 시 자동 미리보기 갱신 (새 글 추가 화면에서도 즉시 single 형태로 노출)
        $(document).on('input', '#title, #borobill_subtitle', scheduleAutoRefresh);
        $(document).on('input', '#content', scheduleAutoRefresh);

        // TinyMCE가 켜져 있으면 에디터 이벤트도 연결
        (function bindTinyMce() {
            if (!window.tinymce) return;
            var tries = 0;
            var maxTries = 40;
            var timer = setInterval(function () {
                tries += 1;
                var ed = tinymce.get('content');
                if (ed && ed.on) {
                    clearInterval(timer);
                    ed.on('keyup change input undo redo SetContent', scheduleAutoRefresh);
                    return;
                }
                if (tries >= maxTries) {
                    clearInterval(timer);
                }
            }, 250);
        })();

        refreshPreview();
    });
})(jQuery);

