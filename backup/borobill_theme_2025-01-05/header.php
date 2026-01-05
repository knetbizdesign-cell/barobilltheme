<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="site-header">
    <div class="header-container">
        <div class="header-content">

            <div class="header-logo">
                <a href="<?php echo esc_url(home_url('/')); ?>" class="logo-link">
                    <img src="<?php echo esc_url(get_template_directory_uri() . '/images/logo.png'); ?>" class="logo-img" alt="바로빌">
                </a>
            </div>

            <nav class="header-nav">
                <?php
                // wp_nav_menu 로 Header Menu 출력 (2단계 메뉴까지)
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

            <div class="header-actions">
                <a href="#" class="chatbot-btn">챗봇바로가기</a>
            </div>

        </div>
    </div>
</header>
