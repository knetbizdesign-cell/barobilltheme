# borobill_theme 작업 히스토리

바로빌 WordPress 테마 개발·수정 이력입니다.  
(커밋 기록 + Cursor 작업 세션 기준, 최신순·날짜별 정리)

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
