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
        // fallback: 홈에 rootcat 파라미터
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
                    <h3 class="footer-nav-title"><a href="<?php echo borobill_cat_url( '초보사업자' ); ?>">초보사업자</a></h3>
                    <ul class="footer-nav-list">
                        <li><a href="<?php echo borobill_cat_url( '사업자-등록' ); ?>">사업자 등록</a></li>
                        <li><a href="<?php echo borobill_cat_url( '홈택스-시작' ); ?>">홈택스 시작</a></li>
                        <li><a href="<?php echo borobill_cat_url( '첫-정산하기' ); ?>">첫 정산하기</a></li>
                        <li><a href="<?php echo borobill_cat_url( '지원사업-정부정책' ); ?>">지원사업/정부정책</a></li>
                        <li><a href="<?php echo borobill_cat_url( '세무일정-달력' ); ?>">세무일정 달력</a></li>
                    </ul>
                </div>

                <div class="footer-nav-column">
                    <h3 class="footer-nav-title"><a href="<?php echo borobill_cat_url( '세무·비즈니스' ); ?>">세무·비즈니스</a></h3>
                    <ul class="footer-nav-list">
                        <li><a href="<?php echo borobill_cat_url( '세금·세무-가이드' ); ?>">세금·세무 가이드</a></li>
                        <li><a href="<?php echo borobill_cat_url( '회계·재무-관리' ); ?>">회계·재무 관리</a></li>
                        <li><a href="<?php echo borobill_cat_url( '기업운영·비즈니스' ); ?>">기업운영·비즈니스</a></li>
                        <li><a href="<?php echo borobill_cat_url( '간편-세무계산기' ); ?>">간편 세무계산기</a></li>
                        <li><a href="<?php echo borobill_cat_url( '세무사전-faq' ); ?>">세무사전 / FAQ</a></li>
                    </ul>
                </div>

                <div class="footer-nav-column">
                    <h3 class="footer-nav-title"><a href="<?php echo borobill_cat_url( '사업자-뉴스룸' ); ?>">사업자 뉴스룸</a></h3>
                    <ul class="footer-nav-list">
                        <li><a href="<?php echo borobill_cat_url( '세무·회계-뉴스' ); ?>">세무·회계 뉴스</a></li>
                        <li><a href="<?php echo borobill_cat_url( '법령·정책-업데이트' ); ?>">법령·정책 업데이트</a></li>
                        <li><a href="<?php echo borobill_cat_url( '시장·경영-트렌드' ); ?>">시장·경영 트렌드</a></li>
                        <li><a href="<?php echo borobill_cat_url( '통계-리포트' ); ?>">통계 리포트</a></li>
                    </ul>
                </div>

                <div class="footer-nav-column">
                    <h3 class="footer-nav-title"><a href="<?php echo borobill_cat_url( '바로빌-가이드' ); ?>">바로빌 가이드</a></h3>
                    <ul class="footer-nav-list">
                        <li><a href="<?php echo borobill_cat_url( '문서-가이드' ); ?>">문서 가이드</a></li>
                        <li><a href="<?php echo borobill_cat_url( '동영상-가이드' ); ?>">동영상 가이드</a></li>
                        <li><a href="<?php echo borobill_cat_url( '첫-정산하기' ); ?>">첫 정산하기</a></li>
                        <li><a href="<?php echo borobill_cat_url( '지원사업-정부정책' ); ?>">지원사업/정부정책</a></li>
                    </ul>
                </div>

                <div class="footer-nav-column">
                    <h3 class="footer-nav-title"><a href="<?php echo borobill_cat_url( '고객사례·인사이트' ); ?>">고객사례·인사이트</a></h3>
                    <ul class="footer-nav-list">
                        <li><a href="<?php echo borobill_cat_url( '고객사례-인터뷰' ); ?>">고객사례 인터뷰</a></li>
                        <li><a href="<?php echo borobill_cat_url( '활용-인사이트' ); ?>">활용 인사이트</a></li>
                        <li><a href="<?php echo borobill_cat_url( '바로빌-소식' ); ?>">바로빌 소식</a></li>
                        <li><a href="<?php echo borobill_cat_url( '지원사업-정부정책' ); ?>">지원사업/정부정책</a></li>
                        <li><a href="<?php echo borobill_cat_url( '파트너·협력사-스토리' ); ?>">파트너·협력사 스토리</a></li>
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
