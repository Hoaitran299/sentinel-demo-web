# Kiến trúc AI Auto-Fix hiện tại

## 1. Quyết định kiến trúc

Hệ thống dùng hai project tách biệt:

| Project | Vai trò |
| --- | --- |
| `sentinel-demo-app` | Laravel application/data plane; phục vụ web và ghi log |
| `sentinel-agent` | Python control plane; observe, threshold, agent, Git, verification, PR và dashboard |

Kiến trúc cũ đặt incident tables, database queue và agent coordinator trong Laravel đã bị
thay thế. Redis không được sử dụng. Database MySQL riêng của backend
(`ai_fix_orchestrator`, user riêng) là source of truth cho cursor, incident, occurrence,
run và timeline state; Laravel không đọc/ghi database này.

## 2. Runtime flow

```text
Browser
  -> Laravel PATCH /profile
  -> MySQL rejects phone = NULL
  -> Laravel writes storage/logs/laravel.log
  -> Python LaravelLogTailer (read-only)
  -> sanitize + fingerprint + MySQL threshold 3/600s
  -> fetch GitHub master into refs/ai-fix/base/master
  -> isolated Git worktree + independent dependencies
  -> capture test/protected-path baseline
  -> Claude restricted read-only analysis
  -> schema/path/prompt-injection guard
  -> Claude Edit/Write in worktree only; no Bash/Web/GitHub token
  -> diff/test-integrity guard
  -> Pint + PHPUnit + npm build + git diff --check
  -> commit exact verified tree
  -> trusted publisher push + create/reuse draft PR
  -> human review

Backend dashboard (loopback) đọc cùng MySQL state để hiển thị tiến trình và retry.
```

## 3. Ownership

Laravel owns:

- authentication, `/profile` UI và business validation;
- application database schema (`users.phone NOT NULL`);
- ghi exception vào `storage/logs/laravel.log` theo format chuẩn;
- test suite, `composer.lock` và `package-lock.json` để worktree bên ngoài verify.

Backend owns:

- log byte cursor and rotation handling;
- fingerprint/window/threshold/dedupe;
- Claude CLI lifecycle;
- managed worktree and dependency bootstrap;
- security policy and verification;
- local/remote fix branch, report and PR state;
- MySQL state store riêng và dashboard theo dõi/retry.

## 4. Source version

Khi PR publishing bật, backend không tạo branch từ local checkout. Trusted publisher fetch
GitHub `master` qua credential riêng vào `refs/ai-fix/base/master`, lấy exact SHA rồi tạo
worktree. Vì vậy local `master` có thể ahead/behind mà không làm PR chứa commit ngoài ý
muốn. Fetch không checkout/reset Laravel working tree.

Incident ghi lại `source_commit` lúc quan sát. Nếu base đổi trước khi agent chạy, run dừng
an toàn thay vì sửa trên code khác với code đã sinh lỗi.

## 5. State và concurrency

- Threshold/dedupe/claim chạy trong transaction được tuần tự hóa bằng MySQL `GET_LOCK`.
- Lần thứ ba trong cửa sổ 600 giây chuyển incident sang `queued`; lần thứ tư chỉ tăng
  occurrence, không tạo run mới.
- `claim()` chuyển `queued -> running` nguyên tử, nên watcher, CLI `retry` hay dashboard
  Retry không thể chạy trùng một incident.
- Mỗi stage được ghi vào `run_events` để dashboard dựng timeline.

Trạng thái incident:

```text
accumulating -> queued -> running -> waiting_for_review
                              \-> failed -> (retry) queued
```

## 6. Isolation

Worktree được tạo trong managed root của backend, không nằm trong Laravel checkout. Nó
bootstrap `vendor`/`node_modules` riêng từ lock files và dùng environment test-only
(SQLite in-memory, array cache/session/mail). Không copy/symlink Laravel `.env`. Sau run,
worktree bị dọn nhưng branch `ai-fix/...` và report được giữ lại.

Chi tiết các lớp bảo vệ xem [agent-safe-execution-boundary.md](agent-safe-execution-boundary.md).

## 7. Publish/PR

Claude không nhận GitHub credential. Trusted publisher chỉ chạy sau verification, kiểm tra
parent commit bằng remote base SHA, push qua clean HTTPS URL + temporary `GIT_ASKPASS`,
tìm PR mở theo `head + base`, rồi reuse hoặc tạo draft PR. Không auto-merge/deploy.
