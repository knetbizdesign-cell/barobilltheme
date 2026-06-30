<!-- Search Modal (bolta-style, reusable component) -->
<div id="search-modal" class="search-modal" role="dialog" aria-modal="true" aria-label="검색" hidden>
    <div class="search-modal__backdrop"></div>
    <div class="search-modal__container">
        <div class="search-modal__header">
            <div class="search-modal__input-wrap">
                <span class="search-modal__icon" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                        <path d="M10.5 18.5a8 8 0 1 1 0-16 8 8 0 0 1 0 16Z" stroke="#94a3b8" stroke-width="2"/>
                        <path d="M17.5 17.5 21 21" stroke="#94a3b8" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </span>
                <div class="search-modal__tag-badges" id="search-modal-tag-badges" aria-live="polite"></div>
                <input
                    id="search-modal-input"
                    class="search-modal__input"
                    type="search"
                    placeholder="검색어를 입력해주세요"
                    autocomplete="off"
                    aria-label="검색어 입력"
                />
                <button class="search-modal__clear" type="button" aria-label="검색어 지우기" hidden>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                        <circle cx="12" cy="12" r="10" fill="#cbd5e1"/>
                        <path d="M8 8l8 8M16 8l-8 8" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </button>
            </div>
            <button class="search-modal__cancel" type="button">취소</button>
        </div>

        <div class="search-modal__popular">
            <h3 class="search-modal__popular-title">자주 찾는 검색어</h3>
            <div class="search-modal__popular-tags" id="search-modal-popular-tags">
                <button type="button" class="search-modal__tag" data-keyword="세금계산서">세금계산서</button>
                <button type="button" class="search-modal__tag" data-keyword="역발행">역발행</button>
                <button type="button" class="search-modal__tag" data-keyword="정발행">정발행</button>
                <button type="button" class="search-modal__tag" data-keyword="예약발행">예약발행</button>
                <button type="button" class="search-modal__tag" data-keyword="마감일">마감일</button>
                <button type="button" class="search-modal__tag" data-keyword="초보사업자">초보사업자</button>
                <button type="button" class="search-modal__tag" data-keyword="비즈니스">비즈니스</button>
                <button type="button" class="search-modal__tag" data-keyword="총정리">총정리</button>
                <button type="button" class="search-modal__tag" data-keyword="공동인증서">공동인증서</button>
                <button type="button" class="search-modal__tag" data-keyword="자동화">자동화</button>
                <button type="button" class="search-modal__tag" data-keyword="TOP5">TOP5</button>
                <button type="button" class="search-modal__tag" data-keyword="바로빌">바로빌</button>
                <button type="button" class="search-modal__tag" data-keyword="트랜드">트랜드</button>
                <button type="button" class="search-modal__tag" data-keyword="세무일정">세무일정</button>
                <button type="button" class="search-modal__tag" data-keyword="지원정책">지원정책</button>
                <button type="button" class="search-modal__tag" data-keyword="홈택스">홈택스</button>
                <button type="button" class="search-modal__tag" data-keyword="정산">정산</button>
                <button type="button" class="search-modal__tag" data-keyword="사업자등록">사업자등록</button>
            </div>
        </div>

        <div class="search-modal__body">
            <!-- 초기 상태: 안내 문구 -->
            <div class="search-modal__empty search-modal__state" data-state="initial">
                <p>검색어를 입력하면 게시물을 찾아드립니다.</p>
            </div>

            <!-- 로딩 -->
            <div class="search-modal__loading search-modal__state" data-state="loading" hidden>
                <div class="search-modal__spinner" aria-label="검색 중"></div>
                <p>검색 중...</p>
            </div>

            <!-- 결과 없음 -->
            <div class="search-modal__no-results search-modal__state" data-state="no-results" hidden>
                <p>검색 결과가 없습니다.</p>
                <p class="search-modal__hint">다른 키워드로 검색해보세요.</p>
            </div>

            <!-- 결과 리스트 -->
            <ul class="search-modal__results search-modal__state" data-state="results" role="listbox" hidden></ul>
        </div>
    </div>
</div>
