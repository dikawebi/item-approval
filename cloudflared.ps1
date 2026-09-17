# ============================================================
# cloudflared.ps1 - Expose localhost via Cloudflare Quick Tunnel
# Pemakaian:
#   .\cloudflared.ps1
#   .\cloudflared.ps1 -Port 8000
# Prasyarat:
#   1. cloudflared terinstall: winget install Cloudflare.cloudflared
#   2. App sudah jalan: php artisan serve --port=8000
# Catatan:
#   - URL *.trycloudflare.com acak & berubah tiap restart.
#   - Setelah URL muncul, update APP_URL di .env lalu
#     jalankan `php artisan config:clear` di terminal lain.
#   - Tunnel ini blocking — tekan Ctrl+C untuk stop.
# ============================================================
param(
    [int]$Port = 8000
)

$ErrorActionPreference = "Stop"

# Cari cloudflared.exe eksplisit (hindari bentrok dengan nama script ini
# + PATH terminal yang belum reload setelah winget install).
$cf = Get-Command "cloudflared.exe" -ErrorAction SilentlyContinue
if (-not $cf) {
    $env:Path = [System.Environment]::GetEnvironmentVariable("Path", "Machine") + ";" + [System.Environment]::GetEnvironmentVariable("Path", "User")
    $cf = Get-Command "cloudflared.exe" -ErrorAction SilentlyContinue
}
if (-not $cf) {
    Write-Host "ERROR: cloudflared belum terinstall / tidak di PATH." -ForegroundColor Red
    Write-Host "Install dengan: winget install Cloudflare.cloudflared" -ForegroundColor Yellow
    Write-Host "Lalu tutup + buka ulang terminal, atau jalankan script ini lagi." -ForegroundColor Yellow
    exit 1
}

# 1. Pastikan app lokal merespons
try {
    $r = Invoke-WebRequest -Uri "http://localhost:$Port" -UseBasicParsing -TimeoutSec 5
    if ($r.StatusCode) { Write-Host "[1/2] App lokal OK : http://localhost:$Port" -ForegroundColor Green }
} catch {
    Write-Host "WARNING: App belum merespons di http://localhost:$Port" -ForegroundColor Yellow
    Write-Host "Jalankan dulu: php artisan serve --host=127.0.0.1 --port=$Port" -ForegroundColor Yellow
}

# 2. Jalankan tunnel (blocking)
Write-Host "[2/2] Memulai Cloudflare Tunnel..." -ForegroundColor Yellow
Write-Host ""
Write-Host "  Setelah URL https://xxx.trycloudflare.com muncul:" -ForegroundColor DarkGray
Write-Host "  1. Buka terminal BARU, set APP_URL=<url-itu> di .env" -ForegroundColor DarkGray
Write-Host "  2. php artisan config:clear" -ForegroundColor DarkGray
Write-Host "  3. Buka <url-itu>/item-approval/login" -ForegroundColor DarkGray
Write-Host ""
Write-Host "  Tekan Ctrl+C untuk menghentikan tunnel." -ForegroundColor DarkGray
Write-Host ""

& $cf.Source tunnel --url "http://localhost:$Port"
