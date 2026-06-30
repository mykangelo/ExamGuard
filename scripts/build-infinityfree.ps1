# Build ExamGuard for InfinityFree FTP upload.
# Output: dist/infinityfree/  (upload entire folder contents to remote htdocs/)

$ErrorActionPreference = "Stop"
$Root = Split-Path -Parent $PSScriptRoot
$Out = Join-Path $Root "dist\infinityfree"

Write-Host "==> Building ExamGuard for InfinityFree"
Write-Host "    Project: $Root"
Write-Host "    Output:  $Out"

Set-Location $Root

Write-Host "==> composer install --no-dev (platform PHP 8.3 for InfinityFree)"
composer install --no-dev --no-interaction
if ($LASTEXITCODE -ne 0) { throw "composer install failed" }

Write-Host "==> npm ci && npm run build"
npm ci --ignore-scripts
if ($LASTEXITCODE -ne 0) { throw "npm ci failed" }
npm run build
if ($LASTEXITCODE -ne 0) { throw "npm run build failed" }

Write-Host "==> Clearing cached config (avoid baking local APP_URL into dist)"
php artisan config:clear --ansi 2>$null
php artisan route:clear --ansi 2>$null
php artisan view:clear --ansi 2>$null

if (Test-Path $Out) {
    $envBackup = $null
    $backupPath = Join-Path $Root "dist\.env.infinityfree.upload.bak"
    if (Test-Path (Join-Path $Out ".env")) {
        Copy-Item (Join-Path $Out ".env") $backupPath -Force
        $envBackup = $backupPath
        Write-Host "    backed up existing dist .env"
    }
    Write-Host "==> Removing old dist/infinityfree"
    Remove-Item $Out -Recurse -Force
}
New-Item -ItemType Directory -Path $Out -Force | Out-Null

$Include = @(
    "app", "bootstrap", "config", "database", "public", "resources", "routes", "vendor",
    "artisan", "composer.json", "composer.lock"
)

foreach ($name in $Include) {
    $src = Join-Path $Root $name
    if (Test-Path $src) {
        Write-Host "    copy $name"
        Copy-Item $src (Join-Path $Out $name) -Recurse -Force
    }
}

# Writable storage skeleton (no logs, no uploaded files)
$StorageDirs = @(
    "storage\app\public\avatars",
    "storage\app\public\violation-snapshots",
    "storage\framework\cache\data",
    "storage\framework\sessions",
    "storage\framework\views",
    "storage\logs",
    "bootstrap\cache"
)
foreach ($rel in $StorageDirs) {
    $dir = Join-Path $Out $rel
    New-Item -ItemType Directory -Path $dir -Force | Out-Null
    $gitkeep = Join-Path $dir ".gitkeep"
    if (-not (Test-Path $gitkeep)) { New-Item -ItemType File -Path $gitkeep -Force | Out-Null }
}

# InfinityFree: document root hack + storage without symlink
Copy-Item (Join-Path $Root "deploy\infinityfree\htdocs.htaccess") (Join-Path $Out ".htaccess") -Force
$PublicStorage = Join-Path $Out "public\storage"
New-Item -ItemType Directory -Path $PublicStorage -Force | Out-Null
Copy-Item (Join-Path $Root "deploy\infinityfree\public-storage.htaccess") (Join-Path $PublicStorage ".htaccess") -Force
Copy-Item (Join-Path $Root "public\storage\serve.php") (Join-Path $PublicStorage "serve.php") -Force

Copy-Item (Join-Path $Root "deploy\infinityfree\public-ping.php") (Join-Path $Out "public\ping.php") -Force
Copy-Item (Join-Path $Root "deploy\infinityfree\public-diag.php") (Join-Path $Out "public\diag.php") -Force
Copy-Item (Join-Path $Root "deploy\infinityfree\public-diag.php") (Join-Path $Out "public\public-diag.php") -Force
Copy-Item (Join-Path $Root "deploy\infinityfree\public-mail-test.php") (Join-Path $Out "public\mail-test.php") -Force

Copy-Item (Join-Path $Root ".env.infinityfree.example") (Join-Path $Out ".env.infinityfree.example") -Force

$envFile = Join-Path $Out ".env"
$envExample = Join-Path $Out ".env.infinityfree.example"
$envBackup = Join-Path $Root "dist\.env.infinityfree.upload.bak"
if (Test-Path $envBackup) {
    Copy-Item $envBackup $envFile -Force
    Write-Host "    restored .env from backup"
} elseif (-not (Test-Path $envFile) -and (Test-Path $envExample)) {
    Copy-Item $envExample $envFile -Force
    Write-Host "    created .env from .env.infinityfree.example (edit APP_KEY, DB_*, APP_URL before upload)"
}

Write-Host ""
Write-Host "==> Done. Next steps:"
Write-Host "    1. php artisan key:generate --show"
Write-Host "    2. Copy dist/infinityfree/.env.infinityfree.example to dist/infinityfree/.env and fill in APP_KEY + DB_*"
Write-Host "    3. Export DB locally and run: .\scripts\strip-fk-from-sql.ps1 ..."
Write-Host "    4. Import SQL in InfinityFree phpMyAdmin"
Write-Host "    5. FTP upload ALL contents of dist/infinityfree/ to remote htdocs/"
Write-Host ""
Write-Host "See INFINITYFREE.md for the full guide."
