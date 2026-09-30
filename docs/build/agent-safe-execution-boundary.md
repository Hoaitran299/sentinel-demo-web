# Agent Safe Execution Boundary

## 1. Nguyên tắc

Log, exception message và nội dung repository (comment, README, test) đều là dữ liệu
**không tin cậy** và có thể chứa prompt injection. Vì vậy:

- agent không chạy trong web host, HTTP request hay queue của Laravel;
- quyền write là capability tạm thời, chỉ cấp sau khi các guard đã sẵn sàng;
- verification, commit và publish thuộc orchestrator, không thuộc model;
- không credential nào của Laravel, Telegram hoặc GitHub đi vào process Claude.

## 2. Credential matrix

| Credential | Laravel | Watcher/publisher | Claude process | Dashboard |
| --- | --- | --- | --- | --- |
| Laravel `.env` / DB password | Có | Không | Không | Không |
| `AI_FIX_DATABASE_URL` | Không | Có | Không | Có (chỉ đọc state + requeue) |
| GitHub token | Không | Có (publisher) | Không | Không dùng |
| Claude login | Không | Có (gọi CLI) | Có | Không |

Process Claude và verification chỉ nhận environment allowlist (`HOME`, `PATH`, `TMPDIR`,
locale, `USER`) cộng biến test-only (`APP_ENV=testing`, SQLite in-memory, array
cache/session/mail).

## 3. Gate contracts

### Gate A — Runner preflight

Claude CLI chạy được, source repository và base branch hợp lệ, GitHub API/fetch base khớp
SHA khi publish bật. Fail ở đây thì không tạo worktree.

### Gate B — Workspace readiness

Worktree mới từ exact base SHA trong managed root; bootstrap
`composer install --no-scripts`, `npm ci --ignore-scripts`, baseline `npm run build`.
`.env.testing` tạm dùng key ngẫu nhiên và bị xóa trước khi cấp quyền write. Snapshot hash
của mọi tracked test và protected path được chụp trước khi agent chạy.

### Gate C — Read-only analysis

Claude chạy `--restricted --safe-mode --strict-mcp-config --disable-slash-commands`,
`--permission-mode plan`, chỉ có `Read,Grep,Glob`; Bash/Edit/Write/Web bị cấm. Incident
nằm trong envelope đánh dấu untrusted. Output phải khớp JSON schema (summary, root cause,
proposed files/fix, required tests, risks, confidence).

### Gate D — Write authorization

Mọi `proposed_files` phải nằm trong `allowed_paths` và ngoài `protected_paths`. Chỉ khi đó
mới chạy stage fix với `Read,Grep,Glob,Edit,Write`, vẫn không có Bash/Web.

### Gate E — Diff/test integrity

Sau fix, và lại sau từng verification command (formatter có thể đổi tree):

- có ít nhất một file thay đổi, không vượt `max_changed_files`/`max_diff_lines`;
- không đụng protected path, không ra ngoài writable scope;
- không sửa/xóa existing test (so hash), không rename/copy;
- bắt buộc có regression test mới.

### Gate F — Trusted verification và publication

Orchestrator chạy command cố định từ config: Pint, PHPUnit, `npm run build`,
`git diff --check`. Chỉ khi pass mới commit exact tree; publisher kiểm tra parent bằng
remote base SHA rồi push và tạo/reuse draft PR.

## 4. Failure behavior

Bất kỳ gate nào fail: run dừng, incident chuyển `failed`, failure report lưu command output
đã sanitize và preserved diff trước khi worktree bị dọn. Không có auto-retry; retry phải
explicit (CLI hoặc dashboard) và tạo run/worktree/branch mới.

## 5. Dashboard boundary

Dashboard không có đăng nhập nên chỉ bind loopback, kiểm tra `Host` header, yêu cầu header
riêng + same-origin cho Retry (CSRF), dùng CSP `default-src 'self'` và chỉ đọc report theo
run ID trong `reports_dir`. Retry chỉ đổi `failed -> queued`; watcher mới là process chạy.

## 6. Giới hạn

Git worktree và Claude restricted mode là defense in depth, không tương đương container
hoặc VM. Production-grade runner nên dùng OS user/container riêng, filesystem mount giới
hạn, network egress policy, CPU/RAM/time limits và secret broker.
