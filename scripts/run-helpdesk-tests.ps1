param(
    [string]$Filter = "",
    [switch]$Coverage
)

$ErrorActionPreference = "Stop"

Write-Host ""
Write-Host "===============================================" -ForegroundColor Cyan
Write-Host "  EJECUCION DE PRUEBAS AUTOMATIZADAS HELPDESK " -ForegroundColor Cyan
Write-Host "===============================================" -ForegroundColor Cyan
Write-Host ""

if (-not (Test-Path ".\artisan")) {
    Write-Host "Error: ejecuta este script desde la raiz del proyecto Laravel." -ForegroundColor Red
    exit 1
}

if (-not (Test-Path ".\tests\Feature\Helpdesk\HelpdeskAutomationTest.php")) {
    Write-Host "Error: no se encontro el archivo tests\Feature\Helpdesk\HelpdeskAutomationTest.php" -ForegroundColor Red
    exit 1
}

$timestamp = Get-Date -Format "yyyyMMdd-HHmmss"
$logDir = ".\storage\logs"
$logFile = Join-Path $logDir "helpdesk-tests-$timestamp.log"

if (-not (Test-Path $logDir)) {
    New-Item -ItemType Directory -Path $logDir | Out-Null
}

Write-Host "Limpiando caches..." -ForegroundColor Yellow
php artisan optimize:clear | Out-Host

Write-Host ""
Write-Host "Iniciando pruebas..." -ForegroundColor Yellow
Write-Host "Log: $logFile" -ForegroundColor DarkGray
Write-Host ""

$arguments = @(
    "artisan",
    "test",
    "tests/Feature/Helpdesk/HelpdeskAutomationTest.php",
    "--testdox",
    "--colors=always"
)

if ($Filter -ne "") {
    $arguments += @("--filter", $Filter)
}

if ($Coverage) {
    $arguments += "--coverage-text"
}

& php $arguments 2>&1 | Tee-Object -FilePath $logFile

$exitCode = $LASTEXITCODE

Write-Host ""
if ($exitCode -eq 0) {
    Write-Host "Pruebas finalizadas correctamente." -ForegroundColor Green
    Write-Host "Resultado: EXIT CODE 0" -ForegroundColor Green
} else {
    Write-Host "Se detectaron fallas en las pruebas." -ForegroundColor Red
    Write-Host "Resultado: EXIT CODE $exitCode" -ForegroundColor Red
}

Write-Host ""
Write-Host "Archivo de resultado: $logFile" -ForegroundColor Cyan
Write-Host ""

exit $exitCode