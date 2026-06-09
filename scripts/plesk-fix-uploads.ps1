# Run on Plesk Windows after Git pull to fix uploads folder ACLs for IIS.
$root = Split-Path -Parent $PSScriptRoot
$dirs = @('uploads', 'uploads\resumes', 'uploads\marketing')

foreach ($rel in $dirs) {
    $path = Join-Path $root $rel
    if (-not (Test-Path $path)) {
        New-Item -ItemType Directory -Path $path -Force | Out-Null
    }
    icacls $path /grant 'IIS_IUSRS:(OI)(CI)M' 2>$null
    icacls $path /grant 'IUSR:(OI)(CI)M' 2>$null
    Write-Host "Fixed ACL: $rel"
}

if (Get-Command php -ErrorAction SilentlyContinue) {
    php (Join-Path $root 'scripts\ensure-uploads-dir.php')
}
