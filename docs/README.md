# Tài liệu sentinel-demo-app

`sentinel-demo-app` là Laravel app tái hiện lỗi profile (`users.phone` NOT NULL) và ghi
Laravel log. Orchestrator [`sentinel-agent`](../../sentinel-agent) đọc log đó, gọi Claude
Code sửa lỗi trong worktree riêng và tạo PR. Tài liệu ở đây chia 3 nhóm:

| Thư mục | Dùng khi | Nội dung |
|---------|----------|----------|
| [`setup/`](setup/) | Muốn chạy dự án | Cài đặt và vận hành, **chỉ bằng Docker** |
| [`build/`](build/) | Muốn hiểu/phát triển tiếp dự án | Đặc tả, kiến trúc, security boundary, tiến độ |
| [`qa/`](qa/) | Có câu hỏi "vì sao thiết kế như vậy?" | Hỏi đáp về quyết định kiến trúc |

## Bắt đầu từ đâu

1. **Chạy thử:** [setup/docker.md](setup/docker.md) — `docker compose up -d --build`, đăng
   nhập Claude, tạo demo user, gây lỗi ba lần, xem PR.
2. **Hiểu demo:** [build/ai-agent-auto-bug-fix-demo.md](build/ai-agent-auto-bug-fix-demo.md).
3. **Hiểu kiến trúc:** [build/agent-auto-fix-architecture.md](build/agent-auto-fix-architecture.md)
   rồi [build/agent-safe-execution-boundary.md](build/agent-safe-execution-boundary.md).
4. **Thắc mắc thiết kế:** [qa/technical-qa.md](qa/technical-qa.md).

## setup/ — chạy dự án

- [docker.md](setup/docker.md) — service `mysql`, `app`, `watcher`, `dashboard`; khởi động,
  database (`sentinel` / `ai_fix_orchestrator`, user/pass `sentinel`), cấu hình, lưu ý.

Cấu hình phía orchestrator (GitHub token, Telegram, nguồn log, dashboard, troubleshooting)
nằm ở `../sentinel-agent/docs/setup/`.

## build/ — xây dựng dự án

- [ai-agent-auto-bug-fix-demo.md](build/ai-agent-auto-bug-fix-demo.md) — mục tiêu, broken
  scenario, terminal flow mong đợi, expected fix, acceptance criteria, non-goals.
- [agent-auto-fix-architecture.md](build/agent-auto-fix-architecture.md) — vai trò hai
  project, runtime flow, ownership, source version, state/concurrency, isolation, publish PR.
- [agent-safe-execution-boundary.md](build/agent-safe-execution-boundary.md) — nguyên tắc an
  toàn, credential matrix, các gate từ preflight đến publish.
- [implementation-tasks.md](build/implementation-tasks.md) — việc đã hoàn thành, việc nên
  làm tiếp, quy tắc khi mở rộng.

## qa/ — hỏi đáp kiến trúc

- [technical-qa.md](qa/technical-qa.md) — vì sao hai project, source of truth, MySQL thay
  SQLite, prompt injection, test integrity, threshold, fingerprint, secret/PII, sandbox,
  GitHub token, retry, chế độ chỉ báo cáo, log remote, Telegram, auto-merge.

Câu hỏi riêng về orchestrator: `../sentinel-agent/docs/qa/architecture-qa.md`.

## Thêm tài liệu mới

- Hướng dẫn cài đặt, cấu hình, vận hành → `setup/` (chỉ viết lệnh chạy qua `docker compose`).
- Đặc tả, thiết kế, kế hoạch, tiến độ → `build/`.
- Câu hỏi kiến trúc → thêm vào `qa/technical-qa.md` dạng `### Câu hỏi?` + trả lời ngắn.
- Thêm file mới thì cập nhật danh sách trong README này.
