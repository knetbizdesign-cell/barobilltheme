<?php

function borobill_theme_setup() {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_image_size( 'borobill_featured', 400, 260, true );
    add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption' ) );

    // header menu location 등록 (관리자에서 메뉴 연결용)
    register_nav_menus(
        array(
            'primary'     => 'Primary Menu',
            'header-menu' => '사이트 상단 GNB',
        )
    );
}
add_action( 'after_setup_theme', 'borobill_theme_setup' );

/**
 * 실시간 미리보기 iframe에서만 관리자 바(검정 영역) 숨김
 * 쿼리 파라미터 borobill_preview_frame=1 일 때 적용
 */
function borobill_hide_admin_bar_in_preview_frame() {
    if ( is_admin() ) {
        return;
    }
    if ( isset( $_GET['borobill_preview_frame'] ) && '1' === $_GET['borobill_preview_frame'] ) {
        add_filter( 'show_admin_bar', '__return_false' );
    }
}
add_action( 'init', 'borobill_hide_admin_bar_in_preview_frame', 1 );

/**
 * 미리보기 iframe용 body 클래스: CSS로 관리자 바 숨김 폴백
 */
function borobill_preview_frame_body_class( $classes ) {
    if ( isset( $_GET['borobill_preview_frame'] ) && '1' === $_GET['borobill_preview_frame'] ) {
        $classes[] = 'borobill-preview-frame';
    }
    return $classes;
}
add_filter( 'body_class', 'borobill_preview_frame_body_class' );

/**
 * 테마 파일 수정 시점 기준 버전 (로컬/운영 캐시 동기화)
 *
 * @param string $relative_path themes/borobill_theme 기준 상대 경로
 * @return string
 */
function borobill_get_theme_file_version( $relative_path ) {
    $path = get_template_directory() . '/' . ltrim( $relative_path, '/' );
    return file_exists( $path ) ? (string) filemtime( $path ) : '1.0';
}

function borobill_enqueue_assets() {
    wp_enqueue_style('pretendard', 'https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.min.css');
    $style_path = get_stylesheet_directory() . '/style.css';
    $style_ver  = file_exists( $style_path ) ? filemtime( $style_path ) : '1.3';
    // Localhost 개발환경에서는 브라우저 캐시로 스타일 반영이 늦지 않도록 버전을 매 요청 갱신
    $host = isset( $_SERVER['HTTP_HOST'] ) ? strtolower( (string) $_SERVER['HTTP_HOST'] ) : '';
    if ( false !== strpos( $host, 'localhost' ) || false !== strpos( $host, '127.0.0.1' ) ) {
        $style_ver = $style_ver . '-' . time();
    }
    wp_enqueue_style('borobill-style', get_stylesheet_uri(), [], $style_ver);

    wp_enqueue_script('jquery');
    $main_js_path = get_template_directory() . '/js/main.js';
    $main_js_ver  = file_exists( $main_js_path ) ? filemtime( $main_js_path ) : '1.1';
    wp_enqueue_script('borobill-main', get_template_directory_uri() . '/js/main.js', ['jquery'], $main_js_ver, true);

    $banner_insight_js_path = get_template_directory() . '/js/banner-insights.js';
    $banner_insight_js_ver  = file_exists( $banner_insight_js_path ) ? filemtime( $banner_insight_js_path ) : '1.0';
    wp_enqueue_script(
        'borobill-banner-insights',
        get_template_directory_uri() . '/js/banner-insights.js',
        array( 'jquery', 'borobill-main' ),
        $banner_insight_js_ver,
        true
    );
    wp_localize_script(
        'borobill-banner-insights',
        'borobillBannerInsight',
        array(
            'ajaxUrl' => admin_url( 'admin-ajax.php', 'relative' ),
            'nonce'   => wp_create_nonce( 'borobill_banner_insight' ),
        )
    );

    if ( ! is_front_page() ) {
        wp_enqueue_style(
            'xeicon',
            'https://cdn.jsdelivr.net/npm/xeicon@2.3.3/xeicon.min.css',
            array(),
            '2.3.3'
        );
    }
    if ( is_singular( 'post' ) ) {
        wp_enqueue_script( 'single-sticky-toc', get_template_directory_uri() . '/js/single-sticky-toc.js', [], '1.0', true );

        $share_modal_path = get_template_directory() . '/js/share-modal.js';
        $share_modal_ver  = file_exists( $share_modal_path ) ? filemtime( $share_modal_path ) : '1.0';
        wp_enqueue_script( 'borobill-share-modal', get_template_directory_uri() . '/js/share-modal.js', array(), $share_modal_ver, true );
    }
    if ( is_category() || is_front_page() || is_home() ) {
        wp_enqueue_script(
            'iconify-icon',
            'https://code.iconify.design/iconify-icon/2.3.0/iconify-icon.min.js',
            array(),
            '2.3.0',
            true
        );
    }

    // 스크롤 등장 애니메이션 (지연 부팅으로 초기 로딩 가벼움)
    $scroll_reveal_path = get_template_directory() . '/js/scroll-reveal.js';
    $scroll_reveal_ver  = file_exists( $scroll_reveal_path ) ? filemtime( $scroll_reveal_path ) : '1.0';
    wp_enqueue_script( 'borobill-scroll-reveal', get_template_directory_uri() . '/js/scroll-reveal.js', [], $scroll_reveal_ver, true );

    // 검색 모달 JS
    $search_modal_path = get_template_directory() . '/js/search-modal.js';
    $search_modal_ver  = file_exists( $search_modal_path ) ? filemtime( $search_modal_path ) : '1.0';
    wp_enqueue_script( 'borobill-search-modal', get_template_directory_uri() . '/js/search-modal.js', [], $search_modal_ver, true );
    wp_localize_script( 'borobill-search-modal', 'borobillSearchModal', array(
        'apiBase' => esc_url_raw( rest_url( 'wp/v2/' ) ),
    ) );

    if ( is_admin() ) {
        borobill_enqueue_editor_button_assets();
    }

    // 히어로 슬라이드 설정값을 JS 로 전달
    $auto_delay    = (int) get_option( 'borobill_hero_auto_delay', 6 );
    $anim_duration = (float) get_option( 'borobill_hero_anim_duration', 0.6 );
    $anim_effect   = get_option( 'borobill_hero_anim_effect', 'default' );

    wp_localize_script(
        'borobill-main',
        'borobillHeroSettings',
        array(
            'autoDelay' => $auto_delay,
            'duration'  => $anim_duration,
            'effect'    => $anim_effect,
            'restUrl'   => esc_url_raw( rest_url( 'wp/v2/' ) ),
        )
    );

    // 하단 배너 슬라이드 전환 설정값을 JS 로 전달
    $bb_auto_delay    = (int) get_option( 'borobill_bottom_banner_auto_delay', 6 );
    $bb_anim_duration = (float) get_option( 'borobill_bottom_banner_anim_duration', 0.6 );
    $bb_anim_effect   = (string) get_option( 'borobill_bottom_banner_anim_effect', 'default' );

    wp_localize_script(
        'borobill-main',
        'borobillBottomBannerSettings',
        array(
            'autoDelay' => $bb_auto_delay,
            'duration'  => $bb_anim_duration,
            'effect'    => $bb_anim_effect,
        )
    );
}
add_action('wp_enqueue_scripts', 'borobill_enqueue_assets');
add_action( 'admin_enqueue_scripts', 'borobill_enqueue_editor_button_assets' );

/**
 * 워드 붙여넣기 span(dir/lang) 제거 + 인접 bold 병합
 */
function borobill_clean_word_paste_spans( $content ) {
    if ( is_admin() || '' === (string) $content ) {
        return $content;
    }

    $prev = null;
    while ( $prev !== $content ) {
        $prev    = $content;
        $content = preg_replace(
            '/<span[^>]*\sdir=["\']LTR["\'][^>]*\slang=["\']en-US["\'][^>]*>(.*?)<\/span>/is',
            '$1',
            $content
        );
        $content = preg_replace(
            '/<span[^>]*\slang=["\']en-US["\'][^>]*\sdir=["\']LTR["\'][^>]*>(.*?)<\/span>/is',
            '$1',
            $content
        );
    }

    $content = preg_replace( '/<\/b>\s*<b>/', '', $content );
    $content = preg_replace( '/<\/strong>\s*<strong>/', '', $content );

    return $content;
}
add_filter( 'the_content', 'borobill_clean_word_paste_spans', 12 );

/**
 * 다크 모드: localStorage 값을 최대한 빨리 적용해 깜빡임(FUOC) 완화
 */
function borobill_color_scheme_head_script() {
    if ( is_admin() ) {
        return;
    }
    ?>
<script>
(function(){try{var k='borobill-theme',t=localStorage.getItem(k);if(t==='dark'||t==='light'){document.documentElement.setAttribute('data-theme',t);}else{document.documentElement.setAttribute('data-theme','light');}}catch(e){document.documentElement.setAttribute('data-theme','light');}})();
</script>
    <?php
}
add_action( 'wp_head', 'borobill_color_scheme_head_script', 0 );

/**
 * 새로고침 시 스크롤 위치 복원하지 않고 항상 맨 위에서 시작
 */
function borobill_scroll_to_top_on_refresh() {
    if ( is_admin() ) {
        return;
    }
    ?>
    <script>
    (function() {
        function isReloadNav() {
            try {
                if ( window.performance && performance.getEntriesByType ) {
                    var navEntries = performance.getEntriesByType('navigation');
                    if ( navEntries && navEntries.length ) {
                        return navEntries[0].type === 'reload';
                    }
                }
            } catch (e) {}
            try {
                // Legacy fallback (Chrome/Edge 구버전)
                return window.performance && performance.navigation && performance.navigation.type === 1;
            } catch (e2) {}
            return false;
        }

        var isReload = isReloadNav();
        if ( isReload && ( 'scrollRestoration' in history ) ) {
            history.scrollRestoration = 'manual';
        }
        if ( isReload ) {
            if ( document.readyState === 'loading' ) {
                document.addEventListener( 'DOMContentLoaded', function() {
                    window.scrollTo( 0, 0 );
                });
            } else {
                window.scrollTo( 0, 0 );
            }
        }
    })();
    </script>
    <?php
}
add_action( 'wp_head', 'borobill_scroll_to_top_on_refresh', 1 );

/**
 * 관리자 글 편집 "실시간 미리보기" iframe 전용: 싱글 페이지 하단 영역 트림
 * - URL에 _bbpv 파라미터가 붙은 경우에만 적용
 * - 최신글(관련글) 섹션/사이드바/푸터 등은 숨김
 */
function borobill_trim_single_for_admin_preview_iframe() {
    if ( is_admin() ) {
        return;
    }
    if ( ! isset( $_GET['_bbpv'] ) ) {
        return;
    }
    if ( ! is_singular( 'post' ) ) {
        return;
    }
    ?>
    <style id="borobill-admin-preview-trim">
        /* 최신글(관련글) 섹션 이하 + 푸터 숨김 */
        .single-related,
        .single-aside.sticky-group,
        .site-footer {
            display: none !important;
        }

        /* 미리보기에서는 본문에 집중: LNB 제거 + 단일 컬럼 */
        .layout--single {
            margin-bottom: 0 !important;
        }
        .single-lnb {
            display: none !important;
        }
        .single-shell {
            display: block !important;
        }
        .single-main {
            grid-column: auto !important;
            max-width: 780px !important;
            margin: 0 auto !important;
        }

        /* 하단 여백 과다 방지 */
        .post-content {
            padding-bottom: 36px !important;
        }
    </style>
    <?php
}
add_action( 'wp_head', 'borobill_trim_single_for_admin_preview_iframe', 99 );

function borobill_enqueue_editor_button_assets() {
    $screen = get_current_screen();
    if ( ! $screen || 'post' !== $screen->post_type ) {
        return;
    }

    wp_enqueue_media();

    wp_enqueue_script(
        'borobill-post-banner-admin',
        get_template_directory_uri() . '/js/post-banner-admin.js',
        array( 'jquery', 'media-editor' ),
        borobill_get_theme_file_version( 'js/post-banner-admin.js' ),
        true
    );

    wp_enqueue_script(
        'borobill-editor-tools-bar',
        get_template_directory_uri() . '/js/editor-tools-bar.js',
        array( 'jquery', 'editor', 'editor-expand' ),
        borobill_get_theme_file_version( 'js/editor-tools-bar.js' ),
        true
    );

    wp_enqueue_style(
        'borobill-editor-button',
        get_template_directory_uri() . '/css/editor-button.css',
        array(),
        borobill_get_theme_file_version( 'css/editor-button.css' )
    );
}

function borobill_is_post_content_editor( $editor_id ) {
    if ( 'content' !== $editor_id ) {
        return false;
    }
    $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
    return $screen && 'post' === $screen->post_type && 'post' === $screen->base;
}

function borobill_configure_post_editor_row1( $buttons, $editor_id = '' ) {
    if ( ! borobill_is_post_content_editor( $editor_id ) ) {
    return $buttons;
    }
    return array_values(
        array_diff(
            $buttons,
            array( 'wp_adv', 'fullscreen', 'dfw', 'borobill_summary' )
        )
    );
}

function borobill_add_editor_plugins( $plugin_array, $editor_id ) {
    if ( borobill_is_post_content_editor( $editor_id ) ) {
        $plugin_array['borobill_editor'] = get_template_directory_uri() . '/js/editor-button.js';
    }
    return $plugin_array;
}

function borobill_add_table_editor_plugin( $plugin_array, $editor_id ) {
    if ( ! borobill_is_post_content_editor( $editor_id ) ) {
        return $plugin_array;
    }
    $path = get_template_directory() . '/js/editor-table.js';
    $plugin_array['borobill_table'] = add_query_arg(
        'ver',
        filemtime( $path ),
        get_template_directory_uri() . '/js/editor-table.js'
    );
    return $plugin_array;
}

function borobill_add_editor_insert_row( $buttons, $editor_id ) {
    if ( ! borobill_is_post_content_editor( $editor_id ) ) {
        return $buttons;
    }
    $row = array(
        'removeformat',
        'charmap',
        'outdent',
        'indent',
        'borobill_table',
        'undo',
        'redo',
    );
    if ( ! wp_is_mobile() ) {
        $row[] = 'wp_help';
    }
    return $row;
}

function borobill_configure_editor_format_row( $buttons, $editor_id ) {
    if ( ! borobill_is_post_content_editor( $editor_id ) ) {
        return $buttons;
    }
    $row = array(
        'fontselect',
        'fontsizeselect',
        'underline',
        'strikethrough',
        'forecolor',
        'backcolor',
        'borobill_lineheight',
        'hr',
        'pastetext',
    );
    return $row;
}

function borobill_post_editor_tinymce_fonts( $init, $editor_id = '' ) {
    if ( ! borobill_is_post_content_editor( $editor_id ) ) {
        return $init;
    }
    $init['wordpress_adv_hidden'] = false;
    $init['fontsize_formats'] = '12px 14px 15px 16px 18px 20px 24px';
    $init['font_formats']     =
        "맑은 고딕='Malgun Gothic',sans-serif;" .
        'Arial=arial,helvetica,sans-serif;' .
        'Times New Roman=times new roman,times,serif;' .
        'Georgia=georgia,palatino,serif;' .
        'Verdana=verdana,geneva,sans-serif;' .
        'Tahoma=tahoma,arial,helvetica,sans-serif;' .
        'Courier New=courier new,courier,monospace';
    return $init;
}

add_filter( 'mce_buttons', 'borobill_configure_post_editor_row1', 10, 2 );
add_filter( 'mce_external_plugins', 'borobill_add_editor_plugins', 10, 2 );
add_filter( 'mce_external_plugins', 'borobill_add_table_editor_plugin', 10, 2 );
add_filter( 'mce_buttons_3', 'borobill_add_editor_insert_row', 10, 2 );
add_filter( 'mce_buttons_2', 'borobill_configure_editor_format_row', 10, 2 );
add_filter( 'tiny_mce_before_init', 'borobill_post_editor_tinymce_fonts', 20, 2 );

/**
 * 관리자 본문 에디터(TinyMCE) 기본 서체를 고딕 계열로 통일
 */
function borobill_admin_editor_gothic_font( $init ) {
    $gothic_css = "body#tinymce.wp-editor{font-family:'Pretendard','Noto Sans KR','Malgun Gothic','Apple SD Gothic Neo',sans-serif;font-size:16px;line-height:1.8;}" .
        /* 요약 블록: 회색 배경만 유지 (라벨/점선 제거) */
        "body#tinymce.wp-editor .post-summary{position:relative;background:#eef2f7;border:0;border-radius:12px;padding:18px 18px 16px;margin:18px 0;min-height:72px;}" .
        "body#tinymce.wp-editor .post-summary p{margin:0;}" .
        /* 편집기 내 표도 프론트와 동일하게 강제 */
        "body#tinymce.wp-editor table{width:100%;max-width:100%;table-layout:auto;border-collapse:separate;border-spacing:0;margin:1.6em 0;background:#fff;border:1px solid #d6dde7;border-radius:15px;overflow:hidden;outline:1px solid #d6dde7;outline-offset:-1px;box-shadow:none;}" .
        "body#tinymce.wp-editor th,body#tinymce.wp-editor td{border-right:1px solid #d6dde7 !important;border-bottom:1px solid #d6dde7 !important;padding:10px 20px !important;text-align:left !important;vertical-align:middle !important;word-break:break-word;overflow-wrap:anywhere;white-space:normal;font-size:16px !important;font-weight:400 !important;line-height:1.65 !important;color:#222e3c !important;}" .
        "body#tinymce.wp-editor th{background:#f5f8fb !important;font-weight:700 !important;color:#111827 !important;}" .
        "body#tinymce.wp-editor tr > td:first-child{background:#f5f8fb !important;font-weight:700 !important;}" .
        "body#tinymce.wp-editor tr:first-child > td{background:#f5f8fb !important;font-weight:700 !important;}" .
        "body#tinymce.wp-editor th *,body#tinymce.wp-editor td *{font-weight:inherit !important;font-size:inherit !important;color:inherit !important;line-height:inherit !important;}" .
        "body#tinymce.wp-editor tr > th:last-child,body#tinymce.wp-editor tr > td:last-child{border-right:0 !important;}" .
        "body#tinymce.wp-editor tbody tr:last-child > th,body#tinymce.wp-editor tbody tr:last-child > td,body#tinymce.wp-editor thead tr:last-child > th,body#tinymce.wp-editor thead tr:last-child > td{border-bottom:0 !important;}";

    if ( isset( $init['content_style'] ) && is_string( $init['content_style'] ) && '' !== trim( $init['content_style'] ) ) {
        $init['content_style'] .= ' ' . $gothic_css;
    } else {
        $init['content_style'] = $gothic_css;
    }

    return $init;
}
add_filter( 'tiny_mce_before_init', 'borobill_admin_editor_gothic_font' );

/**
 * 글 서브타이틀 메타값 반환
 */
function borobill_get_post_subtitle( $post_id = null ) {
    $post_id = $post_id ? (int) $post_id : (int) get_the_ID();
    if ( $post_id <= 0 ) {
        return '';
    }
    $subtitle = get_post_meta( $post_id, '_borobill_subtitle', true );
    return is_string( $subtitle ) ? trim( $subtitle ) : '';
}

/**
 * REST API: 글 서브타이틀 노출 (메인/카테고리 검색 필터용)
 */
function borobill_register_rest_post_fields() {
    register_rest_field(
        'post',
        'borobill_subtitle',
        array(
            'get_callback' => function ( $post_arr ) {
                $post_id = isset( $post_arr['id'] ) ? (int) $post_arr['id'] : 0;
                return $post_id > 0 ? borobill_get_post_subtitle( $post_id ) : '';
            },
            'schema'       => array(
                'description' => 'Post subtitle (borobill)',
                'type'        => 'string',
                'context'     => array( 'view', 'edit' ),
            ),
        )
    );

    // 조회수(추천순 정렬용) — 카테고리 서브페이지 전체게시글 정렬에 사용
    register_rest_field(
        'post',
        'borobill_views',
        array(
            'get_callback' => function ( $post_arr ) {
                $post_id = isset( $post_arr['id'] ) ? (int) $post_arr['id'] : 0;
                if ( $post_id <= 0 ) {
                    return 0;
                }
                $views = get_post_meta( $post_id, '_bb_views', true );
                return is_numeric( $views ) ? (int) $views : 0;
            },
            'schema' => array(
                'description' => 'Post view count (borobill, for recommended sort)',
                'type'       => 'integer',
                'context'    => array( 'view' ),
            ),
        )
    );
}
add_action( 'rest_api_init', 'borobill_register_rest_post_fields' );

/**
 * 카드/리스트용 요약 텍스트 반환
 * - 서브타이틀이 있으면 우선 사용
 * - 없으면 excerpt 사용
 */
function borobill_get_post_summary_text( $post_id = null, $word_limit = 24 ) {
    $post_id   = $post_id ? (int) $post_id : (int) get_the_ID();
    $subtitle  = borobill_get_post_subtitle( $post_id );
    $word_limit = (int) $word_limit;
    if ( $word_limit < 1 ) {
        $word_limit = 24;
    }

    if ( '' !== $subtitle ) {
        return wp_trim_words( $subtitle, $word_limit, '...' );
    }

    return wp_trim_words( wp_strip_all_tags( get_the_excerpt( $post_id ) ), $word_limit, '...' );
}

/**
 * 글 편집 화면: 제목 아래 서브타이틀 입력창
 */
function borobill_render_post_subtitle_field_after_title( $post ) {
    if ( ! $post instanceof WP_Post || 'post' !== $post->post_type ) {
        return;
    }

    $subtitle = borobill_get_post_subtitle( (int) $post->ID );
    wp_nonce_field( 'borobill_save_subtitle', 'borobill_subtitle_nonce' );
    ?>
    <div class="borobill-subtitle-field">
        <label for="borobill_subtitle"><strong>서브 타이틀</strong></label>
        <input
            type="text"
            id="borobill_subtitle"
            name="borobill_subtitle"
            value="<?php echo esc_attr( $subtitle ); ?>"
            placeholder="큰 타이틀 아래에 노출할 서브 타이틀을 입력하세요."
            style="width:100%;max-width:none;margin-top:6px;"
        />
    </div>
    <?php
}
add_action( 'edit_form_after_title', 'borobill_render_post_subtitle_field_after_title' );

/**
 * 글 저장 시 서브타이틀 저장
 */
function borobill_save_post_subtitle( $post_id ) {
    if ( ! isset( $_POST['borobill_subtitle_nonce'] ) ) {
        return;
    }
    if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['borobill_subtitle_nonce'] ) ), 'borobill_save_subtitle' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( wp_is_post_revision( $post_id ) ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }
    if ( isset( $_POST['post_type'] ) && 'post' !== $_POST['post_type'] ) {
        return;
    }

    $subtitle = isset( $_POST['borobill_subtitle'] ) ? sanitize_text_field( wp_unslash( $_POST['borobill_subtitle'] ) ) : '';
    if ( '' === $subtitle ) {
        delete_post_meta( $post_id, '_borobill_subtitle' );
    } else {
        update_post_meta( $post_id, '_borobill_subtitle', $subtitle );
    }
}
add_action( 'save_post_post', 'borobill_save_post_subtitle' );

/**
 * YouTube URL에서 동영상 ID 추출
 */
function borobill_get_youtube_video_id( $url ) {
    if ( ! is_string( $url ) || '' === trim( $url ) ) {
        return '';
    }
    $url = trim( $url );
    if ( preg_match( '#(?:youtu\.be/|youtube\.com/(?:embed/|v/|shorts/))([\w-]{11})(?:[?&].*)?$#i', $url, $m ) ) {
        return $m[1];
    }
    if ( preg_match( '#[?&]v=([\w-]{11})(?:&|$)#i', $url, $m ) ) {
        return $m[1];
    }
    return '';
}

/**
 * 글 대표 영상(YouTube) URL 반환
 */
function borobill_get_post_featured_youtube_url( $post_id = null ) {
    $post_id = $post_id ? (int) $post_id : (int) get_the_ID();
    if ( $post_id <= 0 ) {
        return '';
    }
    $url = get_post_meta( $post_id, '_borobill_featured_youtube', true );
    return is_string( $url ) ? trim( $url ) : '';
}

/**
 * 글 편집 화면(사이드): 대표 영상(YouTube) 메타박스
 */
function borobill_add_featured_youtube_metabox() {
    add_meta_box(
        'borobill_featured_youtube',
        '대표 영상 (YouTube)',
        'borobill_render_featured_youtube_metabox',
        'post',
        'side',
        'default'
    );
}
add_action( 'add_meta_boxes', 'borobill_add_featured_youtube_metabox' );

function borobill_render_featured_youtube_metabox( $post ) {
    if ( ! $post instanceof WP_Post ) {
        return;
    }
    wp_nonce_field( 'borobill_save_featured_youtube', 'borobill_featured_youtube_nonce' );
    $url = borobill_get_post_featured_youtube_url( (int) $post->ID );
    ?>
    <p style="margin:0 0 6px;">
        <label for="borobill_featured_youtube">YouTube URL</label>
    </p>
    <p style="margin:0;">
        <input
            type="url"
            id="borobill_featured_youtube"
            name="borobill_featured_youtube"
            value="<?php echo esc_attr( $url ); ?>"
            placeholder="https://www.youtube.com/watch?v=..."
            style="width:100%;"
        />
    </p>
    <p style="margin:8px 0 0; font-size:12px; color:#646970;">
        입력 시 게시글 상단 이미지 영역에 영상이 표시됩니다. 비우면 대표 이미지가 표시됩니다.
    </p>
    <?php
}

function borobill_save_featured_youtube_meta( $post_id ) {
    if ( ! isset( $_POST['borobill_featured_youtube_nonce'] ) ) {
        return;
    }
    if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['borobill_featured_youtube_nonce'] ) ), 'borobill_save_featured_youtube' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( wp_is_post_revision( $post_id ) ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }
    if ( isset( $_POST['post_type'] ) && 'post' !== $_POST['post_type'] ) {
        return;
    }

    $url = isset( $_POST['borobill_featured_youtube'] ) ? esc_url_raw( trim( wp_unslash( $_POST['borobill_featured_youtube'] ) ) ) : '';
    if ( '' === $url ) {
        delete_post_meta( $post_id, '_borobill_featured_youtube' );
    } else {
        update_post_meta( $post_id, '_borobill_featured_youtube', $url );
    }
}
add_action( 'save_post_post', 'borobill_save_featured_youtube_meta' );

/**
 * 글 하단 배너 이미지 URL
 *
 * @param int|null $post_id
 * @return string
 */
function borobill_get_post_banner_image( $post_id = null ) {
    $post_id = $post_id ? (int) $post_id : (int) get_the_ID();
    if ( $post_id <= 0 ) {
        return '';
    }
    $url = get_post_meta( $post_id, '_borobill_post_banner_image', true );
    return is_string( $url ) ? trim( $url ) : '';
}

/**
 * 글 하단 배너 클릭 링크 URL
 *
 * @param int|null $post_id
 * @return string
 */
function borobill_get_post_banner_url( $post_id = null ) {
    $post_id = $post_id ? (int) $post_id : (int) get_the_ID();
    if ( $post_id <= 0 ) {
        return '';
    }
    $url = get_post_meta( $post_id, '_borobill_post_banner_url', true );
    return is_string( $url ) ? trim( $url ) : '';
}

/**
 * 글 하단 배너 이미지 alt 텍스트
 *
 * @param int|null $post_id
 * @return string
 */
function borobill_get_post_banner_alt( $post_id = null ) {
    $post_id = $post_id ? (int) $post_id : (int) get_the_ID();
    if ( $post_id <= 0 ) {
        return '';
    }
    $alt = get_post_meta( $post_id, '_borobill_post_banner_alt', true );
    return is_string( $alt ) ? trim( $alt ) : '';
}

/**
 * 글 편집 화면(사이드): 배너 메타박스 — 대표 영상 아래
 */
function borobill_add_post_banner_metabox() {
    add_meta_box(
        'borobill_post_banner',
        '배너',
        'borobill_render_post_banner_metabox',
        'post',
        'side',
        'default'
    );
}
add_action( 'add_meta_boxes', 'borobill_add_post_banner_metabox' );

function borobill_render_post_banner_metabox( $post ) {
    if ( ! $post instanceof WP_Post ) {
        return;
    }

    wp_nonce_field( 'borobill_save_post_banner', 'borobill_post_banner_nonce' );

    $image     = borobill_get_post_banner_image( (int) $post->ID );
    $url       = borobill_get_post_banner_url( (int) $post->ID );
    $alt       = borobill_get_post_banner_alt( (int) $post->ID );
    $has_image = '' !== $image;
    $icon_url  = get_template_directory_uri() . '/images/icon-img-box-duotone-line.svg';
    ?>
    <p class="borobill-post-banner-field">
        <label for="borobill_post_banner_image"><strong>배너 이미지</strong></label>
    </p>
    <div
        id="borobill_post_banner_image_preview"
        class="borobill-post-banner-preview<?php echo $has_image ? ' is-filled' : ''; ?>"
    >
        <div class="borobill-post-banner-preview__empty">
            <img
                class="borobill-post-banner-preview__icon"
                src="<?php echo esc_url( $icon_url ); ?>"
                width="32"
                height="32"
                alt=""
                aria-hidden="true"
            />
            <span class="borobill-post-banner-preview__label">이미지 첨부</span>
        </div>
        <img
            class="borobill-post-banner-preview__image"
            src="<?php echo esc_url( $image ); ?>"
            alt=""
        />
    </div>
    <p style="margin:8px 0 0;display:flex;gap:6px;flex-wrap:wrap;">
        <input
            type="hidden"
            id="borobill_post_banner_image"
            name="borobill_post_banner_image"
            value="<?php echo esc_attr( $image ); ?>"
        />
        <button
            type="button"
            class="button borobill-post-banner-image-select"
            data-target-input="#borobill_post_banner_image"
            data-target-preview="#borobill_post_banner_image_preview"
        >이미지 선택</button>
        <button
            type="button"
            class="button borobill-post-banner-image-remove"
            data-target-input="#borobill_post_banner_image"
            data-target-preview="#borobill_post_banner_image_preview"
        >제거</button>
    </p>
    <p style="margin:6px 0 0;font-size:12px;color:#646970;">
        출력 사이즈: 가로 680px(100%) × 세로 200px
    </p>
    <p style="margin:12px 0 6px;">
        <label for="borobill_post_banner_alt"><strong>배너 이미지 alt</strong></label>
    </p>
    <p style="margin:0;">
        <input
            type="text"
            id="borobill_post_banner_alt"
            name="borobill_post_banner_alt"
            value="<?php echo esc_attr( $alt ); ?>"
            placeholder="이미지 대체 텍스트"
            style="width:100%;"
        />
    </p>
    <p style="margin:12px 0 6px;">
        <label for="borobill_post_banner_url"><strong>배너 클릭 링크</strong></label>
    </p>
    <p style="margin:0;">
        <input
            type="url"
            id="borobill_post_banner_url"
            name="borobill_post_banner_url"
            value="<?php echo esc_attr( $url ); ?>"
            placeholder="https://"
            style="width:100%;"
        />
    </p>
    <p style="margin:8px 0 0;font-size:12px;color:#646970;">
        이미지를 등록하면 게시글 본문 가장 아래에 배너가 표시됩니다.
    </p>
    <?php
}

function borobill_save_post_banner_meta( $post_id ) {
    if ( ! isset( $_POST['borobill_post_banner_nonce'] ) ) {
        return;
    }
    if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['borobill_post_banner_nonce'] ) ), 'borobill_save_post_banner' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( wp_is_post_revision( $post_id ) ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }
    if ( isset( $_POST['post_type'] ) && 'post' !== $_POST['post_type'] ) {
        return;
    }

    $image = isset( $_POST['borobill_post_banner_image'] ) ? esc_url_raw( trim( wp_unslash( $_POST['borobill_post_banner_image'] ) ) ) : '';
    if ( '' === $image ) {
        delete_post_meta( $post_id, '_borobill_post_banner_image' );
    } else {
        update_post_meta( $post_id, '_borobill_post_banner_image', $image );
    }

    $url = isset( $_POST['borobill_post_banner_url'] ) ? esc_url_raw( trim( wp_unslash( $_POST['borobill_post_banner_url'] ) ) ) : '';
    if ( '' === $url ) {
        delete_post_meta( $post_id, '_borobill_post_banner_url' );
    } else {
        update_post_meta( $post_id, '_borobill_post_banner_url', $url );
    }

    $alt = isset( $_POST['borobill_post_banner_alt'] ) ? sanitize_text_field( trim( wp_unslash( $_POST['borobill_post_banner_alt'] ) ) ) : '';
    if ( '' === $alt ) {
        delete_post_meta( $post_id, '_borobill_post_banner_alt' );
    } else {
        update_post_meta( $post_id, '_borobill_post_banner_alt', $alt );
    }
}
add_action( 'save_post_post', 'borobill_save_post_banner_meta' );

/**
 * 글 편집 화면: 리딩타임(소요시간 N분) 입력 메타박스
 * - meta_key: _borobill_reading_time (숫자 N → 프론트에서 "N분" 표시)
 */
function borobill_add_reading_time_metabox() {
    add_meta_box(
        'borobill_reading_time',
        '리딩타임',
        'borobill_render_reading_time_metabox',
        'post',
        'normal',
        'default'
    );
}
add_action( 'add_meta_boxes', 'borobill_add_reading_time_metabox' );

function borobill_render_reading_time_metabox( $post ) {
    if ( ! $post instanceof WP_Post ) {
        return;
    }
    wp_nonce_field( 'borobill_save_reading_time', 'borobill_reading_time_nonce' );
    $n = get_post_meta( (int) $post->ID, '_borobill_reading_time', true );
    $n = is_numeric( $n ) && (int) $n > 0 ? (int) $n : 3;
    ?>
    <!--
    <p style="margin:0 0 8px;">
        <label for="borobill_reading_time">리딩타임</label>
    </p>
    -->
    <p style="margin:0;">
        <input
            type="number"
            id="borobill_reading_time"
            name="borobill_reading_time"
            value="<?php echo esc_attr( $n ); ?>"
            min="1"
            step="1"
            style="width:100%; max-width:120px;"
        />
        <span style="display:inline-block; margin-left:6px; color:#666;">분</span>
    </p>
    <p style="margin:8px 0 0; font-size:12px; color:#666;">숫자를 입력하면 상세글 우측에 "소요시간 N분"으로 표시됩니다.</p>
    <?php
}

function borobill_save_reading_time_meta( $post_id ) {
    if ( ! isset( $_POST['borobill_reading_time_nonce'] ) ) {
        return;
    }
    if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['borobill_reading_time_nonce'] ) ), 'borobill_save_reading_time' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( wp_is_post_revision( $post_id ) ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }
    if ( isset( $_POST['post_type'] ) && 'post' !== $_POST['post_type'] ) {
        return;
    }

    $n = isset( $_POST['borobill_reading_time'] ) ? absint( wp_unslash( $_POST['borobill_reading_time'] ) ) : 0;
    if ( $n > 0 ) {
        update_post_meta( $post_id, '_borobill_reading_time', $n );
    } else {
        delete_post_meta( $post_id, '_borobill_reading_time' );
    }
}
add_action( 'save_post_post', 'borobill_save_reading_time_meta' );

/**
 * 글 편집 화면: 리딩타임을 사이드바에 두고, 타이틀 영역과 위쪽 정렬
 */
function borobill_align_reading_time_with_title() {
    $screen = get_current_screen();
    if ( ! $screen || 'post' !== $screen->id ) {
        return;
    }
    ?>
    <script>
    (function() {
        function alignReadingTime() {
            var readingTime = document.getElementById('borobill_reading_time');
            if (!readingTime) return;
            var postbox = readingTime.closest('.postbox');
            if (!postbox) return;
            postbox.classList.add('borobill-reading-time-postbox');
            var titleArea = document.querySelector('.borobill-post-header-card, #titlediv, #titlewrap');
            var sidebar = document.getElementById('postbox-container-1');
            if (titleArea && sidebar) {
                var titleTop = titleArea.getBoundingClientRect().top + (window.pageYOffset || document.documentElement.scrollTop);
                var sidebarTop = sidebar.getBoundingClientRect().top + (window.pageYOffset || document.documentElement.scrollTop);
                var margin = Math.max(0, Math.round(titleTop - sidebarTop));
                postbox.style.marginTop = margin + 'px';
            }
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', alignReadingTime);
        } else {
            alignReadingTime();
        }
        if (typeof jQuery !== 'undefined') {
            jQuery(function() { alignReadingTime(); });
        }
    })();
    </script>
    <?php
}
add_action( 'admin_footer-post.php', 'borobill_align_reading_time_with_title' );
add_action( 'admin_footer-post-new.php', 'borobill_align_reading_time_with_title' );

/**
 * 메인/카테고리 목록은 JS REST로 렌더 — 메인 쿼리 비용만 최소화
 */
function borobill_set_home_posts_per_page( $query ) {
    if ( is_admin() || ! $query->is_main_query() ) {
        return;
    }

    if ( $query->is_home() || $query->is_front_page() || $query->is_category() ) {
        $query->set( 'posts_per_page', 1 );
        $query->set( 'no_found_rows', true );
        $query->set( 'update_post_meta_cache', false );
        $query->set( 'update_post_term_cache', false );
    }
}
add_action( 'pre_get_posts', 'borobill_set_home_posts_per_page' );

/**
 * 메인 페이지: 상위 카테고리 필터(rootcat 파라미터) 적용
 * - rootcat=slug 가 있으면 해당 상위 카테고리 글만 메인 리스트/페이지네이션에 노출
 */
function borobill_home_category_filter( $query ) {
    if ( is_admin() || ! $query->is_main_query() ) {
        return;
    }

    if ( $query->is_home() || $query->is_front_page() ) {
        $root_slug = isset( $_GET['rootcat'] ) ? sanitize_text_field( wp_unslash( $_GET['rootcat'] ) ) : '';

        if ( $root_slug && 'all' !== $root_slug ) {
            $term = get_term_by( 'slug', $root_slug, 'category' );
            if ( $term && ! is_wp_error( $term ) ) {
                $query->set( 'cat', (int) $term->term_id );
            }
        }
    }
}
add_action( 'pre_get_posts', 'borobill_home_category_filter', 11 );

/**
 * 메인 슬라이드(히어로 섹션) 설정용 테마 옵션
 * - 슬라이드별 이미지 / 텍스트 / 배경 색상
 * - (기존) 공통 배경 색상은 기본값으로 사용
 */
function borobill_get_theme_hero_slides_settings_group() {
	return 'borobill_theme_hero_slides';
}

function borobill_get_theme_hero_transition_settings_group() {
	return 'borobill_theme_hero_transition';
}

function borobill_get_theme_bottom_banner_settings_group() {
	return 'borobill_theme_bottom_banner';
}

function borobill_get_theme_bottom_banner_transition_settings_group() {
	return 'borobill_theme_bottom_banner_transition';
}

/**
 * 선택 가능한 CSS 색상 (hex / rgb / rgba, 빈 값 허용)
 *
 * @param mixed $value
 * @return string
 */
function borobill_sanitize_optional_css_color( $value ) {
	if ( ! is_string( $value ) ) {
		return '';
	}
	$value = trim( $value );
	if ( '' === $value ) {
		return '';
	}

	$hex = sanitize_hex_color( $value );
	if ( is_string( $hex ) && '' !== $hex ) {
		return $hex;
	}

	if ( preg_match( '/^rgba?\(\s*([\d.]+)\s*,\s*([\d.]+)\s*,\s*([\d.]+)(?:\s*,\s*([\d.]+))?\s*\)$/i', $value, $m ) ) {
		$r = (int) max( 0, min( 255, round( (float) $m[1] ) ) );
		$g = (int) max( 0, min( 255, round( (float) $m[2] ) ) );
		$b = (int) max( 0, min( 255, round( (float) $m[3] ) ) );
		if ( isset( $m[4] ) && '' !== $m[4] ) {
			$a = max( 0, min( 1, (float) $m[4] ) );
			$a_str = rtrim( rtrim( number_format( $a, 3, '.', '' ), '0' ), '.' );
			if ( '' === $a_str ) {
				$a_str = '0';
			}
			return sprintf( 'rgba(%d, %d, %d, %s)', $r, $g, $b, $a_str );
		}
		return sprintf( 'rgb(%d, %d, %d)', $r, $g, $b );
	}

	return '';
}

/**
 * 선택 가능한 hex 색상 (빈 값 허용)
 *
 * @param mixed $value
 * @return string
 */
function borobill_sanitize_optional_hex_color( $value ) {
	if ( ! is_string( $value ) ) {
		return '';
	}
	$value = trim( $value );
	if ( '' === $value ) {
		return '';
	}
	$hex = sanitize_hex_color( $value );
	return is_string( $hex ) ? $hex : '';
}

/**
 * hex + 레거시 불투명도(%) → rgba 문자열
 *
 * @param string $hex
 * @param int    $opacity_percent
 * @return string
 */
function borobill_hex_to_rgba_css( $hex, $opacity_percent = 100 ) {
	$hex = sanitize_hex_color( is_string( $hex ) ? $hex : '' );
	if ( ! is_string( $hex ) || '' === $hex ) {
		return '';
	}
	$hex = ltrim( $hex, '#' );
	if ( 3 === strlen( $hex ) ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}
	if ( 6 !== strlen( $hex ) ) {
		return '';
	}
	$r = hexdec( substr( $hex, 0, 2 ) );
	$g = hexdec( substr( $hex, 2, 2 ) );
	$b = hexdec( substr( $hex, 4, 2 ) );
	$a = max( 0, min( 100, absint( $opacity_percent ) ) ) / 100;
	$a_str = rtrim( rtrim( number_format( $a, 3, '.', '' ), '0' ), '.' );
	if ( '' === $a_str ) {
		$a_str = '0';
	}
	return sprintf( 'rgba(%d, %d, %d, %s)', $r, $g, $b, $a_str );
}

/**
 * 관리자 버튼 색상 필드 값 (rgba 우선, hex+구 opacity 병합)
 *
 * @param int $slide_id
 * @return string
 */
function borobill_get_hero_button_color_for_editor( $slide_id ) {
	$slide_id = (int) $slide_id;
	$raw      = get_option( 'borobill_hero_button_color_' . $slide_id, '' );
	$color    = is_string( $raw ) ? trim( $raw ) : '';
	if ( '' === $color ) {
		return '';
	}

	$sanitized = borobill_sanitize_optional_css_color( $color );
	if ( '' !== $sanitized && 0 === stripos( $sanitized, 'rgb' ) ) {
		return $sanitized;
	}

	$opacity = absint( get_option( 'borobill_hero_button_opacity_' . $slide_id, 97 ) );
	if ( $opacity > 100 ) {
		$opacity = 100;
	}
	if ( '' !== $sanitized ) {
		return borobill_hex_to_rgba_css( $sanitized, $opacity );
	}

	return '';
}

/**
 * 프론트용 버튼 색상 (css color)
 *
 * @param int $slide_id
 * @return string
 */
function borobill_get_hero_button_color_css( $slide_id ) {
	return borobill_get_hero_button_color_for_editor( (int) $slide_id );
}

/**
 * 히어로 슬라이드별 이미지 크기 (px). 없으면 공통 옵션 폴백.
 *
 * @param int $slide_id
 * @return int
 */
function borobill_get_hero_slide_image_width( $slide_id ) {
	$slide_id = (int) $slide_id;
	$fallback = absint( get_option( 'borobill_hero_image_width', 380 ) );
	if ( $fallback < 200 ) {
		$fallback = 380;
	}
	if ( $fallback > 600 ) {
		$fallback = 600;
	}
	if ( $slide_id < 1 ) {
		return $fallback;
	}

	$raw = get_option( 'borobill_hero_image_width_' . $slide_id, null );
	if ( null === $raw || '' === $raw ) {
		return $fallback;
	}

	$n = absint( $raw );
	if ( $n < 200 ) {
		$n = 200;
	}
	if ( $n > 600 ) {
		$n = 600;
	}
	return $n;
}

/**
 * 히어로 이미지 크기 sanitize (200~600)
 *
 * @param mixed $value
 * @return int
 */
function borobill_sanitize_hero_image_width( $value ) {
	$n = absint( $value );
	if ( $n < 200 ) {
		$n = 200;
	}
	if ( $n > 600 ) {
		$n = 600;
	}
	return $n;
}

/**
 * 하단 배너 버튼 색상 (관리자/프론트 공용, rgba 허용)
 *
 * @param int $slide_id
 * @return string
 */
function borobill_get_bottom_banner_button_color_for_editor( $slide_id ) {
	$slide_id = (int) $slide_id;
	$raw      = get_option( 'borobill_bottom_banner_button_color_' . $slide_id, '' );
	$color    = is_string( $raw ) ? trim( $raw ) : '';
	if ( '' === $color ) {
		return '';
	}
	return borobill_sanitize_optional_css_color( $color );
}

/**
 * 하단 배너 버튼 색상 (프론트 CSS)
 *
 * @param int $slide_id
 * @return string
 */
function borobill_get_bottom_banner_button_color_css( $slide_id ) {
	return borobill_get_bottom_banner_button_color_for_editor( (int) $slide_id );
}

/**
 * 0~100 투명도(불투명도 %) 값
 *
 * @param mixed $value
 * @return int
 */
function borobill_sanitize_percent_0_100( $value ) {
	$n = absint( $value );
	if ( $n > 100 ) {
		$n = 100;
	}
	return $n;
}

function borobill_register_theme_settings() {
	$hero_slides_group    = borobill_get_theme_hero_slides_settings_group();
	$hero_transition_group = borobill_get_theme_hero_transition_settings_group();
	$bottom_banner_group  = borobill_get_theme_bottom_banner_settings_group();

	// 슬라이드별 이미지 / 텍스트 / 배경 색상 (등록된 슬라이드 ID 기준)
	if ( function_exists( 'borobill_get_hero_slide_order_ids' ) ) {
		foreach ( borobill_get_hero_slide_order_ids() as $slide_id ) {
			$slide_id = (int) $slide_id;
			if ( $slide_id < 1 ) {
				continue;
			}
			register_setting( $hero_slides_group, 'borobill_hero_image_' . $slide_id );
			register_setting( $hero_slides_group, 'borobill_hero_badge_' . $slide_id );
			register_setting( $hero_slides_group, 'borobill_hero_title_' . $slide_id );
			register_setting( $hero_slides_group, 'borobill_hero_bg_color_' . $slide_id );
			register_setting( $hero_slides_group, 'borobill_hero_grad_bottom_' . $slide_id );
			register_setting(
				$hero_slides_group,
				'borobill_hero_image_width_' . $slide_id,
				array(
					'type'              => 'integer',
					'sanitize_callback' => 'borobill_sanitize_hero_image_width',
					'default'           => 380,
				)
			);
			register_setting( $hero_slides_group, 'borobill_hero_button_text_' . $slide_id );
			register_setting(
				$hero_slides_group,
				'borobill_hero_button_color_' . $slide_id,
				array(
					'type'              => 'string',
					'sanitize_callback' => 'borobill_sanitize_optional_css_color',
					'default'           => '',
				)
			);
			register_setting(
				$hero_slides_group,
				'borobill_hero_button_text_color_' . $slide_id,
				array(
					'type'              => 'string',
					'sanitize_callback' => 'borobill_sanitize_optional_hex_color',
					'default'           => '#ffffff',
				)
			);
			register_setting(
				$hero_slides_group,
				'borobill_hero_button_opacity_' . $slide_id,
				array(
					'type'              => 'integer',
					'sanitize_callback' => 'borobill_sanitize_percent_0_100',
					'default'           => 97,
				)
			);
        register_setting(
				$hero_slides_group,
				'borobill_hero_button_url_' . $slide_id,
            array(
                'type'              => 'string',
                'sanitize_callback' => 'esc_url_raw',
                'default'           => '',
            )
        );
        register_setting(
				$hero_slides_group,
				'borobill_hero_button_enabled_' . $slide_id,
            array(
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
                'default'           => 1,
            )
        );
		}
    }

    // 슬라이드 전환 옵션
	register_setting( $hero_transition_group, 'borobill_hero_auto_delay' );
	register_setting( $hero_transition_group, 'borobill_hero_anim_duration' );
	register_setting( $hero_transition_group, 'borobill_hero_anim_effect' );

	// 히어로 슬라이드 공통
	register_setting( $hero_slides_group, 'borobill_hero_image_width' );
	register_setting( $hero_slides_group, 'borobill_hero_grad_bottom' );
	register_setting( $hero_slides_group, 'borobill_hero_order' );
	register_setting( $hero_slides_group, 'borobill_hero_bg_color' );

    // ── 하단 배너(CTA) 슬라이드 설정 ──
	$bottom_transition_group = borobill_get_theme_bottom_banner_transition_settings_group();

	if ( function_exists( 'borobill_get_bottom_banner_slide_order_ids' ) ) {
		foreach ( borobill_get_bottom_banner_slide_order_ids() as $slide_id ) {
			$slide_id = (int) $slide_id;
			if ( $slide_id < 1 ) {
				continue;
			}
			register_setting( $bottom_banner_group, 'borobill_bottom_banner_image_' . $slide_id, array(
				'type' => 'string', 'sanitize_callback' => 'esc_url_raw', 'default' => '',
			) );
			register_setting( $bottom_banner_group, 'borobill_bottom_banner_title_' . $slide_id, array(
				'type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => '',
			) );
			register_setting( $bottom_banner_group, 'borobill_bottom_banner_body_' . $slide_id, array(
				'type' => 'string', 'sanitize_callback' => 'sanitize_textarea_field', 'default' => '',
			) );
			register_setting( $bottom_banner_group, 'borobill_bottom_banner_bg_color_' . $slide_id, array(
				'type' => 'string', 'sanitize_callback' => 'sanitize_hex_color', 'default' => '#7ea354',
			) );
			register_setting(
				$bottom_banner_group,
				'borobill_bottom_banner_button_color_' . $slide_id,
				array(
					'type'              => 'string',
					'sanitize_callback' => 'borobill_sanitize_optional_css_color',
					'default'           => '',
				)
			);
			register_setting(
				$bottom_banner_group,
				'borobill_bottom_banner_button_text_color_' . $slide_id,
				array(
					'type'              => 'string',
					'sanitize_callback' => 'borobill_sanitize_optional_hex_color',
					'default'           => '#ffffff',
				)
			);
			register_setting( $bottom_banner_group, 'borobill_bottom_banner_button_text_' . $slide_id, array(
				'type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => '',
			) );
			register_setting( $bottom_banner_group, 'borobill_bottom_banner_url_' . $slide_id, array(
				'type' => 'string', 'sanitize_callback' => 'borobill_sanitize_http_url', 'default' => '',
			) );
			register_setting( $bottom_banner_group, 'borobill_bottom_banner_new_tab_' . $slide_id, array(
				'type' => 'integer', 'sanitize_callback' => 'absint', 'default' => 1,
			) );
		}
	}

	register_setting( $bottom_banner_group, 'borobill_bottom_banner_order' );

	// 하단 배너 슬라이드 전환 옵션
	register_setting( $bottom_transition_group, 'borobill_bottom_banner_auto_delay' );
	register_setting( $bottom_transition_group, 'borobill_bottom_banner_anim_duration' );
	register_setting( $bottom_transition_group, 'borobill_bottom_banner_anim_effect' );
}
add_action( 'admin_init', 'borobill_register_theme_settings' );

/**
 * http/https URL만 허용하는 sanitize.
 *
 * @param string $value
 * @return string
 */
function borobill_sanitize_http_url( $value ) {
    $value = is_string( $value ) ? trim( $value ) : '';
    if ( '' === $value ) {
        return '';
    }

    $value  = esc_url_raw( $value );
    $parsed = wp_parse_url( $value );
    if ( empty( $parsed['scheme'] ) || empty( $parsed['host'] ) ) {
        return '';
    }
    $scheme = strtolower( (string) $parsed['scheme'] );
    if ( ! in_array( $scheme, array( 'http', 'https' ), true ) ) {
        return '';
    }

    return $value;
}

/**
 * 카테고리별 추천게시물 1/2/3순위 — 관리자 카테고리 편집 화면
 */
const BOROBILL_RECOMMENDED_POST_META_KEYS = array( 'borobill_recommended_post_1', 'borobill_recommended_post_2', 'borobill_recommended_post_3' );

/**
 * 해당 카테고리 + 하위 카테고리 term_id 배열 반환
 *
 * @param int $term_id
 * @return int[]
 */
function borobill_get_category_tree_ids( $term_id ) {
    static $cache = array();

    $term_id = (int) $term_id;
    if ( $term_id <= 0 ) {
        return array();
    }
    if ( isset( $cache[ $term_id ] ) ) {
        return $cache[ $term_id ];
    }
    $ids      = array( $term_id );
    $children = get_terms(
        array(
        'taxonomy'   => 'category',
        'child_of'   => $term_id,
        'hide_empty' => false,
        'fields'     => 'ids',
        )
    );
    if ( ! is_wp_error( $children ) && is_array( $children ) ) {
        $ids = array_merge( $ids, array_map( 'intval', $children ) );
    }
    $cache[ $term_id ] = $ids;
    return $ids;
}

/**
 * 추천 아티클 허용 범위(term_id) — WP 하위 트리 + GNB 메뉴 2차(및 그 하위)
 * 메뉴 구조와 WP parent 가 어긋나도 메뉴에 연결된 하위 글이 걸러지지 않도록 함.
 *
 * @param int $term_id 1차 카테고리 term_id
 * @return int[]
 */
function borobill_get_recommended_scope_ids( $term_id ) {
    $term_id = (int) $term_id;
    if ( $term_id <= 0 ) {
        return array();
    }

    $ids = borobill_get_category_tree_ids( $term_id );

    if ( function_exists( 'borobill_get_gnb_child_category_items' ) ) {
        foreach ( borobill_get_gnb_child_category_items( $term_id ) as $child ) {
            $child_id = isset( $child['id'] ) ? (int) $child['id'] : 0;
            if ( $child_id <= 0 ) {
                continue;
            }
            $ids   = array_merge( $ids, borobill_get_category_tree_ids( $child_id ) );
        }
    }

    $ids = array_values( array_unique( array_filter( array_map( 'intval', $ids ) ) ) );

    return $ids;
}

/**
 * 추천게시물 목록 — 관리자 지정(1~3순위) 우선, 없으면 조회수(추천순)
 *
 * @param array{root_term_id?:int,exclude_post_id?:int,category__in?:int[],limit?:int} $args
 * @return WP_Post[]
 */
function borobill_get_recommended_posts( $args = array() ) {
    $limit           = isset( $args['limit'] ) ? max( 1, (int) $args['limit'] ) : 3;
    $exclude_post_id = isset( $args['exclude_post_id'] ) ? (int) $args['exclude_post_id'] : 0;
    $root_term_id    = isset( $args['root_term_id'] ) ? (int) $args['root_term_id'] : 0;
    $posts           = array();
    $used_ids        = $exclude_post_id > 0 ? array( $exclude_post_id ) : array();

    if ( $root_term_id > 0 ) {
        $scope_ids = function_exists( 'borobill_get_recommended_scope_ids' )
            ? borobill_get_recommended_scope_ids( $root_term_id )
            : borobill_get_category_tree_ids( $root_term_id );
        $has_admin = false;
        foreach ( array( 1, 2, 3 ) as $rank ) {
            if ( (int) get_term_meta( $root_term_id, 'borobill_recommended_post_' . $rank, true ) > 0 ) {
                $has_admin = true;
                break;
            }
        }

        if ( $has_admin ) {
            foreach ( array( 1, 2, 3 ) as $rank ) {
                $pid = (int) get_term_meta( $root_term_id, 'borobill_recommended_post_' . $rank, true );
                if ( $pid <= 0 || in_array( $pid, $used_ids, true ) ) {
                    continue;
                }
                $post = get_post( $pid );
                if ( ! $post || 'publish' !== $post->post_status || 'post' !== $post->post_type ) {
                    continue;
                }
                $posts[]    = $post;
                $used_ids[] = $pid;
                if ( count( $posts ) >= $limit ) {
                    return $posts;
                }
            }
        }
    }

    if ( count( $posts ) >= $limit ) {
        return array_slice( $posts, 0, $limit );
    }

    $query_args = array(
        'post_type'           => 'post',
        'post_status'         => 'publish',
        'posts_per_page'      => $limit - count( $posts ),
        'post__not_in'        => $used_ids,
        'meta_key'            => '_bb_views',
        'orderby'             => 'meta_value_num',
        'order'               => 'DESC',
        'no_found_rows'       => true,
        'ignore_sticky_posts' => true,
    );

    if ( ! empty( $args['category__in'] ) ) {
        $query_args['category__in'] = array_values( array_unique( array_map( 'intval', (array) $args['category__in'] ) ) );
    } elseif ( $root_term_id > 0 ) {
        if ( empty( $scope_ids ) ) {
            $scope_ids = function_exists( 'borobill_get_recommended_scope_ids' )
                ? borobill_get_recommended_scope_ids( $root_term_id )
                : borobill_get_category_tree_ids( $root_term_id );
        }
        $query_args['category__in'] = $scope_ids;
    }

    $query = new WP_Query( $query_args );
    foreach ( $query->posts as $post ) {
        if ( ! $post instanceof WP_Post ) {
            continue;
        }
        $posts[] = $post;
        if ( count( $posts ) >= $limit ) {
            break;
        }
    }
    wp_reset_postdata();

    return array_slice( $posts, 0, $limit );
}

/**
 * GNB 1차 카테고리 목록
 *
 * @return WP_Term[]
 */
function borobill_get_nav_menu_item_category_term( $item ) {
    if ( ! $item || 'taxonomy' !== $item->type || 'category' !== $item->object ) {
        return null;
    }

    $term = get_term( (int) $item->object_id, 'category' );
    if ( ! $term || is_wp_error( $term ) ) {
        return null;
    }

    return $term;
}

/**
 * header-menu(GNB) 구조 — LNB 표시용
 *
 * @return array<int, array{title:string,url:string,term_id:int,children:array<int, array{title:string,url:string,term_id:int}>}>
 */
function borobill_get_header_gnb_lnb_groups() {
    static $cached_groups = null;
    if ( null !== $cached_groups ) {
        return $cached_groups;
    }

    $menu_id = borobill_get_header_nav_menu_id();
    if ( $menu_id <= 0 ) {
        $cached_groups = borobill_get_header_gnb_lnb_groups_fallback();
        return $cached_groups;
    }

    $items = wp_get_nav_menu_items(
        $menu_id,
        array(
            'update_post_term_cache' => false,
        )
    );
    if ( empty( $items ) ) {
        $cached_groups = borobill_get_header_gnb_lnb_groups_fallback();
        return $cached_groups;
    }

    $children_map = array();
    foreach ( $items as $item ) {
        $parent_id = (int) $item->menu_item_parent;
        if ( $parent_id > 0 ) {
            if ( ! isset( $children_map[ $parent_id ] ) ) {
                $children_map[ $parent_id ] = array();
            }
            $children_map[ $parent_id ][] = $item;
        }
    }

    $groups = array();
    foreach ( $items as $item ) {
        if ( (int) $item->menu_item_parent !== 0 ) {
            continue;
        }

        $parent_term = borobill_get_nav_menu_item_category_term( $item );
        $children    = array();

        foreach ( $children_map[ (int) $item->ID ] ?? array() as $child_item ) {
            $child_term  = borobill_get_nav_menu_item_category_term( $child_item );
            $children[]  = array(
                'title'   => $child_item->title,
                'url'     => $child_item->url,
                'term_id' => $child_term ? (int) $child_term->term_id : 0,
            );
        }

        $groups[] = array(
            'title'    => $item->title,
            'url'      => $item->url,
            'term_id'  => $parent_term ? (int) $parent_term->term_id : 0,
            'children' => $children,
        );
    }

    if ( empty( $groups ) ) {
        $cached_groups = borobill_get_header_gnb_lnb_groups_fallback();
        return $cached_groups;
    }

    $cached_groups = $groups;
    return $cached_groups;
}

/**
 * header-menu(GNB) 항목 제목 — term_id 기준
 *
 * @param int $term_id
 * @return string
 */
function borobill_get_gnb_menu_title_for_term( $term_id ) {
    $term_id = (int) $term_id;
    if ( $term_id <= 0 ) {
        return '';
    }

    foreach ( borobill_get_header_gnb_lnb_groups() as $group ) {
        if ( (int) $group['term_id'] === $term_id ) {
            return $group['title'];
        }
        foreach ( $group['children'] ?? array() as $child ) {
            if ( (int) $child['term_id'] === $term_id ) {
                return $child['title'];
            }
        }
    }

    return '';
}

/**
 * GNB 메뉴가 없을 때 LNB 폴백 (기존 카테고리 트리)
 *
 * @return array<int, array{title:string,url:string,term_id:int,children:array<int, array{title:string,url:string,term_id:int}>}>
 */
function borobill_get_header_gnb_lnb_groups_fallback() {
    $groups = array();

    foreach ( borobill_get_gnb_parent_category_terms() as $parent ) {
        $children = array();
        $child_terms = get_categories(
            array(
                'hide_empty' => false,
                'parent'     => (int) $parent->term_id,
            )
        );

        foreach ( $child_terms as $child ) {
            $children[] = array(
                'title'   => $child->name,
                'url'     => get_category_link( $child ),
                'term_id' => (int) $child->term_id,
            );
        }

        $groups[] = array(
            'title'    => $parent->name,
            'url'      => get_category_link( $parent ),
            'term_id'  => (int) $parent->term_id,
            'children' => $children,
        );
    }

    return $groups;
}

function borobill_get_gnb_parent_category_terms() {
    $menu_id = borobill_get_header_nav_menu_id();
    if ( $menu_id > 0 ) {
        $items = wp_get_nav_menu_items(
            $menu_id,
            array(
                'update_post_term_cache' => false,
            )
        );

        if ( ! empty( $items ) ) {
            $terms = array();
            foreach ( $items as $item ) {
                if ( (int) $item->menu_item_parent !== 0 ) {
                    continue;
                }
                $term = borobill_get_nav_menu_item_category_term( $item );
                if ( $term ) {
                    $terms[] = $term;
                }
            }
            if ( ! empty( $terms ) ) {
                return $terms;
            }
        }
    }

    $names = array(
        '초보사업자',
        '세무·비즈니스',
        '사업자 뉴스룸',
        '바로빌 가이드',
        '고객사례·인사이트',
    );
    $terms = array();
    foreach ( $names as $name ) {
        $term = get_term_by( 'name', $name, 'category' );
        if ( $term && ! is_wp_error( $term ) ) {
            $terms[] = $term;
        }
    }
    return $terms;
}

/**
 * GNB 메뉴 1차 항목 목록 (메뉴 제목 + 카테고리 term_id)
 * 커스텀 링크 1차도 포함. 메뉴 항목은 수정하지 않음.
 *
 * @return array<int, array{id:int,name:string}>
 */
function borobill_get_gnb_parent_menu_items() {
    $items          = array();
    $header_menu_id = borobill_get_header_nav_menu_id();
    if ( $header_menu_id <= 0 ) {
        $header_menu_id = borobill_find_header_gnb_menu_id();
    }
    if ( $header_menu_id <= 0 ) {
        return $items;
    }

    $menu_items = wp_get_nav_menu_items(
        $header_menu_id,
        array(
            'orderby' => 'menu_order',
            'order'   => 'ASC',
        )
    );
    if ( empty( $menu_items ) ) {
        return $items;
    }

    $seen = array();
    foreach ( $menu_items as $menu_item ) {
        if ( ! $menu_item instanceof WP_Post ) {
            continue;
        }
        if ( 0 !== (int) $menu_item->menu_item_parent ) {
            continue;
        }

        $term = borobill_resolve_category_term_for_gnb_menu_item( $menu_item );
        if ( ! $term || is_wp_error( $term ) ) {
            continue;
        }

        $term_id = (int) $term->term_id;
        if ( isset( $seen[ $term_id ] ) ) {
            continue;
        }

        $name = trim( (string) $menu_item->title );
        if ( '' === $name ) {
            $name = $term->name;
        }

        $items[]             = array(
            'id'   => $term_id,
            'name' => $name,
        );
        $seen[ $term_id ] = true;
    }

    return $items;
}

/**
 * GNB 메뉴 2차 카테고리 목록 (1차 term_id 기준)
 *
 * @param int $parent_term_id 1차(GNB 상위) 카테고리 term_id
 * @return array<int, array{id:int,name:string}>
 */
function borobill_get_gnb_child_category_items( $parent_term_id ) {
    $parent_term_id = (int) $parent_term_id;
    if ( $parent_term_id <= 0 ) {
        return array();
    }

    $header_menu_id = borobill_get_header_nav_menu_id();
    if ( $header_menu_id <= 0 ) {
        $header_menu_id = borobill_find_header_gnb_menu_id();
    }
    if ( $header_menu_id <= 0 ) {
        return array();
    }

    $menu_items = wp_get_nav_menu_items(
        $header_menu_id,
        array(
            'orderby' => 'menu_order',
            'order'   => 'ASC',
        )
    );
    if ( empty( $menu_items ) ) {
        return array();
    }

    $parent_menu_ids = array();
    foreach ( $menu_items as $menu_item ) {
        if ( ! $menu_item instanceof WP_Post ) {
            continue;
        }
        if ( 0 !== (int) $menu_item->menu_item_parent ) {
            continue;
        }

        $term = borobill_resolve_category_term_for_gnb_menu_item( $menu_item );
        if ( $term && ! is_wp_error( $term ) && (int) $term->term_id === $parent_term_id ) {
            $parent_menu_ids[ (int) $menu_item->ID ] = true;
        }
    }

    if ( empty( $parent_menu_ids ) ) {
        return array();
    }

    $items = array();
    $seen  = array();
    foreach ( $menu_items as $menu_item ) {
        if ( ! $menu_item instanceof WP_Post ) {
            continue;
        }
        if ( ! isset( $parent_menu_ids[ (int) $menu_item->menu_item_parent ] ) ) {
            continue;
        }
        if ( 'taxonomy' !== $menu_item->type || 'category' !== $menu_item->object ) {
            continue;
        }

        $term = get_term( (int) $menu_item->object_id, 'category' );
        if ( ! $term || is_wp_error( $term ) ) {
            continue;
        }

        $term_id = (int) $term->term_id;
        if ( isset( $seen[ $term_id ] ) ) {
            continue;
        }

        $name = trim( (string) $menu_item->title );
        if ( '' === $name ) {
            $name = $term->name;
        }

        $items[]             = array(
            'id'   => $term_id,
            'name' => $name,
        );
        $seen[ $term_id ] = true;
    }

    return $items;
}

/**
 *
 * @param int $post_id
 * @return array{parent:int,child:int,post:int}
 */
function borobill_resolve_recommended_post_category_chain( $post_id ) {
    $result = array(
        'parent' => 0,
        'child'  => 0,
        'post'   => (int) $post_id,
    );
    if ( $post_id <= 0 ) {
        return $result;
    }

    $gnb_parent_ids = wp_list_pluck( borobill_get_gnb_parent_menu_items(), 'id' );
    $cats          = wp_get_post_categories( $post_id, array( 'fields' => 'all' ) );

    foreach ( $cats as $cat ) {
        $root = $cat;
        while ( $root->parent ) {
            $root = get_category( $root->parent );
            if ( ! $root || is_wp_error( $root ) ) {
                break;
            }
        }
        if ( ! $root || ! in_array( (int) $root->term_id, array_map( 'intval', $gnb_parent_ids ), true ) ) {
            continue;
        }

        $result['parent'] = (int) $root->term_id;

        if ( (int) $cat->term_id === (int) $root->term_id ) {
            $children = borobill_get_gnb_child_category_items( (int) $root->term_id );
            foreach ( $children as $child ) {
                if ( has_category( (int) $child['id'], $post_id ) ) {
                    $result['child'] = (int) $child['id'];
                    return $result;
                }
            }
            if ( ! empty( $children ) ) {
                $result['child'] = (int) $children[0]['id'];
            }
            return $result;
        }

        $node = $cat;
        while ( $node && (int) $node->term_id !== (int) $root->term_id ) {
            if ( (int) $node->parent === (int) $root->term_id ) {
                $result['child'] = (int) $node->term_id;
                return $result;
            }
            $node = $node->parent ? get_category( $node->parent ) : null;
            if ( $node && is_wp_error( $node ) ) {
                break;
            }
        }
    }

    return $result;
}

/**
 * 추천게시물 순위별 3단 셀렉트 (1차 카테고리 / 하위 카테고리 / 게시글)
 *
 * @param int $rank
 * @param int $selected_post_id
 */
function borobill_render_recommended_rank_selects( $rank, $selected_post_id = 0 ) {
    $rank             = (int) $rank;
    $selected_post_id = (int) $selected_post_id;
    $chain            = borobill_resolve_recommended_post_category_chain( $selected_post_id );
    $parent_id        = (int) $chain['parent'];
    $child_id         = (int) $chain['child'];
    $gnb_parent_items = borobill_get_gnb_parent_menu_items();

    $parent_name = 'borobill_rec_parent_' . $rank;
    $child_name  = 'borobill_rec_child_' . $rank;
    $post_name   = 'borobill_recommended_post_' . $rank;
    ?>
    <div class="borobill-recommended-selects" data-rank="<?php echo esc_attr( (string) $rank ); ?>">
        <select name="<?php echo esc_attr( $parent_name ); ?>" id="<?php echo esc_attr( $parent_name ); ?>" class="borobill-rec-parent postform">
            <option value="0">— 1차 메뉴 —</option>
            <?php foreach ( $gnb_parent_items as $parent_item ) : ?>
                <option value="<?php echo (int) $parent_item['id']; ?>"<?php selected( $parent_id, (int) $parent_item['id'] ); ?>>
                    <?php echo esc_html( $parent_item['name'] ); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select name="<?php echo esc_attr( $child_name ); ?>" id="<?php echo esc_attr( $child_name ); ?>" class="borobill-rec-child postform"<?php disabled( $parent_id <= 0 ); ?>>
            <option value="0">— 2차 카테고리 —</option>
            <?php
            if ( $parent_id > 0 ) {
                foreach ( borobill_get_gnb_child_category_items( $parent_id ) as $child_item ) {
                    echo '<option value="' . (int) $child_item['id'] . '"' . selected( $child_id, (int) $child_item['id'], false ) . '>' . esc_html( $child_item['name'] ) . '</option>';
                }
            }
            ?>
        </select>
        <input type="hidden" name="<?php echo esc_attr( $post_name ); ?>" class="borobill-rec-post-value" value="<?php echo (int) $selected_post_id; ?>" />
        <select id="<?php echo esc_attr( $post_name ); ?>" class="borobill-rec-post postform" data-rank="<?php echo (int) $rank; ?>"<?php disabled( $child_id <= 0 ); ?>>
            <option value="0">— 게시글 선택 —</option>
            <?php
            if ( $child_id > 0 ) {
                $posts = get_posts(
                    array(
                        'post_type'      => 'post',
                        'post_status'    => 'publish',
                        'numberposts'    => 500,
                        'orderby'        => 'date',
                        'order'          => 'DESC',
                        'cat'            => $child_id,
                    )
                );
    foreach ( $posts as $p ) {
        $title = $p->post_title;
        if ( mb_strlen( $title ) > 80 ) {
            $title = mb_substr( $title, 0, 77 ) . '...';
        }
                    echo '<option value="' . (int) $p->ID . '"' . selected( $selected_post_id, (int) $p->ID, false ) . '>' . esc_html( $title ) . '</option>';
                }
            }
            ?>
        </select>
    </div>
    <?php
}

/**
 * 카테고리 추천게시물 순위 행 (term.php · 패널 공용)
 *
 * @param WP_Term $term
 */
function borobill_render_category_recommended_form_rows( $term ) {
    for ( $rank = 1; $rank <= 3; $rank++ ) {
        $post_id = (int) get_term_meta( $term->term_id, 'borobill_recommended_post_' . $rank, true );
        ?>
        <tr class="form-field borobill-recommended-rank" data-rank="<?php echo (int) $rank; ?>">
            <th scope="row"><label for="borobill_rec_parent_<?php echo (int) $rank; ?>"><?php echo (int) $rank; ?>순위</label></th>
            <td>
                <?php borobill_render_recommended_rank_selects( $rank, $post_id ); ?>
        </td>
    </tr>
    <?php
}
}

/**
 * 카테고리 목록 우측 패널: 추천게시물 편집
 *
 * @param WP_Term $term
 */
function borobill_render_category_recommended_panel( $term ) {
    if ( ! $term instanceof WP_Term ) {
        return;
    }

    $tag_id = (int) $term->term_id;
    ?>
    <div class="borobill-category-panel__inner">
        <form id="borobill-category-panel-form" class="borobill-category-panel__form" method="post">
            <?php wp_nonce_field( 'update-tag_' . $tag_id, '_wpnonce', false ); ?>
            <input type="hidden" name="tag_ID" value="<?php echo esc_attr( (string) $tag_id ); ?>" />
            <input type="hidden" name="taxonomy" value="category" />
            <input type="hidden" name="name" value="<?php echo esc_attr( $term->name ); ?>" />
            <input type="hidden" name="slug" value="<?php echo esc_attr( $term->slug ); ?>" />
            <input type="hidden" name="parent" value="<?php echo (int) $term->parent; ?>" />
            <input type="hidden" name="description" value="<?php echo esc_attr( $term->description ); ?>" />

            <h2 class="borobill-category-panel__title"><?php echo esc_html( 'post' === borobill_get_admin_category_list_tab() ? '추천 게시글' : '추천 아티클' ); ?></h2>
            <table class="form-table" role="presentation">
                <?php borobill_render_category_recommended_form_rows( $term ); ?>
            </table>

            <div class="edit-tag-actions borobill-category-panel__actions">
                <?php submit_button( __( 'Update' ), 'primary', 'submit', false ); ?>
                <button type="button" class="button borobill-category-panel__reset" id="borobill-category-panel-reset">초기화</button>
            </div>
        </form>
    </div>
    <?php
}

/**
 * 카테고리 편집 폼: 추천게시물 1~3순위
 */
function borobill_category_edit_form_recommended_articles( $term, $taxonomy ) {
    if ( 'category' !== $taxonomy ) {
        return;
    }
    ?>
    <tr class="form-field term-recommended-wrap">
        <td colspan="2"><h2>추천 아티클</h2></td>
    </tr>
    <?php
    borobill_render_category_recommended_form_rows( $term );
}
add_action( 'category_edit_form_fields', 'borobill_category_edit_form_recommended_articles', 10, 2 );

/**
 * 카테고리 추가 폼: 추천게시물 1~3순위 (추가 폼 비활성화 — 편집 화면만 사용)
 */

/**
 * 카테고리 추천게시물 저장
 */
function borobill_save_category_recommended_articles( $term_id ) {
    if ( ! isset( $_POST['borobill_recommended_post_1'] ) ) {
        return;
    }
    foreach ( BOROBILL_RECOMMENDED_POST_META_KEYS as $key ) {
        $value = isset( $_POST[ $key ] ) ? absint( $_POST[ $key ] ) : 0;
        if ( $value > 0 && 'publish' === get_post_status( $value ) ) {
                update_term_meta( $term_id, $key, $value );
            } else {
                delete_term_meta( $term_id, $key );
            }
    }
}
add_action( 'edited_category', 'borobill_save_category_recommended_articles', 10, 1 );
add_action( 'created_category', 'borobill_save_category_recommended_articles', 10, 1 );

/**
 * 관리자 카테고리: 추천게시물 연동 셀렉트 스크립트
 */
function borobill_admin_category_recommended_assets( $hook ) {
    if ( ! in_array( $hook, array( 'edit-tags.php', 'term.php' ), true ) ) {
        return;
    }
    $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
    if ( ! $screen || 'category' !== $screen->taxonomy ) {
        return;
    }

    $js_path   = get_template_directory() . '/js/admin-category-recommended.js';
    $split_path = get_template_directory() . '/js/admin-category-split.js';
    $css_path  = get_template_directory() . '/css/admin-category.css';
    wp_enqueue_style(
        'borobill-admin-category',
        get_template_directory_uri() . '/css/admin-category.css',
        array(),
        file_exists( $css_path ) ? filemtime( $css_path ) : '1.0'
    );
    wp_enqueue_script(
        'borobill-admin-category-recommended',
        get_template_directory_uri() . '/js/admin-category-recommended.js',
        array( 'jquery' ),
        file_exists( $js_path ) ? filemtime( $js_path ) : '1.0',
        true
    );

    if ( 'edit-tags.php' === $hook ) {
        wp_enqueue_script(
            'borobill-admin-category-split',
            get_template_directory_uri() . '/js/admin-category-split.js',
            array( 'jquery', 'borobill-admin-category-recommended' ),
            file_exists( $split_path ) ? filemtime( $split_path ) : '1.0',
            true
        );
    }

    wp_localize_script(
        'borobill-admin-category-recommended',
        'borobillCategoryRec',
        array(
            'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
            'nonce'             => wp_create_nonce( 'borobill_category_rec' ),
            'loadPanelAction'   => 'borobill_load_category_panel',
            'savePanelAction'   => 'borobill_save_category_recommended',
            'resetPanelAction'  => 'borobill_reset_category_recommended',
            'panelPlaceholder'  => '카테고리를 선택하세요.',
            'panelLoading'      => '불러오는 중…',
            'panelSaveSuccess'  => '저장되었습니다.',
            'panelSaveError'    => '저장에 실패했습니다.',
            'panelResetConfirm' => '추천 설정을 초기화할까요?',
            'panelResetSuccess' => '초기화되었습니다.',
            'panelResetError'   => '초기화에 실패했습니다.',
        )
    );
}
add_action( 'admin_enqueue_scripts', 'borobill_admin_category_recommended_assets' );

/**
 * 글 편집 > 카테고리 메타박스: 「가장 많이 사용됨」 탭 명칭 변경
 */
function borobill_filter_category_most_used_tab_label( $labels ) {
    global $pagenow;
    if ( ! is_admin() || ! in_array( $pagenow, array( 'post.php', 'post-new.php' ), true ) ) {
        return $labels;
    }
    $labels->most_used = '카테고리 추천게시물';
    return $labels;
}
add_filter( 'taxonomy_labels_category', 'borobill_filter_category_most_used_tab_label' );

/**
 * 글 작성·수정 > 카테고리 메타박스: + 카테고리 추가 숨김
 */
function borobill_hide_post_editor_category_adder() {
    if ( ! borobill_is_post_editor_category_screen() ) {
        return;
    }
    ?>
    <style id="borobill-post-editor-category">
        #category-adder { display: none !important; }
    </style>
    <?php
}
add_action( 'admin_head-post.php', 'borobill_hide_post_editor_category_adder' );
add_action( 'admin_head-post-new.php', 'borobill_hide_post_editor_category_adder' );

/**
 * 글 작성·수정 > 모든 카테고리: GNB 메뉴 계층 유지 (선택 항목 상단 분리 비활성)
 *
 * @param array|string $args
 * @param int          $post_id
 * @return array|string
 */
function borobill_post_editor_category_checklist_args( $args, $post_id ) {
    if ( ! borobill_is_post_editor_category_screen() ) {
        return $args;
    }

    $args = (array) $args;
    if ( empty( $args['taxonomy'] ) || 'category' !== $args['taxonomy'] ) {
        return $args;
    }

    $args['checked_ontop'] = false;

    return $args;
}
add_filter( 'wp_terms_checklist_args', 'borobill_post_editor_category_checklist_args', 10, 2 );

/**
 * AJAX: 1차 카테고리 하위 목록
 */
function borobill_ajax_rec_category_children() {
    check_ajax_referer( 'borobill_category_rec', 'nonce' );
    if ( ! current_user_can( 'manage_categories' ) ) {
        wp_send_json_error();
    }
    $parent_id = isset( $_POST['parent_id'] ) ? absint( $_POST['parent_id'] ) : 0;
    $out       = array();
    if ( $parent_id > 0 ) {
        $out = borobill_get_gnb_child_category_items( $parent_id );
    }
    wp_send_json_success( $out );
}
add_action( 'wp_ajax_borobill_rec_category_children', 'borobill_ajax_rec_category_children' );

/**
 * AJAX: 하위 카테고리 게시글 목록
 */
function borobill_ajax_rec_category_posts() {
    check_ajax_referer( 'borobill_category_rec', 'nonce' );
    if ( ! current_user_can( 'manage_categories' ) ) {
        wp_send_json_error();
    }
    $cat_id = isset( $_POST['cat_id'] ) ? absint( $_POST['cat_id'] ) : 0;
    $out    = array();
    if ( $cat_id > 0 ) {
        $posts = get_posts(
            array(
                'post_type'      => 'post',
                'post_status'    => 'publish',
                'numberposts'    => 500,
                'orderby'        => 'date',
                'order'          => 'DESC',
                'cat'            => $cat_id,
            )
        );
        foreach ( $posts as $p ) {
            $title = $p->post_title;
            if ( mb_strlen( $title ) > 80 ) {
                $title = mb_substr( $title, 0, 77 ) . '...';
            }
            $out[] = array(
                'id'   => (int) $p->ID,
                'name' => $title,
            );
        }
    }
    wp_send_json_success( $out );
}
add_action( 'wp_ajax_borobill_rec_category_posts', 'borobill_ajax_rec_category_posts' );

/**
 * AJAX: 카테고리 목록 우측 패널 HTML
 */
function borobill_ajax_load_category_recommended_panel() {
    check_ajax_referer( 'borobill_category_rec', 'nonce' );

    $term_id = isset( $_POST['term_id'] ) ? absint( $_POST['term_id'] ) : 0;
    if ( $term_id <= 0 || ( ! current_user_can( 'manage_categories' ) && ! current_user_can( 'edit_term', $term_id ) ) ) {
        wp_send_json_error();
    }

    $term = get_term( $term_id, 'category', OBJECT, 'edit' );
    if ( ! $term || is_wp_error( $term ) ) {
        wp_send_json_error();
    }

    ob_start();
    borobill_render_category_recommended_panel( $term );
    wp_send_json_success(
        array(
            'html' => ob_get_clean(),
            'name' => $term->name,
        )
    );
}
add_action( 'wp_ajax_borobill_load_category_panel', 'borobill_ajax_load_category_recommended_panel' );

/**
 * AJAX: 카테고리 추천게시물 저장
 */
function borobill_ajax_save_category_recommended_panel() {
    check_ajax_referer( 'borobill_category_rec', 'nonce' );

    $term_id = isset( $_POST['term_id'] ) ? absint( $_POST['term_id'] ) : 0;
    if ( $term_id <= 0 || ( ! current_user_can( 'manage_categories' ) && ! current_user_can( 'edit_term', $term_id ) ) ) {
        wp_send_json_error();
    }

    $term = get_term( $term_id, 'category' );
    if ( ! $term || is_wp_error( $term ) ) {
        wp_send_json_error();
    }

    foreach ( BOROBILL_RECOMMENDED_POST_META_KEYS as $key ) {
        $value = isset( $_POST[ $key ] ) ? absint( $_POST[ $key ] ) : 0;
        if ( $value > 0 && 'publish' === get_post_status( $value ) ) {
            update_term_meta( $term_id, $key, $value );
        } else {
            delete_term_meta( $term_id, $key );
        }
    }

    wp_send_json_success(
        array(
            'message'  => '저장되었습니다.',
            'term_id'  => $term_id,
            'saved'    => array(
                'borobill_recommended_post_1' => (int) get_term_meta( $term_id, 'borobill_recommended_post_1', true ),
                'borobill_recommended_post_2' => (int) get_term_meta( $term_id, 'borobill_recommended_post_2', true ),
                'borobill_recommended_post_3' => (int) get_term_meta( $term_id, 'borobill_recommended_post_3', true ),
            ),
        )
    );
}
add_action( 'wp_ajax_borobill_save_category_recommended', 'borobill_ajax_save_category_recommended_panel' );

/**
 * AJAX: 카테고리 추천 아티클·게시글 설정 초기화
 */
function borobill_ajax_reset_category_recommended_panel() {
    check_ajax_referer( 'borobill_category_rec', 'nonce' );

    $term_id = isset( $_POST['term_id'] ) ? absint( $_POST['term_id'] ) : 0;
    if ( $term_id <= 0 || ! current_user_can( 'edit_term', $term_id ) ) {
        wp_send_json_error();
    }

    foreach ( BOROBILL_RECOMMENDED_POST_META_KEYS as $key ) {
        delete_term_meta( $term_id, $key );
    }

    wp_send_json_success(
        array(
            'message' => '초기화되었습니다.',
        )
    );
}
add_action( 'wp_ajax_borobill_reset_category_recommended', 'borobill_ajax_reset_category_recommended_panel' );

/**
 * 글 > 카테고리 현재 탭 (article=추천아티클, post=추천게시글)
 *
 * @return string
 */
function borobill_get_admin_category_list_tab() {
    $tab = isset( $_REQUEST['borobill_tab'] ) ? sanitize_key( wp_unslash( $_REQUEST['borobill_tab'] ) ) : 'article';
    if ( ! in_array( $tab, array( 'article', 'post' ), true ) ) {
        return 'article';
    }

    return $tab;
}

/**
 * 글 > 카테고리: 추천아티클 / 추천게시글 탭 마크업
 *
 * @return string
 */
function borobill_get_admin_category_page_tabs_html() {
    $current = borobill_get_admin_category_list_tab();
    $base    = admin_url( 'edit-tags.php?taxonomy=category' );

    ob_start();
    ?>
    <nav class="nav-tab-wrapper wp-clearfix borobill-category-tabs" aria-label="<?php echo esc_attr( '카테고리 탭' ); ?>">
        <a href="<?php echo esc_url( add_query_arg( 'borobill_tab', 'article', $base ) ); ?>" class="nav-tab<?php echo 'article' === $current ? ' nav-tab-active' : ''; ?>">추천아티클</a>
        <a href="<?php echo esc_url( add_query_arg( 'borobill_tab', 'post', $base ) ); ?>" class="nav-tab<?php echo 'post' === $current ? ' nav-tab-active' : ''; ?>">추천게시글</a>
    </nav>
    <?php
    return (string) ob_get_clean();
}

/**
 * 카테고리 목록: 우측 패널 컨테이너 + 탭
 */
function borobill_admin_category_split_panel_container() {
    $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
    if ( ! $screen || 'edit-category' !== $screen->id ) {
        return;
    }
    echo borobill_get_admin_category_page_tabs_html();
    ?>
    <div id="borobill-category-panel" class="borobill-category-panel" aria-live="polite">
        <div class="borobill-category-panel__placeholder">
            <p>카테고리를 선택하세요.</p>
        </div>
    </div>
    <?php
}
add_action( 'admin_footer-edit-tags.php', 'borobill_admin_category_split_panel_container' );

function borobill_get_main_banner_menu_slug() {
    return 'borobill-main-banner';
}

function borobill_get_banner_settings_menu_slug() {
    return 'borobill-theme-options';
}

function borobill_render_main_banner_menu_landing() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    wp_safe_redirect( admin_url( 'admin.php?page=' . borobill_get_banner_settings_menu_slug() ) );
    exit;
}

function borobill_is_banner_settings_admin_page() {
    if ( ! is_admin() ) {
        return false;
    }

    $page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

    return borobill_get_banner_settings_menu_slug() === $page;
}

function borobill_is_main_banner_stats_admin_page() {
    if ( ! is_admin() ) {
        return false;
    }

    $page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

    return borobill_get_main_banner_stats_menu_slug() === $page;
}

function borobill_is_banner_settings_admin_hook( $hook ) {
    return borobill_is_banner_settings_admin_page()
        || 'borobill-main-banner_page_borobill-theme-options' === $hook
        || 'toplevel_page_borobill-theme-options' === $hook;
}

function borobill_is_main_banner_stats_admin_hook( $hook ) {
    return borobill_is_main_banner_stats_admin_page()
        || 'borobill-main-banner_page_borobill-main-banner-stats' === $hook;
}

function borobill_register_main_banner_menu() {
    add_menu_page(
        'Main배너',
        'Main배너',
        'manage_options',
        borobill_get_main_banner_menu_slug(),
        'borobill_render_main_banner_menu_landing',
        'dashicons-images-alt2',
        27
    );

    add_submenu_page(
        borobill_get_main_banner_menu_slug(),
        '배너설정',
        '배너설정',
        'manage_options',
        borobill_get_banner_settings_menu_slug(),
        'borobill_render_theme_options_page'
    );

    add_submenu_page(
        borobill_get_main_banner_menu_slug(),
        '인사이트',
        '인사이트',
        'manage_options',
        borobill_get_main_banner_stats_menu_slug(),
        'borobill_render_main_banner_stats_page'
    );

    remove_submenu_page( borobill_get_main_banner_menu_slug(), borobill_get_main_banner_menu_slug() );
}
add_action( 'admin_menu', 'borobill_register_main_banner_menu' );

/**
 * 관리자 사이드바: Main배너·배너설정 아래로 페이지·미디어·댓글 이동
 */
function borobill_move_admin_menu_item( &$menu, $slug, $position ) {
    if ( ! is_array( $menu ) ) {
        return;
    }

    foreach ( $menu as $index => $item ) {
        if ( ! isset( $item[2] ) || $slug !== $item[2] ) {
            continue;
        }

        unset( $menu[ $index ] );
        $menu[ $position ] = $item;
        return;
    }
}

function borobill_reorder_admin_menu_after_banner_settings() {
    global $menu;

    borobill_move_admin_menu_item( $menu, 'edit.php?post_type=page', 28 );
    borobill_move_admin_menu_item( $menu, 'upload.php', 29 );
    borobill_move_admin_menu_item( $menu, 'edit-comments.php', 30 );

    if ( is_array( $menu ) ) {
        ksort( $menu );
    }
}
add_action( 'admin_menu', 'borobill_reorder_admin_menu_after_banner_settings', 999 );

/**
 * 사이트 상단 GNB — header-menu 위치에 연결된 메뉴 ID
 */
function borobill_get_header_nav_menu_id() {
    $locations = get_nav_menu_locations();
    if ( ! empty( $locations['header-menu'] ) ) {
        return (int) $locations['header-menu'];
    }
    return 0;
}

/**
 * GNB 메뉴 이름인지 확인
 *
 * @param int|object $menu 메뉴 ID 또는 메뉴 객체
 */
function borobill_is_header_gnb_menu( $menu ) {
    if ( is_numeric( $menu ) ) {
        $menu = wp_get_nav_menu_object( (int) $menu );
    }
    if ( ! $menu ) {
        return false;
    }

    return in_array( $menu->name, array( '사이트 상단 GNB', '헤더 GNB' ), true );
}

/**
 * header-menu 위치에 메뉴 연결
 *
 * @param int $menu_id
 */
function borobill_link_header_menu_location( $menu_id ) {
    $menu_id = (int) $menu_id;
    if ( $menu_id <= 0 ) {
        return;
    }

    $locations                 = get_nav_menu_locations();
    $locations['header-menu'] = $menu_id;
    set_theme_mod( 'nav_menu_locations', $locations );
}

/**
 * 이름으로 GNB 메뉴 ID 찾기 (사이트 상단 GNB 우선)
 *
 * @return int
 */
function borobill_find_header_gnb_menu_id() {
    foreach ( array( '사이트 상단 GNB', '헤더 GNB' ) as $name ) {
        $menu = wp_get_nav_menu_object( $name );
        if ( $menu ) {
            return (int) $menu->term_id;
        }
    }

    return 0;
}

/**
 * GNB 메뉴가 없으면 생성 후 header-menu 위치에 연결
 */
function borobill_ensure_header_nav_menu_id() {
    $named_menu_id = borobill_find_header_gnb_menu_id();
    if ( $named_menu_id > 0 ) {
        borobill_link_header_menu_location( $named_menu_id );
        return $named_menu_id;
    }

    $menu_id = borobill_get_header_nav_menu_id();
    if ( $menu_id > 0 && wp_get_nav_menu_object( $menu_id ) ) {
        return $menu_id;
    }

    $created = wp_create_nav_menu( '사이트 상단 GNB' );
    if ( is_wp_error( $created ) ) {
        return 0;
    }

    $menu_id = (int) $created;
    borobill_link_header_menu_location( $menu_id );

    return $menu_id;
}

function borobill_add_header_gnb_admin_page() {
    add_theme_page(
        '사이트 상단 GNB',
        '사이트 상단 GNB',
        'edit_theme_options',
        'borobill-header-gnb',
        'borobill_render_header_gnb_admin_page'
    );
}
add_action( 'admin_menu', 'borobill_add_header_gnb_admin_page' );

/**
 * 관리자 사이드바: 모양 > 사이트 상단 GNB 메뉴 숨김
 */
function borobill_hide_header_gnb_from_appearance() {
    remove_submenu_page( 'themes.php', 'borobill-header-gnb' );
}
add_action( 'admin_menu', 'borobill_hide_header_gnb_from_appearance', 999 );

/**
 * 관리자 사이드바: 모양 > 메뉴를 알림판과 글 사이(최상위)로 이동
 */
function borobill_get_nav_menus_admin_menu_slug() {
    return 'borobill-nav-menus';
}

function borobill_register_nav_menus_top_level_menu() {
    add_menu_page(
        '메뉴',
        '메뉴',
        'edit_theme_options',
        borobill_get_nav_menus_admin_menu_slug(),
        '__return_null',
        'dashicons-menu',
        3
    );
}
add_action( 'admin_menu', 'borobill_register_nav_menus_top_level_menu', 1 );

function borobill_move_nav_menus_out_of_appearance() {
    remove_submenu_page( 'themes.php', 'nav-menus.php' );
}
add_action( 'admin_menu', 'borobill_move_nav_menus_out_of_appearance', 999 );

/**
 * 최상위 메뉴 클릭 시 nav-menus.php 로 이동 (슬러그 분리로 모양과 동시 선택 방지)
 */
function borobill_redirect_nav_menus_top_level_page() {
    if ( ! is_admin() ) {
        return;
    }

    $page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
    if ( borobill_get_nav_menus_admin_menu_slug() !== $page ) {
        return;
    }

    wp_safe_redirect( admin_url( 'nav-menus.php' ) );
    exit;
}
add_action( 'load-toplevel_page_borobill-nav-menus', 'borobill_redirect_nav_menus_top_level_page' );

/**
 * nav-menus.php 화면: 사이드바에서 모양(themes.php)이 같이 열리지 않도록 활성 메뉴 고정
 */
function borobill_fix_nav_menus_admin_sidebar_state( $classes ) {
    global $parent_file, $submenu_file, $submenu, $pagenow;

    if ( 'nav-menus.php' !== $pagenow ) {
        return $classes;
    }

    if ( isset( $submenu['themes.php'] ) && is_array( $submenu['themes.php'] ) ) {
        foreach ( $submenu['themes.php'] as $index => $item ) {
            if ( isset( $item[2] ) && 'nav-menus.php' === $item[2] ) {
                unset( $submenu['themes.php'][ $index ] );
            }
        }
        $submenu['themes.php'] = array_values( $submenu['themes.php'] );
    }

    $parent_file  = borobill_get_nav_menus_admin_menu_slug();
    $submenu_file = false;

    return $classes;
}
add_filter( 'admin_body_class', 'borobill_fix_nav_menus_admin_sidebar_state', 999 );

function borobill_render_header_gnb_admin_page() {
    if ( ! current_user_can( 'edit_theme_options' ) ) {
        return;
    }

    $menu_id = borobill_ensure_header_nav_menu_id();
    ?>
    <div class="wrap">
        <h1>사이트 상단 GNB</h1>
        <p>
            <a class="button button-primary" href="<?php echo esc_url( admin_url( 'nav-menus.php' ) ); ?>">메뉴 추가</a>
            <?php if ( $menu_id > 0 ) : ?>
                <a class="button" href="<?php echo esc_url( admin_url( 'nav-menus.php?action=edit&menu=' . $menu_id ) ); ?>">수정</a>
            <?php endif; ?>
        </p>
    </div>
    <?php
}

/**
 * GNB 메뉴 저장 시 header-menu 위치에 항상 연결 (프론트 header.php 반영)
 */
function borobill_persist_header_gnb_location( $menu_id ) {
    if ( ! borobill_is_header_gnb_menu( $menu_id ) ) {
        return;
    }

    borobill_link_header_menu_location( (int) $menu_id );
}
add_action( 'wp_update_nav_menu', 'borobill_persist_header_gnb_location' );

/**
 * nav-menus.php — 현재 편집 중인 메뉴 ID
 *
 * @return int
 */
function borobill_get_editing_nav_menu_id() {
    if ( isset( $_REQUEST['menu'] ) ) {
        return (int) $_REQUEST['menu'];
    }

    return (int) get_user_option( 'nav_menu_recently_edited' );
}

/**
 * nav-menus.php — 사이트 상단 GNB 편집 중인지
 */
function borobill_is_editing_header_gnb_menu() {
    $editing_menu_id = borobill_get_editing_nav_menu_id();
    if ( $editing_menu_id <= 0 ) {
        return false;
    }

    $header_menu_id = borobill_get_header_nav_menu_id();
    if ( $header_menu_id > 0 && (int) $header_menu_id === $editing_menu_id ) {
        return true;
    }

    $menu_obj = wp_get_nav_menu_object( $editing_menu_id );
    if ( ! $menu_obj ) {
        return false;
    }

    return in_array( $menu_obj->name, array( '사이트 상단 GNB', '헤더 GNB' ), true );
}

/**
 * 메뉴 항목 추가 > 페이지(최신순): 사이트 상단 GNB 메뉴 구조와 동기화
 *
 * @param array $most_recent
 * @param array $args
 * @param array $box
 * @param array $recent_args
 * @return array
 */
function borobill_filter_nav_menu_page_recent_for_gnb( $most_recent, $args, $box, $recent_args ) {
    if ( ! borobill_is_editing_header_gnb_menu() ) {
        return $most_recent;
    }

    $header_menu_id = borobill_get_header_nav_menu_id();
    if ( $header_menu_id <= 0 ) {
        $header_menu_id = borobill_get_editing_nav_menu_id();
    }

    $menu_items = wp_get_nav_menu_items(
        $header_menu_id,
        array(
            'orderby' => 'menu_order',
            'order'   => 'ASC',
        )
    );

    if ( empty( $menu_items ) ) {
        return array();
    }

    $synced    = array();
    $seen_keys = array();

    foreach ( $menu_items as $menu_item ) {
        if ( ! $menu_item instanceof WP_Post ) {
            continue;
        }

        // 페이지(최신순): GNB 1차 메뉴만 — 메뉴 구조와 동일
        if ( (int) $menu_item->menu_item_parent !== 0 ) {
            continue;
        }

        if ( in_array( $menu_item->type, array( 'custom' ), true ) ) {
            continue;
        }

        $list_item  = null;
        $dedupe_key = '';

        if ( 'taxonomy' === $menu_item->type && 'category' === $menu_item->object ) {
            $term = get_term( (int) $menu_item->object_id, 'category' );
            if ( $term && ! is_wp_error( $term ) ) {
                $term       = clone $term;
                $menu_title = trim( (string) $menu_item->title );
                if ( '' !== $menu_title ) {
                    $term->name = $menu_title;
                }
                $list_item  = $term;
                $dedupe_key = 'term:' . (int) $term->term_id;
            }
        } elseif ( 'post_type' === $menu_item->type && 'page' === $menu_item->object ) {
            $page = get_post( (int) $menu_item->object_id );
            if ( $page instanceof WP_Post && 'publish' === $page->post_status ) {
                if ( ! empty( $menu_item->title ) && $page->post_title !== $menu_item->title ) {
                    $page             = clone $page;
                    $page->post_title = (string) $menu_item->title;
                }
                $list_item  = $page;
                $dedupe_key = 'page:' . (int) $page->ID;
            }
        }

        if ( ! $list_item || in_array( $dedupe_key, $seen_keys, true ) ) {
            continue;
        }

        $synced[]    = $list_item;
        $seen_keys[] = $dedupe_key;
    }

    return array_slice( $synced, 0, 5 );
}
add_filter( 'nav_menu_items_page_recent', 'borobill_filter_nav_menu_page_recent_for_gnb', 10, 4 );

/**
 * 글 > 카테고리 목록 화면인지
 *
 * @return bool
 */
function borobill_is_admin_category_list_screen() {
    if ( ! is_admin() ) {
        return false;
    }

    global $pagenow, $taxnow;

    if ( 'edit-tags.php' === $pagenow && 'category' === $taxnow ) {
        return true;
    }

    if ( 'edit-tags.php' === $pagenow && isset( $_GET['taxonomy'] ) && 'category' === $_GET['taxonomy'] ) {
        return true;
    }

    if ( function_exists( 'get_current_screen' ) ) {
        $screen = get_current_screen();
        if ( $screen && 'edit-category' === $screen->id ) {
            return true;
        }
    }

    return false;
}

/**
 * 글 작성·수정 화면인지
 *
 * @return bool
 */
function borobill_is_post_editor_category_screen() {
    if ( ! is_admin() ) {
        return false;
    }

    global $pagenow;
    if ( ! in_array( $pagenow, array( 'post.php', 'post-new.php' ), true ) ) {
        return false;
    }

    if ( function_exists( 'get_current_screen' ) ) {
        $screen = get_current_screen();
        if ( $screen && isset( $screen->post_type ) && 'post' === $screen->post_type ) {
            return true;
        }
    }

    return isset( $_GET['post_type'] ) && 'post' === $_GET['post_type'];
}

/**
 * nav-menus.php — 카테고리 추가 목록을 GNB 메뉴 구조와 동기화할지
 *
 * @param array $args get_terms 인자
 * @return bool
 */
function borobill_should_sync_nav_menu_category_add_list( $args = array() ) {
    if ( ! is_admin() ) {
        return false;
    }

    global $pagenow;
    if ( 'nav-menus.php' !== $pagenow ) {
        return false;
    }

    if ( ! borobill_is_editing_header_gnb_menu() ) {
        return false;
    }

    if ( ! empty( $args['search'] ) ) {
        return false;
    }

    return true;
}

/**
 * GNB 메뉴 구조로 카테고리 목록을 맞출 화면인지 (메뉴 편집 · 카테고리 목록)
 *
 * @param array $args get_terms 인자
 * @return bool
 */
function borobill_should_sync_terms_to_gnb_menu( $args = array() ) {
    if ( ! empty( $args['search'] ) ) {
        return false;
    }

    if ( borobill_should_sync_nav_menu_category_add_list( $args ) ) {
        return true;
    }

    if ( borobill_is_admin_category_list_screen() ) {
        return true;
    }

    return borobill_is_post_editor_category_screen();
}

/**
 * GNB 동기화용 get_terms 호출인지 (목록 표시용만, 내부 계층/카운트 쿼리 제외)
 *
 * @param array $args get_terms 인자
 * @return bool
 */
function borobill_is_gnb_category_terms_display_query( $args = array() ) {
    $fields = isset( $args['fields'] ) ? $args['fields'] : 'all';

    if ( in_array( $fields, array( 'ids', 'id=>parent', 'id=>name', 'id=>slug', 'names', 'slugs', 'tt_ids', 'count' ), true ) ) {
        return false;
    }

    return true;
}

/**
 * 글 > 카테고리 목록: 좌측 추가 폼 차단 (카테고리는 메뉴 화면에서만 추가)
 *
 * @param string|WP_Error $term
 * @param string          $taxonomy
 * @return string|WP_Error
 */
function borobill_block_category_create_on_list_page( $term, $taxonomy ) {
    if ( 'category' !== $taxonomy || ! is_admin() ) {
        return $term;
    }

    global $pagenow;
    if ( 'edit-tags.php' !== $pagenow ) {
        return $term;
    }

    if ( isset( $_POST['action'] ) && 'add-tag' === $_POST['action'] ) {
        return new WP_Error(
            'borobill_category_add_disabled',
            '카테고리는 메뉴 화면에서 추가해 주세요.'
        );
    }

    return $term;
}
add_filter( 'pre_insert_term', 'borobill_block_category_create_on_list_page', 10, 2 );

/**
 * GNB nav-menu 순서·계층 그대로 카테고리 목록 생성 (글 편집 체크리스트용)
 *
 * @return array<int, WP_Term>
 */
function borobill_build_gnb_menu_synced_category_terms_from_nav_menu() {
    static $cache = null;

    if ( null !== $cache ) {
        return $cache;
    }

    $synced         = array();
    $header_menu_id = borobill_get_header_nav_menu_id();
    if ( $header_menu_id <= 0 ) {
        $header_menu_id = borobill_find_header_gnb_menu_id();
    }

    if ( $header_menu_id > 0 ) {
        $menu_items = wp_get_nav_menu_items(
            $header_menu_id,
            array(
                'orderby' => 'menu_order',
                'order'   => 'ASC',
            )
        );

        if ( ! empty( $menu_items ) ) {
            $parent_term_by_menu = array();

            foreach ( $menu_items as $menu_item ) {
                if ( ! $menu_item instanceof WP_Post ) {
                    continue;
                }
                if ( 'taxonomy' !== $menu_item->type || 'category' !== $menu_item->object ) {
                    continue;
                }

                $term = get_term( (int) $menu_item->object_id, 'category' );
                if ( ! $term || is_wp_error( $term ) ) {
                    continue;
                }

                $term       = clone $term;
                $menu_title = trim( (string) $menu_item->title );
                if ( '' !== $menu_title ) {
                    $term->name = $menu_title;
                }

                $parent_menu_id = (int) $menu_item->menu_item_parent;
                if ( $parent_menu_id > 0 && isset( $parent_term_by_menu[ $parent_menu_id ] ) ) {
                    $term->parent = (int) $parent_term_by_menu[ $parent_menu_id ];
                } else {
                    $term->parent = 0;
                }

                $synced[] = $term;
                $parent_term_by_menu[ (int) $menu_item->ID ] = (int) $term->term_id;
            }
        }
    }

    $cache = $synced;
    return $cache;
}

/**
 * 글 작성·수정 > 모든 카테고리 탭 get_terms 호출인지
 *
 * @param array $args
 * @return bool
 */
function borobill_is_post_editor_all_categories_checklist_query( $args = array() ) {
    if ( ! borobill_is_post_editor_category_screen() ) {
        return false;
    }

    return isset( $args['get'] ) && 'all' === $args['get'];
}

/**
 * 사이트 상단 GNB 메뉴에 연결된 카테고리(메뉴명·계층 반영)
 *
 * @return array<int, WP_Term>
 */
function borobill_get_gnb_menu_synced_category_terms() {
    static $cache = null;

    if ( null !== $cache ) {
        return $cache;
    }

    $synced = array();

    foreach ( borobill_get_header_gnb_lnb_groups() as $group ) {
        $parent_id = (int) ( $group['term_id'] ?? 0 );
        if ( $parent_id <= 0 ) {
            continue;
        }

        $parent = get_term( $parent_id, 'category' );
        if ( ! $parent || is_wp_error( $parent ) ) {
            continue;
        }

        $parent = clone $parent;
        $title  = trim( (string) ( $group['title'] ?? '' ) );
        if ( '' !== $title ) {
            $parent->name = $title;
        }
        $parent->parent = 0;
        $synced[]       = $parent;

        foreach ( $group['children'] ?? array() as $child ) {
            $child_id = (int) ( $child['term_id'] ?? 0 );
            if ( $child_id <= 0 ) {
                continue;
            }

            $child_term = get_term( $child_id, 'category' );
            if ( ! $child_term || is_wp_error( $child_term ) ) {
                continue;
            }

            $child_term = clone $child_term;
            $child_title = trim( (string) ( $child['title'] ?? '' ) );
            if ( '' !== $child_title ) {
                $child_term->name = $child_title;
            }
            $child_term->parent = $parent_id;
            $synced[]           = $child_term;
        }
    }

    if ( empty( $synced ) ) {
        $header_menu_id = borobill_get_header_nav_menu_id();
        if ( $header_menu_id <= 0 ) {
            $header_menu_id = borobill_find_header_gnb_menu_id();
        }
        if ( $header_menu_id <= 0 && borobill_is_editing_header_gnb_menu() ) {
            $header_menu_id = borobill_get_editing_nav_menu_id();
        }

        if ( $header_menu_id > 0 ) {
            $menu_items = wp_get_nav_menu_items(
                $header_menu_id,
                array(
                    'orderby' => 'menu_order',
                    'order'   => 'ASC',
                )
            );

            if ( ! empty( $menu_items ) ) {
                $parent_term_by_menu = array();

                foreach ( $menu_items as $menu_item ) {
                    if ( ! $menu_item instanceof WP_Post ) {
                        continue;
                    }
                    if ( 'taxonomy' !== $menu_item->type || 'category' !== $menu_item->object ) {
                        continue;
                    }

                    $term = get_term( (int) $menu_item->object_id, 'category' );
                    if ( ! $term || is_wp_error( $term ) ) {
                        continue;
                    }

                    $term       = clone $term;
                    $menu_title = trim( (string) $menu_item->title );
                    if ( '' !== $menu_title ) {
                        $term->name = $menu_title;
                    }

                    $parent_menu_id = (int) $menu_item->menu_item_parent;
                    if ( $parent_menu_id > 0 && isset( $parent_term_by_menu[ $parent_menu_id ] ) ) {
                        $term->parent = (int) $parent_term_by_menu[ $parent_menu_id ];
                    } else {
                        $term->parent = 0;
                    }

                    $synced[] = $term;

                    if ( 0 === $parent_menu_id ) {
                        $parent_term_by_menu[ (int) $menu_item->ID ] = (int) $term->term_id;
                    }
                }
            }
        }
    }

    $cache = $synced;
    return $cache;
}

/**
 * GNB 메뉴 항목 → 카테고리 term (목록 표시용)
 * 커스텀 링크 1차도 동일 이름 카테고리로 연결. 메뉴 항목 자체는 수정하지 않음.
 *
 * @param WP_Post $menu_item
 * @return WP_Term|null
 */
function borobill_resolve_category_term_for_gnb_menu_item( $menu_item ) {
    if ( ! $menu_item instanceof WP_Post ) {
        return null;
    }

    if ( 'taxonomy' === $menu_item->type && 'category' === $menu_item->object ) {
        $term = get_term( (int) $menu_item->object_id, 'category' );
        if ( $term && ! is_wp_error( $term ) ) {
            return $term;
        }
        return null;
    }

    $title = trim( (string) $menu_item->title );
    if ( '' === $title ) {
        return null;
    }

    $candidates = array( $title );
    if ( '세무비즈니스' === $title ) {
        $candidates[] = '세무·비즈니스';
    } elseif ( '세무·비즈니스' === $title ) {
        $candidates[] = '세무비즈니스';
    }

    foreach ( $candidates as $name ) {
        $term = get_term_by( 'name', $name, 'category' );
        if ( $term && ! is_wp_error( $term ) ) {
            return $term;
        }
    }

    // 메뉴는 그대로 두고, 목록·추천아티클 저장용 카테고리만 확보
    $GLOBALS['borobill_skip_gnb_category_sync'] = true;
    $inserted = wp_insert_term(
        $title,
        'category',
        array(
            'slug'   => sanitize_title( $title ),
            'parent' => 0,
        )
    );
    $GLOBALS['borobill_skip_gnb_category_sync'] = false;

    if ( is_wp_error( $inserted ) ) {
        if ( 'term_exists' === $inserted->get_error_code() ) {
            $existing_id = (int) $inserted->get_error_data();
            if ( $existing_id > 0 ) {
                $term = get_term( $existing_id, 'category' );
                if ( $term && ! is_wp_error( $term ) ) {
                    return $term;
                }
            }
        }
        return null;
    }

    $term = get_term( (int) $inserted['term_id'], 'category' );
    if ( ! $term || is_wp_error( $term ) ) {
        return null;
    }

    return $term;
}

/**
 * GNB 메뉴 기준 카테고리 목록 (탭별)
 * - article(추천아티클): 1차 메뉴 전부
 * - post(추천게시글): 2차 메뉴(카테고리)만
 * 메뉴 DB는 읽기만 하며 변경하지 않음.
 *
 * @return array<int, WP_Term>
 */
function borobill_get_admin_category_list_synced_terms() {
    static $cache = array();

    $tab = borobill_get_admin_category_list_tab();
    if ( isset( $cache[ $tab ] ) ) {
        return $cache[ $tab ];
    }

    $synced         = array();
    $header_menu_id = borobill_get_header_nav_menu_id();
    if ( $header_menu_id <= 0 ) {
        $header_menu_id = borobill_find_header_gnb_menu_id();
    }

    if ( $header_menu_id <= 0 ) {
        $cache[ $tab ] = $synced;
        return $synced;
    }

    $menu_items = wp_get_nav_menu_items(
        $header_menu_id,
        array(
            'orderby' => 'menu_order',
            'order'   => 'ASC',
        )
    );

    if ( empty( $menu_items ) ) {
        $cache[ $tab ] = $synced;
        return $synced;
    }

    $primary_menu_ids = array();
    foreach ( $menu_items as $menu_item ) {
        if ( ! $menu_item instanceof WP_Post ) {
            continue;
        }
        if ( 0 === (int) $menu_item->menu_item_parent ) {
            $primary_menu_ids[ (int) $menu_item->ID ] = true;
        }
    }

    $seen_term_ids = array();

    foreach ( $menu_items as $menu_item ) {
        if ( ! $menu_item instanceof WP_Post ) {
            continue;
        }

        $parent_menu_id = (int) $menu_item->menu_item_parent;
        $is_primary     = ( 0 === $parent_menu_id );
        $is_secondary   = ( ! $is_primary && isset( $primary_menu_ids[ $parent_menu_id ] ) );

        if ( 'article' === $tab ) {
            if ( ! $is_primary ) {
                continue;
            }
            $term = borobill_resolve_category_term_for_gnb_menu_item( $menu_item );
        } else {
            if ( ! $is_secondary ) {
                continue;
            }
            // 2차는 실제 카테고리 메뉴만
            if ( 'taxonomy' !== $menu_item->type || 'category' !== $menu_item->object ) {
                continue;
            }
            $term = get_term( (int) $menu_item->object_id, 'category' );
            if ( ! $term || is_wp_error( $term ) ) {
                continue;
            }
        }

        if ( ! $term || is_wp_error( $term ) ) {
            continue;
        }

        $term_id = (int) $term->term_id;
        if ( isset( $seen_term_ids[ $term_id ] ) ) {
            continue;
        }

        $term = clone $term;
        $menu_title = trim( (string) $menu_item->title );
        if ( '' !== $menu_title ) {
            $term->name = $menu_title;
        }
        $term->parent = 0;

        $synced[]                  = $term;
        $seen_term_ids[ $term_id ] = true;
    }

    $cache[ $tab ] = $synced;
    return $synced;
}

/**
 * GNB 메뉴 계층 — 카테고리 목록 테이블용 children 맵
 *
 * @return array<int, int[]>
 */
function borobill_get_gnb_category_children_map() {
    // 탭별 목록은 1차/2차만 평탄 표시 — 계층 대시 없음
    return array();
}

/**
 * 글 > 카테고리 목록: DB 계층 대신 탭용 평탄 목록
 *
 * @param mixed $pre
 * @return mixed
 */
function borobill_filter_category_children_option( $pre ) {
    if ( ! borobill_is_admin_category_list_screen() ) {
        return $pre;
    }

    return array();
}
add_filter( 'pre_option_category_children', 'borobill_filter_category_children_option' );

/**
 * 메뉴 편집 · 글 > 카테고리 목록: GNB 메뉴 구조와 카테고리 목록 동기화
 *
 * @param array         $terms
 * @param array|string  $taxonomies
 * @param array         $args
 * @param WP_Term_Query $term_query
 * @return array
 */
function borobill_filter_get_terms_sync_gnb_categories( $terms, $taxonomies, $args, $term_query ) {
    if ( ! empty( $GLOBALS['borobill_skip_gnb_category_sync'] ) ) {
        return $terms;
    }

    if ( ! borobill_should_sync_terms_to_gnb_menu( $args ) ) {
        return $terms;
    }

    if ( ! borobill_is_gnb_category_terms_display_query( $args ) ) {
        return $terms;
    }

    $taxonomies = (array) $taxonomies;
    if ( ! in_array( 'category', $taxonomies, true ) ) {
        return $terms;
    }

    if ( borobill_is_admin_category_list_screen() ) {
        $synced = borobill_get_admin_category_list_synced_terms();
    } else {
        $synced = borobill_get_gnb_menu_synced_category_terms();
        if ( borobill_is_post_editor_all_categories_checklist_query( $args ) ) {
            $menu_synced = borobill_build_gnb_menu_synced_category_terms_from_nav_menu();
            if ( ! empty( $menu_synced ) ) {
                $synced = $menu_synced;
            }
        }
    }

    if ( empty( $synced ) ) {
        return $terms;
    }

    $offset = isset( $args['offset'] ) ? max( 0, (int) $args['offset'] ) : 0;
    $number = isset( $args['number'] ) ? (int) $args['number'] : 0;

    if ( $number > 0 ) {
        return array_slice( $synced, $offset, $number );
    }

    if ( $offset > 0 ) {
        return array_slice( $synced, $offset );
    }

    return $synced;
}
add_filter( 'get_terms', 'borobill_filter_get_terms_sync_gnb_categories', 10, 4 );

function borobill_nav_menu_admin_labels( $translated, $text, $domain ) {
    if ( ! is_admin() || 'default' !== $domain ) {
        return $translated;
    }

    global $pagenow;
    if ( 'nav-menus.php' !== $pagenow ) {
        return $translated;
    }

    if ( 'Navigation Label' === $text ) {
        return '메뉴명';
    }
    if ( 'Menu Parent' === $text ) {
        return '카테고리 선택';
    }
    if ( 'Menu Order' === $text ) {
        return '메뉴순서';
    }

    return $translated;
}
add_filter( 'gettext', 'borobill_nav_menu_admin_labels', 10, 3 );

function borobill_get_data_usage_definitions() {
    return array(
        'post_content'      => array(
            'label'            => '게시글 본문',
            'table'            => 'wp_posts',
            'page_type'        => 'board',
            'creates_category' => true,
        ),
        'post_meta'         => array(
            'label'            => '게시글 메타',
            'table'            => 'wp_postmeta',
            'page_type'        => 'code',
            'creates_category' => false,
        ),
        'category_board'    => array(
            'label'            => '카테고리(게시판)',
            'table'            => 'wp_terms',
            'page_type'        => 'board',
            'creates_category' => true,
        ),
        'term_relationship' => array(
            'label'            => '글-카테고리 연결',
            'table'            => 'wp_term_relationships',
            'page_type'        => 'code',
            'creates_category' => false,
        ),
        'term_meta'         => array(
            'label'            => '카테고리 메타',
            'table'            => 'wp_termmeta',
            'page_type'        => 'code',
            'creates_category' => false,
        ),
        'nav_menu_item'     => array(
            'label'            => 'GNB 메뉴 항목',
            'table'            => 'wp_posts',
            'page_type'        => 'code',
            'creates_category' => false,
        ),
    );
}

function borobill_get_data_usage_label( $usage_key ) {
    $definitions = borobill_get_data_usage_definitions();
    $usage_key   = sanitize_key( (string) $usage_key );

    if ( isset( $definitions[ $usage_key ] ) ) {
        return $definitions[ $usage_key ]['label'];
    }

    return '';
}

function borobill_resolve_data_usage_key( $usage_key, $page_type, $object, $object_id ) {
    $usage_key = sanitize_key( (string) $usage_key );
    if ( '' !== $usage_key && borobill_get_data_usage_label( $usage_key ) ) {
        return $usage_key;
    }

    if ( 'category' === $object && $object_id > 0 ) {
        return 'post_content';
    }

    if ( 'code' === $page_type ) {
        return 'post_meta';
    }

    return 'post_content';
}

function borobill_get_board_page_usage_label( $page_type ) {
    if ( 'board' === $page_type ) {
        return '게시글';
    }
    if ( 'code' === $page_type ) {
        return '코드 페이지';
    }

    return '';
}

function borobill_is_valid_board_db_table( $table_name ) {
    return (bool) preg_match( '/^[a-z][a-z0-9_]*$/', (string) $table_name );
}

function borobill_sanitize_board_db_table( $table_name ) {
    $table_name = strtolower( (string) $table_name );
    $table_name = preg_replace( '/[^a-z0-9_]/', '', $table_name );

    return $table_name;
}

function borobill_board_db_table_to_category_slug( $table_name ) {
    $slug = preg_replace( '/^wp_/', '', (string) $table_name );
    $slug = str_replace( '_', '-', $slug );

    return sanitize_title( $slug );
}

function borobill_get_menu_item_db_table_name( $menu_item_db_id, $page_type, $stored_table, $object, $object_id ) {
    global $wpdb;

    if ( borobill_is_valid_board_db_table( $stored_table ) ) {
        return $stored_table;
    }

    if ( 'board' === $page_type || ( '' === $page_type && 'category' === $object && $object_id > 0 ) ) {
        return $wpdb->posts;
    }

    return '';
}

function borobill_board_db_table_exists_in_menu( $table_name, $exclude_item_id = 0 ) {
    $menu_id = borobill_get_header_nav_menu_id();
    if ( $menu_id <= 0 || ! borobill_is_valid_board_db_table( $table_name ) ) {
        return false;
    }

    $items = wp_get_nav_menu_items(
        $menu_id,
        array(
            'update_post_term_cache' => false,
        )
    );

    if ( empty( $items ) ) {
        return false;
    }

    foreach ( $items as $item ) {
        if ( (int) $item->ID === (int) $exclude_item_id ) {
            continue;
        }

        $stored = (string) get_post_meta( $item->ID, '_borobill_db_slug', true );
        if ( $stored === $table_name ) {
            return true;
        }
    }

    return false;
}

function borobill_mysql_table_exists( $table_name ) {
    global $wpdb;

    if ( ! borobill_is_valid_board_db_table( $table_name ) ) {
        return false;
    }

    $found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) );

    return ! empty( $found );
}

function borobill_get_nav_menu_item_board_meta( $menu_item_db_id ) {
    $menu_item_db_id = (int) $menu_item_db_id;
    if ( $menu_item_db_id <= 0 ) {
        return array(
            'page_type'  => '',
            'db_slug'    => '',
            'db_usage'   => '',
            'data_usage' => 'post_content',
            'is_new'     => true,
        );
    }

    $page_type = (string) get_post_meta( $menu_item_db_id, '_borobill_page_type', true );
    $db_table  = borobill_sanitize_board_db_table( get_post_meta( $menu_item_db_id, '_borobill_db_slug', true ) );
    $created   = '1' === get_post_meta( $menu_item_db_id, '_borobill_board_created', true );

    if ( ! borobill_is_valid_board_db_table( $db_table ) ) {
        $db_table = '';
    }

    $object    = (string) get_post_meta( $menu_item_db_id, '_menu_item_object', true );
    $object_id = (int) get_post_meta( $menu_item_db_id, '_menu_item_object_id', true );
    $parent_id = (int) get_post_meta( $menu_item_db_id, '_menu_item_menu_item_parent', true );

    $display_table = borobill_get_menu_item_db_table_name( $menu_item_db_id, $page_type, $db_table, $object, $object_id );

    if ( '' === $page_type && '' !== $display_table ) {
        $page_type = 'board';
    }

    $has_board = ( 'category' === $object && $object_id > 0 ) || $created || borobill_is_valid_board_db_table( $db_table );

    $data_usage = borobill_resolve_data_usage_key(
        get_post_meta( $menu_item_db_id, '_borobill_data_usage', true ),
        $page_type,
        $object,
        $object_id
    );
    $usage_def  = borobill_get_data_usage_definitions()[ $data_usage ];

    return array(
        'page_type'  => $usage_def['page_type'],
        'db_table'   => $usage_def['table'],
        'db_slug'    => $usage_def['table'],
        'db_usage'   => $usage_def['label'],
        'data_usage' => $data_usage,
        'is_new'     => ! $has_board,
        'parent_id'  => $parent_id,
    );
}

function borobill_get_category_term_id_from_menu_item( $menu_item_db_id ) {
    $menu_item_db_id = (int) $menu_item_db_id;
    if ( $menu_item_db_id <= 0 ) {
        return 0;
    }

    if ( 'category' !== get_post_meta( $menu_item_db_id, '_menu_item_object', true ) ) {
        return 0;
    }

    return (int) get_post_meta( $menu_item_db_id, '_menu_item_object_id', true );
}

function borobill_nav_menu_admin_item_data() {
    $menu_id = borobill_get_header_nav_menu_id();
    if ( $menu_id <= 0 ) {
        return array();
    }

    $items = wp_get_nav_menu_items(
        $menu_id,
        array(
            'update_post_term_cache' => false,
        )
    );

    if ( empty( $items ) ) {
        return array();
    }

    $data = array();
    foreach ( $items as $item ) {
        $data[ (string) $item->ID ] = borobill_get_nav_menu_item_board_meta( $item->ID );
    }

    return $data;
}

function borobill_ajax_check_menu_db_slug() {
    check_ajax_referer( 'borobill_nav_menu_gnb', 'nonce' );

    if ( ! current_user_can( 'edit_theme_options' ) ) {
        wp_send_json_error( array( 'message' => '권한이 없습니다.' ), 403 );
    }

    $table_name = isset( $_POST['slug'] ) ? borobill_sanitize_board_db_table( wp_unslash( $_POST['slug'] ) ) : '';
    if ( '' === $table_name || ! borobill_is_valid_board_db_table( $table_name ) ) {
        wp_send_json_error( array( 'message' => 'MySQL 테이블명 형식이 올바르지 않습니다. (예: wp_posts)' ) );
    }

    $item_id = isset( $_POST['item_id'] ) ? (int) $_POST['item_id'] : 0;
    $exists  = borobill_mysql_table_exists( $table_name ) || borobill_board_db_table_exists_in_menu( $table_name, $item_id );

    if ( ! $exists ) {
        $category_slug = borobill_board_db_table_to_category_slug( $table_name );
        if ( '' !== $category_slug && term_exists( $category_slug, 'category' ) ) {
            if ( $item_id > 0 ) {
                $object_id = borobill_get_category_term_id_from_menu_item( $item_id );
                $term      = get_term_by( 'slug', $category_slug, 'category' );
                if ( ! $term || is_wp_error( $term ) || (int) $term->term_id !== $object_id ) {
                    $exists = true;
                }
            } else {
                $exists = true;
            }
        }
    } elseif ( $item_id > 0 ) {
        $stored = borobill_sanitize_board_db_table( get_post_meta( $item_id, '_borobill_db_slug', true ) );
        if ( $stored === $table_name ) {
            $exists = false;
        }
    }

    wp_send_json_success(
        array(
            'exists' => $exists,
        )
    );
}
add_action( 'wp_ajax_borobill_check_menu_db_slug', 'borobill_ajax_check_menu_db_slug' );

function borobill_get_gnb_parent_term_id_for_menu_item( $menu_item_db_id ) {
    $menu_item_db_id = (int) $menu_item_db_id;
    if ( $menu_item_db_id <= 0 ) {
        return 0;
    }

    $term_id = borobill_get_category_term_id_from_menu_item( $menu_item_db_id );
    if ( $term_id > 0 ) {
        return $term_id;
    }

    $title = get_the_title( $menu_item_db_id );
    if ( '' === $title ) {
        return 0;
    }

    foreach ( borobill_get_gnb_parent_category_terms() as $term ) {
        if ( $term->name === $title ) {
            return (int) $term->term_id;
        }
    }

    return 0;
}

function borobill_find_nav_menu_item_post_key( $menu_item_db_id, $args ) {
    static $used_keys = array();

    $menu_item_db_id = (int) $menu_item_db_id;
    $post_key        = (string) $menu_item_db_id;

    if ( isset( $_POST['borobill-data-usage'][ $post_key ] ) ) {
        return $post_key;
    }

    if ( isset( $_POST['borobill-page-type'][ $post_key ] ) ) {
        return $post_key;
    }

    if ( empty( $_POST['menu-item-db-id'] ) || ! is_array( $_POST['menu-item-db-id'] ) ) {
        return $post_key;
    }

    $args_title  = isset( $args['menu-item-title'] ) ? wp_unslash( $args['menu-item-title'] ) : '';
    $args_parent = isset( $args['menu-item-parent-id'] ) ? (int) $args['menu-item-parent-id'] : 0;

    foreach ( $_POST['menu-item-db-id'] as $_key => $posted_db_id ) {
        $_key = (string) $_key;

        if ( isset( $used_keys[ $_key ] ) ) {
            continue;
        }

        if ( ! isset( $_POST['borobill-page-type'][ $_key ] ) && ! isset( $_POST['borobill-data-usage'][ $_key ] ) ) {
            continue;
        }

        $posted_title  = isset( $_POST['menu-item-title'][ $_key ] ) ? wp_unslash( $_POST['menu-item-title'][ $_key ] ) : '';
        $posted_parent = isset( $_POST['menu-item-parent-id'][ $_key ] ) ? (int) $_POST['menu-item-parent-id'][ $_key ] : 0;

        if ( $posted_title !== $args_title || $posted_parent !== $args_parent ) {
            continue;
        }

        if ( (int) $_key === $menu_item_db_id || ( (int) $_key < 0 && (int) $posted_db_id === (int) $_key ) ) {
            $used_keys[ $_key ] = true;
            return $_key;
        }
    }

    return $post_key;
}

function borobill_save_nav_menu_item_board_meta( $menu_id, $menu_item_db_id, $args ) {
    if ( ! current_user_can( 'edit_theme_options' ) ) {
        return;
    }

    $menu_item_db_id = (int) $menu_item_db_id;
    if ( $menu_item_db_id <= 0 ) {
        return;
    }

    if (
        ( empty( $_POST['borobill-page-type'] ) || ! is_array( $_POST['borobill-page-type'] ) ) &&
        ( empty( $_POST['borobill-data-usage'] ) || ! is_array( $_POST['borobill-data-usage'] ) )
    ) {
        return;
    }

    $post_key = borobill_find_nav_menu_item_post_key( $menu_item_db_id, $args );

    if ( ! isset( $_POST['borobill-page-type'][ $post_key ] ) && ! isset( $_POST['borobill-data-usage'][ $post_key ] ) ) {
        return;
    }

    $definitions = borobill_get_data_usage_definitions();
    $data_usage  = isset( $_POST['borobill-data-usage'][ $post_key ] )
        ? sanitize_key( wp_unslash( $_POST['borobill-data-usage'][ $post_key ] ) )
        : 'post_content';

    if ( ! isset( $definitions[ $data_usage ] ) ) {
        $data_usage = 'post_content';
    }

    $usage_def = $definitions[ $data_usage ];
    $page_type = $usage_def['page_type'];

    update_post_meta( $menu_item_db_id, '_borobill_data_usage', $data_usage );
    update_post_meta( $menu_item_db_id, '_borobill_page_type', $page_type );

    $parent_menu_item_id = isset( $args['menu-item-parent-id'] ) ? (int) $args['menu-item-parent-id'] : 0;
    if ( $parent_menu_item_id <= 0 ) {
        return;
    }

    $existing_meta = borobill_get_nav_menu_item_board_meta( $menu_item_db_id );
    if ( ! $existing_meta['is_new'] ) {
        return;
    }

    if ( ! $usage_def['creates_category'] ) {
        if ( 'code' === $page_type ) {
            update_post_meta( $menu_item_db_id, '_menu_item_url', '#' );
        }
        update_post_meta( $menu_item_db_id, '_borobill_db_slug', $usage_def['table'] );
        return;
    }

    $menu_title = isset( $args['menu-item-title'] ) ? wp_strip_all_tags( $args['menu-item-title'] ) : '';
    $db_slug    = borobill_sanitize_board_db_table( 'wp_' . sanitize_title( $menu_title ) );
    if ( '' === $db_slug || ! borobill_is_valid_board_db_table( $db_slug ) ) {
        $db_slug = 'wp_board_' . (int) $menu_item_db_id;
    }

    $category_slug = borobill_board_db_table_to_category_slug( $db_slug );
    if ( '' === $category_slug || term_exists( $category_slug, 'category' ) ) {
        return;
    }

    $parent_term_id = borobill_get_gnb_parent_term_id_for_menu_item( $parent_menu_item_id );
    if ( $parent_term_id <= 0 ) {
        return;
    }

    $term_title = '' !== $menu_title ? $menu_title : $category_slug;

    $result = wp_insert_term(
        $term_title,
        'category',
        array(
            'slug'   => $category_slug,
            'parent' => $parent_term_id,
        )
    );

    if ( is_wp_error( $result ) ) {
        return;
    }

    $term_id = (int) $result['term_id'];

    update_post_meta( $menu_item_db_id, '_menu_item_type', 'taxonomy' );
    update_post_meta( $menu_item_db_id, '_menu_item_object', 'category' );
    update_post_meta( $menu_item_db_id, '_menu_item_object_id', $term_id );
    update_post_meta( $menu_item_db_id, '_menu_item_url', get_category_link( $term_id ) );
    update_post_meta( $menu_item_db_id, '_borobill_db_slug', $usage_def['table'] );
    update_post_meta( $menu_item_db_id, '_borobill_board_created', '1' );
}
add_action( 'wp_update_nav_menu_item', 'borobill_save_nav_menu_item_board_meta', 10, 3 );

function borobill_nav_menus_admin_assets( $hook ) {
    if ( 'nav-menus.php' !== $hook ) {
        return;
    }

    $js_path = get_template_directory() . '/js/admin-nav-menu-gnb.js';
    $js_ver  = file_exists( $js_path ) ? filemtime( $js_path ) : '1.0';

    wp_enqueue_script(
        'borobill-nav-menu-gnb',
        get_template_directory_uri() . '/js/admin-nav-menu-gnb.js',
        array( 'nav-menu' ),
        $js_ver,
        true
    );

    wp_localize_script(
        'borobill-nav-menu-gnb',
        'borobillNavMenuGnb',
        array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'borobill_nav_menu_gnb' ),
            'items'     => borobill_nav_menu_admin_item_data(),
            'dataUsage' => borobill_get_data_usage_definitions(),
        )
    );
}
add_action( 'admin_enqueue_scripts', 'borobill_nav_menus_admin_assets' );

/**
 * 메인 슬라이드 설정 페이지용 스크립트 / 스타일
 */
function borobill_theme_options_assets( $hook ) {
    if ( ! borobill_is_banner_settings_admin_page() && ! borobill_is_banner_settings_admin_hook( $hook ) ) {
        return;
    }

    wp_enqueue_media();
    wp_enqueue_script( 'jquery-ui-sortable' );
    wp_enqueue_style( 'wp-color-picker' );

    $alpha_path = get_template_directory() . '/js/wp-color-picker-alpha.min.js';
    wp_enqueue_script(
        'wp-color-picker-alpha',
        get_template_directory_uri() . '/js/wp-color-picker-alpha.min.js',
        array( 'wp-color-picker' ),
        file_exists( $alpha_path ) ? filemtime( $alpha_path ) : '3.0.3',
        true
    );

    wp_enqueue_script(
        'borobill-theme-options',
        get_template_directory_uri() . '/js/theme-options.js',
        array( 'jquery', 'wp-color-picker', 'wp-color-picker-alpha' ),
        file_exists( get_template_directory() . '/js/theme-options.js' ) ? filemtime( get_template_directory() . '/js/theme-options.js' ) : '1.0',
        true
    );
    wp_localize_script(
        'borobill-theme-options',
        'borobillHeroManager',
        array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'borobill_hero_manager' ),
        )
    );
    wp_localize_script(
        'borobill-theme-options',
        'borobillBottomBannerManager',
        array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'borobill_bottom_banner_manager' ),
        )
    );
}
add_action( 'admin_enqueue_scripts', 'borobill_theme_options_assets' );

/**
 * 특정 관리자 화면만 UI 스킨 적용
 * - 모양 > 메뉴(nav-menus.php)
 * - Main배너 > 배너설정
 * - Main배너 > 인사이트
 * - 글(목록/작성/편집)
 * - 통계 대시보드
 */
function borobill_should_apply_admin_skin( $hook ) {
    // 통계 대시보드 / TOP 상세
    if ( 'toplevel_page_borobill-stats' === $hook || 'admin_page_borobill-stats-top10' === $hook ) {
        return true;
    }

    // Main배너 하위 페이지
    if ( borobill_is_banner_settings_admin_page() || borobill_is_main_banner_stats_admin_page() ) {
        return true;
    }

    if ( borobill_is_banner_settings_admin_hook( $hook ) || borobill_is_main_banner_stats_admin_hook( $hook ) ) {
        return true;
    }

    // 모양 > 메뉴
    if ( 'nav-menus.php' === $hook ) {
        return true;
    }

    // 글 목록(edit.php) / 글 편집(post.php) / 글 새로작성(post-new.php)
    if ( in_array( $hook, array( 'edit.php', 'post.php', 'post-new.php' ), true ) ) {
        if ( function_exists( 'get_current_screen' ) ) {
            $screen = get_current_screen();
            if ( $screen && isset( $screen->post_type ) && 'post' === $screen->post_type ) {
                return true;
            }
        }
    }

    return false;
}

function borobill_admin_skin_body_class( $classes ) {
    if ( ! is_admin() ) {
        return $classes;
    }

    if ( borobill_is_banner_settings_admin_page() ) {
        $classes .= ' borobill-admin-skin borobill-banner-settings-admin';
        return $classes;
    }

    if ( borobill_is_main_banner_stats_admin_page() ) {
        $classes .= ' borobill-admin-skin borobill-main-banner-stats-admin';
        return $classes;
    }

    if ( function_exists( 'get_current_screen' ) ) {
        $screen = get_current_screen();
        $id     = $screen ? $screen->id : '';

        $is_menus       = ( 'nav-menus' === $id );
        $is_posts_list  = ( 'edit-post' === $id );
        $is_post_editor = ( 'post' === $id && isset( $screen->post_type ) && 'post' === $screen->post_type );
        $is_stats       = ( 'toplevel_page_borobill-stats' === $id );
        $is_stats_top10 = ( 'admin_page_borobill-stats-top10' === $id );

        if ( $is_menus || $is_posts_list || $is_post_editor || $is_stats || $is_stats_top10 ) {
            $classes .= ' borobill-admin-skin';
        }
    }

    return $classes;
}
add_filter( 'admin_body_class', 'borobill_admin_skin_body_class' );

function borobill_enqueue_admin_skin_assets( $hook ) {
    if ( ! borobill_should_apply_admin_skin( $hook ) ) {
        return;
    }

    $css_path = get_template_directory() . '/admin/admin-skin.css';
    $css_ver  = borobill_get_theme_file_version( 'admin/admin-skin.css' );
    wp_enqueue_style(
        'borobill-admin-skin',
        get_template_directory_uri() . '/admin/admin-skin.css',
        array(),
        $css_ver
    );

    if ( 'admin_page_borobill-stats-top10' === $hook ) {
        wp_enqueue_script(
            'iconify-icon',
            'https://code.iconify.design/iconify-icon/2.3.0/iconify-icon.min.js',
            array(),
            '2.3.0',
            true
        );
    }

    // 글 편집 화면에서만: 좌/우 50:50 편집+미리보기 패널
    if ( in_array( $hook, array( 'post.php', 'post-new.php' ), true ) && function_exists( 'get_current_screen' ) ) {
        $screen = get_current_screen();
        if ( $screen && isset( $screen->post_type ) && 'post' === $screen->post_type ) {
            $js_path = get_template_directory() . '/admin/post-live-preview.js';
            $js_ver  = borobill_get_theme_file_version( 'admin/post-live-preview.js' );
            wp_enqueue_script(
                'borobill-post-live-preview',
                get_template_directory_uri() . '/admin/post-live-preview.js',
                array( 'jquery' ),
                $js_ver,
                true
            );
        }
    }
}
add_action( 'admin_enqueue_scripts', 'borobill_enqueue_admin_skin_assets', 20 );

if ( ! function_exists( 'borobill_hide_admin_post_row' ) ) {
function borobill_hide_admin_post_row() {
    echo '<style>
        body.post-type-post .wp-list-table .row-content,
        body.post-type-post .wp-list-table .row-content *,
        body.post-type-post .wp-list-table .column-title .row-title p,
        body.post-type-post .wp-list-table .column-title .row-title span,
        body.post-type-post .wp-list-table .row-title > p,
        body.post-type-post .wp-list-table .row-title > span {
            display:none !important;
        }
    </style>';
}
}
add_action( 'admin_head-edit.php', 'borobill_hide_admin_post_row' );

function borobill_remove_post_content_column( $columns ) {
    if ( isset( $columns['content'] ) ) {
        unset( $columns['content'] );
    }
    return $columns;
}
add_filter( 'manage_post_posts_columns', 'borobill_remove_post_content_column', 1 );

/**
 * 관리자 카테고리 목록: 설명 컬럼 제거, 이름/슬러그/개수만 표시
 */
function borobill_manage_edit_category_columns( $columns ) {
    if ( isset( $columns['description'] ) ) {
        unset( $columns['description'] );
    }
    return $columns;
}
add_filter( 'manage_edit-category_columns', 'borobill_manage_edit_category_columns' );

function borobill_render_theme_options_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'top';
    if ( ! in_array( $tab, array( 'top', 'bottom' ), true ) ) {
        $tab = 'top';
    }

    $page_slug = borobill_get_banner_settings_menu_slug();
    $top_url   = add_query_arg(
        array(
            'page' => $page_slug,
            'tab'  => 'top',
        ),
        admin_url( 'admin.php' )
    );
    $bottom_url = add_query_arg(
        array(
            'page' => $page_slug,
            'tab'  => 'bottom',
        ),
        admin_url( 'admin.php' )
    );

    $default_base    = get_template_directory_uri() . '/images/';
    // 기존 공통 색상 값을 각 슬라이드 기본값으로 사용
    $default_bg      = get_option( 'borobill_hero_bg_color', '#4f7fcb' );
    $image_width_px  = (int) get_option( 'borobill_hero_image_width', 380 );
    ?>
    <div class="wrap borobill-banner-settings-wrap">
        <h1>배너설정</h1>
        <nav class="nav-tab-wrapper borobill-banner-settings-tabs" aria-label="배너 종류">
            <a href="<?php echo esc_url( $top_url ); ?>" class="nav-tab<?php echo 'top' === $tab ? ' nav-tab-active' : ''; ?>">상단배너</a>
            <a href="<?php echo esc_url( $bottom_url ); ?>" class="nav-tab<?php echo 'bottom' === $tab ? ' nav-tab-active' : ''; ?>">하단배너</a>
        </nav>

        <?php if ( 'top' === $tab ) : ?>
            <p class="borobill-banner-settings-desc">메인 화면 상단 히어로 슬라이드를 설정합니다. 이미지는 <code>borobill_theme/images</code> 폴더에서 업로드한 것을 포함해 미디어 라이브러리에서 선택할 수 있습니다.</p>
        <?php else : ?>
            <p class="borobill-banner-settings-desc">리스트 페이지 페이지네이션 아래에 노출되는 하단 배너(CTA)를 설정합니다.</p>
        <?php endif; ?>

        <style>
            /* 슬라이드 카드 폼: 여백/정렬 통일 */
            #borobill-hero-slides .borobill-hero-slide-card.is-active {
                --bb-space: 14px;
                --bb-space-sm: 10px;
            }
            #borobill-hero-slides .borobill-hero-slide-card .bb-field {
                margin-bottom: var(--bb-space);
            }
            #borobill-hero-slides .borobill-hero-slide-card .bb-field:last-child {
                margin-bottom: 0;
            }
            #borobill-hero-slides .borobill-hero-slide-card .bb-stack {
                display: flex;
                flex-direction: column;
                gap: var(--bb-space-sm);
            }
            #borobill-hero-slides .borobill-hero-slide-card label {
                display: block;
                margin-bottom: 6px;
            }
            #borobill-hero-slides .borobill-hero-slide-card input.regular-text,
            #borobill-hero-slides .borobill-hero-slide-card textarea {
                width: 100% !important;
                max-width: 100% !important;
                box-sizing: border-box;
            }
            #borobill-hero-slides .borobill-hero-slide-card textarea {
                margin-top: 2px;
            }
            #borobill-hero-slides .borobill-hero-slide-card .bb-actions {
                margin-top: 8px;
                display: flex;
                gap: 8px;
                flex-wrap: wrap;
            }
            #borobill-hero-slides .borobill-hero-slide-card .bb-actions .button {
                margin: 0;
            }

            /* 컬러피커(iris): 열려도 레이아웃이 밀리지 않게 오버레이 처리 */
            #borobill-hero-slides .wp-picker-container,
            #borobill-bottom-slides .wp-picker-container,
            .wrap .wp-picker-container {
                position: relative;
                display: inline-flex;
                align-items: center;
                gap: 8px;
                flex-wrap: wrap;
            }

            #borobill-hero-slides .wp-picker-holder,
            #borobill-bottom-slides .wp-picker-holder,
            .wrap .wp-picker-holder {
                position: absolute !important;
                top: calc(100% + 8px);
                left: 0;
                z-index: 100020;
                flex: none !important;
                width: auto !important;
                min-width: 255px;
                margin: 0 !important;
            }

            #borobill-hero-slides .iris-picker,
            #borobill-bottom-slides .iris-picker,
            .wrap .iris-picker {
                box-shadow: 0 18px 32px rgba(15, 23, 42, 0.18);
                border-radius: 10px;
                overflow: hidden;
            }

            /* 색상 선택 버튼(기본) 클릭 시 outline로 인한 흔들림 방지 */
            #borobill-hero-slides .wp-color-result:focus,
            #borobill-hero-slides .wp-color-result:hover,
            #borobill-bottom-slides .wp-color-result:focus,
            #borobill-bottom-slides .wp-color-result:hover,
            .wrap .wp-color-result:focus,
            .wrap .wp-color-result:hover {
                box-shadow: none;
            }

            /* 하단 색상코드 입력 폭: 상단과 동일 */
            #borobill-bottom-slides .wp-picker-container input.wp-color-picker {
                width: 86px !important;
                max-width: 86px !important;
            }
            #borobill-bottom-slides .wp-picker-container input.wp-color-picker.borobill-color-field--alpha,
            #borobill-bottom-slides .wp-picker-container .borobill-color-field--alpha.wp-color-picker {
                width: 86px !important;
                max-width: 86px !important;
            }
            #borobill-bottom-slides .borobill-color-pair .description {
                width: 0;
                min-width: 100%;
                box-sizing: border-box;
                margin: 6px 0 0;
                font-size: 12px;
            }

            /* 히어로(프론트 스타일) 미리보기 - 관리자 전용 */
            .borobill-hero-admin-preview {
                --bb-hero-bg: #4f7fcb;
                /* 기본은 "그라데이션 없음" */
                --bb-hero-grad: rgba(0, 0, 0, 0);
                --bb-hero-grad-opacity: 0;
                position: relative;
                width: 100%;
                /* 컨테이너 크기에 비례해서 1:1로 축소되도록 */
                container-type: inline-size;
                height: clamp(150px, 36cqw, 190px);
                border-radius: 10px;
                overflow: hidden;
                background: var(--bb-hero-bg);
                color: #fff;
                border: 1px solid #e5e7eb;
            }
            .borobill-hero-admin-preview__bg {
                position: absolute;
                inset: 0;
                background: linear-gradient(180deg, rgba(0,0,0,0) 0%, rgba(0,0,0,0) 45%, var(--bb-hero-grad) 100%);
                opacity: var(--bb-hero-grad-opacity, 0);
                z-index: 1;
                pointer-events: none;
            }
            .borobill-hero-admin-preview__content {
                position: relative;
                z-index: 2;
                padding: clamp(14px, 3.5cqw, 18px) clamp(14px, 3.5cqw, 18px) clamp(12px, 3cqw, 16px);
            }
            .borobill-hero-admin-preview__badge {
                display: inline-block;
                padding: clamp(3px, 0.9cqw, 4px) clamp(8px, 2.2cqw, 10px);
                border-radius: clamp(5px, 1.1cqw, 6px);
                background: rgba(255, 255, 255, 0.18);
                font-size: clamp(11px, 2.4cqw, 12px);
                font-weight: 600;
            }
            .borobill-hero-admin-preview__title {
                margin-top: clamp(8px, 2cqw, 10px);
                font-size: clamp(18px, 4.4cqw, 22px);
                line-height: 1.22;
                font-weight: 700;
                letter-spacing: -0.02em;
                max-width: 68%;
                text-wrap: balance;
            }
            .borobill-hero-admin-preview__btn {
                margin-top: clamp(10px, 2.6cqw, 12px);
                display: inline-flex;
                align-items: center;
                gap: clamp(5px, 1.3cqw, 6px);
                padding: clamp(7px, 2cqw, 9px) clamp(10px, 2.8cqw, 12px);
                border-radius: clamp(8px, 2.4cqw, 10px);
                border: 0;
                background: var(--bb-hero-btn-bg, rgba(15, 23, 42, 0.72));
                color: var(--bb-hero-btn-color, #fff);
                font-weight: 700;
                font-size: clamp(11px, 2.8cqw, 12px);
            }

            .borobill-hero-admin-preview__btn:hover,
            .borobill-hero-admin-preview__btn:focus-visible {
                background: var(--bb-hero-btn-bg-hover, rgba(8, 12, 22, 0.86));
            }

            /* 체크 해제 시 hidden이 항상 우선되도록 */
            .borobill-hero-admin-preview__btn[hidden] {
                display: none !important;
            }
            .borobill-hero-admin-preview__illust {
                position: absolute;
                right: clamp(10px, 2.8cqw, 14px);
                bottom: clamp(10px, 2.6cqw, 12px);
                width: clamp(120px, 32cqw, 160px);
                max-width: 42%;
                max-height: 82%;
                object-fit: contain;
                z-index: 0;
                border-radius: 8px;
                pointer-events: none;
            }

            /* 하단 배너 슬라이드 카드 */
            #borobill-bottom-slides .borobill-bottom-slide-card.is-active {
                --bb-space: 14px;
                --bb-space-sm: 10px;
            }
            #borobill-bottom-slides .borobill-bottom-slide-card .bb-field {
                margin-bottom: var(--bb-space, 14px);
            }
            #borobill-bottom-slides .borobill-bottom-slide-card .bb-field:last-child {
                margin-bottom: 0;
            }
            #borobill-bottom-slides .borobill-bottom-slide-card .bb-stack {
                display: flex;
                flex-direction: column;
                gap: var(--bb-space-sm, 10px);
            }
            #borobill-bottom-slides .borobill-bottom-slide-card label {
                display: block;
                margin-bottom: 6px;
            }
            /* 색상 필드는 full-width 제외 (상단과 동일하게 색상표 유지) */
            #borobill-bottom-slides .borobill-bottom-slide-card input.regular-text:not(.borobill-color-field):not(.wp-color-picker),
            #borobill-bottom-slides .borobill-bottom-slide-card textarea {
                width: 100% !important;
                max-width: 100% !important;
                box-sizing: border-box;
            }
            #borobill-bottom-slides .borobill-bottom-slide-card textarea {
                margin-top: 2px;
            }
            #borobill-bottom-slides .borobill-bottom-slide-card .bb-actions {
                margin-top: 8px;
                display: flex;
                gap: 8px;
                flex-wrap: wrap;
            }

            /* 하단 배너 미리보기 */
            .borobill-bottom-admin-preview {
                --cta-bg: #7ea354;
                position: relative;
                display: flex;
                align-items: center;
                gap: 16px;
                height: 100px;
                padding: 0 20px;
                border-radius: 10px;
                overflow: hidden;
                background-color: var(--cta-bg);
                color: #fff;
                border: 1px solid #e5e7eb;
                margin-bottom: 8px;
            }
            .borobill-bottom-admin-preview__img {
                width: 80px;
                height: 80px;
                object-fit: contain;
                flex: 0 0 auto;
                border-radius: 6px;
            }
            .borobill-bottom-admin-preview__content {
                display: flex;
                flex-direction: column;
                gap: 3px;
                flex: 1 1 auto;
                min-width: 0;
            }
            .borobill-bottom-admin-preview__title {
                font-size: 14px;
                font-weight: 700;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            .borobill-bottom-admin-preview__body {
                font-size: 11px;
                opacity: 0.85;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            .borobill-bottom-admin-preview__btn {
                display: inline-block;
                font-size: 11px;
                font-weight: 600;
                padding: 4px 10px;
                border-radius: 6px;
                background: rgba(0,0,0,0.35);
                color: #fff;
                white-space: nowrap;
                max-width: 180px;
                overflow: hidden;
                text-overflow: ellipsis;
            }
        </style>

        <?php
        borobill_render_banner_settings_admin_notices();

        if ( 'top' === $tab ) :
            borobill_render_hero_slides_manager( $default_base, $default_bg, $image_width_px );

            $auto_delay    = (int) get_option( 'borobill_hero_auto_delay', 6 );
            $anim_duration = (float) get_option( 'borobill_hero_anim_duration', 0.6 );
            $anim_effect   = get_option( 'borobill_hero_anim_effect', 'default' );
            ?>
            <div class="borobill-banner-settings-row borobill-banner-settings-row--top-only">
                <form class="borobill-banner-settings-form borobill-banner-settings-form--transition" method="post" action="options.php">
                    <?php settings_fields( borobill_get_theme_hero_transition_settings_group() ); ?>
                    <div class="borobill-banner-settings-row__col borobill-banner-settings-row__col--transition">
                        <div class="borobill-banner-settings-panel">
                            <h2>슬라이드 전환 옵션</h2>
                            <div class="borobill-hero-transition-fields">
                                <div class="borobill-hero-transition-field">
                                    <label for="borobill_hero_auto_delay"><strong>자동 슬라이드 간격</strong></label>
                                    <div class="borobill-hero-transition-field__control">
                                        <input type="number" min="1" max="30" step="1" id="borobill_hero_auto_delay" name="borobill_hero_auto_delay"
                                               value="<?php echo esc_attr( $auto_delay ); ?>"> 초
                                    </div>
                                </div>
                                <div class="borobill-hero-transition-field">
                                    <label for="borobill_hero_anim_duration"><strong>애니메이션 시간</strong></label>
                                    <div class="borobill-hero-transition-field__control">
                                        <input type="number" min="0.2" max="2" step="0.1" id="borobill_hero_anim_duration" name="borobill_hero_anim_duration"
                                               value="<?php echo esc_attr( $anim_duration ); ?>"> 초
                                    </div>
                                </div>
                                <div class="borobill-hero-transition-field">
                                    <label for="borobill_hero_anim_effect"><strong>슬라이드 효과</strong></label>
                                    <div class="borobill-hero-transition-field__control">
                                        <select id="borobill_hero_anim_effect" name="borobill_hero_anim_effect">
                                            <option value="default" <?php selected( $anim_effect, 'default' ); ?>>기본 슬라이드</option>
                                            <option value="smooth" <?php selected( $anim_effect, 'smooth' ); ?>>부드러운 슬라이드</option>
                                            <option value="bounce" <?php selected( $anim_effect, 'bounce' ); ?>>살짝 튕기는 슬라이드</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <p class="description">슬라이드 전환 속도와 효과를 설정합니다.</p>
                            <p class="borobill-banner-settings-form__actions">
                                <?php submit_button( '전환 옵션 저장', 'primary', 'submit', false ); ?>
                            </p>
                        </div>
                    </div>
                </form>
            </div>
        <?php else :
            if ( function_exists( 'borobill_render_bottom_banner_slides_manager' ) ) {
                borobill_render_bottom_banner_slides_manager();
            }

            $bb_auto_delay    = (int) get_option( 'borobill_bottom_banner_auto_delay', 6 );
            $bb_anim_duration = (float) get_option( 'borobill_bottom_banner_anim_duration', 0.6 );
            $bb_anim_effect   = get_option( 'borobill_bottom_banner_anim_effect', 'default' );
            ?>
            <div class="borobill-banner-settings-row borobill-banner-settings-row--top-only">
                <form class="borobill-banner-settings-form borobill-banner-settings-form--transition" method="post" action="options.php">
                    <?php settings_fields( borobill_get_theme_bottom_banner_transition_settings_group() ); ?>
                    <div class="borobill-banner-settings-row__col borobill-banner-settings-row__col--transition">
                        <div class="borobill-banner-settings-panel">
                            <h2>슬라이드 전환 옵션</h2>
                            <div class="borobill-hero-transition-fields">
                                <div class="borobill-hero-transition-field">
                                    <label for="borobill_bottom_banner_auto_delay"><strong>자동 슬라이드 간격</strong></label>
                                    <div class="borobill-hero-transition-field__control">
                                        <input type="number" min="1" max="30" step="1" id="borobill_bottom_banner_auto_delay" name="borobill_bottom_banner_auto_delay"
                                               value="<?php echo esc_attr( $bb_auto_delay ); ?>"> 초
                                    </div>
                                </div>
                                <div class="borobill-hero-transition-field">
                                    <label for="borobill_bottom_banner_anim_duration"><strong>애니메이션 시간</strong></label>
                                    <div class="borobill-hero-transition-field__control">
                                        <input type="number" min="0.2" max="2" step="0.1" id="borobill_bottom_banner_anim_duration" name="borobill_bottom_banner_anim_duration"
                                               value="<?php echo esc_attr( $bb_anim_duration ); ?>"> 초
                                    </div>
                                </div>
                                <div class="borobill-hero-transition-field">
                                    <label for="borobill_bottom_banner_anim_effect"><strong>슬라이드 효과</strong></label>
                                    <div class="borobill-hero-transition-field__control">
                                        <select id="borobill_bottom_banner_anim_effect" name="borobill_bottom_banner_anim_effect">
                                            <option value="default" <?php selected( $bb_anim_effect, 'default' ); ?>>기본 슬라이드</option>
                                            <option value="smooth" <?php selected( $bb_anim_effect, 'smooth' ); ?>>부드러운 슬라이드</option>
                                            <option value="bounce" <?php selected( $bb_anim_effect, 'bounce' ); ?>>살짝 튕기는 슬라이드</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <p class="description">슬라이드 전환 속도와 효과를 설정합니다.</p>
                            <p class="borobill-banner-settings-form__actions">
                                <?php submit_button( '전환 옵션 저장', 'primary', 'submit', false ); ?>
                            </p>
                        </div>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
    <?php
}


/**
 * 포스트 날짜를 "방금 전 / N분 전 / N시간 전 / YYYY.MM.DD" 형식으로 반환
 *
 * - 5분 미만: "방금 전"
 * - 1시간 미만: "N분 전"
 * - 24시간 미만: "N시간 전"
 * - 그 이상: "YYYY.MM.DD"
 */
function borobill_post_date( $post_id = null ) {
    $post_id = $post_id ? $post_id : get_the_ID();

    if ( ! $post_id ) {
        return '';
    }

    $post_timestamp = get_post_time( 'U', true, $post_id );
    $now_timestamp  = current_time( 'timestamp' );

    $diff = $now_timestamp - $post_timestamp;
    if ( $diff < 0 ) {
        $diff = 0;
    }

    // 5분 미만
    if ( $diff < MINUTE_IN_SECONDS * 5 ) {
        return '방금 전';
    }

    // 1시간 미만
    if ( $diff < HOUR_IN_SECONDS ) {
        $mins = floor( $diff / MINUTE_IN_SECONDS );
        if ( $mins < 1 ) {
            $mins = 1;
        }
        return $mins . '분 전';
    }

    // 24시간 미만
    if ( $diff < DAY_IN_SECONDS ) {
        $hours = floor( $diff / HOUR_IN_SECONDS );
        if ( $hours < 1 ) {
            $hours = 1;
        }
        return $hours . '시간 전';
    }

    // 그 이상은 날짜
    return get_the_date( 'Y.m.d', $post_id );
}


// 빠른편집: 특성이미지 필드 추가
function borobill_quick_edit_featured_image( $column_name, $post_type ) {
    // 우리 custom column 'thumbnail' 에만 출력
    if ( 'post' !== $post_type || 'thumbnail' !== $column_name ) {
        return;
    }
    ?>
    <fieldset class="inline-edit-col-right inline-edit-borobill-thumb">
        <div class="inline-edit-col">
            <label>
                <span class="title">특성이미지</span>
                <span class="input-text-wrap">
                    <img src="" class="borobill-qe-thumb" style="max-width:80px;max-height:80px;display:none;margin-bottom:6px;">
                    <button type="button" class="button borobill-set-thumb">이미지 선택</button>
                    <button type="button" class="button borobill-remove-thumb">제거</button>
                    <input type="hidden" name="borobill_qe_thumb_id" class="borobill-qe-thumb-id" value="">
                    <input type="hidden" name="borobill_qe_thumb_removed" class="borobill-qe-thumb-removed" value="0">
                </span>
            </label>
        </div>
    </fieldset>
    <?php
}
add_action( 'quick_edit_custom_box', 'borobill_quick_edit_featured_image', 10, 2 );


// 빠른편집 저장 시 특성이미지 적용 (제거를 누르지 않았으면 기존 썸네일 유지)
function borobill_save_quick_edit_featured_image( $post_id ) {
    if ( ! isset( $_POST['borobill_qe_thumb_id'] ) ) {
        return;
    }

    $thumb_id   = absint( $_POST['borobill_qe_thumb_id'] );
    $user_removed = isset( $_POST['borobill_qe_thumb_removed'] ) && '1' === $_POST['borobill_qe_thumb_removed'];

    if ( $thumb_id ) {
        set_post_thumbnail( $post_id, $thumb_id );
    } elseif ( $user_removed ) {
        delete_post_thumbnail( $post_id );
    }
    /* thumb_id 가 0이고 사용자가 제거를 누르지 않은 경우: 기존 썸네일 그대로 유지 */
}
add_action( 'save_post', 'borobill_save_quick_edit_featured_image' );


// 빠른편집 특성이미지용 스크립트 로드
function borobill_admin_quick_edit_scripts( $hook ) {
    if ( 'edit.php' !== $hook ) {
        return;
    }

    wp_enqueue_media();
    wp_enqueue_script(
        'borobill-admin-quick-edit',
        get_template_directory_uri() . '/js/admin-quick-edit.js',
        array( 'jquery' ),
        borobill_get_theme_file_version( 'js/admin-quick-edit.js' ),
        true
    );
}
add_action( 'admin_enqueue_scripts', 'borobill_admin_quick_edit_scripts' );


// 글 목록: 썸네일 추가, 작성자·댓글 제거, 타이틀 라벨 "타이틀"
function borobill_add_thumbnail_column( $columns ) {
    $new = array();

    foreach ( $columns as $key => $label ) {
        if ( 'comments' === $key ) {
            continue;
        }
        if ( 'author' === $key ) {
            continue;
        }
        if ( 'categories' === $key ) {
            continue;
        }
        if ( 'title' === $key ) {
            $new['thumbnail'] = '썸네일';
            $new['title']     = '타이틀';
            continue;
        }
        $new[ $key ] = $label;
    }

    return $new;
}
add_filter( 'manage_posts_columns', 'borobill_add_thumbnail_column' );

function borobill_filter_posts_list_columns( $columns ) {
    unset( $columns['borobill_reading_time'], $columns['borobill_views'], $columns['categories'] );
    return $columns;
}
add_filter( 'manage_posts_columns', 'borobill_filter_posts_list_columns', 99 );
add_filter( 'manage_edit-post_columns', 'borobill_filter_posts_list_columns', 99 );

function borobill_remove_excerpt_column( $columns ) {
    if ( isset( $columns['title'] ) && isset( $columns['excerpt'] ) ) {
        unset( $columns['excerpt'] );
    }
    return $columns;
}
add_filter( 'manage_edit-post_columns', 'borobill_remove_excerpt_column', 99 );

// 썸네일 컬럼 내용 출력
function borobill_render_thumbnail_column( $column, $post_id ) {
    if ( 'thumbnail' !== $column ) {
        return;
    }

    $thumb_id = get_post_thumbnail_id( $post_id );

    echo '<span class="borobill-thumb-cell" data-thumb-id="' . esc_attr( $thumb_id ) . '">';

    if ( $thumb_id ) {
        echo get_the_post_thumbnail( $post_id, 'thumbnail' );
    }

    echo '</span>';
}
add_action( 'manage_posts_custom_column', 'borobill_render_thumbnail_column', 10, 2 );


/**
 * 관리자 글 목록에서 썸네일과 제목 사이 여백 확보
 */
function borobill_admin_post_list_spacing_styles( $hook ) {
    if ( 'edit.php' !== $hook ) {
        return;
    }

    ?>
    <style>
        .wp-list-table .column-thumbnail {
            min-width: 140px;
            padding-right: 1.25rem;
            vertical-align: middle;
        }

        .wp-list-table .column-thumbnail .borobill-thumb-cell {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 110px;
        }

        .wp-list-table .column-thumbnail img {
            max-width: 100px;
            max-height: 100px;
            width: auto;
            height: auto;
        }

        .wp-list-table .column-thumbnail + .column-title {
            padding-left: 1.75rem;
        }
        .wp-list-table .column-title {
            min-width: 320px;
            width: 32%;
        }
        .wp-list-table .column-title br {
            display:none;
        }
        #the-list .row-title div {
            display:none;
        }
    </style>
    <?php
}
add_action( 'admin_head', 'borobill_admin_post_list_spacing_styles' );

/**
 * 관리자 > 통계 (댓글 메뉴 아래)
 */
function borobill_register_stats_dashboard_menu() {
	add_menu_page(
		'통계',
		'통계',
		'edit_posts',
		'borobill-stats',
		'borobill_render_stats_dashboard_page',
		'dashicons-chart-bar',
		26
	);

	// 메뉴에는 노출하지 않음 — TOP 10 더보기용 상세
	add_submenu_page(
		null,
		'게시글 조회수 전체 보기',
		'게시글 조회수 전체 보기',
		'edit_posts',
		'borobill-stats-top10',
		'borobill_render_stats_top10_detail_page'
	);
}
add_action( 'admin_menu', 'borobill_register_stats_dashboard_menu' );

function borobill_load_banner_hero_settings() {
	require_once get_template_directory() . '/admin/admin-banner-hero-settings.php';
}
add_action( 'init', 'borobill_load_banner_hero_settings', 1 );

function borobill_load_banner_bottom_settings() {
	require_once get_template_directory() . '/admin/admin-banner-bottom-settings.php';
}
add_action( 'init', 'borobill_load_banner_bottom_settings', 1 );

function borobill_load_main_banner_stats_admin() {
	require_once get_template_directory() . '/admin/admin-main-banner-stats.php';
}
add_action( 'init', 'borobill_load_main_banner_stats_admin', 1 );

function borobill_load_banner_insights() {
	require_once get_template_directory() . '/admin/admin-banner-insights.php';
}
add_action( 'init', 'borobill_load_banner_insights', 2 );

function borobill_load_stats_dashboard() {
	require_once get_template_directory() . '/admin/admin-stats-dashboard.php';
}
add_action( 'init', 'borobill_load_stats_dashboard', 1 );

function borobill_load_reading_messages_admin() {
	require_once get_template_directory() . '/admin/admin-reading-messages.php';
}
add_action( 'init', 'borobill_load_reading_messages_admin', 1 );


// header-menu 링크에 .nav-link 클래스 추가 (스타일 재사용)
function borobill_nav_menu_link_class( $atts, $item, $args ) {
    if ( isset( $args->theme_location ) && 'header-menu' === $args->theme_location ) {
        $existing          = isset( $atts['class'] ) ? $atts['class'] . ' ' : '';
        $atts['class'] = $existing . 'nav-link';
    }

    return $atts;
}
add_filter( 'nav_menu_link_attributes', 'borobill_nav_menu_link_class', 10, 3 );


/**
 * GNB 트리 기준 "뱃지에 표시할 카테고리 이름"을 반환
 *
 * - 1순위: GNB 상위(초보사업자 등)에 속한 **자식 카테고리 이름** (예: 사업자 등록, 세무일정 입력)
 * - 2순위: 해당 트리에 자식이 없을 경우, GNB 상위 카테고리 이름
 * - 3순위: 위에 모두 해당 안 되면, 글에 연결된 첫 번째 카테고리 이름
 *
 * 즉, 가능한 경우 항상 "자식 카테고리"만 뱃지에 노출되도록 한다.
 *
 * @param int|null $post_id
 *
 * @return string
 */
function borobill_get_root_gnb_category_name( $post_id = null ) {
    if ( ! $post_id ) {
        $post_id = get_the_ID();
    }

    if ( ! $post_id ) {
        return '카테고리';
    }

    $cats = get_the_category( $post_id );
    if ( empty( $cats ) || is_wp_error( $cats ) ) {
        return '카테고리';
    }

    $gnb_parents = array(
        '초보사업자',
        '세무·비즈니스',
        '사업자 뉴스룸',
        '바로빌 가이드',
        '고객사례·인사이트',
    );

    $child_candidate = null; // GNB 트리 안의 자식 카테고리 후보

    foreach ( $cats as $cat ) {
        $root = $cat;

        // 루트까지 올라가면서 GNB 상위 카테고리인지 체크
        while ( $root && $root->parent ) {
            $parent = get_category( $root->parent );
            if ( is_wp_error( $parent ) || ! $parent ) {
                break;
            }
            $root = $parent;
        }
        if ( ! $root || ! in_array( $root->name, $gnb_parents, true ) ) {
            continue;
        }

        // 이 시점에서 $cat 은 GNB 상위 트리 안에 속해 있음
        if ( $cat->parent && $cat->term_id !== $root->term_id ) {
            // 자식 카테고리이면 바로 이 이름을 사용
            return $cat->name;
        }

        // 루트만 걸려 있는 경우 → 자식 후보가 아직 없으면 루트를 후보로 저장
        if ( ! $child_candidate ) {
            $child_candidate = $root;
        }
    }

    // 자식 후보(또는 루트 후보)가 있으면 그것을 사용
    if ( $child_candidate ) {
        return $child_candidate->name;
    }

    // 위 5개 트리에 속하지 않으면 첫 번째 카테고리 이름 사용
    return $cats[0]->name;
}

/**
 * 특정 루트 카테고리 아래에서, 글에 체크된 "자식 카테고리" 이름을 뱃지용으로 반환.
 *
 * - 루트의 직계 자식 카테고리가 하나라도 글에 체크되어 있으면 그 이름을 사용
 * - 없으면 기본으로 첫 번째 카테고리 이름을 사용
 *
 * @param int|null  $post_id
 * @param WP_Term|null $root_term
 *
 * @return string
 */
function borobill_get_child_category_name_for_root( $post_id = null, $root_term = null ) {
    if ( ! $post_id ) {
        $post_id = get_the_ID();
    }
    if ( ! $post_id ) {
        return '카테고리';
    }

    $cats = get_the_category( $post_id );
    if ( empty( $cats ) || ! ( $root_term instanceof WP_Term ) ) {
        return $cats ? $cats[0]->name : '카테고리';
    }

    foreach ( $cats as $cat ) {
        if ( (int) $cat->parent === (int) $root_term->term_id ) {
            return $cat->name;
        }
    }

    return $cats ? $cats[0]->name : '카테고리';
}

