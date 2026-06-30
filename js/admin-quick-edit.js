jQuery(function ($) {
    var frame;

    function populateQuickEditFields(postId) {
        var $row = $('#post-' + postId);
        var $qeRow = $('#edit-' + postId);
        if (!$qeRow.length) return;

        var $thumbTd = $row.find('.column-thumbnail .borobill-thumb-cell');
        var thumbId = ($thumbTd.attr('data-thumb-id') !== undefined && $thumbTd.attr('data-thumb-id') !== '') ? $thumbTd.attr('data-thumb-id') : '';
        $qeRow.find('.borobill-qe-thumb-id').val(thumbId);
        $qeRow.find('.borobill-qe-thumb-removed').val('0');

        var thumbSrc = $thumbTd.find('img').attr('src') || '';
        if (thumbSrc) {
            $qeRow.find('.borobill-qe-thumb').attr('src', thumbSrc).show();
        } else {
            $qeRow.find('.borobill-qe-thumb').attr('src', '').hide();
        }

        var $rt = $row.find('.borobill-col-rt');
        var rt = 3;
        if ($rt.length) {
            var rtVal = $rt.attr('data-reading-time');
            rt = (rtVal !== undefined && rtVal !== '') ? parseInt(rtVal, 10) : 3;
            if (isNaN(rt) || rt < 0) rt = 3;
        }
        $qeRow.find('.borobill-qe-reading-time').val(rt);

        var $views = $row.find('.borobill-col-views');
        var views = 0;
        if ($views.length) {
            var vVal = $views.attr('data-views');
            views = (vVal !== undefined && vVal !== '') ? parseInt(vVal, 10) : 0;
            if (isNaN(views) || views < 0) views = 0;
        }
        $qeRow.find('.borobill-qe-views').val(views);
    }

    // 빠른편집 열릴 때 현재 글의 썸네일·리딩타임·조회수 값을 필드에 채우기 (WP DOM 반영 후 한 번 더 실행)
    $(document).on('click', 'a.editinline', function () {
        var $row = $(this).closest('tr');
        var postId = $row.attr('id') ? $row.attr('id').replace('post-', '') : '';

        inlineEditPost.revert();

        if (postId) {
            populateQuickEditFields(postId);
            setTimeout(function () {
                populateQuickEditFields(postId);
            }, 0);
        }
    });

    // 이미지 선택 버튼
    $(document).on('click', '.borobill-set-thumb', function (e) {
        e.preventDefault();

        var $wrap = $(this).closest('.inline-edit-col');

        if (!frame) {
            frame = wp.media({
                title: '특성이미지 선택',
                button: { text: '사용하기' },
                multiple: false
            });
        }

        // 클릭할 때마다 현재 행($wrap)을 기준으로 선택 결과 적용
        frame.off('select');
        frame.on('select', function () {
            var attachment = frame.state().get('selection').first().toJSON();
            $wrap.find('.borobill-qe-thumb-id').val(attachment.id);
            $wrap.find('.borobill-qe-thumb-removed').val('0');
            if (attachment.sizes && attachment.sizes.thumbnail) {
                $wrap.find('.borobill-qe-thumb').attr('src', attachment.sizes.thumbnail.url).show();
            } else {
                $wrap.find('.borobill-qe-thumb').attr('src', attachment.url).show();
            }
        });

        frame.open();
    });

    // 제거 버튼: 사용자가 명시적으로 제거한 경우만 저장 시 썸네일 삭제
    $(document).on('click', '.borobill-remove-thumb', function () {
        var $wrap = $(this).closest('.inline-edit-col');
        $wrap.find('.borobill-qe-thumb-id').val('');
        $wrap.find('.borobill-qe-thumb-removed').val('1');
        $wrap.find('.borobill-qe-thumb').attr('src', '').hide();
    });
});


