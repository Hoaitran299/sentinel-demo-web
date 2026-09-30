# Laravel AI Auto-Fix Demo — Application

Đây là ứng dụng Laravel dùng để tái hiện lỗi profile và phát log cho một orchestrator AI
độc lập. Coding agent **không chạy trong Laravel**, không chạy trong HTTP request và không
sửa checkout đang phục vụ ứng dụng.

Companion project:

```text
/Users/wake/Documents/Projects/demo-agent-ai-auto-fix-bug-backend
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

## Cài đặt local

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm ci
npm run build
```

Cấu hình MySQL trong `.env`, giữ:

```env
INCIDENT_RECORDING_ENABLED=false
AUTH_REGISTRATION_ENABLED=false
```

Tạo/cập nhật demo user bằng prompt password ẩn:

```bash
php artisan demo:user --email=demo@example.com
```

Chạy ứng dụng:

```bash
php artisan serve
```

Khi phát triển frontend có thể chạy thêm `npm run dev`.

## Chạy demo incident → PR

1. Start Laravel project này.
2. Start `make watch` và `make ui` trong companion backend.
3. Đăng nhập, mở `/profile`, xóa `phone` và submit ba lần trong 600 giây.
4. Theo dõi các stage `OBSERVE → ANALYZE → GUARD → FIX → VERIFY → GIT → DONE` ở terminal
   backend hoặc dashboard http://localhost:8787.
5. Review draft PR; agent không tự merge hoặc deploy.

Run thực tế đã được chứng minh bằng PR đầu tiên:
[demo-agent-ai-auto-fix-bug#1](https://github.com/nguyenthanhthuc2000/demo-agent-ai-auto-fix-bug/pull/1).

## Verification

```bash
vendor/bin/pint --dirty --format agent
php artisan test --compact
npm run build
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
