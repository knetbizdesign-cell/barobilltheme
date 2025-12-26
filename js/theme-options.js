(function($) {
    'use strict';

    $(function() {
        // 색상 선택기
        if ($.fn.wpColorPicker) {
            $('.borobill-color-field').wpColorPicker();
        }

        // 공용 미디어 프레임
        var mediaFrame;

        $('.borobill-image-select').on('click', function(e) {
            e.preventDefault();

            var $button         = $(this);
            var targetInputSel  = $button.data('target-input');
            var targetPreviewSel = $button.data('target-preview');
            var $targetInput    = $(targetInputSel);
            var $targetPreview  = $(targetPreviewSel);

            // 이미 열려 있으면 다시 사용
            if (mediaFrame) {
                mediaFrame.open();
                mediaFrame.off('select');
            } else {
                mediaFrame = wp.media({
                    title: '히어로 이미지 선택',
                    button: { text: '이미지 선택' },
                    multiple: false
                });
            }

            mediaFrame.on('select', function() {
                var attachment = mediaFrame.state().get('selection').first().toJSON();
                if (attachment && attachment.url) {
                    $targetInput.val(attachment.url);
                    if ($targetPreview.length) {
                        $targetPreview.attr('src', attachment.url);
                    }
                }
            });

            mediaFrame.open();
        });

        // 슬라이드 카드 드래그 정렬
        var $slidesContainer = $('#borobill-hero-slides');
        var $orderInput      = $('#borobill_hero_order');

        if ($slidesContainer.length && $.fn.sortable) {
            $slidesContainer.sortable({
                items: '.borobill-hero-slide-card',
                handle: 'h2',
                axis: 'x',
                update: function() {
                    var order = [];
                    $slidesContainer.find('.borobill-hero-slide-card').each(function() {
                        order.push($(this).data('slide-id'));
                    });
                    if ($orderInput.length) {
                        $orderInput.val(order.join(','));
                    }
                }
            });
        }
    });

})(jQuery);


