#!/bin/bash
set -e

echo "=== Initializing AI Study Assistant Container ==="

# 1. Determine Database Setup: External Cloud MySQL vs Internal MariaDB
if [ -z "$DB_HOST" ] || [ "$DB_HOST" = "localhost" ] || [ "$DB_HOST" = "127.0.0.1" ]; then
    echo ">> DB_HOST not set or localhost. Starting built-in MariaDB/MySQL engine..."
    service mariadb start

    # Wait for MariaDB socket to be ready
    for i in {1..30}; do
        if mysqladmin ping --silent; then
            break
        fi
        sleep 1
    done

    # Ensure database exists
    echo ">> Checking MySQL database 'ai_study_assistant'..."
    mysql -u root -e "CREATE DATABASE IF NOT EXISTS ai_study_assistant CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

    # Auto-seed database if empty
    TABLES_COUNT=$(mysql -u root -D ai_study_assistant -sse "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'ai_study_assistant';" 2>/dev/null || echo "0")
    if [ "$TABLES_COUNT" = "0" ] || [ -z "$TABLES_COUNT" ]; then
        echo ">> Seeding database with initial schema and default subjects/users..."
        mysql -u root ai_study_assistant < /var/www/html/database/ai_study_assistant.sql
        echo ">> Database schema imported successfully!"
    else
        echo ">> Database tables already initialized ($TABLES_COUNT tables found)."
    fi
else
    echo ">> Using external cloud MySQL database at $DB_HOST:$DB_PORT"
fi

# 2. Configure Dynamic Port for Render / Cloud Hosts
TARGET_PORT=${PORT:-80}
echo ">> Configuring Apache to listen on port $TARGET_PORT..."
sed -i "s/Listen 80/Listen $TARGET_PORT/g" /etc/apache2/ports.conf
sed -i "s/:80/:$TARGET_PORT/g" /etc/apache2/sites-available/000-default.conf

# 3. Ensure upload directory permissions
chmod -R 777 /var/www/html/uploads 2>/dev/null || true

echo "=== Starting Apache Web Server on port $TARGET_PORT ==="
exec apache2-foreground
