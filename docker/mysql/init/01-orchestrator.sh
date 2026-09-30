#!/bin/bash
# Database/user riêng cho Python orchestrator, tách khỏi database của Laravel
# (Laravel DB + user được tạo bởi MYSQL_DATABASE/MYSQL_USER của image).
set -e

mysql --protocol=socket -uroot -p"${MYSQL_ROOT_PASSWORD}" <<-EOSQL
    CREATE DATABASE IF NOT EXISTS \`ai_fix_orchestrator\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    CREATE USER IF NOT EXISTS 'aifix'@'%' IDENTIFIED BY '${AIFIX_DB_PASSWORD}';
    GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, REFERENCES ON \`ai_fix_orchestrator\`.* TO 'aifix'@'%';
    FLUSH PRIVILEGES;
EOSQL
