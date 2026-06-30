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
            'header-menu' => 'Header Menu',
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
    if ( is_singular( 'post' ) ) {
        wp_enqueue_script( 'single-sticky-toc', get_template_directory_uri() . '/js/single-sticky-toc.js', [], '1.0', true );
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
        )
    );
}
add_action('wp_enqueue_scripts', 'borobill_enqueue_assets');
add_action( 'admin_enqueue_scripts', 'borobill_enqueue_editor_button_assets' );

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

    wp_enqueue_script(
        'borobill-editor-button',
        get_template_directory_uri() . '/js/editor-button.js',
        array( 'jquery' ),
        filemtime( get_template_directory() . '/js/editor-button.js' ),
        true
    );

    wp_enqueue_style(
        'borobill-editor-button',
        get_template_directory_uri() . '/css/editor-button.css',
        array(),
        filemtime( get_template_directory() . '/css/editor-button.css' )
    );
}

function borobill_add_summary_button( $buttons ) {
    array_push( $buttons, 'borobill_summary' );
    return $buttons;
}

function borobill_add_summary_button_plugin( $plugin_array ) {
    $plugin_array['borobill_summary'] = get_template_directory_uri() . '/js/editor-button.js';
    return $plugin_array;
}

add_filter( 'mce_buttons', 'borobill_add_summary_button' );
add_filter( 'mce_external_plugins', 'borobill_add_summary_button_plugin' );

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
 * 글 편집 화면(사이드): 조회수 입력 메타박스
 * - meta_key: _bb_views
 */
function borobill_add_post_views_metabox() {
    add_meta_box(
        'borobill_post_views',
        '조회수',
        'borobill_render_post_views_metabox',
        'post',
        'side',
        'default'
    );
}
add_action( 'add_meta_boxes', 'borobill_add_post_views_metabox' );

function borobill_render_post_views_metabox( $post ) {
    if ( ! $post instanceof WP_Post ) {
        return;
    }
    wp_nonce_field( 'borobill_save_post_views', 'borobill_post_views_nonce' );
    $views = get_post_meta( (int) $post->ID, '_bb_views', true );
    $views = is_numeric( $views ) ? (int) $views : 0;
    ?>
    <p style="margin:0;">
        <label for="borobill_post_views" class="screen-reader-text">조회수</label>
        <input
            type="number"
            id="borobill_post_views"
            name="borobill_post_views"
            value="<?php echo esc_attr( $views ); ?>"
            min="0"
            step="1"
            style="width:100%;"
        />
    </p>
    <?php
}

function borobill_save_post_views_meta( $post_id ) {
    if ( ! isset( $_POST['borobill_post_views_nonce'] ) ) {
        return;
    }
    if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['borobill_post_views_nonce'] ) ), 'borobill_save_post_views' ) ) {
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

    $views = isset( $_POST['borobill_post_views'] ) ? absint( wp_unslash( $_POST['borobill_post_views'] ) ) : 0;
    update_post_meta( $post_id, '_bb_views', $views );
}
add_action( 'save_post_post', 'borobill_save_post_views_meta' );

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
    <p style="margin:0 0 8px;">
        <label for="borobill_reading_time">리딩타임</label>
    </p>
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
 * 메인 화면 게시글 리스트: 페이지당 7개로 고정
 */
function borobill_set_home_posts_per_page( $query ) {
    if ( is_admin() || ! $query->is_main_query() ) {
        return;
    }

    if ( $query->is_home() || $query->is_front_page() ) {
        $query->set( 'posts_per_page', 7 );
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
function borobill_register_theme_settings() {
    // 슬라이드별 이미지 / 텍스트 / 배경 색상
    for ( $i = 1; $i <= 3; $i++ ) {
        register_setting( 'borobill_theme_options', 'borobill_hero_image_' . $i );
        register_setting( 'borobill_theme_options', 'borobill_hero_badge_' . $i );
        register_setting( 'borobill_theme_options', 'borobill_hero_title_' . $i );
        register_setting( 'borobill_theme_options', 'borobill_hero_bg_color_' . $i );
        register_setting( 'borobill_theme_options', 'borobill_hero_grad_bottom_' . $i );
        // 히어로 버튼(슬라이드별): 텍스트/사용여부
        register_setting( 'borobill_theme_options', 'borobill_hero_button_text_' . $i );
        register_setting(
            'borobill_theme_options',
            'borobill_hero_button_url_' . $i,
            array(
                'type'              => 'string',
                'sanitize_callback' => 'esc_url_raw',
                'default'           => '',
            )
        );
        register_setting(
            'borobill_theme_options',
            'borobill_hero_button_enabled_' . $i,
            array(
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
                'default'           => 1,
            )
        );
    }

    // 슬라이드 전환 옵션
    register_setting( 'borobill_theme_options', 'borobill_hero_auto_delay' );
    register_setting( 'borobill_theme_options', 'borobill_hero_anim_duration' );
    register_setting( 'borobill_theme_options', 'borobill_hero_anim_effect' );

    // 공통 이미지 / 그라데이션 옵션 (이전 버전 호환용)
    register_setting( 'borobill_theme_options', 'borobill_hero_image_width' );
    register_setting( 'borobill_theme_options', 'borobill_hero_grad_bottom' );

    // 슬라이드 순서
    register_setting( 'borobill_theme_options', 'borobill_hero_order' );

    // 기존 공통 배경 색상(호환용, 기본값으로만 사용)
    register_setting( 'borobill_theme_options', 'borobill_hero_bg_color' );

    // ── 하단 배너(CTA) 설정 ──
    register_setting(
        'borobill_theme_options',
        'borobill_bottom_banner_enabled',
        array(
            'type'              => 'integer',
            'sanitize_callback' => 'absint',
            'default'           => 0,
        )
    );
    register_setting(
        'borobill_theme_options',
        'borobill_bottom_banner_new_tab',
        array(
            'type'              => 'integer',
            'sanitize_callback' => 'absint',
            'default'           => 1,
        )
    );
    register_setting(
        'borobill_theme_options',
        'borobill_bottom_banner_image',
        array(
            'type'              => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default'           => '',
        )
    );
    register_setting(
        'borobill_theme_options',
        'borobill_bottom_banner_title',
        array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '바로빌이 궁금하시나요?',
        )
    );
    register_setting(
        'borobill_theme_options',
        'borobill_bottom_banner_body',
        array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_textarea_field',
            'default'           => "전자세금계산서부터 거래명세서,\n매입매출조회까지 바로빌의 다양한 서비스를 이용해보세요.",
        )
    );
    register_setting(
        'borobill_theme_options',
        'borobill_bottom_banner_bg_color',
        array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_hex_color',
            'default'           => '#7ea354',
        )
    );
    register_setting(
        'borobill_theme_options',
        'borobill_bottom_banner_button_text',
        array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '바로빌 바로가기',
        )
    );
    register_setting(
        'borobill_theme_options',
        'borobill_bottom_banner_url',
        array(
            'type'              => 'string',
            'sanitize_callback' => 'borobill_sanitize_http_url',
            'default'           => '',
        )
    );
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
 * 카테고리별 추천 아티클 1/2/3 (노출기준) — 관리자 카테고리 편집 화면에 글 선택 UI 추가
 * GNB 5개 서브메인(루트 카테고리)마다 해당 카테고리(및 하위)에 속한 글만 추천으로 지정 가능
 */
const BOROBILL_RECOMMENDED_POST_META_KEYS = array( 'borobill_recommended_post_1', 'borobill_recommended_post_2', 'borobill_recommended_post_3' );

/**
 * 해당 카테고리 + 하위 카테고리 term_id 배열 반환 (추천 아티클 범위/검증용)
 *
 * @param int $term_id 카테고리 term_id (루트 또는 하위)
 * @return int[]
 */
function borobill_get_category_tree_ids( $term_id ) {
    $term_id = (int) $term_id;
    if ( $term_id <= 0 ) {
        return array();
    }
    $ids = array( $term_id );
    $children = get_terms( array(
        'taxonomy'   => 'category',
        'child_of'   => $term_id,
        'hide_empty' => false,
        'fields'     => 'ids',
    ) );
    if ( ! is_wp_error( $children ) && is_array( $children ) ) {
        $ids = array_merge( $ids, array_map( 'intval', $children ) );
    }
    return $ids;
}

/**
 * 추천 아티클용 글 목록 셀렉트 박스 HTML 생성
 * $term_id 가 주어지면 해당 카테고리(및 하위)에 속한 글만 목록에 표시
 *
 * @param string $name     select name 속성
 * @param int    $selected 선택된 글 ID (0이면 미선택)
 * @param int    $term_id  카테고리 term_id (0이면 전체, >0이면 이 카테고리+하위만)
 * @return void
 */
function borobill_render_recommended_post_select( $name, $selected = 0, $term_id = 0 ) {
    $args = array(
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'numberposts'    => 500,
        'orderby'        => 'date',
        'order'          => 'DESC',
    );
    if ( $term_id > 0 ) {
        $cat_ids = borobill_get_category_tree_ids( $term_id );
        if ( ! empty( $cat_ids ) ) {
            $args['category__in'] = $cat_ids;
        }
    }
    $posts = get_posts( $args );
    echo '<select name="' . esc_attr( $name ) . '" id="' . esc_attr( $name ) . '" class="postform" style="max-width:100%;">';
    echo '<option value="0">— 추천 아티클 선택 —</option>';
    foreach ( $posts as $p ) {
        $title = $p->post_title;
        if ( mb_strlen( $title ) > 80 ) {
            $title = mb_substr( $title, 0, 77 ) . '...';
        }
        echo '<option value="' . (int) $p->ID . '"' . selected( (int) $selected, (int) $p->ID, false ) . '>' . esc_html( $title ) . '</option>';
    }
    echo '</select>';
}

/**
 * 카테고리 편집 폼: 부모 카테고리 아래에 추천 아티클 1/2/3 선택 영역 추가
 */
function borobill_category_edit_form_recommended_articles( $term, $taxonomy ) {
    if ( 'category' !== $taxonomy ) {
        return;
    }
    $id_1 = (int) get_term_meta( $term->term_id, 'borobill_recommended_post_1', true );
    $id_2 = (int) get_term_meta( $term->term_id, 'borobill_recommended_post_2', true );
    $id_3 = (int) get_term_meta( $term->term_id, 'borobill_recommended_post_3', true );
    ?>
    <tr class="form-field term-recommended-wrap">
        <th scope="row"><label for="borobill_recommended_post_1">추천 아티클 (노출기준)</label></th>
        <td>
            <p class="description" style="margin-bottom:10px;">서브메인(이 카테고리) 페이지 상단 "추천 아티클"에는 <strong>이 카테고리와 그 하위 카테고리에 속한 글만</strong> 선택할 수 있습니다. 1·2·3 순서대로 노출됩니다.</p>
            <p style="margin-bottom:6px;"><strong>추천 아티클 1</strong></p>
            <?php borobill_render_recommended_post_select( 'borobill_recommended_post_1', $id_1, $term->term_id ); ?>
            <p style="margin:12px 0 6px;"><strong>추천 아티클 2</strong></p>
            <?php borobill_render_recommended_post_select( 'borobill_recommended_post_2', $id_2, $term->term_id ); ?>
            <p style="margin:12px 0 6px;"><strong>추천 아티클 3</strong></p>
            <?php borobill_render_recommended_post_select( 'borobill_recommended_post_3', $id_3, $term->term_id ); ?>
        </td>
    </tr>
    <?php
}
add_action( 'category_edit_form_fields', 'borobill_category_edit_form_recommended_articles', 10, 2 );

/**
 * 카테고리 추가 폼에도 동일 필드 노출 (선택 사항)
 */
function borobill_category_add_form_recommended_articles( $taxonomy ) {
    if ( 'category' !== $taxonomy ) {
        return;
    }
    ?>
    <div class="form-field term-recommended-wrap">
        <label for="borobill_recommended_post_1">추천 아티클 (노출기준)</label>
        <p class="description" style="margin-bottom:10px;">저장 후 편집 화면에서 이 카테고리(및 하위)에 속한 글만 선택할 수 있습니다.</p>
        <p style="margin-bottom:6px;"><strong>추천 아티클 1</strong></p>
        <?php borobill_render_recommended_post_select( 'borobill_recommended_post_1', 0, 0 ); ?>
        <p style="margin:12px 0 6px;"><strong>추천 아티클 2</strong></p>
        <?php borobill_render_recommended_post_select( 'borobill_recommended_post_2', 0, 0 ); ?>
        <p style="margin:12px 0 6px;"><strong>추천 아티클 3</strong></p>
        <?php borobill_render_recommended_post_select( 'borobill_recommended_post_3', 0, 0 ); ?>
    </div>
    <?php
}
add_action( 'category_add_form_fields', 'borobill_category_add_form_recommended_articles', 10, 1 );

/**
 * 카테고리 편집 시 추천 아티클 1/2/3 저장 (해당 카테고리+하위에 속한 글만 허용)
 */
function borobill_save_category_recommended_articles( $term_id ) {
    if ( ! isset( $_POST['borobill_recommended_post_1'] ) ) {
        return;
    }
    $allowed_cat_ids = borobill_get_category_tree_ids( $term_id );
    foreach ( BOROBILL_RECOMMENDED_POST_META_KEYS as $key ) {
        $field_name = $key;
        $value      = isset( $_POST[ $field_name ] ) ? absint( $_POST[ $field_name ] ) : 0;
        if ( $value > 0 ) {
            $post_cats = wp_get_post_categories( $value );
            $in_tree   = ! empty( array_intersect( $allowed_cat_ids, $post_cats ) );
            if ( $in_tree ) {
                update_term_meta( $term_id, $key, $value );
            } else {
                delete_term_meta( $term_id, $key );
            }
        } else {
            delete_term_meta( $term_id, $key );
        }
    }
}
add_action( 'edited_category', 'borobill_save_category_recommended_articles', 10, 1 );
add_action( 'created_category', 'borobill_save_category_recommended_articles', 10, 1 );

function borobill_add_theme_options_page() {
    add_theme_page(
        '메인/배너 설정',
        '메인/배너 설정',
        'manage_options',
        'borobill-theme-options',
        'borobill_render_theme_options_page'
    );
}
add_action( 'admin_menu', 'borobill_add_theme_options_page' );

/**
 * 메인 슬라이드 설정 페이지용 스크립트 / 스타일
 */
function borobill_theme_options_assets( $hook ) {
    if ( 'appearance_page_borobill-theme-options' !== $hook ) {
        return;
    }

    wp_enqueue_media();
    wp_enqueue_script( 'jquery-ui-sortable' );
    wp_enqueue_style( 'wp-color-picker' );
    wp_enqueue_script(
        'borobill-theme-options',
        get_template_directory_uri() . '/js/theme-options.js',
        array( 'jquery', 'wp-color-picker' ),
        file_exists( get_template_directory() . '/js/theme-options.js' ) ? filemtime( get_template_directory() . '/js/theme-options.js' ) : '1.0',
        true
    );
}
add_action( 'admin_enqueue_scripts', 'borobill_theme_options_assets' );

/**
 * 특정 관리자 화면만 UI 스킨 적용
 * - 모양 > 메뉴(nav-menus.php)
 * - 모양 > 메인/배너 설정(appearance_page_borobill-theme-options)
 * - 글(목록/작성/편집)
 */
function borobill_should_apply_admin_skin( $hook ) {
    // 테마 옵션 페이지(메인/배너 설정)
    if ( 'appearance_page_borobill-theme-options' === $hook ) {
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
    if ( function_exists( 'get_current_screen' ) ) {
        $screen = get_current_screen();
        $hook   = $screen ? $screen->base : '';
        // base 값만으로는 부족하니, enqueue 훅과 동일 로직을 재사용하지 않고 안전하게 screen id 기반으로 체크
        $id = $screen ? $screen->id : '';

        $is_theme_options = ( 'appearance_page_borobill-theme-options' === $id );
        $is_menus         = ( 'nav-menus' === $id );
        $is_posts_list    = ( 'edit-post' === $id );
        $is_post_editor   = ( 'post' === $id && isset( $screen->post_type ) && 'post' === $screen->post_type );

        if ( $is_theme_options || $is_menus || $is_posts_list || $is_post_editor ) {
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
    $css_ver  = file_exists( $css_path ) ? filemtime( $css_path ) : '1.0';
    wp_enqueue_style(
        'borobill-admin-skin',
        get_template_directory_uri() . '/admin/admin-skin.css',
        array(),
        $css_ver
    );

    // 글 편집 화면에서만: 좌/우 50:50 편집+미리보기 패널
    if ( in_array( $hook, array( 'post.php', 'post-new.php' ), true ) && function_exists( 'get_current_screen' ) ) {
        $screen = get_current_screen();
        if ( $screen && isset( $screen->post_type ) && 'post' === $screen->post_type ) {
            $js_path = get_template_directory() . '/admin/post-live-preview.js';
            $js_ver  = file_exists( $js_path ) ? filemtime( $js_path ) : '1.0';
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

/**
 * 관리자 카테고리 목록: 순서 초보사업자 → 고객사례·인사이트 → 나머지
 */
function borobill_sort_admin_category_terms( $terms, $taxonomies, $args ) {
    if ( ! is_array( $terms ) || empty( $terms ) ) {
        return $terms;
    }
    if ( ! in_array( 'category', (array) $taxonomies, true ) ) {
        return $terms;
    }
    if ( ! is_admin() || ! function_exists( 'get_current_screen' ) ) {
        return $terms;
    }
    $screen = get_current_screen();
    if ( ! $screen || 'edit-category' !== $screen->id ) {
        return $terms;
    }

    $order_first = array( '초보사업자', '고객사례·인사이트' );
    $by_name    = array();
    $rest       = array();

    foreach ( $terms as $term ) {
        if ( ! isset( $term->name ) ) {
            $rest[] = $term;
            continue;
        }
        $pos = array_search( $term->name, $order_first, true );
        if ( false !== $pos ) {
            $by_name[ $pos ] = $term;
        } else {
            $rest[] = $term;
        }
    }
    ksort( $by_name );
    $names = array();
    foreach ( $rest as $t ) {
        $names[] = isset( $t->name ) ? $t->name : '';
    }
    array_multisort( $names, SORT_ASC, SORT_NATURAL, $rest );

    return array_merge( array_values( $by_name ), $rest );
}
add_filter( 'get_terms', 'borobill_sort_admin_category_terms', 10, 3 );

/**
 * 관리자 카테고리 목록: 두 열로 표시 (edit-tags.php, taxonomy=category)
 */
function borobill_admin_category_two_columns_css() {
    if ( ! function_exists( 'get_current_screen' ) ) {
        return;
    }
    $screen = get_current_screen();
    if ( ! $screen || 'edit-category' !== $screen->id ) {
        return;
    }
    ?>
    <style id="borobill-category-two-cols">
        #wpbody-content .wrap .wp-list-table tbody { column-count: 2; column-gap: 24px; }
        #wpbody-content .wrap .wp-list-table tbody tr { break-inside: avoid; page-break-inside: avoid; }
    </style>
    <?php
}
add_action( 'admin_head-edit-tags.php', 'borobill_admin_category_two_columns_css' );

function borobill_render_theme_options_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $default_base    = get_template_directory_uri() . '/images/';
    // 기존 공통 색상 값을 각 슬라이드 기본값으로 사용
    $default_bg      = get_option( 'borobill_hero_bg_color', '#4f7fcb' );
    $image_width_px  = (int) get_option( 'borobill_hero_image_width', 380 );

    // 슬라이드별 그라데이션 하단 색 (없으면 예전 공통 옵션이나 기본값 사용)
    $grad_bottom_1   = get_option(
        'borobill_hero_grad_bottom_1',
        get_option( 'borobill_hero_grad_bottom', '#0c2041' )
    );
    $grad_bottom_2   = get_option(
        'borobill_hero_grad_bottom_2',
        get_option( 'borobill_hero_grad_bottom', '#0c2041' )
    );
    $grad_bottom_3   = get_option(
        'borobill_hero_grad_bottom_3',
        get_option( 'borobill_hero_grad_bottom', '#0c2041' )
    );
    ?>
    <div class="wrap">
        <h1>메인/배너 설정</h1>
        <p>메인 화면 상단 히어로 슬라이드와, 리스트 페이지 페이지네이션 아래에 노출되는 하단 배너(CTA)를 설정합니다. 이미지는 <code>borobill_theme/images</code> 폴더에서 업로드한 것을 포함해 미디어 라이브러리에서 선택할 수 있습니다.</p>

        <style>
            /* 슬라이드 카드 드래그 UX */
            #borobill-hero-slides .borobill-hero-slide-card {
                cursor: grab;
                position: relative;
                overflow: visible;
            }
            #borobill-hero-slides .borobill-hero-slide-card:active,
            #borobill-hero-slides .ui-sortable-helper {
                cursor: grabbing;
            }
            #borobill-hero-slides .borobill-hero-slide-card h2 {
                display: block;
                cursor: grab;
                user-select: none;
                padding-right: 32px; /* 우측 상단 아이콘과 겹침 방지 */
            }
            #borobill-hero-slides .borobill-hero-slide-card h2::before {
                font-family: dashicons;
                content: "\f333"; /* move */
                font-size: 18px;
                line-height: 1;
                color: #64748b;
                opacity: 0.85;
                position: absolute;
                top: 12px;
                right: 12px;
                pointer-events: none;
            }
            #borobill-hero-slides .ui-sortable-placeholder {
                visibility: visible !important;
                background: #f8fafc;
                border: 1px dashed #cbd5e1;
                border-radius: 8px;
                min-height: 520px;
            }

            /* 슬라이드 카드 폼: 여백/정렬 통일(트랜디하게) */
            #borobill-hero-slides .borobill-hero-slide-card {
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
            .wrap .wp-picker-container {
                position: relative;
                display: inline-flex;
                align-items: center;
                gap: 8px;
                flex-wrap: wrap;
            }

            #borobill-hero-slides .wp-picker-holder,
            .wrap .wp-picker-holder {
                position: absolute;
                top: calc(100% + 8px);
                left: 0;
                z-index: 9999;
                /* 컬러피커가 카드 밖으로 나가더라도 잘 보이게 */
                margin: 0 !important;
            }

            #borobill-hero-slides .iris-picker,
            .wrap .iris-picker {
                box-shadow: 0 18px 32px rgba(15, 23, 42, 0.18);
                border-radius: 10px;
                overflow: hidden;
            }

            /* 색상 선택 버튼(기본) 클릭 시 outline로 인한 흔들림 방지 */
            #borobill-hero-slides .wp-color-result:focus,
            #borobill-hero-slides .wp-color-result:hover,
            .wrap .wp-color-result:focus,
            .wrap .wp-color-result:hover {
                box-shadow: none;
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
                color: #fff;
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
        </style>

        <form method="post" action="options.php">
            <?php
            settings_fields( 'borobill_theme_options' );
            ?>

            <div id="borobill-hero-slides" style="display:flex; gap:24px; align-items:flex-start; margin-top:16px;">
                <!-- 슬라이드 1 -->
                <div class="borobill-hero-slide-card" data-slide-id="1" style="flex:1; min-width:0; background:#fff; border:1px solid #ddd; padding:16px; border-radius:8px; display:flex; flex-direction:column; min-height:520px;">
                    <h2 style="margin-top:0;">슬라이드 1</h2>
                    <?php $img1 = get_option( 'borobill_hero_image_1', $default_base . 'main.png' ); ?>
                    <div class="bb-field">
                        <?php
                        $bb_prev_badge_1 = get_option( 'borobill_hero_badge_1', '사장님 필독! 전자세금계산서 처음 시작하기' );
                        $bb_prev_title_1 = get_option( 'borobill_hero_title_1', "세무·비즈니스 실무 가이드\n2025년 총정리" );
                        $bb_prev_btn_1   = get_option( 'borobill_hero_button_text_1', '게시글 바로가기' );
                        $bb_prev_btn_en_1 = (int) get_option( 'borobill_hero_button_enabled_1', 1 );
                        $bb_prev_bg_1    = get_option( 'borobill_hero_bg_color_1', $default_bg );
                        ?>
                        <?php
                        $bb_prev_grad_1 = (string) $grad_bottom_1;
                        $bb_prev_grad_on_1 = '' !== trim( $bb_prev_grad_1 ) ? 1 : 0;
                        ?>
                        <div class="borobill-hero-admin-preview" data-bb-hero-preview="1" style="--bb-hero-bg: <?php echo esc_attr( $bb_prev_bg_1 ); ?>; --bb-hero-grad: <?php echo esc_attr( $bb_prev_grad_on_1 ? $bb_prev_grad_1 : 'rgba(0,0,0,0)' ); ?>; --bb-hero-grad-opacity: <?php echo esc_attr( $bb_prev_grad_on_1 ); ?>;">
                            <div class="borobill-hero-admin-preview__bg"></div>
                            <div class="borobill-hero-admin-preview__content">
                                <div class="borobill-hero-admin-preview__badge" data-bb-hero-preview-badge><?php echo esc_html( $bb_prev_badge_1 ); ?></div>
                                <div class="borobill-hero-admin-preview__title" data-bb-hero-preview-title><?php echo nl2br( esc_html( $bb_prev_title_1 ) ); ?></div>
                                <button type="button" class="borobill-hero-admin-preview__btn" data-bb-hero-preview-btn <?php echo 1 === $bb_prev_btn_en_1 ? '' : 'hidden'; ?>>
                                    <span data-bb-hero-preview-btn-text><?php echo esc_html( $bb_prev_btn_1 ); ?></span>
                                </button>
                            </div>
                            <img id="borobill_hero_image_1_preview" class="borobill-hero-admin-preview__illust" src="<?php echo esc_url( $img1 ); ?>" alt="">
                        </div>
                        <input type="hidden" class="borobill-image-input" id="borobill_hero_image_1" name="borobill_hero_image_1"
                               value="<?php echo esc_attr( $img1 ); ?>">
                        <div class="bb-actions">
                            <button type="button"
                                    class="button borobill-image-select"
                                    data-target-input="#borobill_hero_image_1"
                                    data-target-preview="#borobill_hero_image_1_preview">이미지 불러오기</button>
                        </div>
                    </div>
                    <div class="bb-field">
                        <div class="borobill-color-pair" style="display:grid; grid-template-columns: 1fr 1fr; gap:12px; align-items:end; margin-bottom:6px;">
                            <div style="min-width:0;">
                                <label for="borobill_hero_bg_color_1"><strong>배경 색상</strong></label>
                                <input type="text" class="regular-text borobill-color-field" id="borobill_hero_bg_color_1" name="borobill_hero_bg_color_1"
                                       value="<?php echo esc_attr( get_option( 'borobill_hero_bg_color_1', $default_bg ) ); ?>">
                            </div>
                            <div style="min-width:0;">
                                <label for="borobill_hero_grad_bottom_1"><strong>그라데이션 하단 색</strong></label>
                                <input type="text" class="regular-text borobill-color-field" id="borobill_hero_grad_bottom_1" name="borobill_hero_grad_bottom_1"
                                       value="<?php echo esc_attr( $grad_bottom_1 ); ?>">
                            </div>
                        </div>
                        <div>
                            <label for="borobill_hero_image_width"><strong>이미지 크기</strong></label>
                            <input type="number" min="200" max="600" step="10" id="borobill_hero_image_width" name="borobill_hero_image_width"
                                   value="<?php echo esc_attr( $image_width_px ); ?>" style="width:80px;"> px
                        </div>
                    </div>
                    <div class="bb-field">
                        <label for="borobill_hero_badge_1"><strong>배지 텍스트</strong></label>
                        <input type="text" class="regular-text" id="borobill_hero_badge_1" name="borobill_hero_badge_1"
                               value="<?php echo esc_attr( get_option( 'borobill_hero_badge_1', '사장님 필독! 전자세금계산서 처음 시작하기' ) ); ?>">
                    </div>
                    <div class="bb-field">
                        <label for="borobill_hero_title_1"><strong>메인 타이틀</strong></label>
                        <textarea id="borobill_hero_title_1" name="borobill_hero_title_1" rows="3" style="font-size:14px;"><?php echo esc_textarea( get_option( 'borobill_hero_title_1', "세무·비즈니스 실무 가이드\n2025년 총정리" ) ); ?></textarea>
                    </div>
                    <div class="bb-field bb-stack">
                        <div>
                            <label for="borobill_hero_button_text_1"><strong>버튼 텍스트</strong></label>
                        <input type="text" class="regular-text" id="borobill_hero_button_text_1" name="borobill_hero_button_text_1"
                               value="<?php echo esc_attr( get_option( 'borobill_hero_button_text_1', '게시글 바로가기' ) ); ?>">
                        </div>
                        <div>
                            <label for="borobill_hero_button_url_1"><strong>버튼 링크(URL)</strong></label>
                            <input type="url" class="regular-text" id="borobill_hero_button_url_1" name="borobill_hero_button_url_1"
                                   value="<?php echo esc_attr( get_option( 'borobill_hero_button_url_1', '' ) ); ?>"
                                   placeholder="https://example.com">
                        </div>
                        <div>
                            <input type="hidden" name="borobill_hero_button_enabled_1" value="0">
                            <label>
                                <input type="checkbox" name="borobill_hero_button_enabled_1" value="1" <?php checked( (int) get_option( 'borobill_hero_button_enabled_1', 1 ), 1 ); ?>>
                                버튼 사용
                            </label>
                        </div>
                    </div>
                </div>

                <!-- 슬라이드 2 -->
                <div class="borobill-hero-slide-card" data-slide-id="2" style="flex:1; min-width:0; background:#fff; border:1px solid #ddd; padding:16px; border-radius:8px; display:flex; flex-direction:column; min-height:520px;">
                    <h2 style="margin-top:0;">슬라이드 2</h2>
                    <?php $img2 = get_option( 'borobill_hero_image_2', $default_base . '17.png' ); ?>
                    <div class="bb-field">
                        <?php
                        $bb_prev_badge_2 = get_option( 'borobill_hero_badge_2', '사장님 필독! 전자세금계산서 처음 시작하기' );
                        $bb_prev_title_2 = get_option( 'borobill_hero_title_2', "세무·비즈니스 실무 가이드\n2025년 총정리" );
                        $bb_prev_btn_2   = get_option( 'borobill_hero_button_text_2', '게시글 바로가기' );
                        $bb_prev_btn_en_2 = (int) get_option( 'borobill_hero_button_enabled_2', 1 );
                        $bb_prev_bg_2    = get_option( 'borobill_hero_bg_color_2', $default_bg );
                        ?>
                        <?php
                        $bb_prev_grad_2 = (string) $grad_bottom_2;
                        $bb_prev_grad_on_2 = '' !== trim( $bb_prev_grad_2 ) ? 1 : 0;
                        ?>
                        <div class="borobill-hero-admin-preview" data-bb-hero-preview="2" style="--bb-hero-bg: <?php echo esc_attr( $bb_prev_bg_2 ); ?>; --bb-hero-grad: <?php echo esc_attr( $bb_prev_grad_on_2 ? $bb_prev_grad_2 : 'rgba(0,0,0,0)' ); ?>; --bb-hero-grad-opacity: <?php echo esc_attr( $bb_prev_grad_on_2 ); ?>;">
                            <div class="borobill-hero-admin-preview__bg"></div>
                            <div class="borobill-hero-admin-preview__content">
                                <div class="borobill-hero-admin-preview__badge" data-bb-hero-preview-badge><?php echo esc_html( $bb_prev_badge_2 ); ?></div>
                                <div class="borobill-hero-admin-preview__title" data-bb-hero-preview-title><?php echo nl2br( esc_html( $bb_prev_title_2 ) ); ?></div>
                                <button type="button" class="borobill-hero-admin-preview__btn" data-bb-hero-preview-btn <?php echo 1 === $bb_prev_btn_en_2 ? '' : 'hidden'; ?>>
                                    <span data-bb-hero-preview-btn-text><?php echo esc_html( $bb_prev_btn_2 ); ?></span>
                                </button>
                            </div>
                            <img id="borobill_hero_image_2_preview" class="borobill-hero-admin-preview__illust" src="<?php echo esc_url( $img2 ); ?>" alt="">
                        </div>
                        <input type="hidden" class="borobill-image-input" id="borobill_hero_image_2" name="borobill_hero_image_2"
                               value="<?php echo esc_attr( $img2 ); ?>">
                        <div class="bb-actions">
                            <button type="button"
                                    class="button borobill-image-select"
                                    data-target-input="#borobill_hero_image_2"
                                    data-target-preview="#borobill_hero_image_2_preview">이미지 불러오기</button>
                        </div>
                    </div>
                    <div class="bb-field">
                        <div class="borobill-color-pair" style="display:grid; grid-template-columns: 1fr 1fr; gap:12px; align-items:end; margin-bottom:6px;">
                            <div style="min-width:0;">
                                <label for="borobill_hero_bg_color_2"><strong>배경 색상</strong></label>
                                <input type="text" class="regular-text borobill-color-field" id="borobill_hero_bg_color_2" name="borobill_hero_bg_color_2"
                                       value="<?php echo esc_attr( get_option( 'borobill_hero_bg_color_2', $default_bg ) ); ?>">
                            </div>
                            <div style="min-width:0;">
                                <label for="borobill_hero_grad_bottom_2"><strong>그라데이션 하단 색</strong></label>
                                <input type="text" class="regular-text borobill-color-field" id="borobill_hero_grad_bottom_2" name="borobill_hero_grad_bottom_2"
                                       value="<?php echo esc_attr( $grad_bottom_2 ); ?>">
                            </div>
                        </div>
                        <div>
                            <label for="borobill_hero_image_width_2"><strong>이미지 크기</strong></label>
                            <input type="number" min="200" max="600" step="10" id="borobill_hero_image_width_2" name="borobill_hero_image_width"
                                   value="<?php echo esc_attr( $image_width_px ); ?>" style="width:80px;"> px
                        </div>
                    </div>
                    <div class="bb-field">
                        <label for="borobill_hero_badge_2"><strong>배지 텍스트</strong></label>
                        <input type="text" class="regular-text" id="borobill_hero_badge_2" name="borobill_hero_badge_2"
                               value="<?php echo esc_attr( get_option( 'borobill_hero_badge_2', '사장님 필독! 전자세금계산서 처음 시작하기' ) ); ?>">
                    </div>
                    <div class="bb-field">
                        <label for="borobill_hero_title_2"><strong>메인 타이틀</strong></label>
                        <textarea id="borobill_hero_title_2" name="borobill_hero_title_2" rows="3" style="font-size:14px;"><?php echo esc_textarea( get_option( 'borobill_hero_title_2', "세무·비즈니스 실무 가이드\n2025년 총정리" ) ); ?></textarea>
                    </div>
                    <div class="bb-field bb-stack">
                        <div>
                            <label for="borobill_hero_button_text_2"><strong>버튼 텍스트</strong></label>
                            <input type="text" class="regular-text" id="borobill_hero_button_text_2" name="borobill_hero_button_text_2"
                                   value="<?php echo esc_attr( get_option( 'borobill_hero_button_text_2', '게시글 바로가기' ) ); ?>">
                        </div>
                        <div>
                            <label for="borobill_hero_button_url_2"><strong>버튼 링크(URL)</strong></label>
                            <input type="url" class="regular-text" id="borobill_hero_button_url_2" name="borobill_hero_button_url_2"
                                   value="<?php echo esc_attr( get_option( 'borobill_hero_button_url_2', '' ) ); ?>"
                                   placeholder="https://example.com">
                        </div>
                        <div>
                            <input type="hidden" name="borobill_hero_button_enabled_2" value="0">
                            <label>
                                <input type="checkbox" name="borobill_hero_button_enabled_2" value="1" <?php checked( (int) get_option( 'borobill_hero_button_enabled_2', 1 ), 1 ); ?>>
                                버튼 사용
                            </label>
                        </div>
                    </div>
                </div>

                <!-- 슬라이드 3 -->
                <div class="borobill-hero-slide-card" data-slide-id="3" style="flex:1; min-width:0; background:#fff; border:1px solid #ddd; padding:16px; border-radius:8px; display:flex; flex-direction:column; min-height:520px;">
                    <h2 style="margin-top:0;">슬라이드 3</h2>
                    <?php $img3 = get_option( 'borobill_hero_image_3', $default_base . '18.png' ); ?>
                    <div class="bb-field">
                        <?php
                        $bb_prev_badge_3 = get_option( 'borobill_hero_badge_3', '사장님 필독! 전자세금계산서 처음 시작하기' );
                        $bb_prev_title_3 = get_option( 'borobill_hero_title_3', "세무·비즈니스 실무 가이드\n2025년 총정리" );
                        $bb_prev_btn_3   = get_option( 'borobill_hero_button_text_3', '게시글 바로가기' );
                        $bb_prev_btn_en_3 = (int) get_option( 'borobill_hero_button_enabled_3', 1 );
                        $bb_prev_bg_3    = get_option( 'borobill_hero_bg_color_3', $default_bg );
                        ?>
                        <?php
                        $bb_prev_grad_3 = (string) $grad_bottom_3;
                        $bb_prev_grad_on_3 = '' !== trim( $bb_prev_grad_3 ) ? 1 : 0;
                        ?>
                        <div class="borobill-hero-admin-preview" data-bb-hero-preview="3" style="--bb-hero-bg: <?php echo esc_attr( $bb_prev_bg_3 ); ?>; --bb-hero-grad: <?php echo esc_attr( $bb_prev_grad_on_3 ? $bb_prev_grad_3 : 'rgba(0,0,0,0)' ); ?>; --bb-hero-grad-opacity: <?php echo esc_attr( $bb_prev_grad_on_3 ); ?>;">
                            <div class="borobill-hero-admin-preview__bg"></div>
                            <div class="borobill-hero-admin-preview__content">
                                <div class="borobill-hero-admin-preview__badge" data-bb-hero-preview-badge><?php echo esc_html( $bb_prev_badge_3 ); ?></div>
                                <div class="borobill-hero-admin-preview__title" data-bb-hero-preview-title><?php echo nl2br( esc_html( $bb_prev_title_3 ) ); ?></div>
                                <button type="button" class="borobill-hero-admin-preview__btn" data-bb-hero-preview-btn <?php echo 1 === $bb_prev_btn_en_3 ? '' : 'hidden'; ?>>
                                    <span data-bb-hero-preview-btn-text><?php echo esc_html( $bb_prev_btn_3 ); ?></span>
                                </button>
                            </div>
                            <img id="borobill_hero_image_3_preview" class="borobill-hero-admin-preview__illust" src="<?php echo esc_url( $img3 ); ?>" alt="">
                        </div>
                        <input type="hidden" class="borobill-image-input" id="borobill_hero_image_3" name="borobill_hero_image_3"
                               value="<?php echo esc_attr( $img3 ); ?>">
                        <div class="bb-actions">
                            <button type="button"
                                    class="button borobill-image-select"
                                    data-target-input="#borobill_hero_image_3"
                                    data-target-preview="#borobill_hero_image_3_preview">이미지 불러오기</button>
                        </div>
                    </div>
                    <div class="bb-field">
                        <div class="borobill-color-pair" style="display:grid; grid-template-columns: 1fr 1fr; gap:12px; align-items:end; margin-bottom:6px;">
                            <div style="min-width:0;">
                                <label for="borobill_hero_bg_color_3"><strong>배경 색상</strong></label>
                                <input type="text" class="regular-text borobill-color-field" id="borobill_hero_bg_color_3" name="borobill_hero_bg_color_3"
                                       value="<?php echo esc_attr( get_option( 'borobill_hero_bg_color_3', $default_bg ) ); ?>">
                            </div>
                            <div style="min-width:0;">
                                <label for="borobill_hero_grad_bottom_3"><strong>그라데이션 하단 색</strong></label>
                                <input type="text" class="regular-text borobill-color-field" id="borobill_hero_grad_bottom_3" name="borobill_hero_grad_bottom_3"
                                       value="<?php echo esc_attr( $grad_bottom_3 ); ?>">
                            </div>
                        </div>
                        <div>
                            <label for="borobill_hero_image_width_3"><strong>이미지 크기</strong></label>
                            <input type="number" min="200" max="600" step="10" id="borobill_hero_image_width_3" name="borobill_hero_image_width"
                                   value="<?php echo esc_attr( $image_width_px ); ?>" style="width:80px;"> px
                        </div>
                    </div>
                    <div class="bb-field">
                        <label for="borobill_hero_badge_3"><strong>배지 텍스트</strong></label>
                        <input type="text" class="regular-text" id="borobill_hero_badge_3" name="borobill_hero_badge_3"
                               value="<?php echo esc_attr( get_option( 'borobill_hero_badge_3', '사장님 필독! 전자세금계산서 처음 시작하기' ) ); ?>">
                    </div>
                    <div class="bb-field">
                        <label for="borobill_hero_title_3"><strong>메인 타이틀</strong></label>
                        <textarea id="borobill_hero_title_3" name="borobill_hero_title_3" rows="3" style="font-size:14px;"><?php echo esc_textarea( get_option( 'borobill_hero_title_3', "세무·비즈니스 실무 가이드\n2025년 총정리" ) ); ?></textarea>
                    </div>
                    <div class="bb-field bb-stack">
                        <div>
                            <label for="borobill_hero_button_text_3"><strong>버튼 텍스트</strong></label>
                            <input type="text" class="regular-text" id="borobill_hero_button_text_3" name="borobill_hero_button_text_3"
                                   value="<?php echo esc_attr( get_option( 'borobill_hero_button_text_3', '게시글 바로가기' ) ); ?>">
                        </div>
                        <div>
                            <label for="borobill_hero_button_url_3"><strong>버튼 링크(URL)</strong></label>
                            <input type="url" class="regular-text" id="borobill_hero_button_url_3" name="borobill_hero_button_url_3"
                                   value="<?php echo esc_attr( get_option( 'borobill_hero_button_url_3', '' ) ); ?>"
                                   placeholder="https://example.com">
                        </div>
                        <div>
                            <input type="hidden" name="borobill_hero_button_enabled_3" value="0">
                            <label>
                                <input type="checkbox" name="borobill_hero_button_enabled_3" value="1" <?php checked( (int) get_option( 'borobill_hero_button_enabled_3', 1 ), 1 ); ?>>
                                버튼 사용
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <?php
            $bb_enabled   = (int) get_option( 'borobill_bottom_banner_enabled', 0 );
            $bb_image     = (string) get_option( 'borobill_bottom_banner_image', '' );
            $bb_title     = (string) get_option( 'borobill_bottom_banner_title', '바로빌이 궁금하시나요?' );
            $bb_body      = (string) get_option( 'borobill_bottom_banner_body', "전자세금계산서부터 거래명세서,\n매입매출조회까지 바로빌의 다양한 서비스를 이용해보세요." );
            $bb_bg        = (string) get_option( 'borobill_bottom_banner_bg_color', '#7ea354' );
            $bb_btn_text  = (string) get_option( 'borobill_bottom_banner_button_text', '바로빌 바로가기' );
            $bb_url       = (string) get_option( 'borobill_bottom_banner_url', '' );
            $bb_new_tab   = (int) get_option( 'borobill_bottom_banner_new_tab', 1 );
            ?>

            <?php
            // 슬라이드 순서를 저장하는 숨겨진 필드 (예: "1,2,3")
            $hero_order = get_option( 'borobill_hero_order', '1,2,3' );
            ?>
            <input type="hidden" id="borobill_hero_order" name="borobill_hero_order" value="<?php echo esc_attr( $hero_order ); ?>">

            <?php
            $auto_delay      = (int) get_option( 'borobill_hero_auto_delay', 6 );
            $anim_duration   = (float) get_option( 'borobill_hero_anim_duration', 0.6 );
            $anim_effect     = get_option( 'borobill_hero_anim_effect', 'default' );
            ?>

            <div style="margin-top:24px; background:#fff; border:1px solid #ddd; padding:16px; border-radius:8px;">
                <h2 style="margin-top:0;">슬라이드 전환 옵션</h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">전환 설정</th>
                        <td>
                            <div style="display:flex; gap:16px; align-items:center; flex-wrap:wrap;">
                                <div>
                                    <label for="borobill_hero_auto_delay"><strong>자동 슬라이드 간격</strong></label><br>
                                    <input type="number" min="1" max="30" step="1" id="borobill_hero_auto_delay" name="borobill_hero_auto_delay"
                                           value="<?php echo esc_attr( $auto_delay ); ?>" style="width:70px;"> 초
                                </div>
                                <div>
                                    <label for="borobill_hero_anim_duration"><strong>애니메이션 시간</strong></label><br>
                                    <input type="number" min="0.2" max="2" step="0.1" id="borobill_hero_anim_duration" name="borobill_hero_anim_duration"
                                           value="<?php echo esc_attr( $anim_duration ); ?>" style="width:70px;"> 초
                                </div>
                                <div>
                                    <label for="borobill_hero_anim_effect"><strong>슬라이드 효과</strong></label><br>
                                    <select id="borobill_hero_anim_effect" name="borobill_hero_anim_effect">
                                        <option value="default" <?php selected( $anim_effect, 'default' ); ?>>기본 슬라이드</option>
                                        <option value="smooth" <?php selected( $anim_effect, 'smooth' ); ?>>부드러운 슬라이드</option>
                                        <option value="bounce" <?php selected( $anim_effect, 'bounce' ); ?>>살짝 튕기는 슬라이드</option>
                                    </select>
                                </div>
                            </div>
                            <p class="description" style="margin-top:8px;">슬라이드 전환 속도와 효과를 설정합니다.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <div style="margin-top:24px; background:#fff; border:1px solid #ddd; padding:16px; border-radius:8px;">
                <h2 style="margin-top:0;">하단 배너 설정</h2>
                <p class="description">리스트 페이지에서 페이지네이션 바로 아래에 노출되는 CTA 배너입니다.</p>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">배너 노출</th>
                        <td>
                            <label>
                                <input type="checkbox" name="borobill_bottom_banner_enabled" value="1" <?php checked( $bb_enabled, 1 ); ?>>
                                배너 활성화(노출)
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">배너 이미지</th>
                        <td>
                            <div style="display:grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap:26px; align-items:start;">
                                <!-- 1) 이미지/배경색 -->
                                <div style="min-width:0;">
                                    <img id="borobill_bottom_banner_image_preview"
                                         src="<?php echo esc_url( $bb_image ? $bb_image : 'data:image/gif;base64,R0lGODlhAQABAAAAACw=' ); ?>"
                                         style="width:100%;max-width:240px;height:120px;object-fit:cover;display:block;border-radius:8px;border:1px solid #e5e7eb; background:#f8fafc;">

                                    <div style="margin-top:10px; display:flex; gap:8px; flex-wrap:wrap;">
                                        <input type="hidden" class="borobill-image-input" id="borobill_bottom_banner_image" name="borobill_bottom_banner_image"
                                               value="<?php echo esc_attr( $bb_image ); ?>">
                                        <button type="button"
                                                class="button borobill-image-select"
                                                data-target-input="#borobill_bottom_banner_image"
                                                data-target-preview="#borobill_bottom_banner_image_preview">이미지 불러오기</button>
                                        <button type="button"
                                                class="button borobill-image-remove"
                                                data-target-input="#borobill_bottom_banner_image"
                                                data-target-preview="#borobill_bottom_banner_image_preview">제거</button>
                                    </div>
                                    <p class="description" style="margin-top:8px;">권장: 600×240px 이상 (투명 PNG 가능)</p>

                                    <div style="margin-top:18px;">
                                        <label for="borobill_bottom_banner_bg_color"><strong>배경 색상</strong></label><br>
                                        <input type="text" class="regular-text borobill-color-field" id="borobill_bottom_banner_bg_color" name="borobill_bottom_banner_bg_color"
                                               value="<?php echo esc_attr( $bb_bg ); ?>">
                                    </div>
                                </div>

                                <!-- 2) 타이틀/본문 -->
                                <div style="min-width:0;">
                                    <div style="margin-bottom:14px;">
                                        <label for="borobill_bottom_banner_title"><strong>타이틀</strong></label><br>
                                        <input type="text" class="regular-text" id="borobill_bottom_banner_title" name="borobill_bottom_banner_title"
                                               value="<?php echo esc_attr( $bb_title ); ?>" style="width:100%;max-width:100%;">
                                    </div>
                                    <div>
                                        <label for="borobill_bottom_banner_body"><strong>본문</strong></label><br>
                                        <textarea id="borobill_bottom_banner_body" name="borobill_bottom_banner_body" rows="4" style="width:100%;max-width:100%;"><?php echo esc_textarea( $bb_body ); ?></textarea>
                                        <p class="description">줄바꿈은 그대로 반영됩니다.</p>
                                    </div>
                                </div>

                                <!-- 3) CTA -->
                                <div style="min-width:0;">
                                    <div style="margin-bottom:14px;">
                                        <label for="borobill_bottom_banner_button_text"><strong>버튼 텍스트</strong></label><br>
                                        <input type="text" class="regular-text" id="borobill_bottom_banner_button_text" name="borobill_bottom_banner_button_text"
                                               value="<?php echo esc_attr( $bb_btn_text ); ?>" style="width:100%;max-width:100%;">
                                    </div>
                                    <div>
                                        <label for="borobill_bottom_banner_url"><strong>이동 링크(URL)</strong></label><br>
                                        <input type="url" class="regular-text" id="borobill_bottom_banner_url" name="borobill_bottom_banner_url"
                                               value="<?php echo esc_attr( $bb_url ); ?>" placeholder="https://example.com" style="width:100%;max-width:100%;">
                                        <p class="description">http/https만 허용됩니다.</p>
                                        <label style="display:inline-flex;align-items:center;gap:6px; margin-top:4px;">
                                            <input type="checkbox" name="borobill_bottom_banner_new_tab" value="1" <?php checked( $bb_new_tab, 1 ); ?>>
                                            새 창으로 열기
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

            <?php submit_button(); ?>
        </form>
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
        '1.0',
        true
    );
}
add_action( 'admin_enqueue_scripts', 'borobill_admin_quick_edit_scripts' );


// 글 목록: 썸네일 추가, 작성자·댓글 제거, 타이틀 라벨 "타이틀", 리딩타임·조회수 추가
function borobill_add_thumbnail_column( $columns ) {
    $new = array();

    foreach ( $columns as $key => $label ) {
        if ( 'comments' === $key ) {
            continue;
        }
        if ( 'author' === $key ) {
            continue;
        }
        if ( 'title' === $key ) {
            $new['thumbnail']           = '썸네일';
            $new['title']               = '타이틀';
            $new['borobill_reading_time'] = '리딩타임';
            $new['borobill_views']      = '조회수';
            continue;
        }
        $new[ $key ] = $label;
    }

    return $new;
}
add_filter( 'manage_posts_columns', 'borobill_add_thumbnail_column' );

// 글 목록: 리딩타임·조회수 컬럼 내용 (빠른편집용 data 속성 포함)
function borobill_render_reading_time_views_columns( $column, $post_id ) {
    if ( 'borobill_reading_time' === $column ) {
        $n = (int) get_post_meta( $post_id, '_borobill_reading_time', true );
        if ( $n <= 0 ) {
            $n = 3;
        }
        echo '<span class="borobill-col-rt" data-reading-time="' . esc_attr( $n ) . '">' . esc_html( $n . '분' ) . '</span>';
    }
    if ( 'borobill_views' === $column ) {
        $v = (int) get_post_meta( $post_id, '_bb_views', true );
        echo '<span class="borobill-col-views" data-views="' . esc_attr( $v ) . '">' . esc_html( (string) $v ) . '</span>';
    }
}
add_action( 'manage_posts_custom_column', 'borobill_render_reading_time_views_columns', 10, 2 );

// 빠른편집에 리딩타임·조회수 필드 추가
function borobill_quick_edit_reading_time_views( $column_name, $post_type ) {
    if ( 'post' !== $post_type || ( 'borobill_reading_time' !== $column_name && 'borobill_views' !== $column_name ) ) {
        return;
    }
    static $done = false;
    if ( $done ) {
        return;
    }
    $done = true;
    wp_nonce_field( 'borobill_qe_rt_views', 'borobill_qe_rt_views_nonce' );
    ?>
    <fieldset class="inline-edit-col-left">
        <div class="inline-edit-col">
            <label class="inline-edit-group">
                <span class="title">리딩타임</span>
                <input type="number" name="borobill_reading_time" class="borobill-qe-reading-time" value="3" min="0" step="1" style="width:80px;" /> 분
            </label>
            <label class="inline-edit-group">
                <span class="title">조회수</span>
                <input type="number" name="borobill_views" class="borobill-qe-views" value="0" min="0" step="1" style="width:80px;" />
            </label>
        </div>
    </fieldset>
    <?php
}
add_action( 'quick_edit_custom_box', 'borobill_quick_edit_reading_time_views', 10, 2 );

// 빠른편집 저장 시 리딩타임·조회수 메타 저장
function borobill_save_quick_edit_reading_time_views( $post_id ) {
    if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
        return;
    }
    if ( ! isset( $_POST['action'] ) || 'inline-save' !== $_POST['action'] ) {
        return;
    }
    if ( ! isset( $_POST['post_ID'] ) || (int) $_POST['post_ID'] !== (int) $post_id ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }
    if ( ! isset( $_POST['borobill_qe_rt_views_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['borobill_qe_rt_views_nonce'] ) ), 'borobill_qe_rt_views' ) ) {
        return;
    }
    if ( isset( $_POST['borobill_reading_time'] ) ) {
        $n = absint( wp_unslash( $_POST['borobill_reading_time'] ) );
        if ( $n > 0 ) {
            update_post_meta( $post_id, '_borobill_reading_time', $n );
        } else {
            delete_post_meta( $post_id, '_borobill_reading_time' );
        }
    }
    if ( isset( $_POST['borobill_views'] ) ) {
        $v = absint( wp_unslash( $_POST['borobill_views'] ) );
        update_post_meta( $post_id, '_bb_views', $v );
    }
}
add_action( 'save_post_post', 'borobill_save_quick_edit_reading_time_views', 20 );

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

