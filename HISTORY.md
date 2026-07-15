# borobill_theme 작업 히스토리

바로빌 WordPress 테마 개발·수정 이력입니다.  
(커밋 기록 + Cursor 작업 세션 기준, 최신순·날짜별 정리)

---

## 2026-07-14

### 관리자 · 카테고리 (`edit-tags.php`)

- 추천 영역 표시 명칭 **추천 아티클**로 통일 (`functions.php`, 관리자 카테고리 JS/CSS)
- **추천아티클** / **추천게시글** 탭 추가, **카테고리** 제목 바로 아래로 배치 (`functions.php`, `js/admin-category-split.js`, `css/admin-category.css`)
- **추천아티클**: GNB **1차** 메뉴(커스텀 링크 포함) / **추천게시글**: GNB **2차**만 목록·셀렉트에 표시 — 메뉴 저장 시 목록 갱신, 메뉴 DB는 수정하지 않음 (`functions.php`, `js/admin-category-recommended.js`)
- 추천게시글 패널 제목 **추천 게시글** / 업데이트 옆 **초기화**(1·2·3순위 설정만 삭제) (`functions.php`, `js/admin-category-split.js`)
- 오른쪽 패널 **삭제** 버튼 제거(카테고리 삭제 시 GNB 1차 메뉴 연쇄 삭제 방지)

### 관리자 · 배너설정 (상단)

- 컬러피커 안 열림·안 닫힘·서로 열릴 때 기존 표 닫기 / 바깥 클릭 닫기 수정 (`js/theme-options.js`, `admin/admin-skin.css`)
- **버튼 색상**(알파 투명도)·**버튼 글자색** 추가, 미리보기·프론트 반영 (`admin/admin-banner-hero-settings.php`, `js/theme-options.js`, `functions.php`, `style.css`)
- 레이아웃: 1행 배경·그라데이션·이미지 크기 / 2행 버튼색·글자색·배너 순서 (`admin/admin-banner-hero-settings.php`, `admin/admin-skin.css`)
- **이미지 크기** 슬라이드별 옵션 분리·저장, 전환 시 이전 슬라이드 크기 연쇄 변경 방지 (`admin/admin-banner-hero-settings.php`, `functions.php`, `js/theme-options.js`, `js/main.js`, `style.css`, `index.php`)
- 새 슬라이드 저장 시 `admin-ajax.php` 빈 화면 이동 → 배너설정 페이지로 복귀 (`admin/admin-banner-hero-settings.php`)
- 게시 배너 **삭제** 후 페이지 이동 없이 목록에서만 제거 (`js/theme-options.js`, `admin/admin-banner-hero-settings.php`)

### 관리자 · 배너설정 (하단)

- 상단과 동일 **배경·버튼 색(알파)·글자색** + 색상표·코드 입력 폭 상단과 통일 (`admin/admin-banner-bottom-settings.php`, `admin/admin-skin.css`, `js/theme-options.js`, `functions.php`)
- 행 구성: 1행 배경·배너 순서 / 2행 버튼색·글자색 (`admin/admin-banner-bottom-settings.php`)
- 삭제 시 잘못된 페이지 이동·권한 오류 제거, 목록에서만 제거 (`js/theme-options.js`, `admin/admin-banner-bottom-settings.php`)

### 프론트 · 히어로(메인 배너)

- CTA 버튼 배경 더 진하게 / 페이지네이션 **배경만** 연하게·숫자·화살표는 불투명 흰색 (`style.css`)
- ≤768 히어로 페이지네이션 크기 축소 (`style.css`)

### 프론트 · 하단 CTA

- 캐러셀 슬라이드 이동 정상화(트랙만 `transform`) (`js/main.js`, `template-parts/bottom-cta-banner.php`, `style.css`)
- 관리자 배경(`--cta-bg`)·버튼색 프론트 미적용 수정 (`style.css`, `template-parts/bottom-cta-banner.php`)
- 페이지네이션 **화살표만**, 배너 radius 안쪽 / ≤768 숨김 (`template-parts/bottom-cta-banner.php`, `js/main.js`, `style.css`)
- ≤768 배너 높이 고정 / `bottom-cta__content` 불필요 세로 여백 축소 / 슬라이드·배너 높이 불일치(흰 여백) 해소 (`style.css`)
- 홈·카테고리 **리스트↔하단 배너** 간격 `margin-top: 68px`로 통일 (`style.css`)

### 프론트 · 리스트/추천·반응형

- 리스트 **해시태그** 제거(단일글 view 유지) (`js/main.js`)
- 메타 순서: 카테고리+작성일 → 제목 → 서브제목 / 글자 크기↑(썸네일 높이 유지) (`js/main.js`, `style.css`)
- 리스트 서브타이틀 **≤1001**까지 표시 (`style.css`)
- 추천·카테고리 리스트 **1001px부터 PC 스타일** 유지(축소 레이아웃은 ≤1000) (`style.css`, `js/main.js`)
- ≤1000 **뒤로가기** 버튼 숨김 (`style.css`)
- 리스트 페이지네이션 숫자 **최대 5개** + `…` (`js/main.js`)
- ≤520 카드형 썸네일 크기 고정 / ≤480 카드형 1열 + 직사각 비율 (`style.css`)
- ≤1000: 제목↑·작성일↓, 썸네일 상단 정렬·폭 **140px**(홈·카테고리 통일) (`style.css`)

### 프론트 · 단일글 추천

- 우측 추천: 관리자 **추천게시글**(2차 1·2·3순위) 우선 → 없으면 전체 조회수 → 최신 (`single.php`, `functions.php`)

### 관리자 · 통계

- **재방문자**: 재방문 판별 시 **페이지 진입마다 +1**(일 1회 제한 없음) / **순방문자**는 기존 쿠키 기준 유지 (`admin/admin-stats-dashboard.php`)

---

## 2026-07-13

### 관리자 · Main배너 인사이트

- **배너별 연결링크 방문 수** 막대그래프 삭제, **배너별 클릭 수** + 테이블 한 행 배치 (`admin/admin-banner-insights.php`, `admin/admin-skin.css`)
- 카드·막대그래프 높이·타이틀 바로 아래 정렬 조정 (`admin/admin-skin.css`)

### 프론트 · 히어로/헤더/단일글/검색

- 히어로: ≤768 `hero-actions` 숨김 유지 + **전체 폭에서 배너 클릭** 시 연결 링크 이동 (`index.php`, `js/main.js`, `style.css`)
- PC 헤더(1001–1024): 검색 버튼 우측을 히어로 배너 우측 기준선에 맞춤 (`style.css`)
- 단일글: ≤1280 LNB 숨김 시 `.single-main`이 빈 칸 흡수 / ≤1000 aside 숨김 시 main 100% (`style.css`)
- 뒤로가기 버튼 표시 ≤1000 / 모바일 헤더에 검색 토글 항상 표시 (`style.css`)
- 검색 모달 ≤640: 상단 배치·admin bar 여백으로 검색창 잘림 수정 (`style.css`)

### 프론트 · 카테고리/추천·리스트

- 추천 아티클 작성일자 숨김 / 모바일·슬라이더 기준 **1000px** / ≥1001 좌측 큰카드+우측 2카드 (`style.css`, `js/main.js`)
- 추천·전체게시글 화살표·페이지네이션을 메인 배너와 동일 디자인, 제목 왼쪽·날짜 오른쪽 (`style.css`, `category.php` 등)
- ≤380 최신순·추천순 숨김 / ≤1000에서 작성일자를 제목 아래로 (`style.css`)
- ≤520 리스트 썸네일 높이 확대 (`style.css`)

### 성능

- 목록/검색 REST에서 본문·불필요 embed 축소, 카테고리 트리만 조회, 추천 재덮어쓰기 제거 (`js/main.js`, `functions.php`)
- 메인 탭 아래 목록: 첫 6개 선렌더 후 나머지 백그라운드 로드, `rest_url` 사용 (`js/main.js`)

### 관리자 · 통계

- **조회수 0인 글** KPI 카드 제거 → 자리에 **당일 방문자 수** 카드 (`admin/admin-stats-dashboard.php`)
- 기간 설정을 카드 밖(헤더 우측)으로 이동, 기간 변경 시 KPI·그래프·하단 표 동시 갱신
- KPI 1행 6종: 발행글·총/평균 조회수·순/재방문자·평균 체류 + 지난기간 대비·비율 메타 (`functions.php`, 트래킹 JS)
- 상향 `#00c44f` / 하향 `#0062ff`, 좌우 여백 통일 (`admin/admin-skin.css`)
- 당일 선택 시 조회수 그래프 **시간대별**, 그래프 당일 합계 문구 삭제, Y축 포함 타이틀 라인에 맞춰 가로 확장·방문자 그래프 동일·테두리 박스 제거·높이 맞춤
- 3행: 조회수 TOP10(기간 조회수 합) + 더보기 / **시간대별 방문자 수**(날짜·00시만 일자) / **콘텐츠 읽기 분석**(퍼널·구간 이탈·스크롤 집계)
- 3행 높이를 콘텐츠 읽기 분석 기준, TOP10·시간대별 내부 스크롤 / 퍼널 높이·테이블 정렬
- TOP10 **더보기** → 숨김 상세 `borobill-stats-top10` → **게시글 조회수 전체 보기**(전체 공개글·페이지네이션·CSV·평균 읽기·구간 이탈, 뒤로가기+제목 정렬) (`admin/admin-stats-*.php`, `admin/admin-skin.css`)
- 읽기 기록 시 조회수 보정·깨진(읽기만 있고 조회 0) 데이터 맞춤 (`functions.php`, 트래킹)

### 관리자 · 배너설정 (상단/하단 탭, 하단 멀티슬라이드, 미리보기)

- **상단배너 / 하단배너** 탭 분리, 저장 후 탭 유지 (`admin/admin-banner-hero-settings.php`)
- 상단 2행 슬라이드 전환옵션 카드 폭을 1행 히어로 슬라이드와 동일하게 (`admin/admin-skin.css`)
- 하단배너 멀티 슬라이드: 무제한 추가·삭제·게시/정지·순서 + 전환 간격/애니메이션/효과, 기존 CTA 필드 유지·1장 마이그레이션 (`admin/admin-banner-bottom-settings.php`, `js/theme-options.js`, `template-parts/bottom-cta-banner.php`, `js/main.js`, `style.css`, `functions.php`)
- 하단 미리보기를 라이브 CTA와 동일 구성(좌 이미지·텍스트·우하단 버튼+화살표)으로 맞춤, 미리보기 칸을 배너로 쓰고 안쪽 콘텐츠가 칸을 채우도록 수정(좌측 이미지 시작점·우측 여백) (`admin/admin-banner-bottom-settings.php`, `admin/admin-skin.css`, `js/theme-options.js`)

---

## 2026-07-10

### 관리자 · Main배너 메뉴

- 사이드바 최상위 **Main배너** 메뉴 추가, 하위 **배너설정**·**인사이트** (`functions.php`)
- 배너설정을 서브메뉴로 옮기며 깨지던 관리자 스킨 → `admin_body_class`에 `borobill-banner-settings-admin` 고정 (`functions.php`, `admin/admin-skin.css`)
- 인사이트 페이지용 `borobill-main-banner-stats-admin` body class·전체 width 100% (`admin/admin-skin.css`)

### 관리자 · 배너설정

- 히어로 슬라이드 **무제한** 추가·슬라이드별 저장/삭제 (`admin/admin-banner-hero-settings.php`, `js/theme-options.js`, `functions.php`, `index.php`)
- 슬라이드 레지스트리 `borobill_hero_slide_registry` · `borobill_hero_order` 캐러셀 순서
- 관리자 왼쪽 목록 **최신 수정순**, 프론트 노출 순서는 **배너 순서** 필드 기준
- 색상 행(배경·그라데이션·이미지 크기·배너 순서) flex 정렬·색상 선택 UI 여백 (`admin/admin-skin.css`)

### 관리자 · Main배너 > 인사이트

- 상단 **히어로 배너만** 클릭·연결링크 방문 집계 (`admin/admin-banner-insights.php`, `admin/admin-main-banner-stats.php`, `js/banner-insights.js`, `index.php` `data-banner-key`)
- DB: `wp_borobill_banner_insights`(합계), `wp_borobill_banner_insights_daily`(일별)
- **클릭 수**: 배너 버튼 클릭 AJAX / **연결링크 방문 수**: 클릭 후 연결 URL 실제 도착(쿠키·당일 중복 1회 제외)
- 막대그래프 2종(배너별 클릭 수·배너별 연결링크 방문 수) 한 행 + 하단 테이블(배너·배너 순서·클릭 수·연결링크 방문 수)
  - **배너 순서**: 메인 캐러셀 노출 순(게시 슬라이드만 1,2,3… / 정지 시 **미노출**)
- 통계 페이지와 동일 **기간 select**(1일·5일…30일·직접선택, 좌측 배치) — 변경 시 막대그래프 2개·테이블 **동시** 반영(일별 테이블 합산, `borobill_get_stats_visitor_chart_context()` 재사용)
- 테이블 숫자 컬럼 중앙 정렬
- 최근 14일 **라인 차트 2종** 추가 시도 후 **삭제**
- 테이블 **페이지 총 조회수**·**유형**·**연결 링크** 컬럼 추가/검토 후 **제거**
- **하단 CTA 배너** 인사이트 집계·표시 **제외** (`template-parts/bottom-cta-banner.php` `data-banner-key` 제거)

---

## 2026-07-09

### 프론트 · 메인·카테고리 리스트

- **index** 카테고리 필터 줄 우측에 보기 방식 3종(사진만·목록형·카드형) 버튼 + 전환 기능 연동 (`template-parts/category-filter.php`, `index.php`, `js/main.js`, `style.css`)
  - 카테고리 페이지와 동일 Iconify 아이콘·`post-list--view-*` 클래스 적용
  - 메인 리스트에도 카드형·사진만 보기 레이아웃 CSS 확장 (`layout--category` 외 선택자 추가)
- 카드형 보기 배열(카테고리→이미지→타이틀→서브) 수정 시도 후 **직전 작업 취소**(원복)
- 게시글 카드 **전체 클릭** 가능: `<a class="article-card__link"><article>…</article></a>` (`js/main.js` `renderList()`)
- 카드 링크 hover 배경 `#f4f4f4` (`style.css` `.article-card__link`)
- 링크 hover 글자색 `#0f4cff` 적용 시도 — 자식(타이틀·뱃지 등) 고유 색상 때문에 일부만 반영됨
- 카테고리 필터 칩 행 숨김(`hidden`) 시도 후 **직전 작업 취소**(원복)

### 프론트 · 추천게시물

- index·카테고리 추천 영역 뱃지를 **GNB 1차 메뉴명** 기준으로 표시 (`template-parts/recommended.php`, `functions.php` `borobill_get_root_gnb_category_name()` 등)

### 검색 모달

- 검색 모달 카드 **화면 정중앙** 배치 (`style.css` `.search-modal`)
- 모달 열릴 때 헤더·본문 간격이 튀던 현상 수정 (`js/search-modal.js`)
  - 원인: `body { position: fixed; top: -scrollY }` 스크롤 잠금
  - 대안: 휠·터치 스크롤 차단 + `html.search-modal-open { overflow: hidden }` (레이아웃 이동 없음)

### 관리자 · 메뉴(GNB)

- 사이드바 **메뉴**를 알림판과 글 사이 최상위로 분리 (`functions.php` `borobill-nav-menus`)
- **메뉴** 클릭 시 **모양**이 같이 선택·펼쳐지지 않도록 활성 메뉴 고정
- 운영 배포 시 GNB가 로컬과 다른 이유 정리: **로컬·운영 DB(메뉴·카테고리) 별도** — 테마 파일만 올려도 메뉴 내용은 자동 동기화 안 됨
- 운영 글 목록 **빨간 원**: Yoast SEO 등 **플러그인 컬럼**(테마와 무관) — 로컬·운영 플러그인 차이

### 관리자 · 카테고리 (`edit-tags.php`)

- 좌측 **카테고리 추가** 폼 비활성(숨김)
- 우측 목록을 **현재 GNB 메뉴 구조**와 동기화 (`functions.php` `get_terms` 필터·목록 출력)
- 목록 클릭 시 **페이지 이동 없이** 우측 패널에 편집·추천게시물 UI (`js/admin-category-split.js`, `css/admin-category.css`)
- 추천게시물 2차 셀렉트: GNB **하위 메뉴만** 노출 (`js/admin-category-recommended.js` 연동)
- 카테고리 편집 화면 일부 UI 숨김·패널 작업 일부 **직전 작업 취소**(원복)

### 관리자 · 글 편집

- 사이드바 **조회수** 메타박스·`_bb_views` 저장 로직 **삭제** (임의 수정 불가)
- 카테고리 메타박스
  - **모든 카테고리**·**카테고리 추천게시물** 탭 목록을 GNB(header)와 통일
  - 2차 카테고리 **들여쓰기·계층** 표시 수정 (예: 세무 가이드가 1차와 같은 줄로 나오던 문제)
  - **+ 카테고리 추가** 숨김
- **리딩타임** 메타박스: 라벨 `<p>` 주석 처리(입력·저장은 유지)
- **대표 영상** 아래 **배너** 메타박스 추가 (`functions.php`, `js/post-banner-admin.js`)
  - 배너 이미지 1개 + 클릭 URL
  - 단일 글 본문 **맨 아래** 출력 (`single.php`, `style.css` `.post-bottom-banner`)
  - 이미지 없을 때 로컬 SVG + 「이미지 첨부」 (`images/icon-img-box-duotone-line.svg`, CDN Iconify 제거)
- 관리자 JS/CSS **파일 수정 시각** 기준 버전 (`borobill_get_theme_file_version()`)

### 관리자 · 글 목록 (`edit.php`)

- **리딩타임** 컬럼 제거 (`functions.php`)
- 테마 **조회수·카테고리** 컬럼 숨김 (`borobill_filter_posts_list_columns`) — 운영에서 플러그인 컬럼은 별도로 남을 수 있음
- 제목 아래 **편집·빠른편집·휴지통·보기** hover 없이 항상 표시 (`admin/admin-skin.css`)
- 빠른편집에서 리딩타임·조회수 채우기 제거 (`js/admin-quick-edit.js`)

---

## 2026-07-08

### 관리자 · 사이트 상단 헤더(GNB)

- 모양 > **사이트 상단 GNB** 관리 페이지 추가 (`functions.php` · `borobill-header-gnb`)
  - 메뉴 없으면「사이트 상단 GNB」자동 생성 후 `header-menu` 위치 연결
  - 메뉴 추가 / 수정 → `nav-menus.php` 바로가기
- GNB 저장 시 `header-menu` 위치 유지 (`wp_update_nav_menu`)
- `nav-menus.php` GNB 편집 전용 UI (`js/admin-nav-menu-gnb.js`)
  - 카테고리 선택(1차/2차) · 메뉴순서 · 데이터 사용처(게시글 본문/메타 등)
  - 메뉴 추가 모달(카테고리 레벨·사용처·순서)
  - 게시판형 메뉴 저장 시 카테고리 연동(`_borobill_*` 메타)
- 메뉴 항목 추가의 카테고리·페이지(최신순) 목록을 GNB 구조와 동기화
- 라벨 한글화(메뉴명·카테고리 선택·메뉴순서), 관리자 스킨 적용(모양 > 메뉴)

### 관리자 · 통계 대시보드

- 관리자 메뉴「통계」대시보드 구축 (`admin/admin-stats-dashboard.php`, `admin/admin-skin.css`, `functions.php`)
  - KPI: 발행 글 · 총 조회수 · 글당 평균 · 조회수 0인 글
  - 조회수 그래프(TOP 10 막대) · 일자별 조회수(라인, 기간 select)
  - 하단 3열: 조회수 TOP 10 · GNB 카테고리별 · 최근 30일 발행·조회수 순
- 일자별 조회수 자동 집계 테이블 `wp_borobill_daily_views` (`_bb_views`는 수동 입력 기준 지표와 병행)
- 상단 레이아웃: 전체 width 100%, KPI·조회수 그래프와 일자별 조회수(aside) 정렬
  - 일자별 카드 시작 = 조회수 0인 글 KPI 행 / 하단 = 조회수 그래프와 맞춤
- 하단 3열: TOP 10 높이 기준, 같은 행 나머지 카드 높이 동기화 (`admin_footer` 스크립트)
- 일자별 라인 그래프: `chart_dg` 클래스 추가, 빈 여백 완화·그래프 영역 확장
- 「아직 집계된 조회수가 없습니다」문구 주석 처리

### 프론트 · 카테고리 리스트 보기 방식

- 전체게시글 툴바(최신순·추천순 우측)에 보기 전환 3종 추가 (`category.php`, `js/main.js`, `style.css`)
  - 사진만 보기 · 목록형 보기 · 카드형 보기
- Iconify 연동(카테고리 페이지만): `duo-icons:app` / `fa:th-list`(좌우 반전) / `ic:baseline-view-stream`
- 버튼: 한글 라벨 숨김·아이콘만 표시, 미선택 연회색·선택 진회색
- 버튼 UI: 정사각형 `43.33px`, `border: 1px solid #eee`, 첫 버튼만 `border-left`, 양끝 `border-radius: 5px`
- 레이아웃 전환: `post-list--view-photo` / `list` / `card` 클래스 적용

---

## 2026-07-07

### 프론트 · 레이아웃

- 반응형 `min-width` 제거·`max-width`만 통일 작업은 **진행하지 않음** (기존 혼용 구조 유지)
- 로그인 시 관리자바 스크롤 유지 (`style.css` · `#wpadminbar` `position: fixed`, ≤600px)
- 모바일 게시글 목록: 무한 스크롤 해제, 페이지당 6개 + 페이지네이션 표시 (`js/main.js`)
- 모바일 뒤로가기 버튼 — index(홈) 제외 전 페이지, `footer.php` + Xeicon (`functions.php`)
  - 스타일: 46×46px, `border-radius: 16px`, 아이콘 `font-weight: 500`, 다크모드 전용 스타일 주석 처리 (`style.css` · `.single-back-btn`)

### 본문 · Q/A

- 본문 줄바꿈: `.post-content` 등 `word-break: normal`, `overflow-wrap: break-word` (`style.css`)
- `borobill_clean_word_paste_spans` (`functions.php`): Word `span(dir/lang)` 제거·인접 `<b>` 병합만 유지
- 제거·원복: `&nbsp;` 변환, `/` → `·` 치환, WORD JOINER·조사 병합 등 추가 가공
- QA 간격 CSS 원복, Q/A 한 줄 표시 유지

### 관리자 · 글 편집 · TinyMCE

- 네이버형 툴바 전면 교체 시도 후 **작성 영역만 이전 상태로 롤백**, 이후 형광펜 표시 기능만 단계적으로 추가
- **1행:** `wp_adv`·`fullscreen`·`dfw`·툴바 내 `borobill_summary` 제거
- **2행:** 글꼴(`fontselect`) 맨 앞, 글자 크기·밑줄·취소선·글자색·배경색·줄간격·구분선·텍스트로 붙여넣기
- **3행:** 포맷 제거~되돌리기/다시 실행·미디어 삽입, **들여쓰기 ↔ 되돌리기 사이 `표`**
- 2·3행 항상 표시 (`wordpress_adv_hidden` = false), 중복 버튼 제거
- 비주얼/코드 탭 옆 **요약 블록**·**집중 모드** (`js/editor-tools-bar.js`)
- 툴바 원래 자리 집중 모드(`dfw`) CSS 숨김 (`admin/admin-skin.css`)
- 글꼴 목록: 맑은 고딕·Arial·Times New Roman 등 윈도우 기본 서체
- **표 삽입:** `js/editor-table.js` (`borobill_table`) — 8×8 그리드 선택, 직접 입력 최대 20행×12열 (`css/editor-button.css`)

### 버그 수정

- `mce_external_plugins` 필터 인자 수(`10, 2`) 누락 → 글 편집 PHP Fatal error
- `borobill_insert` 플러그인 이중 로드 → `borobill_editor` 단일 플러그인 + `editor-table.js` 분리
- `editor-button.js` 플러그인 등록 괄호 오류


---

## 2026-07-06

### 단일 글 (프론트)

- 모바일/태블릿(≤1024px) 단일 글 타이틀 상단에 메인과 동일한 카테고리 뱃지 추가 (`single.php`, `borobill_get_root_gnb_category_name()`)
- 데스크톱 단일 글에서는 뱃지 숨김 (`style.css`)

### 관리자 · 카테고리 (글 > 카테고리)

- 추천 아티클 선택 UI를 **추천게시물 1~3순위** 3단 셀렉트로 변경
  - 1차: GNB 상위 카테고리 / 2차: 2차 카테고리 / 3차: 해당 카테고리 게시글
  - 연동 스크립트: `js/admin-category-recommended.js`
- UI 문구 **「하위 카테고리」→「2차 카테고리」** 변경, 1차 선택 전에도 `— 2차 카테고리 —` 동일 표기
- 섹션 제목 `추천게시물` (`<h2>`), 순위 라벨 `1순위`~`3순위`
- 설명 입력란과 추천게시물 제목 간격 `margin-top: 20px` — 인라인 제거, CSS 파일로 분리 (`css/admin-category.css`)
- 관리자 카테고리 전용 스타일: 셀렉트 한 줄 배치 등

### 관리자 · 글 편집

- 카테고리 메타박스 「가장 많이 사용됨」 탭 → **「카테고리 추천게시물」** (`taxonomy_labels_category` 필터, `post.php`·`post-new.php`)

---

## 2026-06-30

- 작업 히스토리 문서(`HISTORY.md`) 작성

---

## 2026-06-29

- 로컬 WordPress 환경(`localhost:8080`) 연결 상태 확인

---

## 2026-04-29

- 모바일 헤더 상·하 패딩 조정 (세로 공간 축소)
- 스크롤 시 헤더 영역이 늘어나는 현상 수정
- 768~1024px 구간 추천·전체게시글 여백 조정 (큰 이미지 높이 기준)
- 1200~1020px 구간 추천아티클·전체게시글 간격 조정
- 추천 세로 카드: 이미지 높이 확대 + 텍스트 그룹 세로 중앙 정렬

---

## 2026-04-27

- 카테고리 페이지(초보사업자·세무·비즈니스·뉴스룸·가이드·고객사례) 반응형 3단계 재구성
  - 1200~1024px / 1023~768px / 767px 이하
  - LNB 없는 레이아웃, [볼타 인사이트](https://bolta.io/insight) 참고 피드형 리스트
- 1200~1024px 추천아티클 우측 2카드 간격 10px로 축소
- 배지 1줄 고정(`nowrap`), 가로 나열 카드 스타일 통일

---

## 2026-04-27

> Git 커밋 전 로컬 작업. `style.css`, `functions.php`, 템플릿·JS 대량 수정 포함.

### 반응형 · 레이아웃 (메인)

- 태블릿/모바일 추천게시물: 뱃지·타이틀 순서 변경, 모바일 조회수·날짜 숨김
- 히어로: 모바일 CTA 하단 여백, 태블릿·모바일 인디케이터 제거 + 스와이프
- 1024px 이하 조회수 숨김, 카테고리 필터 왼쪽 정렬·`margin-bottom: 30px`
- 헤더 GNB 상단 고정(`position: fixed`), 모바일 헤더 높이 70px(1180px 이하)
- 카테고리·필터·리스트 좌측 기준선 통일, 1020px 이하 좌우 가터 유지
- 카테고리 섹션 1312px 중앙 정렬, 인라인 검색창 제거(헤더 검색 모달만 사용)
- 리스트 구분선 위·아래 28px, 1020px 이하 추천·리스트 gap 30px·타이틀→날짜 순
- 서브타이틀: PC 표시 / 1120px 미만 숨김, 클릭 시 글 상세 이동
- PC 추천·리스트 뱃지·날짜·조회수 복원
- 모바일 히어로 슬라이드 번호(우하단 `1 | 3` 배지)
- 고정 헤더와 본문 시작선 정렬(`--page-top-gutter`, `--header-fixed-stack-extra`)
- 모바일 GNB 상단 빈 틈(스크롤 본문 비침) 수정 — `top: 0` + padding-top 통합

### 검색 · 다크모드 · UI

- 검색 모달(`search-modal.js`, `template-parts/search-modal.php`) 추가
- 토스 스타일 다크모드 (`data-theme`, localStorage, 헤더 토글)
- 다크모드: 페이지네이션·로고(logo3)·카테고리·검색 뱃지·푸터 색상
- 모바일 자주 찾는 검색어 3줄(2×3), 검색 취소 버튼 유지
- 「검색하기」 버튼 `cursor: pointer`
- 토스 참고 반응형 버튼·썸네일 비율·카테고리 칩 스타일

### 단일 글 · 카테고리 · 관리자

- `single.php` 토스형 본문 타이포·여백, 하단 최신글 메인 피드와 동일 카드
- sticky TOC(`single-sticky-toc.js`, `toc-sticky-v2.js`), 사이드바 sticky
- 구독(뉴스레터) 블록 제거 → `subscribe-block-backup.md` 백업
- 카테고리 서브·자녀 페이지 정렬 버튼 아래 여백(52px)
- 스크롤 등장 애니메이션(`scroll-reveal.js`)
- 하단 CTA 배너(`template-parts/bottom-cta-banner.php`)
- 관리자: 퀵에디트 썸네일, 에디터 버튼, 라이브 미리보기, 테마 옵션(히어로 슬라이드)
- REST 필드(`borobill_subtitle`, 조회수 등), SVG·로고·소셜 아이콘 에셋 추가

---

## 2025-12-26

- 테마 전체 초기 백업 커밋 (`Initial backup of borobill_theme`)

---

## 2025-11-26

### 기본 구조 · Figma PC 레이아웃

- 테마 초기 커밋 및 프로젝트 생성
- Figma 디자인 기준 레이아웃 정렬 (1920px, 1084px 콘텐츠 폭)
- 헤더·히어로(1078×379)·추천 3카드·카테고리 필터·게시물 리스트·배너·푸터
- Pretendard 폰트, Figma 타이포·간격·색상 적용
- 히어로 오버레이 그라디언트, 일러스트(`1.png`) 레이어링
- 추천 카드 298×220 썸네일, placeholder·fallback 이미지
- 카드 hover 애니메이션 제거, 검색 아이콘 UI

### WordPress 연동

- 대표 이미지: 히어로·메인 리스트 연동 + fallback
- 퀵에디트 대표 이미지 업로드(`admin-quick-edit.js`)
- 헤더 메뉴(`header-menu`) 등록, 2단 드롭다운
- `page.php` 추가 (메뉴 페이지 레이아웃)

### 문서

- `FIGMA_IMPLEMENTATION_GUIDE.md` — Figma → WP 구현 가이드
- `GITHUB_SETUP.md` — GitHub 저장소 연결 안내

---

## 참고

| 항목 | 내용 |
|------|------|
| 로컬 URL | `http://localhost:8080/wordpress/` |
| 테마 경로 | `wp-content/themes/borobill_theme` |
| 원격 저장소 | `github.com/knetbizdesign-cell/untitled-project` |
| 미커밋 상태 | 2026년 대부분 작업은 아직 Git에 반영되지 않음 |

*이 파일은 작업 요약용입니다. 상세 스펙은 `FIGMA_IMPLEMENTATION_GUIDE.md`, 복구용 코드는 `subscribe-block-backup.md`를 참고하세요.*
