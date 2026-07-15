(function($) {
    'use strict';

    $(function() {
        // 색상 선택기
        function initHeroSlideColorPickers($scope) {
            if (!$.fn.wpColorPicker || !$scope || !$scope.length) {
                return;
            }

            $scope.find('.borobill-color-field').each(function () {
                var $field = $(this);
                if ($field.hasClass('wp-color-picker')) {
                    return;
                }

                $field.wpColorPicker({
                    change: function (event) {
                        var $target = $(event.target);
                        var $heroCard = $target.closest('.borobill-hero-slide-card');
                        if ($heroCard.length) {
                            setTimeout(function () { updateHeroPreview($heroCard); }, 0);
                        }
                        var $bottomCard = $target.closest('.borobill-bottom-slide-card');
                        if ($bottomCard.length) {
                            setTimeout(function () { updateBottomPreview($bottomCard); }, 0);
                        }
                    },
                    clear: function (event) {
                        var $target = $(event.target);
                        var $heroCard = $target.closest('.borobill-hero-slide-card');
                        if ($heroCard.length) {
                            setTimeout(function () { updateHeroPreview($heroCard); }, 0);
                        }
                        var $bottomCard = $target.closest('.borobill-bottom-slide-card');
                        if ($bottomCard.length) {
                            setTimeout(function () { updateBottomPreview($bottomCard); }, 0);
                        }
                    }
                });
            });
        }

        if ($.fn.wpColorPicker) {
            initHeroSlideColorPickers($(document));

            function forceCloseWpColorPicker($container) {
                if (!$container || !$container.length) {
                    return;
                }

                var $input = $container.find('input.wp-color-picker').first();
                var instance = $input.length ? $input.data('wpWpColorPicker') : null;

                // iris('toggle') 기반 close는 연속 호출 시 다시 열릴 수 있어 hide로 강제 종료
                if ($input.length) {
                    try {
                        $input.iris('hide');
                    } catch (err) {
                        // ignore
                    }
                }

                $container.removeClass('wp-picker-active');
                $container.find('.wp-color-result').removeClass('wp-picker-open').attr('aria-expanded', 'false');
                $container.find('.wp-picker-input-wrap').addClass('hidden');
                // holder만 숨김 (iris에 inline hide 남기면 다음 열기가 막힘)
                $container.find('.wp-picker-holder').hide();

                if (instance && typeof instance.close === 'function') {
                    $('body').off('click.wpcolorpicker', instance.close);
                }
            }

            function closeAllColorPickers(exceptWrap) {
                $('.wp-picker-container').each(function () {
                    if (exceptWrap && this === exceptWrap) {
                        return;
                    }
                    forceCloseWpColorPicker($(this));
                });
            }

            // 다른 색상표 버튼을 누르면 기존 열린 표는 닫기 (한 번에 하나만)
            // 다시 열 수 있게 이 피커 holder의 display는 해제
            $(document)
                .off('mousedown.borobillColorExclusive')
                .on('mousedown.borobillColorExclusive', '.wp-picker-container .wp-color-result', function () {
                    var $wrap = $(this).closest('.wp-picker-container');
                    closeAllColorPickers($wrap.get(0));
                    $wrap.find('.wp-picker-holder').css('display', '');
                });

            // 색상표 바깥 클릭 시 전부 닫기 (타이틀 등 입력 가능하도록)
            $(document)
                .off('mousedown.borobillCloseColorPicker click.borobillCloseColorPicker')
                .on('mousedown.borobillCloseColorPicker', function (e) {
                    if ($(e.target).closest('.wp-picker-container').length) {
                        return;
                    }
                    closeAllColorPickers(null);
                });
        }

        // 공용 미디어 프레임
        var mediaFrame;

        $(document).on('click', '.borobill-image-select', function(e) {
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
                    $targetInput.val(attachment.url).trigger('change');
                    if ($targetPreview.length) {
                        $targetPreview.attr('src', attachment.url);
                    }
                }
            });

            mediaFrame.open();
        });

        // 이미지 제거 버튼(공용)
        $(document).on('click', '.borobill-image-remove', function(e) {
            e.preventDefault();

            var $button          = $(this);
            var targetInputSel   = $button.data('target-input');
            var targetPreviewSel = $button.data('target-preview');
            var $targetInput     = $(targetInputSel);
            var $targetPreview   = $(targetPreviewSel);

            if ($targetInput.length) {
                $targetInput.val('').trigger('change');
            }
            if ($targetPreview.length) {
                // 1x1 gif placeholder
                $targetPreview.attr('src', 'data:image/gif;base64,R0lGODlhAQABAAAAACw=');
            }
        });

        // 슬라이드 목록 + 에디터 (배너설정)
        var $heroManager     = $('.borobill-hero-manager');
        var $slidesList      = $('#borobill-hero-slides-list');
        var $slidesContainer = $('#borobill-hero-slides');
        var $editorEmpty     = $('#borobill-hero-editor-empty');
        var activeSlideId    = $heroManager.length ? String($heroManager.data('active-slide') || '') : '';

        function getSlideCard(slideId) {
            return $slidesContainer.find('.borobill-hero-slide-card[data-slide-id="' + slideId + '"]');
        }

        function getListItem(slideId) {
            return $slidesList.find('.borobill-hero-manager__item[data-slide-id="' + slideId + '"]');
        }

        function getSlideTitleFromCard($card) {
            var raw = $card.find('textarea[id^="borobill_hero_title_"]').val() || '';
            raw = String(raw).replace(/\r\n|\r|\n/g, ' ').replace(/\s+/g, ' ').trim();
            if (!raw) {
                return '슬라이드 ' + String($card.data('slide-id') || '');
            }
            return raw;
        }

        function syncListItemTitle($card) {
            if (!$card || !$card.length) return;
            var slideId = String($card.data('slide-id') || '');
            var title = getSlideTitleFromCard($card);
            getListItem(slideId).find('.borobill-hero-manager__item-title').text(title);
            $card.find('.borobill-hero-slide-card__title').text(title);
        }

        function setHeroStatusUi($card, status) {
            if (!$card || !$card.length) return;
            var slideId = String($card.data('slide-id') || '');
            var isPublished = status === 'published';
            var label = isPublished ? '게시' : '정지';

            $card.find('[data-hero-status-input]').val(status);
            $card.find('[data-hero-status-badge]')
                .text(label)
                .removeClass('is-published is-paused')
                .addClass(isPublished ? 'is-published' : 'is-paused');
            $card.find('[data-hero-status-action="published"]').prop('disabled', isPublished);
            $card.find('[data-hero-status-action="paused"]').prop('disabled', !isPublished);

            getListItem(slideId).find('[data-list-status]')
                .text(label)
                .removeClass('is-published is-paused')
                .addClass(isPublished ? 'is-published' : 'is-paused');
        }

        function showHeroSlideEditor(slideId) {
            slideId = String(slideId || '');
            if (!slideId) {
                $slidesContainer.find('.borobill-hero-slide-form').prop('hidden', true);
                $slidesList.find('.borobill-hero-manager__item').removeClass('is-active');
                $editorEmpty.prop('hidden', false);
                activeSlideId = '';
                return;
            }

            var $form = $slidesContainer.find('.borobill-hero-slide-form[data-slide-id="' + slideId + '"]');
            var $card = getSlideCard(slideId);
            if (!$form.length || !$card.length) return;

            activeSlideId = slideId;
            $editorEmpty.prop('hidden', true);
            $slidesContainer.find('.borobill-hero-slide-form').prop('hidden', true);
            $form.prop('hidden', false);
            $slidesContainer.find('.borobill-hero-slide-card').removeClass('is-active').prop('hidden', true);
            $card.addClass('is-active').prop('hidden', false);
            $slidesList.find('.borobill-hero-manager__item').removeClass('is-active');
            getListItem(slideId).addClass('is-active');
            updateHeroPreview($card);
        }

        function removeHeroSlideFromUi(slideId) {
            slideId = String(slideId || '');
            getListItem(slideId).remove();
            $slidesContainer.find('.borobill-hero-slide-form[data-slide-id="' + slideId + '"]').remove();
            removeSlideFromCarouselOrder(slideId);
            renumberHeroListMeta();

            var $first = $slidesList.find('.borobill-hero-manager__item').first();
            if ($first.length) {
                showHeroSlideEditor(String($first.data('slide-id') || ''));
            } else {
                showHeroSlideEditor('');
            }
        }

        function createHeroSlide() {
            if (!window.borobillHeroManager || !window.borobillHeroManager.ajaxUrl) {
                return;
            }

            var $btn = $('#borobill-hero-create');
            $btn.prop('disabled', true);

            $.post(window.borobillHeroManager.ajaxUrl, {
                action: 'borobill_create_hero_slide',
                nonce: window.borobillHeroManager.nonce
            }).done(function (response) {
                if (!response || !response.success || !response.data) {
                    window.alert('새 슬라이드를 만들지 못했습니다.');
                    return;
                }

                var slideId = String(response.data.slide_id || '');
                if (!slideId) {
                    window.alert('새 슬라이드를 만들지 못했습니다.');
                    return;
                }

                $slidesList.prepend(response.data.list_item_html || '');
                $slidesContainer.append(response.data.editor_html || '');

                var $form = $slidesContainer.find('.borobill-hero-slide-form[data-slide-id="' + slideId + '"]');
                initHeroSlideColorPickers($form);
                if (response.data.hero_order) {
                    setHeroCarouselOrder(String(response.data.hero_order).split(','));
                } else {
                    applySlideOrderPosition(slideId, $form.find('.borobill-hero-slide-order-input').val());
                }
                renumberHeroListMeta();
                showHeroSlideEditor(slideId);
            }).fail(function () {
                window.alert('새 슬라이드를 만들지 못했습니다.');
            }).always(function () {
                $btn.prop('disabled', false);
            });
        }

        function deleteHeroSlide(slideId, isDraft) {
            slideId = String(slideId || '');
            if (!slideId) {
                return;
            }

            if (!window.confirm('이 슬라이드를 삭제할까요?')) {
                return;
            }

            if (isDraft) {
                removeHeroSlideFromUi(slideId);
                return;
            }

            if (!window.borobillHeroManager || !window.borobillHeroManager.ajaxUrl) {
                return;
            }

            $.post(window.borobillHeroManager.ajaxUrl, {
                action: 'borobill_delete_hero_slide',
                nonce: window.borobillHeroManager.nonce,
                slide_id: slideId
            }).done(function (response) {
                if (response && response.success) {
                    removeHeroSlideFromUi(slideId);
                    return;
                }
                window.alert('슬라이드를 삭제하지 못했습니다.');
            }).fail(function () {
                window.alert('슬라이드를 삭제하지 못했습니다.');
            });
        }

        function getHeroCarouselOrder() {
            var raw = String($('.borobill-hero-order-input').first().val() || '');
            return raw.split(',').map(function (id) {
                return String(id).trim();
            }).filter(Boolean);
        }

        function setHeroCarouselOrder(order) {
            var unique = [];
            (order || []).forEach(function (id) {
                id = String(id || '').trim();
                if (!id || unique.indexOf(id) !== -1) {
                    return;
                }
                unique.push(id);
            });

            var orderString = unique.join(',');
            $('.borobill-hero-order-input').val(orderString);
            syncHeroOrderInputDisplays();
        }

        function syncHeroOrderInputDisplays() {
            var order = getHeroCarouselOrder();
            var maxPosition = Math.max(1, order.length);

            $('.borobill-hero-slide-order-input').each(function () {
                var slideId = String($(this).data('slide-id') || '');
                var idx = order.indexOf(slideId);
                $(this).attr('max', maxPosition);
                $(this).val(idx >= 0 ? idx + 1 : maxPosition + 1);
            });
        }

        function applySlideOrderPosition(slideId, position) {
            slideId = String(slideId || '');
            if (!slideId) {
                return;
            }

            position = parseInt(position, 10);
            if (isNaN(position)) {
                return;
            }

            var order = getHeroCarouselOrder().filter(function (id) {
                return id !== slideId;
            });

            if (position < 1) {
                position = 1;
            }
            if (position > order.length + 1) {
                position = order.length + 1;
            }

            order.splice(position - 1, 0, slideId);
            setHeroCarouselOrder(order);
        }

        function removeSlideFromCarouselOrder(slideId) {
            slideId = String(slideId || '');
            if (!slideId) {
                return;
            }

            setHeroCarouselOrder(getHeroCarouselOrder().filter(function (id) {
                return id !== slideId;
            }));
        }

        function syncHeroOrderInputs() {
            if (!$slidesList.length) {
                return;
            }
            var order = [];
            $slidesList.find('.borobill-hero-manager__item').each(function () {
                order.push(String($(this).data('slide-id') || ''));
            });
            $('.borobill-hero-order-input').val(order.join(','));
        }

        function updateHeroOrderFromList() {
            syncHeroOrderInputs();
        }

        function renumberHeroListMeta() {
            $slidesList.find('.borobill-hero-manager__item').each(function (idx) {
                $(this).find('.borobill-hero-manager__item-meta span:first').text('슬라이드 ' + (idx + 1));
            });
        }

        if ($slidesList.length && $slidesContainer.length) {
            if (activeSlideId) {
                showHeroSlideEditor(activeSlideId);
            } else {
                showHeroSlideEditor(String($slidesList.find('.borobill-hero-manager__item').first().data('slide-id') || '1'));
            }

            $slidesList.on('click', '.borobill-hero-manager__item-btn', function (e) {
                e.preventDefault();
                var slideId = String($(this).closest('.borobill-hero-manager__item').data('slide-id') || '');
                showHeroSlideEditor(slideId);
            });

            $('#borobill-hero-create').on('click', function (e) {
                e.preventDefault();
                createHeroSlide();
            });

            $slidesContainer.on('click', '.borobill-hero-slide-delete', function (e) {
                e.preventDefault();
                var $form = $(this).closest('.borobill-hero-slide-form');
                var slideId = String($form.data('slide-id') || '');
                var isDraft = String($form.data('is-draft') || '') === '1';
                deleteHeroSlide(slideId, isDraft);
            });

            $slidesContainer.on('click', '[data-hero-status-action]', function (e) {
                e.preventDefault();
                var status = String($(this).data('hero-status-action') || '');
                var $card = $(this).closest('.borobill-hero-slide-card');
                if (!$card.length || (status !== 'published' && status !== 'paused')) return;
                setHeroStatusUi($card, status);
            });

            $slidesContainer.on('input', 'textarea[id^="borobill_hero_title_"]', function () {
                syncListItemTitle($(this).closest('.borobill-hero-slide-card'));
            });

            $slidesContainer.on('change', '.borobill-hero-slide-order-input', function () {
                applySlideOrderPosition(String($(this).data('slide-id') || ''), $(this).val());
            });

            if ($.fn.sortable) {
                $slidesList.sortable({
                    items: '.borobill-hero-manager__item',
                    handle: '.borobill-hero-manager__item-handle',
                    axis: 'y',
                    tolerance: 'pointer',
                    distance: 4,
                    update: function () {
                        renumberHeroListMeta();
                    }
                });
            }

            syncHeroOrderInputDisplays();
            renumberHeroListMeta();

            $('.borobill-hero-slide-form').on('submit', function () {
                var $form = $(this);
                var slideId = String($form.data('slide-id') || activeSlideId || '');
                applySlideOrderPosition(slideId, $form.find('.borobill-hero-slide-order-input').val());
                $form.find('.borobill-hero-active-slide-input').val(slideId);
            });
        }

        function initHeroPreviews() {
            if (!$slidesContainer.length) return;
            $slidesContainer.find('.borobill-hero-slide-card').each(function () {
                updateHeroPreview($(this));
            });
        }

        // 입력 변경 시 미리보기 동기화
        if ($slidesContainer.length) {
            $slidesContainer.on('input change', 'input, textarea, select', function () {
                updateHeroPreview($(this).closest('.borobill-hero-slide-card'));
            });
            $slidesContainer.on('click', 'input[type="checkbox"][name^="borobill_hero_button_enabled_"]', function () {
                updateHeroPreview($(this).closest('.borobill-hero-slide-card'));
            });
        }

        initHeroPreviews();

        // 미디어 선택/제거 후에도 미리보기 동기화
        $(document).on('click', '.borobill-image-select, .borobill-image-remove', function () {
            var $card = $(this).closest('.borobill-hero-slide-card');
            if (!$card.length) return;
            setTimeout(function () {
                updateHeroPreview($card);
            }, 60);
        });

        // --- 히어로 라이브 미리보기(관리자) --- //
        function escapeHtml(str) {
            return String(str || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function nl2brSafe(str) {
            return escapeHtml(str).replace(/\r\n|\r|\n/g, '<br>');
        }

        function clamp(n, min, max) {
            return Math.max(min, Math.min(max, n));
        }

        function parseColorToRgb(input) {
            var raw = String(input || '').trim();
            if (!raw) return null;

            // hex
            var mHex = raw.match(/^#?([0-9a-f]{3}|[0-9a-f]{6})$/i);
            if (mHex) {
                var h = mHex[1];
                if (h.length === 3) {
                    h = h.split('').map(function (ch) { return ch + ch; }).join('');
                }
                var num = parseInt(h, 16);
                return { r: (num >> 16) & 255, g: (num >> 8) & 255, b: num & 255, a: 1 };
            }

            // rgb/rgba
            var mRgb = raw.match(/^rgba?\(\s*([0-9.]+)\s*,\s*([0-9.]+)\s*,\s*([0-9.]+)(?:\s*,\s*([0-9.]+))?\s*\)$/i);
            if (mRgb) {
                return {
                    r: Math.round(Number(mRgb[1])),
                    g: Math.round(Number(mRgb[2])),
                    b: Math.round(Number(mRgb[3])),
                    a: (typeof mRgb[4] === 'undefined' || mRgb[4] === '') ? 1 : clamp(Number(mRgb[4]), 0, 1)
                };
            }

            return null;
        }

        function rgbaToCss(rgb, a) {
            var alpha = clamp(Number(a), 0, 1);
            return 'rgba(' + clamp(rgb.r, 0, 255) + ', ' + clamp(rgb.g, 0, 255) + ', ' + clamp(rgb.b, 0, 255) + ', ' + alpha + ')';
        }

        function darkenToLowLuminance(rgb, ratioBlack) {
            var t = clamp(Number(ratioBlack), 0, 1);
            return {
                r: Math.round(rgb.r * (1 - t)),
                g: Math.round(rgb.g * (1 - t)),
                b: Math.round(rgb.b * (1 - t)),
            };
        }

        function rgbToHsl(rgb) {
            var r = clamp(rgb.r, 0, 255) / 255;
            var g = clamp(rgb.g, 0, 255) / 255;
            var b = clamp(rgb.b, 0, 255) / 255;

            var max = Math.max(r, g, b);
            var min = Math.min(r, g, b);
            var d = max - min;

            var h = 0;
            var s = 0;
            var l = (max + min) / 2;

            if (d !== 0) {
                s = d / (1 - Math.abs(2 * l - 1));
                if (max === r) {
                    h = ((g - b) / d) % 6;
                } else if (max === g) {
                    h = (b - r) / d + 2;
                } else {
                    h = (r - g) / d + 4;
                }
                h = h * 60;
                if (h < 0) h += 360;
            }

            return { h: h, s: s, l: l };
        }

        function hslToRgb(hsl) {
            var h = ((Number(hsl.h) % 360) + 360) % 360;
            var s = clamp(Number(hsl.s), 0, 1);
            var l = clamp(Number(hsl.l), 0, 1);

            var c = (1 - Math.abs(2 * l - 1)) * s;
            var x = c * (1 - Math.abs(((h / 60) % 2) - 1));
            var m = l - c / 2;

            var rp = 0, gp = 0, bp = 0;
            if (h < 60) { rp = c; gp = x; bp = 0; }
            else if (h < 120) { rp = x; gp = c; bp = 0; }
            else if (h < 180) { rp = 0; gp = c; bp = x; }
            else if (h < 240) { rp = 0; gp = x; bp = c; }
            else if (h < 300) { rp = x; gp = 0; bp = c; }
            else { rp = c; gp = 0; bp = x; }

            return {
                r: Math.round((rp + m) * 255),
                g: Math.round((gp + m) * 255),
                b: Math.round((bp + m) * 255),
            };
        }

        function boostSaturation(rgb, amount) {
            var hsl = rgbToHsl(rgb);
            hsl.s = clamp(hsl.s + Number(amount), 0, 1);
            return hslToRgb(hsl);
        }

        function applyPreviewButtonColors(previewEl, bgColor, buttonColor, buttonTextColor) {
            if (!previewEl) return;
            var textColor = String(buttonTextColor || '').trim() || '#ffffff';
            previewEl.style.setProperty('--bb-hero-btn-color', textColor);

            var custom = String(buttonColor || '').trim();
            if (custom) {
                var customRgb = parseColorToRgb(custom);
                if (customRgb) {
                    var opacity = typeof customRgb.a === 'number' ? customRgb.a : 1;
                    var hoverOpacity = Math.min(1, opacity + 0.08);
                    var customHover = darkenToLowLuminance(customRgb, 0.12);
                    previewEl.style.setProperty('--bb-hero-btn-bg', rgbaToCss(customRgb, opacity));
                    previewEl.style.setProperty('--bb-hero-btn-bg-hover', rgbaToCss(customHover, hoverOpacity));
                    return;
                }
            }

            var rgb = parseColorToRgb(bgColor);
            if (!rgb) return;

            // 프론트(main.js)와 동일한 룰
            var btnBase = boostSaturation(darkenToLowLuminance(rgb, 0.52), 0.14);
            var btnHover = boostSaturation(darkenToLowLuminance(rgb, 0.64), 0.16);

            previewEl.style.setProperty('--bb-hero-btn-bg', rgbaToCss(btnBase, 0.97));
            previewEl.style.setProperty('--bb-hero-btn-bg-hover', rgbaToCss(btnHover, 1));
        }

        function applyBottomPreviewButtonColors(previewEl, buttonColor, buttonTextColor) {
            if (!previewEl) return;
            var textColor = String(buttonTextColor || '').trim() || '#ffffff';
            previewEl.style.setProperty('--cta-btn-color', textColor);

            var custom = String(buttonColor || '').trim();
            if (custom) {
                var customRgb = parseColorToRgb(custom);
                if (customRgb) {
                    var opacity = typeof customRgb.a === 'number' ? customRgb.a : 1;
                    var hoverOpacity = Math.min(1, opacity + 0.08);
                    var customHover = darkenToLowLuminance(customRgb, 0.12);
                    previewEl.style.setProperty('--cta-btn-bg', rgbaToCss(customRgb, opacity));
                    previewEl.style.setProperty('--cta-btn-bg-hover', rgbaToCss(customHover, hoverOpacity));
                    return;
                }
            }

            previewEl.style.removeProperty('--cta-btn-bg');
            previewEl.style.removeProperty('--cta-btn-bg-hover');
        }

        // 하단 미리보기: 색상표 change에서도 호출될 수 있어 상위 스코프에 둠
        function updateBottomPreview($card) {
            if (!$card || !$card.length) return;
            var id = String($card.data('slide-id') || '').trim();
            if (!id) return;

            var $preview = $card.find('.borobill-bottom-admin-preview[data-bb-bottom-preview="' + id + '"]');
            if (!$preview.length) return;

            var $cta    = $preview;
            var $media  = $preview.find('[data-bb-bottom-preview-media]');
            var $action = $preview.find('[data-bb-bottom-preview-action]');
            var $imgInp = $card.find('#borobill_bottom_banner_image_' + id);
            var $bg     = $card.find('#borobill_bottom_banner_bg_color_' + id);
            var $btnColor = $card.find('#borobill_bottom_banner_button_color_' + id);
            var $btnTextColor = $card.find('#borobill_bottom_banner_button_text_color_' + id);
            var $title  = $card.find('#borobill_bottom_banner_title_' + id);
            var $body   = $card.find('#borobill_bottom_banner_body_' + id);
            var $btn    = $card.find('#borobill_bottom_banner_button_text_' + id);

            var imgVal   = $imgInp.length ? String($imgInp.val() || '').trim() : '';
            var bgVal    = $bg.length ? String($bg.val() || '').trim() : '';
            var btnColorVal = $btnColor.length ? String($btnColor.val() || '').trim() : '';
            var btnTextColorVal = $btnTextColor.length ? String($btnTextColor.val() || '').trim() : '#ffffff';
            var titleVal = $title.length ? String($title.val() || '') : '';
            var bodyVal  = $body.length ? String($body.val() || '') : '';
            var btnVal   = $btn.length ? String($btn.val() || '').trim() : '';

            $cta.get(0).style.setProperty('--cta-bg', bgVal || '#7ea354');
            applyBottomPreviewButtonColors($cta.get(0), btnColorVal, btnTextColorVal);
            $cta.toggleClass('has-media', !!imgVal);

            if ($media.length) {
                if (imgVal) {
                    $media.prop('hidden', false).removeAttr('hidden');
                    $media.find('.borobill-bottom-admin-preview__illust').attr('src', imgVal);
                } else {
                    $media.prop('hidden', true).attr('hidden', 'hidden');
                    $media.find('.borobill-bottom-admin-preview__illust').attr('src', 'data:image/gif;base64,R0lGODlhAQABAAAAACw=');
                }
            }

            $preview.find('[data-bb-bottom-preview-title]').text(titleVal);
            $preview.find('[data-bb-bottom-preview-body]').html(
                String(bodyVal || '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/\n/g, '<br>')
            );
            $preview.find('[data-bb-bottom-preview-btn]').text(btnVal);

            if ($action.length) {
                if (btnVal) {
                    $action.prop('hidden', false).removeAttr('hidden');
                } else {
                    $action.prop('hidden', true).attr('hidden', 'hidden');
                }
            }
        }

        function updateHeroPreview($card) {
            if (!$card || !$card.length) return;
            var id = String($card.data('slide-id') || '').trim();
            if (!id) return;

            var $preview = $card.find('.borobill-hero-admin-preview[data-bb-hero-preview="' + id + '"]');
            if (!$preview.length) return;

            var $bg = $card.find('#borobill_hero_bg_color_' + id);
            var $grad = $card.find('#borobill_hero_grad_bottom_' + id);
            var $btnColor = $card.find('#borobill_hero_button_color_' + id);
            var $btnTextColor = $card.find('#borobill_hero_button_text_color_' + id);
            var $badge = $card.find('#borobill_hero_badge_' + id);
            var $title = $card.find('#borobill_hero_title_' + id);
            var $btnText = $card.find('#borobill_hero_button_text_' + id);
            var $btnEnabled = $card.find('input[type="checkbox"][name="borobill_hero_button_enabled_' + id + '"]');

            var bgVal = $bg.length ? $bg.val() : '';
            var gradVal = $grad.length ? $grad.val() : '';
            var btnColorVal = $btnColor.length ? $btnColor.val() : '';
            var btnTextColorVal = $btnTextColor.length ? $btnTextColor.val() : '#ffffff';
            var badgeVal = $badge.length ? $badge.val() : '';
            var titleVal = $title.length ? $title.val() : '';
            var btnTextVal = $btnText.length ? $btnText.val() : '';
            var btnEnabled = $btnEnabled.length ? $btnEnabled.is(':checked') : true;

            // 값이 비어있으면 그라데이션을 "끄기"
            if (String(bgVal || '').trim() !== '') {
                $preview.get(0).style.setProperty('--bb-hero-bg', String(bgVal));
            } else {
                $preview.get(0).style.removeProperty('--bb-hero-bg');
            }
            if (String(gradVal || '').trim() !== '') {
                $preview.get(0).style.setProperty('--bb-hero-grad', String(gradVal));
                $preview.get(0).style.setProperty('--bb-hero-grad-opacity', '1');
            } else {
                $preview.get(0).style.removeProperty('--bb-hero-grad');
                $preview.get(0).style.setProperty('--bb-hero-grad-opacity', '0');
            }

            $preview.find('[data-bb-hero-preview-badge]').text(String(badgeVal || ''));
            $preview.find('[data-bb-hero-preview-title]').html(nl2brSafe(titleVal || ''));
            $preview.find('[data-bb-hero-preview-btn-text]').text(String(btnTextVal || ''));

            // 버튼 컬러: 지정값(rgba 포함) 우선, 없으면 배경색 기반 자동
            var effectiveBg = String(bgVal || '').trim();
            if (!effectiveBg) {
                effectiveBg = '#4f7fcb';
            }
            applyPreviewButtonColors($preview.get(0), effectiveBg, btnColorVal, btnTextColorVal);

            var $btn = $preview.find('[data-bb-hero-preview-btn]');
            // hidden은 prop로 강제(속성/브라우저별 이슈 방지)
            $btn.prop('hidden', !btnEnabled);
        }

        // ── 하단 배너 슬라이드 매니저 ─────────────────────────────────────────── //
        var $bottomManager    = $('.borobill-bottom-banner-manager');
        var $bottomList       = $('#borobill-bottom-slides-list');
        var $bottomContainer  = $('#borobill-bottom-slides');
        var $bottomEditorEmpty = $('#borobill-bottom-editor-empty');
        var activeBottomSlideId = $bottomManager.length ? String($bottomManager.data('active-slide') || '') : '';

        function getBottomSlideCard(slideId) {
            return $bottomContainer.find('.borobill-bottom-slide-card[data-slide-id="' + slideId + '"]');
        }

        function getBottomListItem(slideId) {
            return $bottomList.find('.borobill-bottom-manager__item[data-slide-id="' + slideId + '"]');
        }

        function getBottomSlideTitleFromCard($card) {
            var raw = $card.find('input[id^="borobill_bottom_banner_title_"]').val() || '';
            raw = String(raw).replace(/\s+/g, ' ').trim();
            if (!raw) {
                return '슬라이드 ' + String($card.data('slide-id') || '');
            }
            return raw;
        }

        function syncBottomListItemTitle($card) {
            if (!$card || !$card.length) return;
            var slideId = String($card.data('slide-id') || '');
            var title = getBottomSlideTitleFromCard($card);
            getBottomListItem(slideId).find('.borobill-hero-manager__item-title').text(title);
            $card.find('.borobill-hero-slide-card__title').text(title);
        }

        function setBottomStatusUi($card, status) {
            if (!$card || !$card.length) return;
            var slideId = String($card.data('slide-id') || '');
            var isPublished = status === 'published';
            var label = isPublished ? '게시' : '정지';

            $card.find('[data-bottom-status-input]').val(status);
            $card.find('[data-bottom-status-badge]')
                .text(label)
                .removeClass('is-published is-paused')
                .addClass(isPublished ? 'is-published' : 'is-paused');
            $card.find('[data-bottom-status-action="published"]').prop('disabled', isPublished);
            $card.find('[data-bottom-status-action="paused"]').prop('disabled', !isPublished);

            getBottomListItem(slideId).find('[data-list-status]')
                .text(label)
                .removeClass('is-published is-paused')
                .addClass(isPublished ? 'is-published' : 'is-paused');
        }

        function showBottomSlideEditor(slideId) {
            slideId = String(slideId || '');
            if (!slideId) {
                $bottomContainer.find('.borobill-bottom-slide-form').prop('hidden', true);
                $bottomList.find('.borobill-bottom-manager__item').removeClass('is-active');
                $bottomEditorEmpty.prop('hidden', false);
                activeBottomSlideId = '';
                return;
            }

            var $form = $bottomContainer.find('.borobill-bottom-slide-form[data-slide-id="' + slideId + '"]');
            var $card = getBottomSlideCard(slideId);
            if (!$form.length || !$card.length) return;

            activeBottomSlideId = slideId;
            $bottomEditorEmpty.prop('hidden', true);
            $bottomContainer.find('.borobill-bottom-slide-form').prop('hidden', true);
            $form.prop('hidden', false);
            $bottomContainer.find('.borobill-bottom-slide-card').removeClass('is-active').prop('hidden', true);
            $card.addClass('is-active').prop('hidden', false);
            $bottomList.find('.borobill-bottom-manager__item').removeClass('is-active');
            getBottomListItem(slideId).addClass('is-active');
            updateBottomPreview($card);
        }

        function removeBottomSlideFromUi(slideId) {
            slideId = String(slideId || '');
            getBottomListItem(slideId).remove();
            $bottomContainer.find('.borobill-bottom-slide-form[data-slide-id="' + slideId + '"]').remove();
            removeSlideFromBottomOrder(slideId);
            renumberBottomListMeta();

            var $first = $bottomList.find('.borobill-bottom-manager__item').first();
            if ($first.length) {
                showBottomSlideEditor(String($first.data('slide-id') || ''));
            } else {
                showBottomSlideEditor('');
            }
        }

        function createBottomSlide() {
            if (!window.borobillBottomBannerManager || !window.borobillBottomBannerManager.ajaxUrl) {
                return;
            }

            var $btn = $('#borobill-bottom-create');
            $btn.prop('disabled', true);

            $.post(window.borobillBottomBannerManager.ajaxUrl, {
                action: 'borobill_create_bottom_banner_slide',
                nonce: window.borobillBottomBannerManager.nonce
            }).done(function (response) {
                if (!response || !response.success || !response.data) {
                    window.alert('새 슬라이드를 만들지 못했습니다.');
                    return;
                }

                var slideId = String(response.data.slide_id || '');
                if (!slideId) {
                    window.alert('새 슬라이드를 만들지 못했습니다.');
                    return;
                }

                $bottomList.prepend(response.data.list_item_html || '');
                $bottomContainer.append(response.data.editor_html || '');

                var $form = $bottomContainer.find('.borobill-bottom-slide-form[data-slide-id="' + slideId + '"]');
                initHeroSlideColorPickers($form);
                if (response.data.bottom_order) {
                    setBottomCarouselOrder(String(response.data.bottom_order).split(','));
                } else {
                    applyBottomSlideOrderPosition(slideId, $form.find('.borobill-bottom-slide-order-input').val());
                }
                renumberBottomListMeta();
                showBottomSlideEditor(slideId);
            }).fail(function () {
                window.alert('새 슬라이드를 만들지 못했습니다.');
            }).always(function () {
                $btn.prop('disabled', false);
            });
        }

        function deleteBottomSlide(slideId, isDraft) {
            slideId = String(slideId || '');
            if (!slideId) {
                return;
            }

            if (!window.confirm('이 슬라이드를 삭제할까요?')) {
                return;
            }

            if (isDraft) {
                removeBottomSlideFromUi(slideId);
                return;
            }

            if (!window.borobillBottomBannerManager || !window.borobillBottomBannerManager.ajaxUrl) {
                return;
            }

            $.post(window.borobillBottomBannerManager.ajaxUrl, {
                action: 'borobill_delete_bottom_banner_slide',
                nonce: window.borobillBottomBannerManager.nonce,
                slide_id: slideId
            }).done(function (response) {
                if (response && response.success) {
                    removeBottomSlideFromUi(slideId);
                    return;
                }
                window.alert('슬라이드를 삭제하지 못했습니다.');
            }).fail(function () {
                window.alert('슬라이드를 삭제하지 못했습니다.');
            });
        }

        function getBottomCarouselOrder() {
            var raw = String($('.borobill-bottom-order-input').first().val() || '');
            return raw.split(',').map(function (id) {
                return String(id).trim();
            }).filter(Boolean);
        }

        function setBottomCarouselOrder(order) {
            var unique = [];
            (order || []).forEach(function (id) {
                id = String(id || '').trim();
                if (!id || unique.indexOf(id) !== -1) {
                    return;
                }
                unique.push(id);
            });

            var orderString = unique.join(',');
            $('.borobill-bottom-order-input').val(orderString);
            syncBottomOrderInputDisplays();
        }

        function syncBottomOrderInputDisplays() {
            var order = getBottomCarouselOrder();
            var maxPosition = Math.max(1, order.length);

            $('.borobill-bottom-slide-order-input').each(function () {
                var slideId = String($(this).data('slide-id') || '');
                var idx = order.indexOf(slideId);
                $(this).attr('max', maxPosition);
                $(this).val(idx >= 0 ? idx + 1 : maxPosition + 1);
            });
        }

        function applyBottomSlideOrderPosition(slideId, position) {
            slideId = String(slideId || '');
            if (!slideId) return;

            position = parseInt(position, 10);
            if (isNaN(position)) return;

            var order = getBottomCarouselOrder().filter(function (id) {
                return id !== slideId;
            });

            if (position < 1) position = 1;
            if (position > order.length + 1) position = order.length + 1;

            order.splice(position - 1, 0, slideId);
            setBottomCarouselOrder(order);
        }

        function removeSlideFromBottomOrder(slideId) {
            slideId = String(slideId || '');
            if (!slideId) return;
            setBottomCarouselOrder(getBottomCarouselOrder().filter(function (id) {
                return id !== slideId;
            }));
        }

        function renumberBottomListMeta() {
            $bottomList.find('.borobill-bottom-manager__item').each(function (idx) {
                $(this).find('.borobill-hero-manager__item-meta span:first').text('슬라이드 ' + (idx + 1));
            });
        }

        if ($bottomList.length && $bottomContainer.length) {
            if (activeBottomSlideId) {
                showBottomSlideEditor(activeBottomSlideId);
            } else {
                var $firstBottom = $bottomList.find('.borobill-bottom-manager__item').first();
                if ($firstBottom.length) {
                    showBottomSlideEditor(String($firstBottom.data('slide-id') || ''));
                }
            }

            $bottomList.on('click', '.borobill-hero-manager__item-btn', function (e) {
                e.preventDefault();
                var slideId = String($(this).closest('.borobill-bottom-manager__item').data('slide-id') || '');
                if (slideId) showBottomSlideEditor(slideId);
            });

            $('#borobill-bottom-create').on('click', function (e) {
                e.preventDefault();
                createBottomSlide();
            });

            $bottomContainer.on('click', '.borobill-bottom-slide-delete', function (e) {
                e.preventDefault();
                var $form = $(this).closest('.borobill-bottom-slide-form');
                var slideId = String($form.data('slide-id') || '');
                var isDraft = String($form.data('is-draft') || '') === '1';
                deleteBottomSlide(slideId, isDraft);
            });

            $bottomContainer.on('click', '[data-bottom-status-action]', function (e) {
                e.preventDefault();
                var status = String($(this).data('bottom-status-action') || '');
                var $card = $(this).closest('.borobill-bottom-slide-card');
                if (!$card.length || (status !== 'published' && status !== 'paused')) return;
                setBottomStatusUi($card, status);
            });

            $bottomContainer.on('input', 'input[id^="borobill_bottom_banner_title_"]', function () {
                syncBottomListItemTitle($(this).closest('.borobill-bottom-slide-card'));
            });

            $bottomContainer.on('change', '.borobill-bottom-slide-order-input', function () {
                applyBottomSlideOrderPosition(String($(this).data('slide-id') || ''), $(this).val());
            });

            $bottomContainer.on('input change', 'input, textarea, select', function () {
                updateBottomPreview($(this).closest('.borobill-bottom-slide-card'));
            });

            if ($.fn.sortable) {
                $bottomList.sortable({
                    items: '.borobill-bottom-manager__item',
                    handle: '.borobill-hero-manager__item-handle',
                    axis: 'y',
                    tolerance: 'pointer',
                    distance: 4,
                    update: function () {
                        renumberBottomListMeta();
                    }
                });
            }

            syncBottomOrderInputDisplays();
            renumberBottomListMeta();

            // 초기 미리보기
            $bottomContainer.find('.borobill-bottom-slide-card').each(function () {
                updateBottomPreview($(this));
            });

            $('.borobill-bottom-slide-form').on('submit', function () {
                var $form = $(this);
                var slideId = String($form.data('slide-id') || activeBottomSlideId || '');
                applyBottomSlideOrderPosition(slideId, $form.find('.borobill-bottom-slide-order-input').val());
                $form.find('.borobill-bottom-active-slide-input').val(slideId);
            });

            // 이미지 선택/제거 후 미리보기 갱신
            $(document).on('click', '.borobill-image-select, .borobill-image-remove', function () {
                var $card = $(this).closest('.borobill-bottom-slide-card');
                if (!$card.length) return;
                setTimeout(function () {
                    updateBottomPreview($card);
                }, 60);
            });
        }

    });

})(jQuery);


