<?php
/**
 * 카테고리 필터 탭
 * 실제 필터 동작은 js/main.js 와 .article-card[data-category] 속성을 사용.
 *
 * Target args:
 *  - root_term (WP_Term|null): 현재 보고 있는 루트 카테고리
 *  - filter_items (array|null): ['label' => string, 'slug' => string, 'cat_ids' => array<int>]
 *  - initial_group (string): JS 초기 필터 키 (현재 카테고리 slug 등, 비면 '전체')
 */
$root_term = isset( $args['root_term'] ) && $args['root_term'] instanceof WP_Term ? $args['root_term'] : null;
$initial_group = isset( $args['initial_group'] ) && is_string( $args['initial_group'] ) ? trim( $args['initial_group'] ) : '';
$filter_items = isset( $args['filter_items'] ) && is_array( $args['filter_items'] ) ? $args['filter_items'] : [];

$root_cat_ids = [];
$root_children = [];

if ( $root_term ) {
    $root_cat_ids[] = (int) $root_term->term_id;
    $root_children = get_categories(
        [
            'hide_empty' => false,
            'parent'     => $root_term->term_id,
        ]
    );

    if ( empty( $filter_items ) ) {
        foreach ( $root_children as $child ) {
            $filter_items[] = [
                'label'   => $child->name,
                'slug'    => $child->slug,
                'cat_ids' => [ (int) $child->term_id ],
            ];
        }
    }

    foreach ( $root_children as $child ) {
        $root_cat_ids[] = (int) $child->term_id;
    }

    // 루트(부모) 카테고리 범위: 직계 자식뿐 아니라 모든 하위(손자 포함)까지 포함
    $descendants = get_term_children( (int) $root_term->term_id, 'category' );
    if ( is_array( $descendants ) ) {
        foreach ( $descendants as $did ) {
            $root_cat_ids[] = (int) $did;
        }
    }
}

$root_cat_ids = array_values( array_unique( array_filter( $root_cat_ids ) ) );

$filter_items_json = wp_json_encode( array_values( $filter_items ) );
$root_cat_ids_json = wp_json_encode( array_values( $root_cat_ids ) );
$filter_scope = $root_term ? 'root' : 'global';
?>
<section class="section section-filter section-filter--category" style="padding-top:24px; padding-bottom:8px;">
    <div class="section-inner">
        <div class="category-subfilter-row">
            <div id="category-filter"
                 class="category-subfilter-tabs"
                 data-filter-scope="<?php echo esc_attr( $filter_scope ); ?>"
                 data-filter-items="<?php echo esc_attr( $filter_items_json ); ?>"
                 data-root-cat-ids="<?php echo esc_attr( $root_cat_ids_json ); ?>"
                 data-root-term="<?php echo esc_attr( $root_term ? $root_term->slug : '' ); ?>"
                 data-initial-group="<?php echo esc_attr( $initial_group ); ?>"
            ></div>
        </div>
    </div>
</section>
<!-- 카테고리 필터 버튼 영역 JS로 랜더링 -->