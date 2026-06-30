<?php
/**
 * 메인 게시글 리스트
 */
?>
<section class="section section-list">
    <div class="section-inner">
        <!--
          CLS 방지: JS 렌더링 전에도 리스트 높이를 확보(스켈레톤)
          - 배너(section-bottom-cta)가 빈 리스트 바로 아래에 먼저 보였다가
            글이 렌더링되며 밀리는 "튀어나옴" 현상을 제거한다.
        -->
        <div id="post-list" class="post-list post-list--skeleton" aria-busy="true">
            <?php for ( $i = 0; $i < 6; $i++ ) : ?>
                <article class="article-card article-card--feed article-card--skeleton reveal is-visible" aria-hidden="true">
                    <div class="article-info">
                        <h3 class="article-title">
                            <span class="skeleton-line" aria-hidden="true"></span>
                            <span class="skeleton-line is-short" aria-hidden="true"></span>
                        </h3>
                        <span class="skeleton-block skeleton-date" aria-hidden="true"></span>
                    </div>
                    <div class="article-thumb-wrap" aria-hidden="true">
                        <div class="skeleton-thumb"></div>
                    </div>
                </article>
            <?php endfor; ?>
        </div>
        <div id="post-pagination" class="pagination"></div>
    </div>
</section>
<!-- 메인 게시글 리스트 영역 JS로 랜더링 -->