# GitHub 저장소 연결 가이드

## 사전 준비사항

1. **Git 설치 확인**
   - Git이 설치되어 있지 않다면: https://git-scm.com/download/win 에서 다운로드 및 설치
   - 설치 후 PowerShell 또는 명령 프롬프트를 재시작하세요.

2. **GitHub 저장소 확인**
   - 저장소 URL: `https://github.com/knetbizdesign-cell/untitled-project`
   - 저장소가 없다면 GitHub에서 먼저 생성해주세요.

## 연결 방법

### 방법 1: 배치 스크립트 사용 (권장)

테마 디렉토리에서 `setup-github.bat` 파일을 더블클릭하거나 실행하세요.

### 방법 2: 수동 명령어 실행

PowerShell 또는 명령 프롬프트에서 다음 명령어를 순서대로 실행하세요:

```bash
# 테마 디렉토리로 이동
cd C:\xampp\htdocs\wordpress\wp-content\themes\borobill_theme

# Git 저장소 초기화
git init

# 파일 추가
git add .

# 첫 커밋
git commit -m "Initial commit: borobill_theme"

# GitHub 원격 저장소 연결
git remote add origin https://github.com/knetbizdesign-cell/untitled-project.git

# 브랜치 이름을 main으로 설정
git branch -M main

# GitHub에 푸시
git push -u origin main
```

## 인증 문제 해결

GitHub에 푸시할 때 인증이 필요할 수 있습니다:

1. **Personal Access Token 사용** (권장)
   - GitHub → Settings → Developer settings → Personal access tokens → Tokens (classic)
   - 새 토큰 생성 (repo 권한 필요)
   - 푸시 시 비밀번호 대신 토큰 사용

2. **GitHub Desktop 사용**
   - GitHub Desktop을 설치하여 GUI로 관리할 수 있습니다.

## 이후 작업

연결이 완료된 후:

```bash
# 변경사항 커밋
git add .
git commit -m "변경사항 설명"

# GitHub에 푸시
git push
```


