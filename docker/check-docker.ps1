# Docker Installation Verification Script for VOTESYS (PowerShell)
# Run this script to verify Docker installation before deploying VOTESYS

Write-Host "=== VOTESYS Docker Installation Check ===" -ForegroundColor Blue
Write-Host ""

# Function to check if command exists
function Test-CommandExists($Command) {
    try {
        Get-Command $Command -ErrorAction Stop | Out-Null
        return $true
    }
    catch {
        return $false
    }
}

# Function to print status with colors
function Write-Status($Success, $Message) {
    if ($Success) {
        Write-Host "[OK] $Message" -ForegroundColor Green
    } else {
        Write-Host "[FAIL] $Message" -ForegroundColor Red
    }
}

# Check Docker
Write-Host "Checking Docker installation..." -ForegroundColor Blue
if (Test-CommandExists "docker") {
    try {
        $dockerVersion = docker --version 2>$null
        Write-Status $true "Docker is installed: $dockerVersion"
        
        # Check if Docker daemon is running
        try {
            docker info 2>$null | Out-Null
            Write-Status $true "Docker daemon is running"
        }
        catch {
            Write-Status $false "Docker daemon is not running. Please start Docker Desktop."
            Write-Host "  -> Start Docker Desktop and try again" -ForegroundColor Yellow
        }
    }
    catch {
        Write-Status $false "Docker command failed"
    }
} else {
    Write-Status $false "Docker is not installed"
    Write-Host "  -> Please install Docker Desktop from https://www.docker.com/products/docker-desktop" -ForegroundColor Yellow
}

Write-Host ""

# Check Docker Compose
Write-Host "Checking Docker Compose..." -ForegroundColor Blue
$composeAvailable = $false

# Check for docker-compose (standalone)
if (Test-CommandExists "docker-compose") {
    try {
        $composeVersion = docker-compose --version 2>$null
        Write-Status $true "Docker Compose (standalone) is available: $composeVersion"
        $composeAvailable = $true
    }
    catch {
        Write-Status $false "Docker Compose (standalone) command failed"
    }
}

# Check for docker compose (plugin)
try {
    $composeV2Version = docker compose version 2>$null
    if ($composeV2Version) {
        Write-Status $true "Docker Compose (plugin) is available: $composeV2Version"
        $composeAvailable = $true
    }
}
catch {
    # Ignore error if docker compose plugin is not available
}

if (-not $composeAvailable) {
    Write-Status $false "Docker Compose is not available"
    Write-Host "  -> Docker Compose should be included with Docker Desktop" -ForegroundColor Yellow
}

Write-Host ""

# Check system resources
Write-Host "Checking system resources..." -ForegroundColor Blue

# Check available memory
try {
    $totalMemoryGB = [math]::Round((Get-CimInstance Win32_PhysicalMemory | Measure-Object -Property Capacity -Sum).Sum / 1GB, 1)
    if ($totalMemoryGB -ge 2) {
        Write-Status $true "Total RAM: ${totalMemoryGB}GB (sufficient)"
    } else {
        Write-Status $false "Total RAM: ${totalMemoryGB}GB (minimum 2GB recommended)"
    }
}
catch {
    Write-Host "  -> Could not check available RAM" -ForegroundColor Yellow
}

# Check available disk space
try {
    $currentDrive = (Get-Location).Drive.Name
    $freeSpaceGB = [math]::Round((Get-PSDrive $currentDrive).Free / 1GB, 1)
    if ($freeSpaceGB -ge 5) {
        Write-Status $true "Available disk space on ${currentDrive}: ${freeSpaceGB}GB (sufficient)"
    } else {
        Write-Status $false "Available disk space on ${currentDrive}: ${freeSpaceGB}GB (minimum 5GB recommended)"
    }
}
catch {
    Write-Host "  -> Could not check available disk space" -ForegroundColor Yellow
}

Write-Host ""

# Check port availability
Write-Host "Checking port availability..." -ForegroundColor Blue
$ports = @(8080, 8081, 3307, 6380)

foreach ($port in $ports) {
    try {
        $netstatResult = netstat -an | Select-String ":$port "
        if ($netstatResult) {
            Write-Status $false "Port $port is in use"
            Write-Host "  -> You may need to stop the service using this port or change the port in docker-compose.yml" -ForegroundColor Yellow
        } else {
            Write-Status $true "Port $port is available"
        }
    }
    catch {
        Write-Host "  -> Could not check port $port availability" -ForegroundColor Yellow
    }
}

Write-Host ""

# Final recommendations
Write-Host "=== Recommendations ===" -ForegroundColor Blue

$dockerReady = $false
if (Test-CommandExists "docker") {
    try {
        docker info 2>$null | Out-Null
        $dockerReady = $true
    }
    catch {
        $dockerReady = $false
    }
}

if ($dockerReady -and $composeAvailable) {
    Write-Host "[OK] Your system is ready for VOTESYS Docker deployment!" -ForegroundColor Green
    Write-Host ""
    Write-Host "Next steps:" -ForegroundColor Blue
    Write-Host "1. Navigate to the VOTESYS directory"
    Write-Host "2. Run: docker compose up -d --build"
    Write-Host "3. Access VOTESYS at http://localhost:8080"
    Write-Host "4. Access phpMyAdmin at http://localhost:8081"
} else {
    Write-Host "[WARNING] Please install and start Docker before proceeding" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Installation links:" -ForegroundColor Blue
    Write-Host "• Windows/Mac: https://www.docker.com/products/docker-desktop"
    Write-Host "• Linux: https://docs.docker.com/engine/install/"
}

Write-Host ""
Write-Host "For detailed setup instructions, see: docs/DOCKER.md" -ForegroundColor Blue
Write-Host "=== End of Check ===" -ForegroundColor Blue

# Pause to keep window open if run directly
if ($Host.Name -eq "ConsoleHost") {
    Write-Host ""
    Write-Host "Press any key to continue..." -ForegroundColor Gray
    $null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
}