$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
Set-Location -LiteralPath $projectRoot
$phpExecutable = (Get-Command php).Source
$mysqlCandidates = @(
    'D:\apps\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysqld.exe'
    'C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysqld.exe'
)
$mysqlExecutable = $mysqlCandidates | Where-Object { Test-Path -LiteralPath $_ } | Select-Object -First 1
$runtimeDirectory = Join-Path $projectRoot '.runtime'
New-Item -ItemType Directory -Force -Path $runtimeDirectory | Out-Null
if (-not (Get-NetTCPConnection -State Listen -LocalPort 3307 -ErrorAction SilentlyContinue)) {
    if (-not $mysqlExecutable) {
        throw 'MySQL tidak ditemukan. Jalankan MySQL sesuai DB_PORT pada .env.'
    }
    $mysqlBase = Split-Path -Parent (Split-Path -Parent $mysqlExecutable)
    $dataDirectory = Join-Path $runtimeDirectory 'mysql'
    if (-not (Test-Path -LiteralPath (Join-Path $dataDirectory 'auto.cnf'))) {
        New-Item -ItemType Directory -Force -Path $dataDirectory | Out-Null
        & $mysqlExecutable --no-defaults --initialize-insecure "--basedir=$mysqlBase" "--datadir=$dataDirectory" --console
        if ($LASTEXITCODE -ne 0) {
            throw 'Inisialisasi MySQL gagal.'
        }
    }
    Start-Process -FilePath $mysqlExecutable -WindowStyle Hidden -ArgumentList @(
        '--no-defaults'
        "--basedir=`"$mysqlBase`""
        "--datadir=`"$dataDirectory`""
        '--port=3307'
        '--bind-address=127.0.0.1'
        '--mysqlx=0'
        "--log-error=`"$runtimeDirectory\mysql.log`""
    )
}
if (-not (Get-NetTCPConnection -State Listen -LocalPort 8000 -ErrorAction SilentlyContinue)) {
    Start-Process -FilePath $phpExecutable `
        -ArgumentList 'artisan', 'serve', '--host=127.0.0.1', '--port=8000' `
        -WorkingDirectory $projectRoot `
        -WindowStyle Hidden `
        -RedirectStandardOutput "$runtimeDirectory\server.log" `
        -RedirectStandardError "$runtimeDirectory\server-error.log"
}
Write-Host 'Website: http://127.0.0.1:8000 | Dashboard: http://127.0.0.1:8000/admin'
Write-Host 'Worker WhatsApp (jika integrasi aktif): php artisan queue:work --tries=1'
