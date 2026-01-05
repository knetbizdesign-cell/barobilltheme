jQuery(function ($) {
    var frame;

    // 빠른편집 열릴 때 현재 글의 썸네일 ID를 숨은 필드에 채워 넣기
    $(document).on('click', 'a.editinline', function () {
        var $row   = $(this).closest('tr');
        var postId = $row.attr('id').replace('post-', '');

        inlineEditPost.revert(); // 기본 빠른편집 세팅

        var $qeRow   = $('#edit-' + postId);
        var $thumbTd = $row.find('.column-thumbnail .borobill-thumb-cell');
        var thumbId  = $thumbTd.data('thumb-id') || '';

        $qeRow.find('.borobill-qe-thumb-id').val(thumbId);

        var thumbSrc = $thumbTd.find('img').attr('src') || '';
        if (thumbSrc) {
            $qeRow.find('.borobill-qe-thumb').attr('src', thumbSrc).show();
        } else {
            $qeRow.find('.borobill-qe-thumb').hide();
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
            if (attachment.sizes && attachment.sizes.thumbnail) {
                $wrap.find('.borobill-qe-thumb').attr('src', attachment.sizes.thumbnail.url).show();
            } else {
                $wrap.find('.borobill-qe-thumb').attr('src', attachment.url).show();
            }
        });

        frame.open();
    });

    // 제거 버튼
    $(document).on('click', '.borobill-remove-thumb', function () {
        var $wrap = $(this).closest('.inline-edit-col');
        $wrap.find('.borobill-qe-thumb-id').val('');
        $wrap.find('.borobill-qe-thumb').attr('src', '').hide();
    });
});


