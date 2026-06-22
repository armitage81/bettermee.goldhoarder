$ErrorActionPreference = 'Stop'

$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$confFile = Join-Path $scriptDir 'deploy.conf'

if (-not (Test-Path $confFile)) {
    Write-Error "deploy.conf not found. Copy deploy.conf.example to deploy.conf and fill in your server details."
    exit 1
}

$config = @{}
Get-Content $confFile | ForEach-Object {
    $line = $_.Trim()
    if ($line -and -not $line.StartsWith('#')) {
        $parts = $line -split '=', 2
        if ($parts.Count -eq 2) {
            $config[$parts[0].Trim()] = $parts[1].Trim()
        }
    }
}

$required = @('FTP_HOST', 'FTP_USER', 'FTP_PASSWORD', 'FTP_REMOTE_PATH')
foreach ($key in $required) {
    if (-not $config[$key]) {
        Write-Error "Missing required config: $key"
        exit 1
    }
}

$ftpHost = $config['FTP_HOST']
$ftpUser = $config['FTP_USER']
$ftpPassword = $config['FTP_PASSWORD']
$remotePath = $config['FTP_REMOTE_PATH'].TrimEnd('/')

$appDir = Join-Path $scriptDir 'app'
if (-not (Test-Path $appDir)) {
    Write-Error "app/ directory not found."
    exit 1
}

$credential = New-Object System.Net.NetworkCredential($ftpUser, $ftpPassword)

function Ensure-FtpDirectory($ftpBase, $dirPath) {
    $parts = $dirPath -split '[/\\]' | Where-Object { $_ -ne '' }
    $current = $ftpBase
    foreach ($part in $parts) {
        $current = "$current/$part"
        try {
            $request = [System.Net.FtpWebRequest]::Create($current)
            $request.Method = [System.Net.WebRequestMethods+Ftp]::MakeDirectory
            $request.Credentials = $credential
            $request.UseBinary = $true
            $response = $request.GetResponse()
            $response.Close()
        } catch [System.Net.WebException] {
            # directory likely already exists
        }
    }
}

function Upload-Directory($localDir, $ftpBase, $remoteDir) {
    $ftpTarget = "$ftpBase/$remoteDir"
    Ensure-FtpDirectory $ftpBase $remoteDir

    $files = Get-ChildItem -Path $localDir -File
    foreach ($file in $files) {
        $uploadUrl = "$ftpTarget/$($file.Name)"
        Write-Host "  $uploadUrl"
        $request = [System.Net.FtpWebRequest]::Create($uploadUrl)
        $request.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
        $request.Credentials = $credential
        $request.UseBinary = $true
        $content = [System.IO.File]::ReadAllBytes($file.FullName)
        $request.ContentLength = $content.Length
        $stream = $request.GetRequestStream()
        $stream.Write($content, 0, $content.Length)
        $stream.Close()
        $response = $request.GetResponse()
        $response.Close()
    }

    $subdirs = Get-ChildItem -Path $localDir -Directory
    foreach ($subdir in $subdirs) {
        Upload-Directory $subdir.FullName $ftpBase "$remoteDir/$($subdir.Name)"
    }
}

$ftpBase = "ftp://$ftpHost$remotePath"
Write-Host "Deploying app/ to ftp://$ftpHost$remotePath ..."

$folders = @('includes', 'public')
foreach ($folder in $folders) {
    $localPath = Join-Path $appDir $folder
    if (Test-Path $localPath) {
        Write-Host "Uploading $folder/ ..."
        Upload-Directory $localPath $ftpBase $folder
    }
}

# Generate and upload env.php with DB config
$dbHost = if ($config['DB_HOST']) { $config['DB_HOST'] } else { 'localhost' }
$dbName = $config['DB_NAME']
$dbUser = $config['DB_USER']
$dbPass = $config['DB_PASS']
$basePath = $config['BASE_PATH']

if ($dbName -and $dbUser) {
    Write-Host "Uploading env.php ..."
    $envPhp = @"
<?php
putenv('DB_HOST=$dbHost');
putenv('DB_NAME=$dbName');
putenv('DB_USER=$dbUser');
putenv('DB_PASS=$dbPass');
putenv('BASE_PATH=$basePath');
"@
    $envBytes = [System.Text.Encoding]::UTF8.GetBytes($envPhp)
    $uploadUrl = "$ftpBase/includes/env.php"
    $request = [System.Net.FtpWebRequest]::Create($uploadUrl)
    $request.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
    $request.Credentials = $credential
    $request.UseBinary = $true
    $request.ContentLength = $envBytes.Length
    $stream = $request.GetRequestStream()
    $stream.Write($envBytes, 0, $envBytes.Length)
    $stream.Close()
    $response = $request.GetResponse()
    $response.Close()
    Write-Host "  $uploadUrl"
}

Write-Host "Deployment complete."
