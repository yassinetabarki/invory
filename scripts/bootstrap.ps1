<#
.SYNOPSIS
    Scaffolds the official Laravel 13 React starter kit into this repository,
    adds Laravel Sail (Docker: PostgreSQL + Mailpit), and keeps the existing
    docs/, .claude/, CLAUDE.md, README.md and sail.cmd files untouched.

.DESCRIPTION
    Composer cannot create a project in a non-empty directory, so the starter kit
    is created in a temporary folder and then merged in. Files that already exist
    in the repository are never overwritten.

    Prerequisites (docs/SETUP.md): Laragon with PHP >= 8.3 + Composer on PATH,
    the Laravel installer, Node >= 22, Git, Docker Desktop + WSL2.

.EXAMPLE
    powershell -ExecutionPolicy Bypass -File scripts\bootstrap.ps1
#>
[CmdletBinding()]
param()

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
$utf8NoBom = New-Object System.Text.UTF8Encoding $false

function Assert-Command([string]$Name, [string]$Hint) {
    if (-not (Get-Command $Name -ErrorAction SilentlyContinue)) {
        throw "'$Name' was not found on PATH. $Hint"
    }
}

function Invoke-Native([string]$Description, [scriptblock]$Command) {
    Write-Host "==> $Description" -ForegroundColor Cyan
    & $Command
    if ($LASTEXITCODE -ne 0) { throw "$Description failed (exit code $LASTEXITCODE)." }
}

function Set-EnvValue([string]$Path, [string]$Key, [string]$Value) {
    $text = [System.IO.File]::ReadAllText($Path)
    if ($text -match "(?m)^$Key=") {
        $text = $text -replace "(?m)^$Key=.*$", "$Key=$Value"
    } else {
        $text = $text.TrimEnd() + "`n$Key=$Value`n"
    }
    # UTF-8 without BOM: a BOM would corrupt the first .env key.
    [System.IO.File]::WriteAllText($Path, $text, $utf8NoBom)
}

# Fast suite = SQLite in-memory, so `composer test` runs natively (Laragon) without Docker.
# sail:install points phpunit.xml at the container DB; put SQLite back (CI overrides via env vars).
function Set-PhpUnitSqlite([string]$Path) {
    $lines = [System.IO.File]::ReadAllLines($Path) | Where-Object { $_ -notmatch 'name="DB_(CONNECTION|DATABASE)"' }
    $out = New-Object System.Collections.Generic.List[string]
    foreach ($line in $lines) {
        $out.Add($line)
        if ($line -match '^(\s*)<env name="APP_ENV"') {
            $indent = $Matches[1]
            $out.Add("$indent<env name=""DB_CONNECTION"" value=""sqlite""/>")
            $out.Add("$indent<env name=""DB_DATABASE"" value="":memory:""/>")
        }
    }
    [System.IO.File]::WriteAllText($Path, (($out -join "`n") + "`n"), $utf8NoBom)
}

# --- Prerequisites ----------------------------------------------------------
Assert-Command php      'Add PHP 8.3+ to Laragon, then Menu > Tools > Path > Add Laragon to Path (docs/SETUP.md, step 1).'
Assert-Command composer 'Laragon ships Composer; add Laragon to PATH (docs/SETUP.md, step 1).'
Assert-Command laravel  'Run: composer global require laravel/installer'
Assert-Command node     'Install Node.js 22 or newer.'
Assert-Command npm      'Install Node.js 22 or newer.'
Assert-Command git      'Install Git.'
Assert-Command docker   'Install Docker Desktop (docs/SETUP.md, step 1).'
Assert-Command wsl      'Enable WSL2 (docs/SETUP.md, step 1).'

$phpVersion = (& php -r 'echo PHP_VERSION;')
if ([version]($phpVersion -replace '[^0-9.].*$', '') -lt [version]'8.3') {
    throw "PHP $phpVersion found; Laravel 13 requires PHP 8.3 or newer."
}

if (Test-Path (Join-Path $root 'artisan')) {
    throw "Laravel is already scaffolded in $root (artisan exists). Nothing to do."
}

# --- Scaffold in a temporary folder ------------------------------------------
$scaffoldName   = "invory-scaffold-$(Get-Date -Format 'yyyyMMddHHmmss')"
$scaffoldParent = [System.IO.Path]::GetTempPath()
$scaffold       = Join-Path $scaffoldParent $scaffoldName

Push-Location $scaffoldParent
try {
    # React starter kit (Inertia 3 + React 19 + TS + Tailwind 4 + shadcn/ui + Wayfinder + Fortify),
    # Pest, no teams (ADR-0003). SQLite only for the initial scaffold; Sail switches .env to PostgreSQL below.
    Invoke-Native 'Creating the Laravel 13 React starter kit' {
        laravel new $scaffoldName --react --pest --database=sqlite --no-node --no-boost --no-interaction
    }
} finally {
    Pop-Location
}

# --- Merge into the repository (never overwrite existing files) -------------
Write-Host "==> Merging the scaffold into $root (existing files are kept)" -ForegroundColor Cyan
& robocopy $scaffold $root /E /XC /XN /XO /MT:16 /NFL /NDL /NJH /NJS /NP | Out-Null
if ($LASTEXITCODE -ge 8) { throw "robocopy failed (exit code $LASTEXITCODE)." }
Remove-Item -Recurse -Force $scaffold

# --- Post-merge setup --------------------------------------------------------
Push-Location $root
try {
    if (-not (Test-Path '.git')) {
        Invoke-Native 'Initialising Git repository (branch main)' { git init -b main }
    }

    Invoke-Native 'Installing Laravel Boost (dev dependency)' { composer require laravel/boost --dev --no-interaction }
    Invoke-Native 'Installing NPM dependencies' { npm install }
    Invoke-Native 'Building frontend assets (also generates Wayfinder routes)' { npm run build }

    # Docker stack for the app runtime (ADR-0005): app container + PostgreSQL + Mailpit.
    Invoke-Native 'Installing Laravel Sail (pgsql, mailpit)' { php artisan sail:install --with=pgsql,mailpit --no-interaction }

    foreach ($envFile in @('.env', '.env.example')) {
        if (Test-Path $envFile) {
            $path = (Resolve-Path $envFile).Path
            Set-EnvValue $path 'APP_NAME' 'Invory'
            Set-EnvValue $path 'APP_URL' 'http://localhost'
            Set-EnvValue $path 'APP_CURRENCY' 'EUR'
        }
    }

    Set-PhpUnitSqlite (Resolve-Path 'phpunit.xml').Path
    Invoke-Native 'Running the test suite natively (SQLite in-memory)' { php artisan test --compact }
} finally {
    Pop-Location
}

Write-Host ''
Write-Host 'Scaffold ready. Next steps (docs/SETUP.md):' -ForegroundColor Green
Write-Host '  1. git add -A ; git commit -m "chore: scaffold Laravel 13 React starter kit + Sail + project foundation"'
Write-Host '  2. php artisan boost:install    (agent: Claude Code; features: guidelines, skills, MCP server; do NOT pick Sail for the MCP command)'
Write-Host '  3. Start Docker Desktop, stop Laragon''s Apache/MySQL, then:'
Write-Host '       .\sail up -d'
Write-Host '       .\sail artisan migrate'
Write-Host '       npm run dev                -> http://localhost'
