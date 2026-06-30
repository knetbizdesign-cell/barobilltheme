<?php
/**
 * 카테고리 필터 탭
 *
 * 실제 필터 동작은 js/main.js 와 .article-card[data-category] 속성을 사용.
 */

$categories = get_categories( [
    'hide_empty' => false,
] );
?>

<section class="section section-filter" style="padding:0; background:none; border:none;">
    <div class="section-inner" style="max-width:1312px;margin:0 auto;padding:0;">
        <div style="display:flex;justify-content:center;align-items:center;width:100%;">
<?php
// 필터 버튼만 flex, wrap 없이 중앙정렬
    echo '<button class="filter-item active" data-category="all">전체</button>';
    $want_order = [
        '초보사업자',
        '세무·비즈니스',
        '사업자 뉴스룸',
        '바로빌 가이드',
        '고객사례·인사이트'
    ];
    foreach ($want_order as $want_cat) {
        foreach ($categories as $cat) {
            if ($cat->name === $want_cat) {
                echo '<button class="filter-item" data-category="'.esc_attr($cat->slug).'">'.esc_html($cat->name).'</button>';
                break;
            }
        }
    }
?>
        </div>
    </div>
</section>






