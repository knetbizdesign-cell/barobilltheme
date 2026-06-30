@echo off
chcp 65001 >nul
echo ========================================
echo GitHub 저장소 연결 스크립트
echo ========================================
echo.

cd /d "%~dp0"

echo [1/5] Git 저장소 초기화 중...
git init
if %errorlevel% neq 0 (
    echo 오류: Git이 설치되어 있지 않습니다.
    echo Git을 먼저 설치해주세요: https://git-scm.com/download/win
    pause
    exit /b 1
)

echo [2/5] 파일 추가 중...
git add .

echo [3/5] 첫 커밋 생성 중...
git commit -m "Initial commit: borobill_theme"

echo [4/5] GitHub 원격 저장소 연결 중...
git remote add origin https://github.com/knetbizdesign-cell/untitled-project.git
if %errorlevel% neq 0 (
    echo 원격 저장소가 이미 존재합니다. 업데이트합니다...
    git remote set-url origin https://github.com/knetbizdesign-cell/untitled-project.git
)

echo [5/5] 브랜치 이름 설정 및 푸시 중...
git branch -M main
git push -u origin main

echo.
echo ========================================
echo 완료! GitHub 저장소에 연결되었습니다.
echo ========================================
pause


