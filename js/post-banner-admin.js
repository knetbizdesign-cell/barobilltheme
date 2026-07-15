(function ($) {
    'use strict';

    var mediaFrame;

    function setPreviewState($preview, url) {
        if (!$preview.length) {
            return;
        }

        var $img = $preview.find('.borobill-post-banner-preview__image');
        var $empty = $preview.find('.borobill-post-banner-preview__empty');
        var hasImage = !!url;

        if (hasImage) {
            $img.attr('src', url).prop('hidden', false);
            $empty.prop('hidden', true);
            $preview.addClass('is-filled');
            return;
        }

        $img.attr('src', '').prop('hidden', true);
        $empty.prop('hidden', false);
        $preview.removeClass('is-filled');
    }

    function initExistingPreviews() {
        var $preview = $('#borobill_post_banner_image_preview');
        var $input = $('#borobill_post_banner_image');

        if (!$preview.length || !$input.length) {
            return;
        }

        setPreviewState($preview, ($input.val() || '').trim());
    }

    function bindImagePicker() {
        $(document).off('click.borobillPostBanner', '.borobill-post-banner-image-select');
        $(document).on('click.borobillPostBanner', '.borobill-post-banner-image-select', function (e) {
            e.preventDefault();

            var $button = $(this);
            var $targetInput = $($button.data('target-input'));
            var $targetPreview = $($button.data('target-preview'));

            if (!mediaFrame) {
                mediaFrame = wp.media({
                    title: '배너 이미지 선택',
                    button: { text: '이미지 선택' },
                    library: { type: 'image' },
                    multiple: false
                });
            }

            mediaFrame.off('select');
            mediaFrame.on('select', function () {
                var attachment = mediaFrame.state().get('selection').first().toJSON();
                if (attachment && attachment.url) {
                    $targetInput.val(attachment.url);
                    setPreviewState($targetPreview, attachment.url);
                }
            });

            mediaFrame.open();
        });

        $(document).off('click.borobillPostBannerRemove', '.borobill-post-banner-image-remove');
        $(document).on('click.borobillPostBannerRemove', '.borobill-post-banner-image-remove', function (e) {
            e.preventDefault();

            var $button = $(this);
            var $targetInput = $($button.data('target-input'));
            var $targetPreview = $($button.data('target-preview'));

            $targetInput.val('');
            setPreviewState($targetPreview, '');
        });
    }

    $(function () {
        bindImagePicker();
        initExistingPreviews();
    });
})(jQuery);
