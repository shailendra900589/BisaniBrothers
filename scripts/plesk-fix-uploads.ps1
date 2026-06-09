# Run on Plesk Windows after Git pull to fix uploads folder ACLs for IIS.
$root = Split-Path -Parent $PSScriptRoot
$vhost = Split-Path -Parent $root
$dirs = @(
    (Join-Path $root 'uploads'),
    (Join-Path $root 'uploads\resumes'),
    (Join-Path $root 'uploads\marketing'),
    (Join-Path $vhost 'tmp\bb-uploads'),
    (Join-Path $vhost 'tmp\bb-uploads\resumes'),
    (Join-Path $vhost 'tmp\bb-uploads\marketing')
)

foreach ($path in $dirs) {
    if (-not (Test-Path $path)) {
        New-Item -ItemType Directory -Path $path -Force | Out-Null
    }
    icacls $path /grant 'IIS_IUSRS:(OI)(CI)M' 2>$null
    icacls $path /grant 'IUSR:(OI)(CI)M' 2>$null
    Write-Host "Fixed ACL: $path"
}

if (Get-Command php -ErrorAction SilentlyContinue) {
    php (Join-Path $root 'scripts\ensure-uploads-dir.php')
}
