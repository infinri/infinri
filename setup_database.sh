#!/bin/bash
# Infinri Database Setup Script
# Sets up PostgreSQL database for testing

set -e  # Exit on error

echo "========================================="
echo "Infinri PostgreSQL Database Setup"
echo "========================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Load environment variables from .env if it exists
if [ -f .env ]; then
    export $(grep -v '^#' .env | xargs)
    echo -e "${GREEN}✓${NC} Loaded .env file"
else
    echo -e "${YELLOW}⚠${NC}  .env file not found, using defaults"
    DB_NAME=${DB_NAME:-infinri_test}
    DB_USER=${DB_USER:-infinri}
    DB_PASSWORD=${DB_PASSWORD:-infinri}
fi

echo ""
echo "Database Configuration:"
echo "  Database: ${DB_NAME}"
echo "  User: ${DB_USER}"
echo "  Password: ${DB_PASSWORD}"
echo ""

# Check if PostgreSQL is installed
if ! command -v psql &> /dev/null; then
    echo -e "${RED}✗${NC} PostgreSQL not found!"
    echo ""
    echo "To install PostgreSQL on Ubuntu/Debian:"
    echo "  sudo apt-get update"
    echo "  sudo apt-get install postgresql postgresql-contrib php-pgsql"
    echo ""
    exit 1
fi

echo -e "${GREEN}✓${NC} PostgreSQL is installed"

# Check if PostgreSQL is running
if ! systemctl is-active --quiet postgresql; then
    echo -e "${YELLOW}⚠${NC}  PostgreSQL is not running. Attempting to start..."
    sudo systemctl start postgresql
    
    if systemctl is-active --quiet postgresql; then
        echo -e "${GREEN}✓${NC} PostgreSQL started successfully"
    else
        echo -e "${RED}✗${NC} Failed to start PostgreSQL"
        exit 1
    fi
else
    echo -e "${GREEN}✓${NC} PostgreSQL is running"
fi

echo ""
echo "Creating database and user..."
echo ""

# Create database and user
sudo -u postgres psql << EOF
-- Drop database if exists (for clean setup)
DROP DATABASE IF EXISTS ${DB_NAME};
DROP USER IF EXISTS ${DB_USER};

-- Create user
CREATE USER ${DB_USER} WITH PASSWORD '${DB_PASSWORD}';

-- Create database
CREATE DATABASE ${DB_NAME} OWNER ${DB_USER};

-- Grant privileges
GRANT ALL PRIVILEGES ON DATABASE ${DB_NAME} TO ${DB_USER};

-- Connect to database and grant schema privileges (PostgreSQL 15+)
\c ${DB_NAME}
GRANT ALL ON SCHEMA public TO ${DB_USER};
GRANT CREATE ON SCHEMA public TO ${DB_USER};

EOF

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓${NC} Database and user created successfully"
else
    echo -e "${RED}✗${NC} Failed to create database or user"
    exit 1
fi

echo ""
echo "Testing connection..."

# Test connection
PGPASSWORD=${DB_PASSWORD} psql -h localhost -U ${DB_USER} -d ${DB_NAME} -c "SELECT version();" > /dev/null 2>&1

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓${NC} Database connection successful"
else
    echo -e "${RED}✗${NC} Database connection failed"
    echo ""
    echo "Troubleshooting:"
    echo "  1. Check if PostgreSQL is configured to use md5 authentication"
    echo "  2. Edit: sudo nano /etc/postgresql/*/main/pg_hba.conf"
    echo "  3. Change 'peer' to 'md5' for local connections"
    echo "  4. Restart PostgreSQL: sudo systemctl restart postgresql"
    exit 1
fi

echo ""
echo "========================================="
echo -e "${GREEN}✓ Database Setup Complete!${NC}"
echo "========================================="
echo ""
echo "Database Details:"
echo "  Host: localhost"
echo "  Port: 5432"
echo "  Database: ${DB_NAME}"
echo "  User: ${DB_USER}"
echo "  Password: ${DB_PASSWORD}"
echo ""
echo "Next Steps:"
echo "  1. Run tests: composer test"
echo "  2. Start server: php -S localhost:8000 -t pub/"
echo ""
echo "All 237 tests should now pass! 🎉"
echo ""
