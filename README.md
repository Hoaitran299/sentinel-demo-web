# Laravel AI Auto-Fix Demo — Application

Đây là ứng dụng Laravel dùng để tái hiện lỗi profile và phát log cho một orchestrator AI
độc lập. Coding agent **không chạy trong Laravel**, không chạy trong HTTP request và không
sửa checkout đang phục vụ ứng dụng.

Companion project:

```text
../sentinel-agent
```

Backend Python theo dõi `storage/logs/laravel.log`, gom lỗi theo threshold, chạy Claude
Code trong isolated worktree, verify, commit, push branch và tạo draft Pull Request. State
của backend nằm trong database MySQL riêng (`ai_fix_orchestrator`), tách khỏi database
của app này, và có dashboard tại http://localhost:8787.

## Trách nhiệm của project này

- Authentication Laravel Breeze và trang `/profile`.
- MySQL schema có `users.phone` là `NOT NULL`.
- Broken baseline cố ý thiếu backend validation cho `phone`.
- Ghi exception vào Laravel log chuẩn.
- Cung cấp test suite và lock files để worktree bên ngoài bootstrap/verify.

Project này không sở hữu threshold, agent state, Claude, Git worktree hoặc GitHub token.
Các artifact `IncidentRecorder` cũ vẫn có trong checkout local hiện tại nhưng bị tắt mặc
định bằng `INCIDENT_RECORDING_ENABLED=false`; active demo flow chỉ đọc Laravel log từ
project backend và không phụ thuộc các artifact này.

## Cài đặt (Docker)

Project chỉ hỗ trợ chạy bằng Docker; `compose.yaml` chạy Laravel, MySQL và orchestrator
`../sentinel-agent` cùng lúc. Chi tiết: [docs/docker.md](docs/docker.md).

```bash
docker compose up -d --build   # lần đầu tự tạo .env từ .env.example + APP_KEY
docker compose run --rm watcher claude             # lần đầu: /login rồi /exit
docker compose exec app php artisan demo:user --email=demo@example.com
```

MySQL: DB `sentinel`, user `sentinel`, password `sentinel` (host `127.0.0.1:3307`).
Giữ trong `.env`:

```env
INCIDENT_RECORDING_ENABLED=false
AUTH_REGISTRATION_ENABLED=false
```

Laravel: http://localhost:8000 — Dashboard: http://localhost:8787

## Chạy demo incident → PR

1. `docker compose up -d` (chạy cả Laravel, watcher và dashboard).
2. Theo dõi watcher: `docker compose logs -f watcher`.
3. Đăng nhập, mở `/profile`, xóa `phone` và submit ba lần trong 600 giây.
4. Theo dõi các stage `OBSERVE → ANALYZE → GUARD → FIX → VERIFY → GIT → DONE` ở terminal
   backend hoặc dashboard http://localhost:8787.
5. Review draft PR; agent không tự merge hoặc deploy.

Run thực tế đã được chứng minh bằng PR đầu tiên:
[demo-agent-ai-auto-fix-bug#1](https://github.com/nguyenthanhthuc2000/demo-agent-ai-auto-fix-bug/pull/1).

## Verification

```bash
docker compose exec app vendor/bin/pint --dirty --format agent
docker compose exec app php artisan test --compact
docker compose exec app npm run build
git diff --check
```

## Tài liệu

- [Đặc tả demo hiện tại](docs/ai-agent-auto-bug-fix-demo.md)
- [Kiến trúc hai project](docs/agent-auto-fix-architecture.md)
- [Security boundary](docs/agent-safe-execution-boundary.md)
- [Checklist triển khai](docs/implementation-tasks.md)
- [Technical Q&A](docs/technical-qa.md)
- [Deploy Ubuntu VPS](docs/deployment-ubuntu-vps.md)
- [Chạy bằng Docker](docs/docker.md)
