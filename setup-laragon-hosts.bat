@echo off
:: SMS Panel - Laragon hosts kaydi ekler (Yonetici olarak calistirin)
:: Sag tik -> Yonetici olarak calistir

set HOSTS=%SystemRoot%\System32\drivers\etc\hosts
findstr /C:"smspanel.test" %HOSTS% >nul 2>&1
if %errorlevel%==0 (
    echo smspanel.test zaten hosts dosyasinda kayitli.
) else (
    echo 127.0.0.1      smspanel.test           #laragon magic!>> %HOSTS%
    echo smspanel.test hosts dosyasina eklendi.
)

echo.
echo Simdi Laragon uygulamasini acin ve "Start All" tiklayin.
echo Ardından tarayicida http://smspanel.test/giris adresine gidin.
pause
