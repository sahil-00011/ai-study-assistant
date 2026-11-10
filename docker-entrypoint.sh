#!/bin/bash
set -e

echo "=== Initializing AI Study Assistant Container ==="

# 1. Start built-in MariaDB daemon if local database is used
if [ -z "$DB_HOST" ] || [ "$DB_HOST" = "localhost" ] || [ "$DB_HOST" = "127.0.0.1" ]; then
    echo ">> DB_HOST is set to local. Initializing MariaDB service..."

    # Ensure runtime directories exist with mysql permissions
    mkdir -p /var/run/mysqld /var/lib/mysql /var/log/mysql
    chown -R mysql:mysql /var/run/mysqld /var/lib/mysql /var/log/mysql
    chmod 777 /var/run/mysqld

    # Initialize data directory if not already created
    if [ ! -d "/var/lib/mysql/mysql" ]; then
        echo ">> Initializing MariaDB data directory with mysql_install_db..."
        mysql_install_db --user=mysql --datadir=/var/lib/mysql --skip-name-resolve >/dev/null 2>&1 || true
    fi

    # Start MariaDB in background via mysqld_safe
    echo ">> Starting MariaDB daemon via mysqld_safe..."
    mysqld_safe --user=mysql --skip-syslog --skip-name-resolve --bind-address=0.0.0.0 &

    # Wait for MariaDB to respond
    echo ">> Waiting for MariaDB socket..."
    READY=0
    for i in {1..30}; do
        if mysqladmin --socket=/var/run/mysqld/mysqld.sock ping --silent 2>/dev/null; then
            READY=1
            echo ">> MariaDB socket is ready!"
            break
        fi
        sleep 1
    done

    if [ $READY -eq 1 ]; then
        echo ">> Configuring MySQL users and privileges..."
        mysql --socket=/var/run/mysqld/mysqld.sock -u root <<'EOF' || true
FLUSH PRIVILEGES;
ALTER USER 'root'@'localhost' IDENTIFIED VIA mysql_native_password USING PASSWORD('');
CREATE USER IF NOT EXISTS 'root'@'127.0.0.1' IDENTIFIED BY '';
CREATE USER IF NOT EXISTS 'root'@'%' IDENTIFIED BY '';
GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost' WITH GRANT OPTION;
GRANT ALL PRIVILEGES ON *.* TO 'root'@'127.0.0.1' WITH GRANT OPTION;
GRANT ALL PRIVILEGES ON *.* TO 'root'@'%' WITH GRANT OPTION;
CREATE DATABASE IF NOT EXISTS `ai_study_assistant` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
FLUSH PRIVILEGES;
EOF

        echo ">> Checking if database tables are populated..."
        TABLES_COUNT=$(mysql --socket=/var/run/mysqld/mysqld.sock -u root -D ai_study_assistant -sse "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'ai_study_assistant';" 2>/dev/null || echo "0")
        if [ "$TABLES_COUNT" = "0" ] || [ -z "$TABLES_COUNT" ]; then
            echo ">> Seeding ai_study_assistant schema and initial data..."
            mysql --socket=/var/run/mysqld/mysqld.sock -u root ai_study_assistant < /var/www/html/database/ai_study_assistant.sql || true
            echo ">> Schema import completed!"
        else
            echo ">> Database already has $TABLES_COUNT tables."
        fi
    else
        echo ">> WARNING: MariaDB did not respond within 30 seconds."
    fi
else
    echo ">> Using external cloud database at $DB_HOST:$DB_PORT"
fi

# 2. Configure Dynamic Port for Render
TARGET_PORT=${PORT:-80}
echo ">> Configuring Apache to listen on port $TARGET_PORT..."
sed -i "s/Listen [0-9]*/Listen $TARGET_PORT/g" /etc/apache2/ports.conf
sed -i "s/:[0-9]*>/:$TARGET_PORT>/g" /etc/apache2/sites-available/000-default.conf

# 3. Ensure upload directory permissions
chmod -R 777 /var/www/html/uploads 2>/dev/null || true

echo "=== Starting Apache Web Server on port $TARGET_PORT ==="
exec apache2-foreground
