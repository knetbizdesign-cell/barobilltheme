<?php
/**
 * 부모 카테고리별 서브 카테고리 필터
 *
 * 사용 위치: category.php
 * 필터 버튼: .subfilter-item[data-subcat]
 * 카드     : .article-card[data-subcat]
 */

// category.php 에서 전달된 루트 카테고리
$root_term = isset( $args['borobill_root_term'] ) && $args['borobill_root_term'] instanceof WP_Term
    ? $args['borobill_root_term']
    : null;

if ( ! $root_term ) {
    return;
}

$children = get_categories(
    [
        'hide_empty' => false,
        'parent'     => $root_term->term_id,
    ]
);
?>

<section class="section section-filter section-filter--sub" style="padding-top:24px; padding-bottom:8px;">
    <div class="section-inner">
        <div class="category-subfilter-row">
            <div class="category-subfilter-tabs">
                <?php
                // 허브 페이지 내부에서만 동작하는 필터: URL 이동 없이 JS 로만 처리
                ?>
                <button type="button"
                        class="subfilter-item active"
                        data-subcat="all">전체</button>
                <?php foreach ( $children as $child ) : ?>
                    <button type="button"
                            class="subfilter-item"
                            data-subcat="<?php echo esc_attr( $child->slug ); ?>">
                        <?php echo esc_html( $child->name ); ?>
                    </button>
                <?php endforeach; ?>
            </div>
            <form role="search"
                  method="get"
                  class="category-subfilter-search"
                  action="<?php echo esc_url( home_url( '/' ) ); ?>">
                <label class="screen-reader-text" for="category-search">검색어</label>
                <input type="search"
                       id="category-search"
                       class="search-field"
                       placeholder="검색어를 입력해주세요"
                       value="<?php echo get_search_query(); ?>"
                       name="s" />
                <?php if ( $root_term instanceof WP_Term ) : ?>
                    <input type="hidden" name="cat" value="<?php echo (int) $root_term->term_id; ?>" />
                <?php endif; ?>
                <button type="submit" class="search-icon-btn search-btn-text" aria-label="검색">검색</button>
            </form>
        </div>
    </div>
</section>


