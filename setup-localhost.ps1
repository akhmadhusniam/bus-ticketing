param(
    [string] $ProjectDir = $PSScriptRoot,
    [string] $XamppDir = 'C:\xampp',
    [string] $BasePath = 'bus-ticketing'
)

$project = (Resolve-Path $ProjectDir).Path
$public = Join-Path $project 'public'
$vhost = Join-Path $XamppDir 'apache\conf\extra\httpd-vhosts.conf'
$httpd = Join-Path $XamppDir 'apache\bin\httpd.exe'
$publicForward = $public -replace '\\', '/'
$appUrl = "http://localhost/$BasePath"

if (-not (Test-Path $public)) {
    throw "Folder public tidak ditemukan: $public"
}

if (-not (Test-Path $vhost)) {
    throw "File konfigurasi vhost tidak ditemukan: $vhost"
}

# VirtualHost + Alias: / -> aplikasi, /bus-ticketing -> aplikasi dengan prefix.
Copy-Item -Path $vhost -Destination "$vhost.bak" -Force
$content = Get-Content -Path $vhost -Raw
$content = [regex]::Replace($content, '(?ms)# Bus Ticketing.*?</VirtualHost>\s*', '')

$block = @"
# Bus Ticketing — ditambahkan oleh setup-localhost.ps1
<VirtualHost *:80>
    ServerName localhost
    ServerAlias 127.0.0.1
    DocumentRoot "$publicForward"

    Alias /$BasePath "$publicForward"

    <Directory "$publicForward">
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog "logs/bus-ticketing-error.log"
    CustomLog "logs/bus-ticketing-access.log" common
</VirtualHost>
"@

Set-Content -Path $vhost -Value ($content.TrimEnd() + "`r`n`r`n" + $block + "`r`n") -Encoding ascii
Write-Host "VirtualHost dan Alias /$BasePath diperbarui di $vhost"

# Samakan URL absolut yang dibuat helper route()/asset().
foreach ($file in @((Join-Path $project '.env'), (Join-Path $project '.env.example'))) {
    if (Test-Path $file) {
        $lines = Get-Content $file
        if ($lines -match '^APP_URL=') {
            $lines = $lines -replace '^APP_URL=.*', "APP_URL=$appUrl"
        }
        else {
            $lines += "APP_URL=$appUrl"
        }
        Set-Content -Path $file -Value $lines -Encoding ascii
    }
}

# Restart Apache.
if (Test-Path $httpd) {
    $httpdDir = Split-Path $httpd

    Push-Location $httpdDir
    & .\httpd.exe -k restart
    $restarted = ($LASTEXITCODE -eq 0)
    Pop-Location

    if ($restarted) {
        Write-Host "Apache berhasil di-restart."
    }
    else {
        # XAMPP biasanya menjalankan Apache sebagai aplikasi konsol, bukan service.
        Stop-Process -Name httpd -Force -ErrorAction SilentlyContinue
        Start-Sleep -Seconds 1
        Start-Process -FilePath $httpd -WorkingDirectory $httpdDir
        Write-Host "Apache konsol XAMPP di-restart."
    }
}
else {
    Write-Warning "httpd.exe tidak ditemukan. Restart Apache manual lewat XAMPP Control Panel."
}

Write-Host "Selesai. Buka $appUrl/"
