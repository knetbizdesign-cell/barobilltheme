# 구독(뉴스레터) 영역 백업

상세게시글(single) 페이지에서 제거해 둔 구독 블록입니다.  
**나중에 다시 쓰고 싶을 때** 이 파일만 지정해서 말하면 됩니다.

---

## 나중에 복구할 때 할 말 (새 채팅 / Cursor가 기억 못해도 됨)

- **가장 확실:** 채팅 입력창에 **@** 누르고 `subscribe-block-backup` 검색해서 이 파일 선택한 뒤  
  **「이 파일 내용으로 구독영역 복구해줘」** 또는 **「구독영역 복구해줘」** 라고 보내기.
- **한 줄로:** **「@subscribe-block-backup.md 보고 구독영역 복구해줘」**

이 파일이 테마 폴더(`borobill_theme`)에 있으므로 **새 채팅**에서도 @ 로 이 파일만 지정하면 Cursor가 내용을 읽고 single.php / style.css 에 다시 넣어줄 수 있습니다.

---

## 1. single.php 에 넣을 HTML

### 1-1. 사이드바용 (추천게시물 섹션 바로 다음, single-toc-box 바로 전)

```html
            <!-- 구독(뉴스레터) 폼 -->
            <div class="single-aside-subscribe">
                <p class="subscribe-title">일 줄이는 세무·비즈니스·회계·실무 팁<br>매달 받아보세요</p>
                <form class="subscribe-form" method="post" action="#">
                    <label class="subscribe-label" for="subscribe-email">이메일</label>
                    <input class="subscribe-input" id="subscribe-email" type="email" name="email" placeholder="Enter your email" required />
                    <label class="subscribe-consent">
                        <input type="checkbox" name="consent" required />
                        <span>바로빌이 데이터를 수집하고 처리하는 것에 동의합니다.</span>
                    </label>
                    <button class="subscribe-cta" type="submit">구독하기</button>
                </form>
            </div>
```

### 1-2. 모바일/태블릿용 (</div> 로 레이아웃 닫는 직후, 스크립트 전)

```html
    <!-- 모바일/태블릿용 구독 블록 (사이드바가 숨겨지는 구간에서 노출) -->
    <div class="single-subscribe-mobile">
        <div class="single-aside-subscribe">
            <p class="subscribe-title">일 줄이는 세무·비즈니스·회계·실무 팁<br>매달 받아보세요</p>
            <form class="subscribe-form" method="post" action="#">
                <label class="subscribe-label" for="subscribe-email-m">이메일</label>
                <input class="subscribe-input" id="subscribe-email-m" type="email" name="email" placeholder="Enter your email" required />
                <label class="subscribe-consent">
                    <input type="checkbox" name="consent" required />
                    <span>바로빌이 데이터를 수집하고 처리하는 것에 동의합니다.</span>
                </label>
                <button class="subscribe-cta" type="submit">구독하기</button>
            </form>
        </div>
    </div>
```

---

## 2. style.css 에 넣을 CSS

아래 블록들을 style.css **적절한 위치**에 순서대로 넣으면 됩니다.  
(기존에 있던 자리: 모바일 구독 블록 → single-toc 인근 → 구독 폼 스타일 → 미디어쿼리 내 .single-aside 구독 오버라이드)

### 2-1. 모바일/태블릿용 구독 블록 (single-toc 관련 스타일 위쪽 근처)

```css
/* 모바일/태블릿용 구독 블록: PC에서는 숨김 */
.single-subscribe-mobile {
    display: none;
}

@media (max-width: 1200px) {
  /* (같은 미디어쿼리 안에 .single-aside.sticky-group 등 이미 있으면, 아래만 추가) */
  /* 모바일/태블릿: 사이드바 숨기고, 본문 아래 구독 블록 표시 */
  .single-subscribe-mobile {
    display: block;
    max-width: 560px;
    margin: 36px auto 0;
    padding: 0 var(--gutter-mobile, 20px);
    box-sizing: border-box;
  }
  .single-subscribe-mobile .single-aside-subscribe {
    width: 100%;
    max-width: 100%;
  }
}
```

### 2-2. 사이드바 공통 너비 (single-aside-related, single-toc-box 와 함께 있는 selector에 .single-aside-subscribe 포함)

```css
.single-aside-related, .single-aside-search, .single-aside-subscribe, .single-toc-box {
    width: 345px;
    min-width: 0;
    max-width: 100%;
    box-sizing: border-box;
    padding-left: 0;
    padding-right: 0;
    padding-top: 10px;
    margin: 0 auto;
}
```

### 2-3. 구독(뉴스레터) 블록 — 사이드바용

```css
/* ===========================
   구독(뉴스레터) 블록 — 사이드바용
   =========================== */
.single-aside-subscribe {
    background: #f5f8fb;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 22px 20px 20px;
    box-sizing: border-box;
}

.subscribe-title {
    margin: 0 0 16px;
    font-size: 16px;
    font-weight: 700;
    line-height: 1.45;
    color: #111827;
    letter-spacing: -0.01em;
}

.subscribe-form {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.subscribe-label {
    font-size: 13px;
    font-weight: 500;
    color: #6b7280;
    margin: 0;
}

.subscribe-input {
    width: 100%;
    height: 42px;
    padding: 0 14px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 14px;
    font-family: inherit;
    color: #222;
    background: #fff;
    box-sizing: border-box;
    outline: none;
    transition: border-color 0.18s ease;
}
.subscribe-input:focus {
    border-color: #f59e0b;
}
.subscribe-input::placeholder {
    color: #bfc0c7;
    font-size: 13px;
}

.subscribe-consent {
    display: flex;
    align-items: flex-start;
    gap: 7px;
    font-size: 12px;
    line-height: 1.45;
    color: #6b7280;
    cursor: pointer;
    margin: 2px 0 4px;
}
.subscribe-consent input[type="checkbox"] {
    margin-top: 2px;
    flex-shrink: 0;
    accent-color: #4e5fff;
}
.subscribe-required {
    color: #ef4444;
    font-style: normal;
    text-decoration: none;
}

.subscribe-cta {
    width: 100%;
    height: 42px;
    border: none;
    border-radius: 8px;
    background: #4e5fff;
    color: #fff;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.22s ease;
    letter-spacing: -0.01em;
}
.subscribe-cta:hover {
    background: #3f50e1;
}
```

### 2-4. 미디어쿼리 안 .single-aside 구독 오버라이드 (같은 미디어쿼리 내 .single-aside 관련 스타일 옆에)

```css
    .single-aside .single-aside-related,
    .single-aside .single-aside-subscribe,
    .single-aside .single-toc-box {
        width: 100%;
        max-width: 100%;
        margin: 0;
        padding-top: 2px;
    }

    .single-aside .single-aside-subscribe {
        padding: 18px 16px 16px;
    }
    .single-aside .subscribe-title {
        font-size: 14px;
        margin-bottom: 12px;
    }
    .single-aside .subscribe-input {
        height: 38px;
        font-size: 13px;
    }
    .single-aside .subscribe-cta {
        height: 38px;
        font-size: 14px;
    }
```

---

## 3. functions.php (선택)

미리보기에서 구독 블록을 숨기는 스타일이 있다면, 복구 시 해당 selector에 다시 `.single-subscribe-mobile` 을 넣으면 됩니다.

```css
.single-related,
.single-aside.sticky-group,
.single-subscribe-mobile,
.site-footer {
    display: none !important;
}
```
