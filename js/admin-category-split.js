(function ($) {
    'use strict';

    if (typeof borobillCategoryRec === 'undefined') {
        return;
    }

    var cfg = borobillCategoryRec;
    var $panel = null;
    var activeTermId = 0;

    function initLayout() {
        var $container = $('#col-container');
        $panel = $('#borobill-category-panel');

        var $tabs = $('.borobill-category-tabs');
        var $headerEnd = $('.wrap > hr.wp-header-end');
        if ($tabs.length && $headerEnd.length) {
            $tabs.insertAfter($headerEnd);
        }

        if (!$container.length || !$panel.length) {
            return;
        }

        $panel.appendTo($container);
        $container.addClass('borobill-category-split-ready');
    }

    function getTermIdFromRow($row) {
        var id = $row.attr('id') || '';
        if (id.indexOf('tag-') === 0) {
            return parseInt(id.replace('tag-', ''), 10) || 0;
        }

        var href = $row.find('a.row-title').attr('href') || '';
        var match = href.match(/tag_ID=(\d+)/);
        return match ? parseInt(match[1], 10) : 0;
    }

    function setActiveRow(termId) {
        $('#the-list tr').removeClass('borobill-category-row--active');
        if (termId > 0) {
            $('#tag-' + termId).addClass('borobill-category-row--active');
        }
    }

    function showLoading() {
        $panel.html(
            '<div class="borobill-category-panel__placeholder borobill-category-panel__placeholder--loading"><p>' +
                cfg.panelLoading +
                '</p></div>'
        );
    }

    function loadPanel(termId, force) {
        if (!termId || (!force && termId === activeTermId)) {
            return;
        }

        activeTermId = termId;
        setActiveRow(termId);
        showLoading();

        $.post(cfg.ajaxUrl, {
            action: cfg.loadPanelAction,
            nonce: cfg.nonce,
            term_id: termId,
            borobill_tab: (new URLSearchParams(window.location.search)).get('borobill_tab') || 'article',
        })
            .done(function (res) {
                if (!res || !res.success || !res.data || !res.data.html) {
                    activeTermId = 0;
                    setActiveRow(0);
                    $panel.html(
                        '<div class="borobill-category-panel__placeholder"><p>' +
                            cfg.panelPlaceholder +
                            '</p></div>'
                    );
                    return;
                }

                $panel.html(res.data.html);
            })
            .fail(function () {
                activeTermId = 0;
                setActiveRow(0);
                $panel.html(
                    '<div class="borobill-category-panel__placeholder"><p>' +
                        cfg.panelPlaceholder +
                        '</p></div>'
                );
            });
    }

    function collectFormData($form) {
        // 게시글 select 는 disabled 될 수 있어 hidden(.borobill-rec-post-value) 값을 우선 사용
        $form.find('.borobill-recommended-rank').each(function () {
            var $row = $(this);
            var $post = $row.find('.borobill-rec-post');
            if ($post.length) {
                $row.find('.borobill-rec-post-value').val($post.val() || '0');
            }
        });

        var data = {
            action: cfg.savePanelAction,
            nonce: cfg.nonce,
            term_id: parseInt($form.find('input[name="tag_ID"]').val(), 10) || 0,
        };

        for (var rank = 1; rank <= 3; rank++) {
            data['borobill_recommended_post_' + rank] =
                $form.find('input[name="borobill_recommended_post_' + rank + '"]').val() || '0';
        }

        return data;
    }

    $(function () {
        if (!$('body').hasClass('taxonomy-category') || !$('body').hasClass('edit-tags-php')) {
            return;
        }

        initLayout();

        $(document).on(
            'click',
            '#the-list a.row-title, #the-list .row-actions .edit a',
            function (event) {
                var $row = $(this).closest('tr');
                var termId = getTermIdFromRow($row);

                if (!termId) {
                    return;
                }

                event.preventDefault();
                loadPanel(termId);
            }
        );

        $panel.on('submit', '#borobill-category-panel-form', function (event) {
            event.preventDefault();

            var $form = $(this);
            var $button = $form.find('input[type="submit"], button[type="submit"]');
            var data = collectFormData($form);

            if (!data.term_id) {
                return;
            }

            $button.prop('disabled', true);

            $.post(cfg.ajaxUrl, data)
                .done(function (res) {
                    if (res && res.success) {
                        window.wp.a11y && window.wp.a11y.speak
                            ? window.wp.a11y.speak(cfg.panelSaveSuccess)
                            : alert(cfg.panelSaveSuccess);
                        if (data.term_id) {
                            loadPanel(data.term_id);
                        }
                    } else {
                        alert(cfg.panelSaveError);
                    }
                })
                .fail(function () {
                    alert(cfg.panelSaveError);
                })
                .always(function () {
                    $button.prop('disabled', false);
                });
        });

        $panel.on('click', '#borobill-category-panel-reset', function () {
            var $form = $('#borobill-category-panel-form');
            var termId = parseInt($form.find('input[name="tag_ID"]').val(), 10) || 0;
            var $button = $(this);

            if (!termId) {
                return;
            }

            if (!window.confirm(cfg.panelResetConfirm || '추천 설정을 초기화할까요?')) {
                return;
            }

            $button.prop('disabled', true);

            $.post(cfg.ajaxUrl, {
                action: cfg.resetPanelAction,
                nonce: cfg.nonce,
                term_id: termId,
            })
                .done(function (res) {
                    if (!res || !res.success) {
                        alert(cfg.panelResetError);
                        return;
                    }

                    window.wp.a11y && window.wp.a11y.speak
                        ? window.wp.a11y.speak(cfg.panelResetSuccess)
                        : alert(cfg.panelResetSuccess);

                    loadPanel(termId, true);
                })
                .fail(function () {
                    alert(cfg.panelResetError);
                })
                .always(function () {
                    $button.prop('disabled', false);
                });
        });
    });
})(jQuery);
