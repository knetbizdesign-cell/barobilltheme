<?php
/**
 * 푸터 카테고리 링크 헬퍼
 * 슬러그로 카테고리 아카이브 URL을 반환. 못 찾으면 홈 URL.
 */
if ( ! function_exists( 'borobill_cat_url' ) ) {
    function borobill_cat_url( $slug ) {
        $term = get_category_by_slug( $slug );
        if ( $term && ! is_wp_error( $term ) ) {
            return esc_url( get_category_link( $term->term_id ) );
        }
        return esc_url( add_query_arg( 'rootcat', $slug, home_url( '/' ) ) );
    }
}
?>
<footer class="site-footer">
    <div class="footer-container">
        <div class="footer-content">

            <div class="footer-brand">
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="footer-logo">
                    <img src="<?php echo esc_url( get_template_directory_uri() . '/images/logo2.png' ); ?>" class="footer-logo-img" alt="barobill | Blog">
                </a>
            </div>

            <nav class="footer-nav" aria-label="푸터 카테고리">
                <div class="footer-nav-column">
                    <h3 class="footer-nav-title"><a href="<?php echo borobill_cat_url( '세무-비즈니스' ); ?>">세무 비즈니스</a></h3>
                    <ul class="footer-nav-list">
                        <li><a href="<?php echo borobill_cat_url( '세무-가이드' ); ?>">세무 가이드</a></li>
                        <li><a href="<?php echo borobill_cat_url( '세무-사전' ); ?>">세무 사전</a></li>
                        <li><a href="<?php echo borobill_cat_url( '세무-일정' ); ?>">세무 일정</a></li>
                    </ul>
                </div>
                <div class="footer-nav-column">
                    <h3 class="footer-nav-title"><a href="<?php echo borobill_cat_url( '바로빌-가이드' ); ?>">바로빌 가이드</a></h3>
                    <ul class="footer-nav-list">
                        <li><a href="<?php echo borobill_cat_url( '세무-서비스' ); ?>">세무 서비스</a></li>
                        <li><a href="<?php echo borobill_cat_url( '금융-서비스' ); ?>">금융 서비스</a></li>
                        <li><a href="<?php echo borobill_cat_url( '메시징-서비스' ); ?>">메시징 서비스</a></li>
                        <li><a href="<?php echo borobill_cat_url( '이용-안내' ); ?>">이용 안내</a></li>
                    </ul>
                </div>
                <div class="footer-nav-column">
                    <h3 class="footer-nav-title"><a href="<?php echo borobill_cat_url( '개발자-가이드' ); ?>">개발자 가이드</a></h3>
                    <ul class="footer-nav-list">
                        <li><a href="<?php echo borobill_cat_url( 'api-서비스' ); ?>">API 서비스</a></li>
                        <li><a href="<?php echo borobill_cat_url( 'erp-연동' ); ?>">ERP 연동</a></li>
                        <li><a href="<?php echo borobill_cat_url( '플랫폼-연동' ); ?>">플랫폼 연동</a></li>
                        <li><a href="<?php echo borobill_cat_url( '그룹웨어-연동' ); ?>">그룹웨어 연동</a></li>
                    </ul>
                </div>
                <div class="footer-nav-column">
                    <h3 class="footer-nav-title"><a href="<?php echo borobill_cat_url( '바로빌소식' ); ?>">바로빌 소식</a></h3>
                    <ul class="footer-nav-list">
                        <li><a href="<?php echo borobill_cat_url( '바로빌-이용후기' ); ?>">바로빌 이용후기</a></li>
                        <li><a href="<?php echo borobill_cat_url( 'api-연동후기' ); ?>">API 연동후기</a></li>
                    </ul>
                </div>
            </nav>

            <div class="footer-social">
                <a href="https://blog.naver.com/knetbiz" class="social-link social-link--naver" aria-label="네이버" target="_blank" rel="noopener noreferrer">
                    <img src="<?php echo esc_url( get_template_directory_uri() . '/images/naver.svg' ); ?>" alt="Naver">
                </a>
                <a href="https://www.youtube.com/channel/UC-Lu30aV7ZmRVgjTzqwBBIg" class="social-link social-link--youtube" aria-label="유튜브" target="_blank" rel="noopener noreferrer">
                    <img src="<?php echo esc_url( get_template_directory_uri() . '/images/youtube.svg' ); ?>" alt="YouTube">
                </a>
            </div>

        </div>

        <div class="footer-bottom">
            <p class="footer-company">(주)케이넷 | 대표: 이천호</p>
            <p class="footer-company">사업자등록번호 416-81-38772</p>
            <p class="footer-company">주소: 광주 북구 첨단과기로208번길 43-22, B동 1901~3호(와이어스파크) ㈜ 케이넷</p>
            <p class="footer-company">고객센터: 일반문의 <b>1544-8385</b> | 제휴·연동 문의 <b>1544-9256</b></p>

            <div class="footer-bottom-row">
                <p class="footer-copy">Copyrightⓒ KNET corp. All Rights Reserved.</p>
            </div>
        </div>
    </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
