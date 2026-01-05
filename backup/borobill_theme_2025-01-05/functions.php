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


function borobill_enqueue_assets() {
    wp_enqueue_style('pretendard', 'https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.min.css');
    wp_enqueue_style('borobill-style', get_stylesheet_uri(), [], '1.3');

    wp_enqueue_script('jquery');
    wp_enqueue_script('borobill-main', get_template_directory_uri() . '/js/main.js', ['jquery'], '1.1', true);

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
}
add_action( 'admin_init', 'borobill_register_theme_settings' );

function borobill_add_theme_options_page() {
    add_theme_page(
        '메인 슬라이드 설정',
        '메인 슬라이드 설정',
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
        '1.0',
        true
    );
}
add_action( 'admin_enqueue_scripts', 'borobill_theme_options_assets' );

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
        <h1>메인 슬라이드 설정</h1>
        <p>메인 화면 상단 히어로 슬라이드의 이미지, 문구, 배경 색상을 설정합니다. 이미지는 <code>borobill_theme/images</code> 폴더에서 업로드한 것을 포함해 미디어 라이브러리에서 선택할 수 있습니다.</p>

        <form method="post" action="options.php">
            <?php
            settings_fields( 'borobill_theme_options' );
            ?>

            <div id="borobill-hero-slides" style="display:flex; gap:24px; align-items:flex-start; margin-top:16px;">
                <!-- 슬라이드 1 -->
                <div class="borobill-hero-slide-card" data-slide-id="1" style="flex:1; min-width:0; background:#fff; border:1px solid #ddd; padding:16px; border-radius:8px; display:flex; flex-direction:column; min-height:520px;">
                    <h2 style="margin-top:0;">슬라이드 1</h2>
                    <?php $img1 = get_option( 'borobill_hero_image_1', $default_base . 'main.png' ); ?>
                    <div style="margin-bottom:12px;">
                        <img id="borobill_hero_image_1_preview" src="<?php echo esc_url( $img1 ); ?>" style="max-width:100%;max-height:220px;object-fit:contain;display:block;margin-bottom:8px;border-radius:6px;">
                        <input type="hidden" class="borobill-image-input" id="borobill_hero_image_1" name="borobill_hero_image_1"
                               value="<?php echo esc_attr( $img1 ); ?>">
                        <button type="button"
                                class="button borobill-image-select"
                                data-target-input="#borobill_hero_image_1"
                                data-target-preview="#borobill_hero_image_1_preview">이미지 불러오기</button>
                    </div>
                    <div style="margin-bottom:12px;">
                        <div style="display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap; margin-bottom:6px;">
                            <div>
                                <label for="borobill_hero_bg_color_1"><strong>배경 색상</strong></label><br>
                                <input type="text" class="regular-text borobill-color-field" id="borobill_hero_bg_color_1" name="borobill_hero_bg_color_1"
                                       value="<?php echo esc_attr( get_option( 'borobill_hero_bg_color_1', $default_bg ) ); ?>">
                            </div>
                            <div>
                                <label for="borobill_hero_grad_bottom_1"><strong>그라데이션 하단 색</strong></label><br>
                                <input type="text" class="regular-text borobill-color-field" id="borobill_hero_grad_bottom_1" name="borobill_hero_grad_bottom_1"
                                       value="<?php echo esc_attr( $grad_bottom_1 ); ?>">
                            </div>
                        </div>
                        <div>
                            <label for="borobill_hero_image_width"><strong>이미지 크기</strong></label><br>
                            <input type="number" min="200" max="600" step="10" id="borobill_hero_image_width" name="borobill_hero_image_width"
                                   value="<?php echo esc_attr( $image_width_px ); ?>" style="width:80px;"> px
                        </div>
                    </div>
                    <div style="margin-bottom:8px;">
                        <label for="borobill_hero_badge_1"><strong>배지 텍스트</strong></label><br>
                        <input type="text" class="regular-text" id="borobill_hero_badge_1" name="borobill_hero_badge_1"
                               value="<?php echo esc_attr( get_option( 'borobill_hero_badge_1', '사장님 필독! 전자세금계산서 처음 시작하기' ) ); ?>" style="width:100%;max-width:100%;">
                    </div>
                    <div>
                        <label for="borobill_hero_title_1"><strong>메인 타이틀</strong></label><br>
                        <textarea id="borobill_hero_title_1" name="borobill_hero_title_1" rows="3" style="width:100%;max-width:100%;font-size:14px;"><?php echo esc_textarea( get_option( 'borobill_hero_title_1', "세무·비즈니스 실무 가이드\n2025년 총정리" ) ); ?></textarea>
                    </div>
                </div>

                <!-- 슬라이드 2 -->
                <div class="borobill-hero-slide-card" data-slide-id="2" style="flex:1; min-width:0; background:#fff; border:1px solid #ddd; padding:16px; border-radius:8px; display:flex; flex-direction:column; min-height:520px;">
                    <h2 style="margin-top:0;">슬라이드 2</h2>
                    <?php $img2 = get_option( 'borobill_hero_image_2', $default_base . '17.png' ); ?>
                    <div style="margin-bottom:12px;">
                        <img id="borobill_hero_image_2_preview" src="<?php echo esc_url( $img2 ); ?>" style="max-width:100%;max-height:220px;object-fit:contain;display:block;margin-bottom:8px;border-radius:6px;">
                        <input type="hidden" class="borobill-image-input" id="borobill_hero_image_2" name="borobill_hero_image_2"
                               value="<?php echo esc_attr( $img2 ); ?>">
                        <button type="button"
                                class="button borobill-image-select"
                                data-target-input="#borobill_hero_image_2"
                                data-target-preview="#borobill_hero_image_2_preview">이미지 불러오기</button>
                    </div>
                    <div style="margin-bottom:12px;">
                        <div style="display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap; margin-bottom:6px;">
                            <div>
                                <label for="borobill_hero_bg_color_2"><strong>배경 색상</strong></label><br>
                                <input type="text" class="regular-text borobill-color-field" id="borobill_hero_bg_color_2" name="borobill_hero_bg_color_2"
                                       value="<?php echo esc_attr( get_option( 'borobill_hero_bg_color_2', $default_bg ) ); ?>">
                            </div>
                            <div>
                                <label for="borobill_hero_grad_bottom_2"><strong>그라데이션 하단 색</strong></label><br>
                                <input type="text" class="regular-text borobill-color-field" id="borobill_hero_grad_bottom_2" name="borobill_hero_grad_bottom_2"
                                       value="<?php echo esc_attr( $grad_bottom_2 ); ?>">
                            </div>
                        </div>
                        <div>
                            <label for="borobill_hero_image_width_2"><strong>이미지 크기</strong></label><br>
                            <input type="number" min="200" max="600" step="10" id="borobill_hero_image_width_2" name="borobill_hero_image_width"
                                   value="<?php echo esc_attr( $image_width_px ); ?>" style="width:80px;"> px
                        </div>
                    </div>
                    <div style="margin-bottom:8px;">
                        <label for="borobill_hero_badge_2"><strong>배지 텍스트</strong></label><br>
                        <input type="text" class="regular-text" id="borobill_hero_badge_2" name="borobill_hero_badge_2"
                               value="<?php echo esc_attr( get_option( 'borobill_hero_badge_2', '사장님 필독! 전자세금계산서 처음 시작하기' ) ); ?>" style="width:100%;max-width:100%;">
                    </div>
                    <div>
                        <label for="borobill_hero_title_2"><strong>메인 타이틀</strong></label><br>
                        <textarea id="borobill_hero_title_2" name="borobill_hero_title_2" rows="3" style="width:100%;max-width:100%;font-size:14px;"><?php echo esc_textarea( get_option( 'borobill_hero_title_2', "세무·비즈니스 실무 가이드\n2025년 총정리" ) ); ?></textarea>
                    </div>
                </div>

                <!-- 슬라이드 3 -->
                <div class="borobill-hero-slide-card" data-slide-id="3" style="flex:1; min-width:0; background:#fff; border:1px solid #ddd; padding:16px; border-radius:8px; display:flex; flex-direction:column; min-height:520px;">
                    <h2 style="margin-top:0;">슬라이드 3</h2>
                    <?php $img3 = get_option( 'borobill_hero_image_3', $default_base . '18.png' ); ?>
                    <div style="margin-bottom:12px;">
                        <img id="borobill_hero_image_3_preview" src="<?php echo esc_url( $img3 ); ?>" style="max-width:100%;max-height:220px;object-fit:contain;display:block;margin-bottom:8px;border-radius:6px;">
                        <input type="hidden" class="borobill-image-input" id="borobill_hero_image_3" name="borobill_hero_image_3"
                               value="<?php echo esc_attr( $img3 ); ?>">
                        <button type="button"
                                class="button borobill-image-select"
                                data-target-input="#borobill_hero_image_3"
                                data-target-preview="#borobill_hero_image_3_preview">이미지 불러오기</button>
                    </div>
                    <div style="margin-bottom:12px;">
                        <div style="display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap; margin-bottom:6px;">
                            <div>
                                <label for="borobill_hero_bg_color_3"><strong>배경 색상</strong></label><br>
                                <input type="text" class="regular-text borobill-color-field" id="borobill_hero_bg_color_3" name="borobill_hero_bg_color_3"
                                       value="<?php echo esc_attr( get_option( 'borobill_hero_bg_color_3', $default_bg ) ); ?>">
                            </div>
                            <div>
                                <label for="borobill_hero_grad_bottom_3"><strong>그라데이션 하단 색</strong></label><br>
                                <input type="text" class="regular-text borobill-color-field" id="borobill_hero_grad_bottom_3" name="borobill_hero_grad_bottom_3"
                                       value="<?php echo esc_attr( $grad_bottom_3 ); ?>">
                            </div>
                        </div>
                        <div>
                            <label for="borobill_hero_image_width_3"><strong>이미지 크기</strong></label><br>
                            <input type="number" min="200" max="600" step="10" id="borobill_hero_image_width_3" name="borobill_hero_image_width"
                                   value="<?php echo esc_attr( $image_width_px ); ?>" style="width:80px;"> px
                        </div>
                    </div>
                    <div style="margin-bottom:8px;">
                        <label for="borobill_hero_badge_3"><strong>배지 텍스트</strong></label><br>
                        <input type="text" class="regular-text" id="borobill_hero_badge_3" name="borobill_hero_badge_3"
                               value="<?php echo esc_attr( get_option( 'borobill_hero_badge_3', '사장님 필독! 전자세금계산서 처음 시작하기' ) ); ?>" style="width:100%;max-width:100%;">
                    </div>
                    <div>
                        <label for="borobill_hero_title_3"><strong>메인 타이틀</strong></label><br>
                        <textarea id="borobill_hero_title_3" name="borobill_hero_title_3" rows="3" style="width:100%;max-width:100%;font-size:14px;"><?php echo esc_textarea( get_option( 'borobill_hero_title_3', "세무·비즈니스 실무 가이드\n2025년 총정리" ) ); ?></textarea>
                    </div>
                </div>
            </div>

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

            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}


function borobill_post_date() {
    return get_the_date('Y.m.d');
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
                </span>
            </label>
        </div>
    </fieldset>
    <?php
}
add_action( 'quick_edit_custom_box', 'borobill_quick_edit_featured_image', 10, 2 );


// 빠른편집 저장 시 특성이미지 적용
function borobill_save_quick_edit_featured_image( $post_id ) {
    if ( ! isset( $_POST['borobill_qe_thumb_id'] ) ) {
        return;
    }

    $thumb_id = absint( $_POST['borobill_qe_thumb_id'] );

    if ( $thumb_id ) {
        set_post_thumbnail( $post_id, $thumb_id );
    } else {
        delete_post_thumbnail( $post_id );
    }
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


// 글 목록에 썸네일 컬럼 추가
function borobill_add_thumbnail_column( $columns ) {
    $new = array();

    foreach ( $columns as $key => $label ) {
        if ( 'title' === $key ) {
            $new['thumbnail'] = '썸네일';
        }
        $new[ $key ] = $label;
    }

    return $new;
}
add_filter( 'manage_posts_columns', 'borobill_add_thumbnail_column' );

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


// header-menu 링크에 .nav-link 클래스 추가 (스타일 재사용)
function borobill_nav_menu_link_class( $atts, $item, $args ) {
    if ( isset( $args->theme_location ) && 'header-menu' === $args->theme_location ) {
        $existing          = isset( $atts['class'] ) ? $atts['class'] . ' ' : '';
        $atts['class'] = $existing . 'nav-link';
    }

    return $atts;
}
add_filter( 'nav_menu_link_attributes', 'borobill_nav_menu_link_class', 10, 3 );


