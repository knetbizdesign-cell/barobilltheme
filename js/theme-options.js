(function($) {
    'use strict';

    $(function() {
        // 색상 선택기
        if ($.fn.wpColorPicker) {
            $('.borobill-color-field').wpColorPicker({
                change: function (event) {
                    // 컬러피커(드래그/클릭) 변경 즉시: 해당 슬라이드 미리보기만 갱신
                    var $card = $(event.target).closest('.borobill-hero-slide-card');
                    if ($card.length) {
                        setTimeout(function () { updateHeroPreview($card); }, 0);
                    }
                },
                clear: function (event) {
                    // "지우기"도 즉시 반영
                    var $card = $(event.target).closest('.borobill-hero-slide-card');
                    if ($card.length) {
                        setTimeout(function () { updateHeroPreview($card); }, 0);
                    }
                }
            });

            // 한 번에 하나의 컬러피커만 열리도록(레이아웃 흔들림/동시 오픈 방지)
            function closeOtherPickers($currentContainer) {
                $('.wp-picker-container').each(function () {
                    var $c = $(this);
                    if ($currentContainer && $currentContainer.length && $c.get(0) === $currentContainer.get(0)) {
                        return;
                    }
                    $c.find('.wp-picker-holder').hide();
                });
            }

            $(document).off('click.borobillColorPickerOpen').on('click.borobillColorPickerOpen', '.wp-picker-container .wp-color-result, .wp-picker-container .wp-color-result-text, input.wp-color-picker', function (e) {
                var $container = $(this).closest('.wp-picker-container');
                if (!$container.length) return;
                closeOtherPickers($container);
                // WP 기본 토글 동작은 유지(여기서는 다른 것만 닫음)
                e.stopPropagation();
            });

            // 바깥 클릭 시 열린 컬러피커 닫기
            $(document).off('click.borobillColorPickerClose').on('click.borobillColorPickerClose', function (e) {
                var $t = $(e.target);
                if ($t.closest('.wp-picker-container').length || $t.closest('.wp-picker-holder').length) {
                    return;
                }
                closeOtherPickers(null);
            });
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

        // 이미지 제거 버튼(공용)
        $('.borobill-image-remove').on('click', function(e) {
            e.preventDefault();

            var $button          = $(this);
            var targetInputSel   = $button.data('target-input');
            var targetPreviewSel = $button.data('target-preview');
            var $targetInput     = $(targetInputSel);
            var $targetPreview   = $(targetPreviewSel);

            if ($targetInput.length) {
                $targetInput.val('');
            }
            if ($targetPreview.length) {
                // 1x1 gif placeholder
                $targetPreview.attr('src', 'data:image/gif;base64,R0lGODlhAQABAAAAACw=');
            }
        });

        // 슬라이드 카드 드래그 정렬
        var $slidesContainer = $('#borobill-hero-slides');
        var $orderInput      = $('#borobill_hero_order');

        function renumberHeroSlides() {
            if (!$slidesContainer.length) return;
            $slidesContainer.find('.borobill-hero-slide-card').each(function(idx) {
                $(this).find('h2').first().text('슬라이드 ' + (idx + 1));
            });
        }

        function applyHeroOrderFromInput() {
            if (!$slidesContainer.length || !$orderInput.length) return;
            var raw = ($orderInput.val() || '').toString();
            if (!raw) {
                renumberHeroSlides();
                return;
            }

            var ids = raw.split(',').map(function(s) { return s.trim(); }).filter(Boolean);
            ids.forEach(function(id) {
                var $card = $slidesContainer.find('.borobill-hero-slide-card[data-slide-id="' + id + '"]');
                if ($card.length) {
                    $slidesContainer.append($card);
                }
            });
            renumberHeroSlides();
        }

        if ($slidesContainer.length && $.fn.sortable) {
            // 저장된 순서가 있으면 관리자 UI에도 반영
            applyHeroOrderFromInput();

            $slidesContainer.sortable({
                items: '.borobill-hero-slide-card',
                handle: 'h2',
                axis: 'x',
                tolerance: 'pointer',
                // 클릭하자마자(거의 즉시) 드래그 시작되도록 임계값 최소화
                distance: 0,
                delay: 0,
                placeholder: 'borobill-hero-placeholder',
                cancel: 'input,textarea,button,select,option,a,label',
                start: function() {
                    $('body').addClass('borobill-is-dragging');
                },
                stop: function() {
                    $('body').removeClass('borobill-is-dragging');
                    // 드래그 종료 시점에 UI 재번호를 확실히 반영
                    renumberHeroSlides();
                },
                update: function() {
                    var order = [];
                    $slidesContainer.find('.borobill-hero-slide-card').each(function() {
                        order.push($(this).data('slide-id'));
                    });
                    if ($orderInput.length) {
                        $orderInput.val(order.join(','));
                    }
                    // 위치 기준으로 "슬라이드 1/2/3" 라벨도 갱신
                    renumberHeroSlides();
                }
            });
            // 초기 진입에서도 번호를 위치 기준으로 확정
            renumberHeroSlides();
        } else {
            // sortable이 없더라도 라벨은 위치 기준으로 정렬
            applyHeroOrderFromInput();
        }

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
                return { r: (num >> 16) & 255, g: (num >> 8) & 255, b: num & 255 };
            }

            // rgb/rgba
            var mRgb = raw.match(/^rgba?\(\s*([0-9.]+)\s*,\s*([0-9.]+)\s*,\s*([0-9.]+)(?:\s*,\s*([0-9.]+))?\s*\)$/i);
            if (mRgb) {
                return {
                    r: Math.round(Number(mRgb[1])),
                    g: Math.round(Number(mRgb[2])),
                    b: Math.round(Number(mRgb[3])),
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

        function applyPreviewButtonColors(previewEl, bgColor) {
            if (!previewEl) return;
            var rgb = parseColorToRgb(bgColor);
            if (!rgb) return;

            // 프론트(main.js)와 동일한 룰
            var btnBase = boostSaturation(darkenToLowLuminance(rgb, 0.52), 0.14);
            var btnHover = boostSaturation(darkenToLowLuminance(rgb, 0.64), 0.16);

            previewEl.style.setProperty('--bb-hero-btn-bg', rgbaToCss(btnBase, 0.78));
            previewEl.style.setProperty('--bb-hero-btn-bg-hover', rgbaToCss(btnHover, 0.88));
        }

        function updateHeroPreview($card) {
            if (!$card || !$card.length) return;
            var id = String($card.data('slide-id') || '').trim();
            if (!id) return;

            var $preview = $card.find('.borobill-hero-admin-preview[data-bb-hero-preview="' + id + '"]');
            if (!$preview.length) return;

            var $bg = $card.find('#borobill_hero_bg_color_' + id);
            var $grad = $card.find('#borobill_hero_grad_bottom_' + id);
            var $badge = $card.find('#borobill_hero_badge_' + id);
            var $title = $card.find('#borobill_hero_title_' + id);
            var $btnText = $card.find('#borobill_hero_button_text_' + id);
            var $btnEnabled = $card.find('input[type="checkbox"][name="borobill_hero_button_enabled_' + id + '"]');

            var bgVal = $bg.length ? $bg.val() : '';
            var gradVal = $grad.length ? $grad.val() : '';
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

            // 버튼 컬러도 프론트와 1:1로 동기화(배경색 기반)
            var effectiveBg = String(bgVal || '').trim();
            if (!effectiveBg) {
                effectiveBg = '#4f7fcb';
            }
            applyPreviewButtonColors($preview.get(0), effectiveBg);

            var $btn = $preview.find('[data-bb-hero-preview-btn]');
            // hidden은 prop로 강제(속성/브라우저별 이슈 방지)
            $btn.prop('hidden', !btnEnabled);
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
            // 체크박스는 click에서도 즉시 반영(변경 이벤트 누락 방지)
            $slidesContainer.on('click', 'input[type="checkbox"][name^="borobill_hero_button_enabled_"]', function () {
                updateHeroPreview($(this).closest('.borobill-hero-slide-card'));
            });
        }

        initHeroPreviews();

        // 미디어 선택/제거 후에도 미리보기 동기화
        // (기존 핸들러와 충돌하지 않게, document 기준으로 후킹)
        $(document).on('click', '.borobill-image-select, .borobill-image-remove', function () {
            var $card = $(this).closest('.borobill-hero-slide-card');
            if (!$card.length) return;
            // mediaFrame select 콜백 이후 반영되도록 약간 딜레이
            setTimeout(function () {
                updateHeroPreview($card);
            }, 60);
        });

    });

})(jQuery);


