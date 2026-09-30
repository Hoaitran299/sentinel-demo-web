# Deploy hai project lên Ubuntu VPS

## 1. Topology

Khuyến nghị hai host/user tách biệt:

```text
APP VPS/user
  Nginx -> PHP-FPM -> Laravel -> MySQL
  writes storage/logs/laravel.log

RUNNER VPS/user
  Python watcher + dashboard (127.0.0.1) -> MySQL ai_fix_orchestrator
  Git/Composer/npm/Claude Code
  managed worktrees -> trusted GitHub publisher
```

Nếu khác VPS, backend đọc log từ xa bằng `AI_FIX_LOG_SOURCE=http|ssh|s3` (nginx có auth,
SSH key chỉ đọc, hoặc đẩy log lên S3/Cloudflare R2); xem `docs/log-sources.md` của backend.
Không mount toàn bộ Laravel `.env` hoặc production filesystem vào runner.

## 2. Laravel host

Yêu cầu: Nginx, PHP 8.3+, required extensions, Composer 2, MySQL và release-built assets.

```bash
cd /var/www/demo-agent-ai-auto-fix-bug
composer install --no-dev --prefer-dist --optimize-autoloader
php artisan migrate --force
php artisan optimize
```

Environment quan trọng:

```env
APP_ENV=production
APP_DEBUG=false
AUTH_REGISTRATION_ENABLED=false
INCIDENT_RECORDING_ENABLED=false
LOG_CHANNEL=stack
```

Nginx/PHP-FPM user cần write `storage` và `bootstrap/cache`. Provision demo user bằng
`php artisan demo:user` dưới application user; không hard-code password.

## 3. Runner host

Yêu cầu: Python 3.9+, MySQL 8 (local hoặc managed), Git, PHP/Composer, Node/npm, Claude
Code CLI và đủ disk cho concurrent dependencies/worktrees. Clone cả Laravel repository và
backend dưới dedicated user, ví dụ:

```text
/srv/ai-fix/source/demo-agent-ai-auto-fix-bug
/srv/ai-fix/backend/demo-agent-ai-auto-fix-bug-backend
/srv/ai-fix/logs/laravel.log
```

Sửa `config/demo.json` cho paths thật (`source_repo`, `log_path`, `claude_binary`). Tạo
backend `.env` mode `0600` gồm `AI_FIX_DATABASE_URL` (database/user MySQL riêng, không
phải user Laravel) và publisher token; ưu tiên fine-grained PAT/GitHub App chỉ có
Contents + Pull requests read/write trên đúng repository. Chi tiết tạo database xem
`docs/dashboard.md` trong backend.

Preflight:

```bash
cd /srv/ai-fix/backend/demo-agent-ai-auto-fix-bug-backend
make install
make test
PYTHONPATH=src .venv/bin/python -m autofix_agent preflight --config config/demo.json
```

## 4. systemd watcher

Ví dụ `/etc/systemd/system/ai-fix-watcher.service`:

```ini
[Unit]
Description=AI Fix Log Watcher
After=network-online.target
Wants=network-online.target

[Service]
Type=simple
User=aifix
Group=aifix
WorkingDirectory=/srv/ai-fix/backend/demo-agent-ai-auto-fix-bug-backend
ExecStart=/usr/bin/env PYTHONPATH=src /srv/ai-fix/backend/demo-agent-ai-auto-fix-bug-backend/.venv/bin/python -m autofix_agent watch --config config/demo.json
Restart=on-failure
RestartSec=5
TimeoutStopSec=30
UMask=0077
NoNewPrivileges=true
PrivateTmp=true

[Install]
WantedBy=multi-user.target
```

Sau khi kiểm tra path Python/Claude/Composer/npm dưới user `aifix`:

```bash
sudo systemctl daemon-reload
sudo systemctl enable --now ai-fix-watcher
sudo journalctl -u ai-fix-watcher -f
```

Dashboard chạy thành unit thứ hai giống hệt, chỉ đổi `ExecStart` sang
`... -m autofix_agent ui --config config/demo.json`. Nó chỉ bind `127.0.0.1`; truy cập qua
`ssh -L 8787:127.0.0.1:8787 aifix@runner-host`.

## 5. Security checklist

- Runner user không đọc Laravel production `.env` hoặc MySQL credential của Laravel.
- MySQL user của backend chỉ có quyền trên `ai_fix_orchestrator`.
- Log source đã redact hoặc backend sanitizer được test với format thực tế.
- Backend `.env` mode `0600`; token/database URL không nằm trong systemd unit/argv.
- Managed workspace root không nằm trong web root.
- Branch protection cấm publisher push/merge trực tiếp `master`.
- Disk/time/process limits và outbound network allowlist được cấu hình.
- Dashboard chỉ truy cập qua SSH tunnel; không mở port 8787 ra Internet.
- Report/MySQL state/log retention có lịch cleanup và backup phù hợp.
- Human review vẫn bắt buộc trước merge/deploy.

## 6. Rollback/stop

```bash
sudo systemctl stop ai-fix-watcher
```

Dừng watcher không ảnh hưởng Laravel. Không xóa active worktree thủ công. Failed report,
MySQL state và fix branches được giữ để audit; revoke token nếu nghi ngờ lộ credential.
