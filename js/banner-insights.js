(function ($) {
    'use strict';

    if (!window.borobillBannerInsight || !window.borobillBannerInsight.ajaxUrl) {
        return;
    }

    var cfg = window.borobillBannerInsight;
    var COOKIE_NAME = 'borobill_bn_src';
    var COOKIE_MAX_AGE = 1800;

    function setBannerSrcCookie(bannerKey) {
        if (!bannerKey) {
            return;
        }
        document.cookie = COOKIE_NAME + '=' + encodeURIComponent(String(bannerKey)) +
            '; path=/; max-age=' + COOKIE_MAX_AGE + '; SameSite=Lax';
    }

    function getAttr($el, name) {
        if (!$el || !$el.length) {
            return '';
        }
        return String($el.attr(name) || '').trim();
    }

    function trackBannerClick(bannerKey) {
        bannerKey = String(bannerKey || '').trim();
        if (!bannerKey) {
            return;
        }

        setBannerSrcCookie(bannerKey);

        var payload = {
            action: 'borobill_track_banner_click',
            nonce: cfg.nonce,
            banner_key: bannerKey
        };

        // 새 탭/페이지 이동 직후에도 요청이 끊기지 않도록 beacon 우선
        if (navigator.sendBeacon) {
            try {
                var body = new URLSearchParams();
                Object.keys(payload).forEach(function (key) {
                    body.append(key, payload[key]);
                });
                if (navigator.sendBeacon(cfg.ajaxUrl, body)) {
                    return;
                }
            } catch (e) {
                // fall through to jQuery
            }
        }

        $.ajax({
            url: cfg.ajaxUrl,
            method: 'POST',
            data: payload,
            keepalive: true
        });
    }

    $(function () {
        document.addEventListener('click', function (event) {
            var target = event.target;
            if (!target || !target.closest) {
                return;
            }

            var heroBtn = target.closest('.hero-next-btn');
            if (heroBtn) {
                var $wrapper = $(heroBtn).closest('.hero-wrapper');
                var $active = $wrapper.find('.hero-slide.is-active').first();
                if (!$active.length) {
                    return;
                }

                var btnEnabled = getAttr($active, 'data-btn-enabled') !== '0';
                var btnUrl = getAttr($active, 'data-btn-url');
                var bannerKey = getAttr($active, 'data-banner-key');

                if (btnEnabled && btnUrl && bannerKey) {
                    trackBannerClick(bannerKey);
                }
                return;
            }

            var heroWrapper = target.closest('.hero-wrapper');
            if (heroWrapper) {
                if (target.closest('.hero-carousel-pagination, [data-hero-next], a, button')) {
                    return;
                }

                var $mobileWrapper = $(heroWrapper);
                var $mobileActive = $mobileWrapper.find('.hero-slide.is-active').first();
                if (!$mobileActive.length) {
                    return;
                }

                var mobileBtnEnabled = getAttr($mobileActive, 'data-btn-enabled') !== '0';
                var mobileBtnUrl = getAttr($mobileActive, 'data-btn-url');
                var mobileBannerKey = getAttr($mobileActive, 'data-banner-key');

                if (mobileBtnEnabled && mobileBtnUrl && mobileBannerKey) {
                    trackBannerClick(mobileBannerKey);
                }
                return;
            }

            var bottomBtn = target.closest('.bottom-cta__button');
            if (bottomBtn) {
                var $bottomSlide = $(bottomBtn).closest('.bottom-cta-slide');
                var bottomBannerKey = getAttr($bottomSlide, 'data-banner-key');
                if (bottomBannerKey) {
                    trackBannerClick(bottomBannerKey);
                }
            }
        }, true);
    });
})(jQuery);
