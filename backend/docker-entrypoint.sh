#!/bin/sh
set -e

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo "${BLUE}=====================================${NC}"
echo "${BLUE}Symfony Application Initialization${NC}"
echo "${BLUE}=====================================${NC}"

# Create a stub .env so Symfony bootEnv() never crashes when the file is
# absent (backend/.env is gitignored). The value is read from the container
# APP_ENV env var injected by Docker Compose, so it works for all envs.
printf "APP_ENV=%s\nAPP_DEBUG=%s\n" "${APP_ENV:-dev}" "${APP_DEBUG:-1}" > /var/www/backend/.env

# In production var/ is a persistent volume, so the compiled container survives
# a rebuild and the .initialized guard below would skip the warmup — the new
# image would run yesterday's compiled config. Rebuild it on every start; it
# costs a few seconds and it is the difference between deploying and appearing
# to deploy.
if [ "$APP_ENV" = "prod" ] && [ -f /var/www/backend/var/.initialized ]; then
    echo "${BLUE}Rebuilding production cache...${NC}"
    php bin/console cache:clear --no-warmup
    php bin/console cache:warmup
    php bin/console assets:install --no-interaction > /dev/null 2>&1 || true
    echo "${GREEN}✓ Production cache rebuilt${NC}"
fi

# First run: install dependencies and warm up cache
if [ ! -f /var/www/backend/var/.initialized ]; then
    echo "${BLUE}First run: initializing application...${NC}"
    
    # Install/update dependencies if vendor is empty (mounted volume)
    if [ ! -d /var/www/backend/vendor/symfony ]; then
        echo "${BLUE}Installing Composer dependencies...${NC}"
        composer install --prefer-dist --no-interaction
    fi
    
    # Clear and warm up cache
    echo "${BLUE}Clearing and warming up cache...${NC}"
    php bin/console cache:clear --no-warmup
    php bin/console cache:warmup
    
    # Install Symfony bundle assets (EasyAdmin CSS/JS etc.)
    echo "${BLUE}Installing Symfony assets...${NC}"
    php bin/console assets:install --no-interaction > /dev/null 2>&1 || true

    # Mark as initialized
    touch /var/www/backend/var/.initialized
    echo "${GREEN}✓ Application dependencies installed${NC}"
fi

# Database preparation (dev and test environments)
if [ "$APP_ENV" != "prod" ]; then
    echo "${BLUE}Waiting for database connection...${NC}"
    
    # Wait for database to be ready
    until php bin/console doctrine:query:sql "SELECT 1" > /dev/null 2>&1; do
        echo "  Waiting for database to be ready..."
        sleep 2
    done
    
    echo "${GREEN}✓ Database is ready${NC}"
    
    # Run migrations
    echo "${BLUE}Running database migrations...${NC}"
    if php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration > /dev/null 2>&1; then
        echo "${GREEN}✓ Migrations completed${NC}"
    else
        echo "${BLUE}  No migrations to run${NC}"
    fi

    # Load dev fixtures if database is empty
    echo "${BLUE}Checking for seed data...${NC}"
    USER_COUNT=$(php bin/console doctrine:query:sql "SELECT COUNT(*) FROM \"user\"" 2>/dev/null | grep -oE '[0-9]+' | head -1 || echo "0")
    
    if [ "$USER_COUNT" = "0" ] || [ "$USER_COUNT" = "" ]; then
        echo "${BLUE}Loading development fixtures...${NC}"
        if php bin/console doctrine:fixtures:load --group=dev --no-interaction > /dev/null 2>&1; then
            echo "${GREEN}✓ Development fixtures loaded${NC}"
        else
            echo "${BLUE}  No fixtures available${NC}"
        fi
    else
        echo "${GREEN}✓ Database already contains data (${USER_COUNT} users found)${NC}"
    fi
fi

# Everything above runs as root, so var/ ends up root-owned while php-fpm
# serves as www-data. Symfony's cache pools are written at runtime, not at
# warmup: the rate limiters and the AMS login throttle silently fail to persist
# when this is missing — they read back zero every time and never trip.
chown -R www-data:www-data /var/www/backend/var

echo "${GREEN}=====================================${NC}"
echo "${GREEN}✓ Application ready${NC}"
echo "${GREEN}=====================================${NC}"
echo ""

# Execute the main command
exec "$@"