# Chabrin Lease System - SMS Configuration Setup Script
# Run this script in PowerShell from your project directory

Write-Host "=== Chabrin Lease System - SMS Setup ===" -ForegroundColor Green
Write-Host ""

# Check if we're in the right directory
if (-not (Test-Path ".\artisan")) {
    Write-Host "ERROR: Please run this script from the project root directory" -ForegroundColor Red
    Write-Host "Expected location: C:\Users\kiman\Projects\chabrin-lease-system" -ForegroundColor Yellow
    exit 1
}

Write-Host "Step 1: Pulling latest changes from git..." -ForegroundColor Cyan
git pull origin claude/add-modern-feature-46f10

if ($LASTEXITCODE -ne 0) {
    Write-Host "WARNING: Git pull had issues. Continuing anyway..." -ForegroundColor Yellow
}

Write-Host ""
Write-Host "Step 2: Adding Africa's Talking credentials to .env file..." -ForegroundColor Cyan

# Check if .env exists
if (-not (Test-Path ".\.env")) {
    Write-Host "ERROR: .env file not found!" -ForegroundColor Red
    Write-Host "Creating .env from .env.example..." -ForegroundColor Yellow
    Copy-Item ".\.env.example" ".\.env"
}

# Read current .env content
$envContent = Get-Content ".\.env" -Raw

# Check if credentials already exist
if ($envContent -match "AFRICAS_TALKING_USERNAME") {
    Write-Host "Africa's Talking credentials already exist in .env" -ForegroundColor Yellow
    Write-Host "Updating credentials..." -ForegroundColor Yellow

    # Update existing values
    $envContent = $envContent -replace "AFRICAS_TALKING_USERNAME=.*", "AFRICAS_TALKING_USERNAME=tech@chabrinagencies.co.ke"
    $envContent = $envContent -replace "AFRICAS_TALKING_API_KEY=.*", "AFRICAS_TALKING_API_KEY=c9df9f6abe34247ed49e860fc78554fc70ba08fad07ea38df07aff6a2e486c17"
    $envContent = $envContent -replace "AFRICAS_TALKING_SHORTCODE=.*", "AFRICAS_TALKING_SHORTCODE=CHABRIN"

    Set-Content ".\.env" -Value $envContent -NoNewline
} else {
    Write-Host "Adding new Africa's Talking credentials..." -ForegroundColor Yellow

    # Add credentials to end of file
    $newLines = "`r`n`r`n# Africa's Talking SMS Configuration`r`nAFRICAS_TALKING_USERNAME=tech@chabrinagencies.co.ke`r`nAFRICAS_TALKING_API_KEY=c9df9f6abe34247ed49e860fc78554fc70ba08fad07ea38df07aff6a2e486c17`r`nAFRICAS_TALKING_SHORTCODE=CHABRIN"

    Add-Content ".\.env" -Value $newLines
}

Write-Host "SUCCESS: Credentials added successfully!" -ForegroundColor Green

Write-Host ""
Write-Host "Step 3: Clearing Laravel configuration cache..." -ForegroundColor Cyan
php artisan config:clear
php artisan cache:clear

Write-Host ""
Write-Host "=== Setup Complete! ===" -ForegroundColor Green
Write-Host ""
Write-Host "SMS/OTP functionality is now configured." -ForegroundColor Green
Write-Host "The system will send verification codes via SMS when:" -ForegroundColor White
Write-Host "  - Tenants digitally sign leases" -ForegroundColor White
Write-Host "  - OTP verification is required" -ForegroundColor White
Write-Host ""
Write-Host "Next steps:" -ForegroundColor Cyan
Write-Host "  1. Refresh your browser at http://127.0.0.1:8000/admin" -ForegroundColor White
Write-Host "  2. Try creating a new lease" -ForegroundColor White
Write-Host "  3. Test the digital signing workflow" -ForegroundColor White
Write-Host ""
