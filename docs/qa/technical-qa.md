# Technical Q&A

### Hệ thống giải quyết gì?

Nó tự động hóa từ một lỗi lặp lại trong Laravel log đến verified draft PR. Human vẫn review,
merge và deploy.

### Vì sao dùng hai project?

Để web app không mang Claude/Git/GitHub credential hoặc long-running process. Laravel là
data plane; Python backend là control plane có lifecycle và quyền riêng.

### Laravel có gửi incident sang backend không?

Không. Backend tail file log read-only bằng inode + byte offset. Vì vậy app không cần HTTP
callback, Redis, queue job hay agent table cho active flow.

### Source of truth là gì?

- Laravel log: raw observation stream.
- Backend MySQL `ai_fix_orchestrator` (database riêng, không phải database Laravel):
  cursor/incident/occurrence/run/timeline state.
- Git commit: code version.
- Verification record: bằng chứng exact tree pass.
- GitHub PR: review surface.

### Vì sao chuyển từ SQLite sang MySQL?

Có thêm dashboard đọc state song song với watcher, và runner/dashboard có thể nằm khác
process/host. MySQL cho concurrent access ổn định, backup bằng `mysqldump` và dùng chung
hạ tầng MySQL sẵn có. Threshold/dedupe/claim vẫn nguyên tử nhờ transaction + named lock
`GET_LOCK`. Database/user tách khỏi Laravel nên app không biết đến các bảng agent.
SQLite chỉ còn làm fallback cho unit test; state cũ chuyển sang bằng `make import-sqlite`.

### Có UI để theo dõi không?

Có. `make ui` trong backend mở dashboard local (http://localhost:8787): danh sách incident,
stepper tiến trình, timeline stage, phân tích Claude, kết quả verification, report, link PR
và nút Retry cho incident failed. Dashboard chỉ bind loopback và không giữ quyền
Claude/GitHub; Retry chỉ đưa incident vào hàng đợi để watcher chạy.

### Agent có thể bị prompt injection từ log/repo không?

Log và repository đều untrusted. Analysis bị restricted read-only, output dùng schema,
proposed path phải qua policy; write chỉ mở sau baseline guard. Bash/Web/MCP bị tắt.

### Agent có sửa test để làm pipeline pass không?

Không. Backend hash mọi tracked test trước write và từ chối mọi thay đổi/xóa existing
test. Fix bắt buộc tạo regression test mới.

### Vì sao phải đợi ba lần lỗi?

Một lỗi đơn lẻ có thể do thao tác bất thường. Ba occurrence cùng fingerprint trong 600 giây
cho thấy lỗi tái hiện được, đồng thời tránh tốn chi phí agent cho nhiễu.

### Fingerprint được tạo thế nào?

SHA-256 của message đã chuẩn hóa (bỏ context JSON, gộp mã `SQLSTATE[...]`, thay số bằng
`?`) cộng top application frame (`app/...php:line`). Giá trị động như ID, số điện thoại
hay timestamp không ảnh hưởng, nên các lần lỗi giống nhau gom vào một incident.

### Làm sao bảo đảm không lộ secret/PII?

Sanitizer che password/token/cookie/API key, email, số điện thoại, home path và SQL
bindings trước khi lưu hoặc gửi cho Claude. Report và failure output cũng đi qua sanitizer.

### Hai lỗi đồng thời có tạo hai run không?

Không. Ghi nhận occurrence và chuyển `queued` chạy trong transaction tuần tự hóa; `claim()`
chuyển `queued -> running` nguyên tử nên chỉ một process được chạy incident.

### Git worktree đã đủ sandbox chưa?

Chưa. Worktree tách code và dependency, Claude restricted mode giới hạn tool, nhưng đây là
defense in depth. Production nên thêm OS user/container, network egress policy và
resource limits.

### Agent có GitHub token không?

Không. Chỉ trusted publisher trong backend đọc token từ `.env` backend, push qua
temporary `GIT_ASKPASS`; token không có trong prompt, argv, remote URL hay report.

### Nếu verification fail thì sao?

Không commit, không push. Incident chuyển `failed`, failure report giữ command output và
diff. Sau khi xử lý nguyên nhân, retry bằng dashboard hoặc CLI; retry tạo branch mới.

### Local `master` khác GitHub thì PR có bị lẫn commit không?

Không. Khi publish bật, worktree được tạo từ GitHub `master` fetch vào private ref, và
publisher kiểm tra parent của verified commit bằng remote base SHA trước khi push.

### Lỗi vẫn lặp lại trong lúc PR chưa merge, agent có fix lại không?

Không. Khi một loại lỗi (fingerprint) đã có fix đang chờ review, đã merge nhưng có thể chưa
deploy, hoặc PR bị đóng, các lần lỗi mới chỉ được gắn vào incident đó và đếm là "lặp lại
sau fix"; backend không chạy Claude hay tạo PR mới. Watcher tự đồng bộ trạng thái PR từ
GitHub. Người vận hành mở lại bằng nút "Cho phép fix lại" trên dashboard hoặc lệnh
`release` khi fix đã deploy mà lỗi vẫn còn.

### Có thể chỉ báo cáo lỗi mà không để AI tự sửa không?

Có. Dashboard backend có mục **Cài đặt** lưu trong bảng `settings`: chế độ
`report_and_fix` (báo Telegram + tự fix, mặc định), `fix_only` (tự fix, không báo) hoặc
`report_only` (chỉ báo; người vận hành bấm "Chạy AI fix" khi muốn), cùng số lần lỗi và cửa
sổ thời gian. Watcher áp dụng ngay, không cần restart.

### Log không nằm cùng máy với backend thì sao?

Backend đọc được log qua `AI_FIX_LOG_SOURCE`: file local, HTTP Range (nginx có auth), SSH,
hoặc S3-compatible storage (AWS S3, Cloudflare R2, MinIO). App Laravel không cần thay đổi;
chỉ cần expose/đẩy file log. Chi tiết trong `docs/setup/log-sources.md` của `sentinel-agent`.

### Có thông báo khi agent chạy không?

Có. Backend gửi Telegram khi lỗi đạt ngưỡng và agent bắt đầu, khi fix pass verification
(kèm link PR), khi run dừng vì lỗi, và khi PR được merge hoặc bị đóng. Notifier chạy trong
watcher của backend, không nằm trong Laravel; bot token chỉ ở `.env` backend và không bao
giờ được đưa cho Claude. Xem `docs/setup/telegram.md` trong `sentinel-agent`.

### Agent có tự merge/deploy không?

Không. Kết quả cuối là draft PR ở trạng thái chờ review.

### Hệ thống đã chạy end-to-end thật chưa?

Có. Run verified tạo commit `d698726...` và
[PR #1](https://github.com/nguyenthanhthuc2000/demo-agent-ai-auto-fix-bug/pull/1).
