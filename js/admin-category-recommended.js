(function ($) {
    'use strict';

    function fillSelect($select, options, placeholder, selected) {
        $select.empty();
        $select.append($('<option>', { value: '0', text: placeholder }));
        (options || []).forEach(function (opt) {
            $select.append(
                $('<option>', {
                    value: String(opt.id),
                    text: opt.name,
                    selected: String(selected) === String(opt.id),
                })
            );
        });
    }

    function getRow($el) {
        return $el.closest('.borobill-recommended-rank');
    }

    function syncPostValue($row) {
        var val = $row.find('.borobill-rec-post').val() || '0';
        $row.find('.borobill-rec-post-value').val(val);
    }

    function resetChildAndPost($row) {
        var $child = $row.find('.borobill-rec-child');
        var $post = $row.find('.borobill-rec-post');
        fillSelect($child, [], '— 2차 카테고리 —', 0);
        fillSelect($post, [], '— 게시글 선택 —', 0);
        $child.prop('disabled', true);
        $post.prop('disabled', true);
        $row.find('.borobill-rec-post-value').val('0');
    }

    $(document).on('change', '.borobill-rec-parent', function () {
        var $row = getRow($(this));
        var parentId = $(this).val();
        var $child = $row.find('.borobill-rec-child');

        resetChildAndPost($row);

        if (!parentId || parentId === '0') {
            return;
        }

        $.post(borobillCategoryRec.ajaxUrl, {
            action: 'borobill_rec_category_children',
            nonce: borobillCategoryRec.nonce,
            parent_id: parentId,
        }).done(function (res) {
            if (!res || !res.success) {
                return;
            }
            fillSelect($child, res.data, '— 2차 카테고리 —', 0);
            $child.prop('disabled', false);
        });
    });

    $(document).on('change', '.borobill-rec-child', function () {
        var $row = getRow($(this));
        var catId = $(this).val();
        var $post = $row.find('.borobill-rec-post');

        fillSelect($post, [], '— 게시글 선택 —', 0);
        $post.prop('disabled', true);
        $row.find('.borobill-rec-post-value').val('0');

        if (!catId || catId === '0') {
            return;
        }

        $.post(borobillCategoryRec.ajaxUrl, {
            action: 'borobill_rec_category_posts',
            nonce: borobillCategoryRec.nonce,
            cat_id: catId,
        }).done(function (res) {
            if (!res || !res.success) {
                return;
            }
            fillSelect($post, res.data, '— 게시글 선택 —', 0);
            $post.prop('disabled', false);
        });
    });

    $(document).on('change', '.borobill-rec-post', function () {
        syncPostValue(getRow($(this)));
    });
})(jQuery);
