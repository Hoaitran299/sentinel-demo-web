# Đặc tả demo AI Agent Auto Bug Fix

## Mục tiêu

Chứng minh vertical slice:

```text
3 lỗi profile giống nhau -> Claude phân tích/sửa -> trusted verification -> draft PR
```

AI không merge hoặc deploy. Người vận hành review PR cuối cùng.

## Broken scenario

Broken baseline cố ý đọc raw `phone` trong `ProfileController` trong khi
`ProfileUpdateRequest` chưa validate field này. Middleware đổi empty string thành `null`;
MySQL từ chối vì `users.phone` là `NOT NULL`, tạo `QueryException` và Laravel ghi log.

Thao tác demo:

1. Đăng nhập bằng demo user.
2. Mở `/profile`.
3. Xóa phone rồi submit ba lần trong 600 giây.
4. Laravel tiếp tục là ứng dụng bình thường; Python backend quan sát log từ bên ngoài.

## Expected terminal flow

```text
OBSERVE count=1/3
OBSERVE count=2/3
OBSERVE count=3/3
TRIGGER threshold reached
ANALYZE read-only
GUARD analysis schema/proposed paths
FIX worktree write only
GUARD diff scope/test integrity
VERIFY Pint
VERIFY PHPUnit
VERIFY npm build
VERIFY git diff --check
GIT commit + trusted push
DONE PR URL + report path
```

Cùng các stage này hiển thị trên dashboard backend (http://localhost:8787) dưới dạng
stepper và timeline theo incident.

## Expected fix

- `phone` có rule `required|string|max:20`.
- Controller chỉ fill `$request->validated()`.
- Regression tests mới cho missing, empty và quá 20 ký tự.
- Existing tests không bị sửa.

## Acceptance criteria

- Error được đọc từ log; Laravel không gọi Claude/worktree/GitHub.
- Lần thứ ba trigger đúng một run; lần thứ tư không duplicate active incident.
- Agent analysis read-only trước mọi quyền write.
- Diff chỉ gồm allowlisted source và new tests.
- Full trusted verification pass.
- Main Laravel checkout không đổi.
- Verified commit dựa trên GitHub `master` khi publish mode bật.
- Draft PR được tạo/reuse idempotently.
- Token không xuất hiện trong prompt/argv/report/remote URL.
- Failure lưu report/diff và có thể retry explicit (CLI hoặc dashboard).

## Evidence đã chạy

- Incident `7dfba14a...` đạt threshold 3/3.
- Root cause được Claude xác định đúng.
- Fresh-worktree Vite bootstrap issue được phát hiện và sửa trong orchestrator.
- Verified commit: `d6987267fc11225b4f319aca264bd9a9a772e3f0`.
- Draft PR: [#1](https://github.com/nguyenthanhthuc2000/demo-agent-ai-auto-fix-bug/pull/1).
- Changed files: controller, form request và một new regression test file.

## Non-goals

- Auto merge/deploy/rollback.
- Sửa dependency, CI, authentication hoặc migration tự động.
- Multi-repository orchestration.
- Production credential access cho Claude.
- Redis/Horizon.
