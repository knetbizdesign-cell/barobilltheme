<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="site-header">
    <div class="header-container">
        <div class="header-content">

            <div class="header-logo">
                <a href="<?php echo esc_url(home_url('/')); ?>" class="logo-link" aria-label="<?php echo esc_attr(get_bloginfo('name')); ?>">
                    <img
                        src="<?php echo esc_url(get_template_directory_uri() . '/images/logo.png'); ?>"
                        class="logo-img logo-img--light"
                        height="22"
                        alt="<?php echo esc_attr(get_bloginfo('name')); ?>"
                    />
                    <img
                        src="<?php echo esc_url(get_template_directory_uri() . '/images/logo3.png'); ?>"
                        class="logo-img logo-img--dark"
                        height="22"
                        alt=""
                        aria-hidden="true"
                    />
                </a>
            </div>

            <nav class="header-nav">
                <?php
                // 데스크톱용 GNB
                wp_nav_menu(
                    array(
                        'theme_location' => 'header-menu',
                        'container'      => false,
                        'menu_class'     => 'nav-menu',
                        'fallback_cb'    => false,
                        'depth'          => 2,
                    )
                );
                ?>
            </nav>

            <!-- PC: 테마 전환 + 검색하기 -->
            <div class="header-actions">
                <button type="button" class="borobill-theme-toggle" aria-label="다크 모드로 전환" aria-pressed="false" title="테마">
                    <span class="borobill-theme-toggle__icon borobill-theme-toggle__icon--moon" aria-hidden="true">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M21 14.5A8.5 8.5 0 0 1 9.5 3 7 7 0 1 0 21 14.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    <span class="borobill-theme-toggle__icon borobill-theme-toggle__icon--sun" aria-hidden="true">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="4" stroke="currentColor" stroke-width="2"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    </span>
                </button>
                <button type="button" class="chatbot-btn" data-open-search aria-label="검색하기">
                    <span class="chatbot-label">검색하기</span>
                </button>
            </div>

            <!-- 모바일 검색 (토스페이먼츠 feed 스타일: 아이콘 → 검색바) -->
            <form class="header-mobile-search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
                <label class="screen-reader-text" for="header-mobile-search-input">검색</label>
                <div class="header-mobile-search-box">
                    <span class="header-mobile-search-icon" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                            <path d="M10.5 18.5a8 8 0 1 1 0-16 8 8 0 0 1 0 16Z" stroke="currentColor" stroke-width="2" />
                            <path d="M17.5 17.5 21 21" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                        </svg>
                    </span>
                    <input id="header-mobile-search-input" class="header-mobile-search-input" type="search" name="s" placeholder="검색어를 입력해주세요." autocomplete="off" />
                </div>
                <button class="header-mobile-search-cancel" type="button">취소</button>
            </form>

            <div class="header-mobile-actions">
                <button type="button" class="borobill-theme-toggle borobill-theme-toggle--mobile" aria-label="다크 모드로 전환" aria-pressed="false" title="테마">
                    <span class="borobill-theme-toggle__icon borobill-theme-toggle__icon--moon" aria-hidden="true">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M21 14.5A8.5 8.5 0 0 1 9.5 3 7 7 0 1 0 21 14.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    <span class="borobill-theme-toggle__icon borobill-theme-toggle__icon--sun" aria-hidden="true">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="4" stroke="currentColor" stroke-width="2"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    </span>
                </button>
                <button class="header-search-toggle" type="button" aria-label="검색 열기" aria-expanded="false" data-open-search>
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                        <path d="M10.5 18.5a8 8 0 1 1 0-16 8 8 0 0 1 0 16Z" stroke="currentColor" stroke-width="2" />
                        <path d="M17.5 17.5 21 21" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                    </svg>
                </button>

                <button class="header-mobile-toggle" type="button" aria-controls="header-mobile-nav" aria-expanded="false" aria-label="메뉴 열기">
                    <span class="menu-icon" aria-hidden="true">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path d="M4 7h16" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                            <path d="M4 12h16" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                            <path d="M4 17h16" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                        </svg>
                    </span>
                    <span class="close-icon" aria-hidden="true">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path d="M6 6 18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                            <path d="M6 18 18 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                        </svg>
                    </span>
                </button>
            </div>

        </div>
    </div>

    <!-- 모바일 메뉴 -->
    <nav id="header-mobile-nav" class="header-mobile-nav" aria-label="모바일 GNB">
        <?php
        wp_nav_menu(
            array(
                'theme_location' => 'header-menu',
                'container'      => false,
                'menu_class'     => 'mobile-nav-menu',
                'fallback_cb'    => false,
                'depth'          => 2,
            )
        );
        ?>
    </nav>
</header>

<?php // 검색 모달 (모든 페이지 공통) ?>
<?php get_template_part( 'template-parts/search-modal' ); ?>
