#!/bin/bash
# Docker Installation Verification Script for VOTESYS

echo "=== VOTESYS Docker Installation Check ==="
echo

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to check if command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Function to print status
print_status() {
    if [ $1 -eq 0 ]; then
        echo -e "${GREEN}✓${NC} $2"
    else
        echo -e "${RED}✗${NC} $2"
    fi
}

# Check Docker
echo -e "${BLUE}Checking Docker installation...${NC}"
if command_exists docker; then
    DOCKER_VERSION=$(docker --version 2>/dev/null)
    print_status 0 "Docker is installed: $DOCKER_VERSION"
    
    # Check if Docker daemon is running
    if docker info >/dev/null 2>&1; then
        print_status 0 "Docker daemon is running"
    else
        print_status 1 "Docker daemon is not running. Please start Docker Desktop."
        echo -e "${YELLOW}  → Start Docker Desktop and try again${NC}"
    fi
else
    print_status 1 "Docker is not installed"
    echo -e "${YELLOW}  → Please install Docker Desktop from https://www.docker.com/products/docker-desktop${NC}"
fi

echo

# Check Docker Compose
echo -e "${BLUE}Checking Docker Compose...${NC}"
if command_exists "docker-compose" || docker compose version >/dev/null 2>&1; then
    if command_exists "docker-compose"; then
        COMPOSE_VERSION=$(docker-compose --version 2>/dev/null)
        print_status 0 "Docker Compose (standalone) is available: $COMPOSE_VERSION"
    fi
    
    if docker compose version >/dev/null 2>&1; then
        COMPOSE_V2_VERSION=$(docker compose version 2>/dev/null)
        print_status 0 "Docker Compose (plugin) is available: $COMPOSE_V2_VERSION"
    fi
else
    print_status 1 "Docker Compose is not available"
    echo -e "${YELLOW}  → Docker Compose should be included with Docker Desktop${NC}"
fi

echo

# Check system resources
echo -e "${BLUE}Checking system resources...${NC}"

# Check available memory (Linux/macOS)
if command_exists free; then
    TOTAL_MEM=$(free -m | awk 'NR==2{printf "%.0f", $2/1024}')
    if [ "$TOTAL_MEM" -ge 2 ]; then
        print_status 0 "Available RAM: ${TOTAL_MEM}GB (sufficient)"
    else
        print_status 1 "Available RAM: ${TOTAL_MEM}GB (minimum 2GB recommended)"
    fi
elif command_exists vm_stat; then
    # macOS memory check
    TOTAL_MEM=$(echo "$(vm_stat | grep 'Pages free' | awk '{print $3}' | sed 's/\.//')" | awk '{printf "%.0f", $1 * 4096 / 1024 / 1024 / 1024}')
    if [ "$TOTAL_MEM" -ge 2 ]; then
        print_status 0 "Available RAM: ${TOTAL_MEM}GB (sufficient)"
    else
        print_status 1 "Available RAM: ${TOTAL_MEM}GB (minimum 2GB recommended)"
    fi
else
    echo -e "${YELLOW}  → Could not check available RAM${NC}"
fi

# Check available disk space
if command_exists df; then
    DISK_SPACE=$(df -h . | awk 'NR==2 {print $4}' | sed 's/G.*//')
    if [ "$DISK_SPACE" -ge 5 ]; then
        print_status 0 "Available disk space: ${DISK_SPACE}GB (sufficient)"
    else
        print_status 1 "Available disk space: ${DISK_SPACE}GB (minimum 5GB recommended)"
    fi
else
    echo -e "${YELLOW}  → Could not check available disk space${NC}"
fi

echo

# Check port availability
echo -e "${BLUE}Checking port availability...${NC}"
PORTS=(8080 8081 3307 6380)

for port in "${PORTS[@]}"; do
    if command_exists netstat; then
        if netstat -tuln 2>/dev/null | grep -q ":$port "; then
            print_status 1 "Port $port is in use"
            echo -e "${YELLOW}  → You may need to stop the service using this port or change the port in docker-compose.yml${NC}"
        else
            print_status 0 "Port $port is available"
        fi
    elif command_exists ss; then
        if ss -tuln 2>/dev/null | grep -q ":$port "; then
            print_status 1 "Port $port is in use"
            echo -e "${YELLOW}  → You may need to stop the service using this port or change the port in docker-compose.yml${NC}"
        else
            print_status 0 "Port $port is available"
        fi
    else
        echo -e "${YELLOW}  → Could not check port $port availability${NC}"
    fi
done

echo

# Final recommendations
echo -e "${BLUE}=== Recommendations ===${NC}"

if command_exists docker && docker info >/dev/null 2>&1; then
    echo -e "${GREEN}✓ Your system is ready for VOTESYS Docker deployment!${NC}"
    echo
    echo -e "${BLUE}Next steps:${NC}"
    echo "1. Navigate to the VOTESYS directory"
    echo "2. Run: docker compose up -d --build"
    echo "3. Access VOTESYS at http://localhost:8080"
    echo "4. Access phpMyAdmin at http://localhost:8081"
else
    echo -e "${YELLOW}⚠ Please install and start Docker before proceeding${NC}"
    echo
    echo -e "${BLUE}Installation links:${NC}"
    echo "• Windows/Mac: https://www.docker.com/products/docker-desktop"
    echo "• Linux: https://docs.docker.com/engine/install/"
fi

echo
echo -e "${BLUE}For detailed setup instructions, see: docs/DOCKER.md${NC}"
echo "=== End of Check ==="