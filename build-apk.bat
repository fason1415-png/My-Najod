@echo off
setlocal enabledelayedexpansion
chcp 65001 >nul

set "ROOT=%~dp0"
set "APP_DIR=%ROOT%mobile_app_flutter"
set "OUT_APK=%ROOT%MY_NajotLink.apk"
set "FLUTTER_CMD="

echo ==========================================
echo   MY NajotLink Android APK Builder
echo ==========================================

if not exist "%APP_DIR%\pubspec.yaml" (
  echo [X] mobile_app_flutter papkasi topilmadi.
  pause
  exit /b 1
)

pushd "%APP_DIR%"

call :resolve_flutter
if errorlevel 1 goto :fail

echo [0/5] Flutter topildi: %FLUTTER_CMD%
call "%FLUTTER_CMD%" --version >nul 2>&1
if errorlevel 1 (
  echo [X] Flutter ishga tushmadi: %FLUTTER_CMD%
  goto :fail
)

echo [1/5] Flutter Android scaffold tekshirilmoqda...
if not exist "android\app\src\main\AndroidManifest.xml" (
  call "%FLUTTER_CMD%" create . --platforms=android
  if errorlevel 1 goto :fail
)

echo [2/5] Android manifest ruxsatlari qo'llanmoqda...
if exist "%APP_DIR%\android_manifest_template.xml" (
  copy /Y "%APP_DIR%\android_manifest_template.xml" "android\app\src\main\AndroidManifest.xml" >nul
)

echo [3/5] Paketlar yuklanmoqda...
call "%FLUTTER_CMD%" pub get
if errorlevel 1 goto :fail

echo [4/5] Release APK build...
call "%FLUTTER_CMD%" build apk --release
if errorlevel 1 (
  echo [!] Release build xatolik berdi, debug APK sinab ko'rilmoqda...
  call "%FLUTTER_CMD%" build apk --debug
  if errorlevel 1 goto :fail
  set "BUILT_APK=%APP_DIR%\build\app\outputs\flutter-apk\app-debug.apk"
) else (
  set "BUILT_APK=%APP_DIR%\build\app\outputs\flutter-apk\app-release.apk"
)

echo [5/5] APK nusxalanmoqda...
if not exist "%BUILT_APK%" (
  echo [X] APK topilmadi: %BUILT_APK%
  goto :fail
)

copy /Y "%BUILT_APK%" "%OUT_APK%" >nul
if errorlevel 1 goto :fail

popd
echo.
echo [OK] Tayyor APK:
echo %OUT_APK%
pause
exit /b 0

:fail
popd
echo.
echo [X] Build yakunlanmadi.
echo Flutter SDK, Android SDK va JDK o'rnatilganini tekshiring.
pause
exit /b 1

:resolve_flutter
for %%I in (flutter.bat flutter) do (
  where %%I >nul 2>&1
  if not errorlevel 1 (
    for /f "delims=" %%P in ('where %%I 2^>nul') do (
      set "FLUTTER_CMD=%%P"
      goto :flutter_found
    )
  )
)

for %%P in (
  "%USERPROFILE%\flutter\bin\flutter.bat"
  "%USERPROFILE%\development\flutter\bin\flutter.bat"
  "%LOCALAPPDATA%\Programs\Flutter\bin\flutter.bat"
  "C:\src\flutter\bin\flutter.bat"
  "C:\flutter\bin\flutter.bat"
) do (
  if exist %%~P (
    set "FLUTTER_CMD=%%~P"
    goto :flutter_found
  )
)

echo [!] Flutter PATH da topilmadi.
set /p FLUTTER_CMD=Flutter yo'lini kiriting (masalan C:\src\flutter\bin\flutter.bat): 
if "%FLUTTER_CMD%"=="" (
  echo [X] Flutter yo'li kiritilmadi.
  exit /b 1
)
if not exist "%FLUTTER_CMD%" (
  echo [X] Bunday fayl topilmadi: %FLUTTER_CMD%
  exit /b 1
)

:flutter_found
exit /b 0
