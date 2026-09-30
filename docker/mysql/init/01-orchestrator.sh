#!/bin/bash
# Database riêng cho Python orchestrator, dùng chung user `sentinel` với Laravel
# (Laravel DB `sentinel` + user được tạo bởi MYSQL_DATABASE/MYSQL_USER của image).
# Không gộp vào DB `sentinel` vì cả hai đều có bảng `incidents`.
set -e

mysql --protocol=socket -uroot -p"${MYSQL_ROOT_PASSWORD}" <<-EOSQL
    CREATE DATABASE IF NOT EXISTS \`ai_fix_orchestrator\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, REFERENCES ON \`ai_fix_orchestrator\`.* TO '${MYSQL_USER}'@'%';
    FLUSH PRIVILEGES;
EOSQL
