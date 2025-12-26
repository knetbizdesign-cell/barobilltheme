/**
 * Borobill Theme JavaScript
 * 카테고리 필터 및 인터랙션 기능
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        // 메인 홈: 최상위 카테고리 필터 기능
        $('.filter-item').on('click', function() {
            var category = $(this).data('category');

            // 활성 상태 변경
            $('.filter-item').removeClass('active');
            $(this).addClass('active');

            // 전체 버튼(데이터 category=all) 스타일도 일반 버튼 디폴트로 자동 복귀
            if (category !== 'all') {
                $('.filter-item[data-category="all"]').removeClass('active');
            }

            // 포스트 필터링
            if (category === 'all') {
                $('.article-card').fadeIn(300);
            } else {
                $('.article-card').each(function() {
                    var cardCategory = ($(this).data('category') || '').toString();
                    // data-category 값이 "chobo-biz seomu-biz" 처럼 공백으로 여러 개 들어올 수 있음
                    var roots = cardCategory.split(/\s+/);
                    if (roots.indexOf(category) !== -1) {
                        $(this).fadeIn(300);
                    } else {
                        $(this).fadeOut(300);
                    }
                });
            }
        });

        // 카테고리 상세: 서브 카테고리 필터 기능
        $('.subfilter-item').on('click', function() {
            var subcat = $(this).data('subcat');

            $('.subfilter-item').removeClass('active');
            $(this).addClass('active');

            if (subcat === 'all') {
                $('.article-card').fadeIn(300);
            } else {
                $('.article-card').each(function() {
                    var cardSub = $(this).data('subcat');
                    if (cardSub === subcat) {
                        $(this).fadeIn(300);
                    } else {
                        $(this).fadeOut(300);
                    }
                });
            }
        });
        
        // 카드 스크롤/호버 애니메이션 제거 (정적인 카드)

        // 히어로 슬라이더 (원형 버튼 클릭 시 이미지 전환)
        var $heroCarousel = $('.hero-carousel');
        var $heroSlides   = $heroCarousel.find('.hero-slide');
        var $heroDots     = $('.hero-indicators .dot');
        var $heroBadge    = $('.hero-badge');
        var $heroTitle    = $('.hero-title');
        var $heroWrapper  = $('.hero-wrapper');

        if ($heroCarousel.length && $heroSlides.length && $heroDots.length) {
            var heroCount   = $heroSlides.length;
            var current     = 0;
            var defaultBg   = $heroWrapper.length ? $heroWrapper.css('--hero-bg-color') || '#4f7fcb' : '#4f7fcb';
            var autoTimer   = null;
            var defaultGrad = $heroWrapper.length ? $heroWrapper.css('--hero-grad-bottom') || 'rgba(0,0,0,0.55)' : 'rgba(0,0,0,0.55)';

            // PHP 에서 전달된 설정값 사용
            var settings    = window.borobillHeroSettings || {};
            var autoDelay   = (parseInt(settings.autoDelay, 10) || 6) * 1000;
            var duration    = parseFloat(settings.duration) || 0.6;
            var effect      = settings.effect || 'default';

            // 애니메이션 시간 / 이징을 CSS 변수로 전달
            var ease = 'ease';
            if (effect === 'smooth') {
                ease = 'ease-in-out';
            } else if (effect === 'bounce') {
                ease = 'cubic-bezier(.16,.77,.52,1.13)';
            }
            $heroCarousel.css('--hero-duration', duration + 's');
            $heroCarousel.css('--hero-ease', ease);

            function setHeroSlide(index) {
                if (index < 0 || index >= heroCount) {
                    index = 0;
                }
                current = index;

                // 슬라이드 이동 (가로로 배치된 슬라이드를 translateX 로 이동)
                var offset = -100 * index;
                $heroCarousel.css('transform', 'translateX(' + offset + '%)');

                // 인디케이터 활성 상태 갱신
                $heroDots.removeClass('is-active');
                $heroDots.filter('[data-index="' + index + '"]').addClass('is-active');

                // 텍스트(배지 / 타이틀) 업데이트
                var $slide = $heroSlides.eq(index);
                if ($slide.length) {
                    var badgeText  = $slide.data('badge');
                    var titleHtml  = $slide.data('titleHtml');
                    var bgColor    = $slide.data('bgColor');
                    var gradBottom = $slide.data('gradBottom');

                    if ($heroBadge.length && typeof badgeText !== 'undefined') {
                        $heroBadge.text(badgeText);
                    }
                    if ($heroTitle.length && typeof titleHtml !== 'undefined') {
                        $heroTitle.html(titleHtml);
                    }
                    if ($heroWrapper.length) {
                        $heroWrapper.css('--hero-bg-color', bgColor || defaultBg);
                        $heroWrapper.css('--hero-grad-bottom', gradBottom || defaultGrad);
                    }
                }
            }

            // 초기 상태
            setHeroSlide(0);

            // 도트 클릭 이벤트
            $heroDots.on('click', function() {
                var idx = parseInt($(this).data('index'), 10);
                if (!isNaN(idx)) {
                    setHeroSlide(idx);
                    restartAuto();
                }
            });

            // 자동 슬라이드
            function startAuto() {
                if (autoTimer) {
                    clearInterval(autoTimer);
                }
                autoTimer = setInterval(function() {
                    setHeroSlide(current + 1);
                }, autoDelay);
            }

            function restartAuto() {
                startAuto();
            }

            startAuto();

            // 호버 시 자동 슬라이드 일시 정지
            $heroWrapper.on('mouseenter', function() {
                if (autoTimer) {
                    clearInterval(autoTimer);
                    autoTimer = null;
                }
            }).on('mouseleave', function() {
                startAuto();
            });
        }

        // 헤더 스크롤 효과
        var lastScroll = 0;
        $(window).on('scroll', function() {
            var currentScroll = $(this).scrollTop();
            
            if (currentScroll > 100) {
                $('.site-header').addClass('scrolled');
            } else {
                $('.site-header').removeClass('scrolled');
            }
            
            lastScroll = currentScroll;
        });

        // 부드러운 스크롤
        $('a[href^="#"]').on('click', function(e) {
            var target = $(this.getAttribute('href'));
            if (target.length) {
                e.preventDefault();
                $('html, body').stop().animate({
                    scrollTop: target.offset().top - 80
                }, 1000);
            }
        });
        
    });
    
})(jQuery);

