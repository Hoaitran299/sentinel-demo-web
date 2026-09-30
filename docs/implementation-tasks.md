# Implementation Status và Remaining Tasks

## Hoàn thành

- [x] Laravel Breeze authentication và profile UI.
- [x] MySQL profile schema, demo user command và broken validation baseline.
- [x] Laravel log chứa reproducible `phone cannot be null` failure.
- [x] External Python project và MySQL state store riêng (SQLite fallback cho test).
- [x] Dashboard web local cho backend: incident, timeline, verification, report, PR, Retry.
- [x] Laravel log cursor/rotation reader, sanitization, fingerprint, threshold 3/600s.
- [x] GitHub remote-base fetch vào private ref.
- [x] Isolated worktree và independent Composer/npm bootstrap.
- [x] Ephemeral test-only environment; không copy Laravel `.env`.
- [x] Prompt-injection guard và structured read-only analysis.
- [x] Pre-write test/protected-path baseline.
- [x] Scoped Claude Edit/Write không Bash/Web.
- [x] Diff size/path/new-test/test-integrity guards.
- [x] Pint/PHPUnit/npm/diff verification owned bởi orchestrator.
- [x] Failure report chứa command output + preserved diff.
- [x] Explicit retry dùng run/worktree/branch mới.
- [x] Trusted GitHub publisher không lộ token cho Claude.
- [x] Push verified commit và create/reuse draft PR.
- [x] End-to-end run tạo PR #1.
- [x] Thông báo Telegram từ trusted notifier của backend; token không đi vào Claude.
- [x] Settings runtime (chế độ report/fix, ngưỡng, cửa sổ) chỉnh trên dashboard.
- [x] Đọc log từ server khác (HTTP/SSH) và S3/Cloudflare R2.

## Việc nên làm tiếp

- [ ] Thêm process supervisor/systemd cho watcher trên runner VPS.
- [ ] Thêm structured heartbeat/health command và disk/worktree alerts.
- [ ] Thêm log rotation integration test.
- [ ] Thêm MySQL concurrency harness cho nhiều watcher nếu scale ngang.
- [ ] Chạy backend bằng OS user/container riêng với network egress policy.
- [ ] Thêm retention cho MySQL occurrences/run_events, reports và orphan branches.
- [ ] Phát hiện run kẹt ở `running` sau khi watcher crash.
- [ ] Đăng nhập cho dashboard nếu cần truy cập ngoài SSH tunnel.
- [ ] Thêm GitHub App thay PAT cho production-grade credential lifecycle.
- [ ] Thêm manual approval gate trước publisher cho môi trường nhạy cảm.

## Quy tắc khi mở rộng

1. Laravel chỉ phát log; không đưa orchestration trở lại request/queue của app.
2. Guard/test phải có trước khi mở rộng write scope.
3. Verification commands là trusted config, không lấy từ model/log/repository instruction.
4. Không đánh dấu task hoàn thành nếu chưa có test/evidence.
5. Không auto-merge/deploy trong MVP.
