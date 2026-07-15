(function ($) {
    'use strict';

    var cfg = window.borobillNavMenuGnb || {};
    var tablePattern = /^[a-z][a-z0-9_]*$/;

    function getItemId($item) {
        return String($item.find('.menu-item-data-db-id').val() || '');
    }

    function getItemData(itemId) {
        if (cfg.items && cfg.items[itemId]) {
            return cfg.items[itemId];
        }
        return {};
    }

    function isSecondaryItem($item) {
        var parentId = parseInt($item.find('.menu-item-data-parent-id').val(), 10) || 0;
        return parentId > 0 || $item.menuItemDepth() > 0;
    }

    function isNewBoardItem($item, data) {
        if ($item.hasClass('pending')) {
            return true;
        }
        if (data && data.is_new === false) {
            return false;
        }
        if (data && data.is_new) {
            return true;
        }
        if (data && (data.db_table || data.db_slug)) {
            return false;
        }
        return true;
    }

    function getDataUsageDefinitions() {
        return cfg.dataUsage || {};
    }

    function getDataUsageDef(usageKey) {
        var defs = getDataUsageDefinitions();
        if (defs[usageKey]) {
            return defs[usageKey];
        }
        return {
            label: '게시글 본문',
            table: 'wp_posts',
            page_type: 'board',
            creates_category: true
        };
    }

    function buildDataUsageSelectOptions(selected) {
        var html = '';
        var defs = getDataUsageDefinitions();

        $.each(defs, function (key, def) {
            html += '<option value="' + key + '"' + (key === selected ? ' selected' : '') + '>' + def.label + '</option>';
        });

        return html;
    }

    function getUsageLabel(pageType, dataUsage) {
        if (dataUsage) {
            return getDataUsageDef(dataUsage).label;
        }
        if (pageType === 'board') {
            return '게시글 본문';
        }
        if (pageType === 'code') {
            return '게시글 메타';
        }
        return '—';
    }

    function setUsageDisplay($display, label) {
        if (!$display.find('.borobill-db-slug-display__value').length) {
            $display.html(
                '<span class="borobill-db-slug-display__value"></span>' +
                '<span class="borobill-db-slug-display__arrow" aria-hidden="true"></span>'
            );
        }
        $display.find('.borobill-db-slug-display__value').text(label);
    }

    function updateDataUsageFieldState($item) {
        var $field = $item.find('.borobill-data-usage-field');
        var $wrap = $item.find('.borobill-secondary-options');
        var $select = $wrap.find('.borobill-data-usage');
        var $display = $wrap.find('.borobill-db-slug-display');
        var $pageType = $wrap.find('.borobill-page-type');
        var itemId = getItemId($item);
        var data = getItemData(itemId);
        var isNew = isNewBoardItem($item, data);
        var dataUsage = data.data_usage || $select.val() || 'post_content';
        var usageDef = getDataUsageDef(dataUsage);
        var usageLabel = data.db_usage || usageDef.label;

        if ($select.length) {
            $select.val(dataUsage);
        }
        if ($pageType.length) {
            $pageType.val(usageDef.page_type || 'board');
        }

        if (!isNew) {
            $field.addClass('is-locked');
            if ($select.length) {
                $select.prop('disabled', true).hide();
            }
            if (!$wrap.find('.borobill-data-usage-hidden').length && $select.length) {
                $select.after(
                    '<input type="hidden" class="borobill-data-usage-hidden" name="borobill-data-usage[' + itemId + ']" value="' + dataUsage + '" />'
                );
            } else {
                $wrap.find('.borobill-data-usage-hidden').val(dataUsage);
            }
            setUsageDisplay($display, usageLabel);
        } else {
            $field.removeClass('is-locked');
            $wrap.find('.borobill-data-usage-hidden').remove();
            if ($select.length) {
                $select.prop('disabled', false).show();
            }
            $display.empty();
        }
    }

    function updateDbSlugFieldState($item) {
        updateDataUsageFieldState($item);
    }

    function toggleSecondaryOptions($item) {
        var $wrap = $item.find('.borobill-secondary-options');
        if (!$wrap.length) {
            return;
        }

        if (isSecondaryItem($item)) {
            $wrap.addClass('is-visible');
            updateDbSlugFieldState($item);
        } else {
            $wrap.removeClass('is-visible');
        }
    }

    function ensurePageTypeHidden($item, $wrap) {
        var itemId = getItemId($item);
        var data = getItemData(itemId);
        var pageType = data.page_type || 'board';
        var $pageType = $wrap.find('.borobill-page-type');

        $item.find('.borobill-page-type-field').remove();

        if (!$pageType.length) {
            $wrap.prepend(
                '<input type="hidden" class="borobill-page-type" name="borobill-page-type[' + itemId + ']" value="' + pageType + '" />'
            );
            return;
        }

        if ($pageType.is('select')) {
            pageType = $pageType.val() || pageType;
            $pageType.remove();
            $wrap.prepend(
                '<input type="hidden" class="borobill-page-type" name="borobill-page-type[' + itemId + ']" value="' + pageType + '" />'
            );
            return;
        }

        $pageType.val(pageType);
    }

    function initSecondaryOptions($item) {
        if ($item.find('.borobill-secondary-options').length) {
            if (!$item.find('.borobill-data-usage').length) {
                $item.find('.borobill-secondary-options').remove();
                initSecondaryOptions($item);
                return;
            }

            var $wrap = $item.find('.borobill-secondary-options').first();
            var $moveCombo = $item.find('.field-move-combo').first();
            if ($moveCombo.length && !$wrap.prev().is($moveCombo)) {
                $moveCombo.after($wrap);
            }
            $wrap.addClass('borobill-menu-row');
            ensurePageTypeHidden($item, $wrap);
            toggleSecondaryOptions($item);
            updateDbSlugFieldState($item);
            return;
        }

        var itemId = getItemId($item);
        var data = getItemData(itemId);
        var pageType = data.page_type || 'board';
        var dataUsage = data.data_usage || 'post_content';

        var html =
            '<div class="borobill-secondary-options borobill-menu-row" style="display:none;">' +
                '<input type="hidden" class="borobill-page-type" name="borobill-page-type[' + itemId + ']" value="' + pageType + '" />' +
                '<p class="description description-wide borobill-data-usage-field">' +
                    '<label>용도</label>' +
                    '<select class="borobill-data-usage widefat" name="borobill-data-usage[' + itemId + ']">' +
                        buildDataUsageSelectOptions(dataUsage) +
                    '</select>' +
                    '<div class="borobill-db-slug-display widefat" aria-hidden="true">' +
                        '<span class="borobill-db-slug-display__value"></span>' +
                        '<span class="borobill-db-slug-display__arrow" aria-hidden="true"></span>' +
                    '</div>' +
                    '<span class="borobill-db-slug-error"></span>' +
                '</p>' +
            '</div>';

        var $moveCombo = $item.find('.field-move-combo').first();
        if ($moveCombo.length) {
            $moveCombo.after(html);
        } else {
            var $parentField = $item.find('p.description-wide').has('.borobill-category-level').first();
            if ($parentField.length) {
                $parentField.after(html);
            }
        }

        toggleSecondaryOptions($item);
    }

    function getDefaultSecondaryParentId($item) {
        var $prevTop = $item.prevAll('.menu-item-depth-0').first();
        if ($prevTop.length) {
            return parseInt($prevTop.find('.menu-item-data-db-id').val(), 10) || 0;
        }

        var $firstTop = $('#menu-to-edit > li.menu-item-depth-0').first();
        if ($firstTop.length) {
            return parseInt($firstTop.find('.menu-item-data-db-id').val(), 10) || 0;
        }

        return 0;
    }

    function applyCategoryLevel($item, level) {
        var $wpParent = $item.find('.edit-menu-item-parent');
        var nextParentId = level === 'secondary' ? String(getDefaultSecondaryParentId($item)) : '0';

        if (!$wpParent.length || $wpParent.val() === nextParentId) {
            toggleSecondaryOptions($item);
            return;
        }

        $wpParent.val(nextParentId);
        if (typeof wpNavMenu !== 'undefined' && typeof wpNavMenu.changeMenuParent === 'function') {
            wpNavMenu.changeMenuParent($wpParent);
        }

        window.setTimeout(function () {
            toggleSecondaryOptions($item);
        }, 0);
    }

    function syncCategorySelect($item) {
        var $level = $item.find('.borobill-category-level');
        if (!$level.length) {
            return;
        }

        var currentParentId = parseInt($item.find('.menu-item-data-parent-id').val(), 10) || 0;
        $level.val(currentParentId > 0 ? 'secondary' : 'primary');
        toggleSecondaryOptions($item);
    }

    function initCategorySelects($scope) {
        ($scope || $('#menu-to-edit > li.menu-item')).each(function () {
            var $item = $(this);
            var $parentField = $item.find('p.description-wide').has('.edit-menu-item-parent').first();

            if (!$parentField.length || $item.find('.borobill-category-level').length) {
                syncCategorySelect($item);
                initSecondaryOptions($item);
                return;
            }

            $parentField.find('label[for^="edit-menu-item-parent-"]').contents().filter(function () {
                return this.nodeType === 3;
            }).first().replaceWith('카테고리 선택');

            $parentField.find('.edit-menu-item-parent').after(
                '<select class="borobill-category-level widefat">' +
                    '<option value="primary">1차 카테고리</option>' +
                    '<option value="secondary">2차 카테고리</option>' +
                '</select>'
            );

            syncCategorySelect($item);
            initSecondaryOptions($item);
        });
    }

    function clearMenuItemFields($item) {
        $item.find('.edit-menu-item-title').val('');
        $item.find('.edit-menu-item-url').val('');
        $item.find('.edit-menu-item-attr-title').val('');
        $item.find('.edit-menu-item-description').val('');
        $item.find('.edit-menu-item-classes').val('');
        $item.find('.edit-menu-item-xfn').val('');
        $item.find('.menu-item-title').text('');
        $item.find('.borobill-data-usage-field').removeClass('is-locked');
        $item.find('.borobill-data-usage').val('post_content').prop('disabled', false).show();
        $item.find('.borobill-data-usage-hidden').remove();
        $item.find('.borobill-db-slug-display').empty();
        $item.find('.borobill-page-type').val('board');
        $item.find('.borobill-db-slug-error').text('');
    }

    function titleToTableName(title) {
        var s = (title || '').toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');

        if (!s) {
            s = 'board_' + Date.now().toString(36).slice(-6);
        }
        if (s.indexOf('wp_') !== 0) {
            s = 'wp_' + s;
        }

        return s.replace(/[^a-z0-9_]/g, '');
    }

    function getDefaultParentForNewSecondary() {
        var $last = $('#menu-to-edit > li.menu-item-depth-0').last();
        if ($last.length) {
            return parseInt($last.find('.menu-item-data-db-id').val(), 10) || 0;
        }
        return 0;
    }

    function buildModalOrderOptions(categoryLevel) {
        var total;
        var defaultVal;
        var html = '';
        var i;

        if (categoryLevel === 'primary') {
            total = $('#menu-to-edit > li.menu-item-depth-0').length + 1;
            defaultVal = total;
        } else {
            var parentId = getDefaultParentForNewSecondary();
            total = 0;
            $('#menu-to-edit > li.menu-item').each(function () {
                if (parseInt($(this).find('.menu-item-data-parent-id').val(), 10) === parentId) {
                    total += 1;
                }
            });
            total += 1;
            defaultVal = total;
        }

        for (i = 1; i <= total; i += 1) {
            html += '<option value="' + i + '"' + (i === defaultVal ? ' selected' : '') + '>' + i + ' / ' + total + '</option>';
        }

        return html;
    }

    function syncModalUsageFields() {
        var categoryLevel = $('#borobill-modal-category').val();
        var $usageRow = $('#borobill-modal-usage-row');

        $('#borobill-modal-order').html(buildModalOrderOptions(categoryLevel));

        if (categoryLevel !== 'secondary') {
            $usageRow.removeClass('is-visible').hide();
            return;
        }

        $usageRow.addClass('is-visible').show();
    }

    function ensureAddMenuModal() {
        if ($('#borobill-add-menu-modal').length && !$('#borobill-modal-data-usage').length) {
            $('#borobill-add-menu-modal').remove();
        }

        if ($('#borobill-add-menu-modal').length) {
            return;
        }

        var html =
            '<div id="borobill-add-menu-modal" class="borobill-add-menu-modal" style="display:none;" aria-hidden="true">' +
                '<div class="borobill-add-menu-modal__backdrop"></div>' +
                '<div class="borobill-add-menu-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="borobill-add-menu-modal-title">' +
                    '<h2 id="borobill-add-menu-modal-title" class="borobill-add-menu-modal__title">메뉴 추가</h2>' +
                    '<div class="borobill-add-menu-modal__body menu-item-settings mod_dg">' +
                        '<p class="description description-wide borobill-modal-row-title">' +
                            '<label for="borobill-modal-title">메뉴명<br />' +
                            '<input type="text" id="borobill-modal-title" class="widefat edit-menu-item-title" autocomplete="off" />' +
                            '</label>' +
                        '</p>' +
                        '<div class="field-move-combo description-group borobill-modal-row-2">' +
                            '<p class="description description-wide">' +
                                '<label for="borobill-modal-category">카테고리 선택</label>' +
                                '<select id="borobill-modal-category" class="widefat borobill-category-level">' +
                                    '<option value="primary">1차 카테고리</option>' +
                                    '<option value="secondary">2차 카테고리</option>' +
                                '</select>' +
                            '</p>' +
                            '<p class="description description-wide">' +
                                '<label for="borobill-modal-order">메뉴순서</label>' +
                                '<select id="borobill-modal-order" class="widefat edit-menu-item-order"></select>' +
                            '</p>' +
                        '</div>' +
                        '<div id="borobill-modal-usage-row" class="borobill-secondary-options borobill-menu-row">' +
                            '<p class="description description-wide borobill-data-usage-field">' +
                                '<label for="borobill-modal-data-usage">용도</label>' +
                                '<select id="borobill-modal-data-usage" class="widefat borobill-data-usage"></select>' +
                            '</p>' +
                        '</div>' +
                        '<p id="borobill-modal-error" class="borobill-add-menu-modal__error"></p>' +
                    '</div>' +
                    '<div class="borobill-add-menu-modal__footer">' +
                        '<button type="button" class="button button-primary" id="borobill-modal-submit">추가</button>' +
                        '<button type="button" class="button" id="borobill-modal-cancel">취소</button>' +
                    '</div>' +
                '</div>' +
            '</div>';

        $('body').append(html);
        $('#borobill-modal-data-usage').html(buildDataUsageSelectOptions('post_content'));
    }

    function resetAddMenuModal() {
        var $modal = $('#borobill-add-menu-modal');

        $modal.find('#borobill-modal-title').val('');
        $modal.find('#borobill-modal-category').val('primary');
        $modal.find('#borobill-modal-data-usage').val('post_content');
        $modal.find('#borobill-modal-error').text('');
        syncModalUsageFields();
    }

    function openAddMenuModal() {
        ensureAddMenuModal();
        $('#borobill-modal-data-usage').html(buildDataUsageSelectOptions('post_content'));
        resetAddMenuModal();
        $('#borobill-add-menu-modal').show().attr('aria-hidden', 'false');
        $('html, body').addClass('borobill-add-menu-modal-open');
        $('#borobill-modal-title').trigger('focus');
    }

    function closeAddMenuModal() {
        $('#borobill-add-menu-modal').hide().attr('aria-hidden', 'true');
        $('html, body').removeClass('borobill-add-menu-modal-open');
    }

    function checkDbSlugValue(slug, itemId, $error) {
        $error.text('');

        if (!slug) {
            return $.Deferred().resolve({ valid: true }).promise();
        }

        if (!tablePattern.test(slug)) {
            $error.text('MySQL 테이블명 형식만 입력할 수 있습니다. (예: wp_posts)');
            return $.Deferred().resolve({ valid: false }).promise();
        }

        return $.post(cfg.ajaxUrl, {
            action: 'borobill_check_menu_db_slug',
            nonce: cfg.nonce,
            slug: slug,
            item_id: itemId || 0
        }).then(function (res) {
            if (!res || !res.success) {
                $error.text((res && res.data && res.data.message) ? res.data.message : '중복 확인에 실패했습니다.');
                return { valid: false };
            }
            if (res.data && res.data.exists) {
                $error.text('이미 사용 중인 DB명입니다.');
                return { valid: false };
            }
            return { valid: true };
        });
    }

    function applyModalValuesToMenuItem($item, data) {
        $item.find('.edit-menu-item-title').val(data.title);
        $item.find('.menu-item-title').text(data.title);

        if (data.categoryLevel === 'secondary') {
            $item.find('.borobill-category-level').val('secondary');
            applyCategoryLevel($item, 'secondary');
        } else {
            $item.find('.borobill-category-level').val('primary');
            applyCategoryLevel($item, 'primary');
        }

        window.setTimeout(function () {
            var usageDef = getDataUsageDef(data.dataUsage);

            $item.find('.borobill-data-usage').val(data.dataUsage);
            $item.find('.borobill-page-type').val(usageDef.page_type || data.pageType);

            if ((usageDef.page_type || data.pageType) === 'code') {
                $item.find('.edit-menu-item-url').val('#');
            }

            updateDataUsageFieldState($item);

            if (typeof wpNavMenu !== 'undefined') {
                $('#menu-to-edit').updateOrderDropdown();
            }

            var $order = $item.find('.edit-menu-item-order');
            if ($order.length && typeof wpNavMenu !== 'undefined' && typeof wpNavMenu.changeMenuOrder === 'function') {
                $order.val(String(data.order));
                wpNavMenu.changeMenuOrder($order);
            }

            if ($item.hasClass('menu-item-edit-inactive')) {
                $item.find('.item-edit').trigger('click');
            }
        }, data.categoryLevel === 'secondary' ? 200 : 80);
    }

    function submitAddMenuModal() {
        var $modal = $('#borobill-add-menu-modal');
        var title = ($modal.find('#borobill-modal-title').val() || '').trim();
        var categoryLevel = $modal.find('#borobill-modal-category').val();
        var dataUsage = $modal.find('#borobill-modal-data-usage').val() || 'post_content';
        var usageDef = getDataUsageDef(dataUsage);
        var pageType = usageDef.page_type || 'board';
        var order = parseInt($modal.find('#borobill-modal-order').val(), 10) || 1;
        var $error = $modal.find('#borobill-modal-error');

        $error.text('');

        if (!title) {
            $error.text('메뉴명을 입력해 주세요.');
            return;
        }

        function createMenuItem() {
            if (typeof wpNavMenu === 'undefined') {
                return;
            }

            wpNavMenu.addLinkToMenu('#', title, wpNavMenu.addMenuItemToBottom, function () {
                var $item = $('#menu-to-edit > li.pending').last();
                if (!$item.length) {
                    $item = $('#menu-to-edit > li').last();
                }

                initCategorySelects($item);
                applyModalValuesToMenuItem($item, {
                    title: title,
                    categoryLevel: categoryLevel,
                    dataUsage: dataUsage,
                    pageType: pageType,
                    order: order
                });
                closeAddMenuModal();
            });
        }

        createMenuItem();
    }

    function insertAddMenuButton($saveBtn) {
        if (!$saveBtn.length || $saveBtn.siblings('.borobill-add-menu-item').length) {
            return;
        }
        $('<button type="button" class="button button-large borobill-add-menu-item">메뉴 추가</button>')
            .insertBefore($saveBtn)
            .on('click', function () {
                openAddMenuModal();
            });
    }

    function syncCategorySelectsFromDrag() {
        $('#menu-to-edit > li.menu-item').each(function () {
            var $item = $(this);
            var parentId = String(parseInt($item.find('.menu-item-data-parent-id').val(), 10) || 0);
            var $wpParent = $item.find('.edit-menu-item-parent');

            if ($wpParent.length) {
                $wpParent.val(parentId);
            }

            syncCategorySelect($item);
            toggleSecondaryOptions($item);
        });
    }

    function checkDbSlug($input) {
        var $item = $input.closest('li.menu-item');
        var $error = $item.find('.borobill-db-slug-error');
        var slug = ($input.val() || '').trim().toLowerCase();
        var itemId = getItemId($item);

        if ($input.prop('readonly') || $input.prop('disabled') || !$input.is(':visible')) {
            return $.Deferred().resolve({ valid: true }).promise();
        }

        if (!slug) {
            return $.Deferred().resolve({ valid: true }).promise();
        }

        return checkDbSlugValue(slug, itemId, $error);
    }

    $(document).on('change', '.borobill-category-level', function () {
        applyCategoryLevel($(this).closest('li.menu-item'), $(this).val());
    });

    $(document).on('change', 'li.menu-item .borobill-data-usage', function () {
        var $item = $(this).closest('li.menu-item');
        var usageDef = getDataUsageDef($(this).val());
        $item.find('.borobill-page-type').val(usageDef.page_type || 'board');
    });

    $(document).on('mousedown selectstart dragstart', '.borobill-db-slug-display, .borobill-data-usage-field.is-locked', function (e) {
        e.preventDefault();
    });

    $(document).on('menu-item-added', function (event, $menuMarkup) {
        initCategorySelects($menuMarkup);
    });

    $(function () {
        initCategorySelects();
        insertAddMenuButton($('#save_menu_header'));
        insertAddMenuButton($('#save_menu_footer'));
        ensureAddMenuModal();

        $(document).on('click', '#borobill-modal-cancel, #borobill-add-menu-modal .borobill-add-menu-modal__backdrop', function () {
            closeAddMenuModal();
        });

        $(document).on('click', '#borobill-modal-submit', function () {
            submitAddMenuModal();
        });

        $(document).on('change', '#borobill-modal-category', function () {
            syncModalUsageFields();
        });

        $(document).on('keydown', '#borobill-add-menu-modal', function (e) {
            if (e.key === 'Escape') {
                closeAddMenuModal();
            }
        });

        $('#menu-to-edit').on('sortstop', function () {
            window.setTimeout(syncCategorySelectsFromDrag, 0);
        });

        $('#update-nav-menu').on('click', '.menu-item-handle', function (e) {
            if ($(e.target).closest('a, input, button, .item-controls').length) {
                return;
            }

            var editLink = $(this).find('a.item-edit')[0];
            if (editLink && typeof wpNavMenu !== 'undefined' && typeof wpNavMenu.eventOnClickEditLink === 'function') {
                e.preventDefault();
                wpNavMenu.eventOnClickEditLink(editLink);
            }
        });
    });
})(jQuery);
