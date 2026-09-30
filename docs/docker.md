# Chạy hai project bằng Docker

`compose.yaml` ở project này chạy cả Laravel app lẫn companion orchestrator
(`../sentinel-agent`, đổi bằng `AI_FIX_BACKEND_PATH`).

| Service     | Nội dung                                                   | Truy cập                 |
|-------------|------------------------------------------------------------|--------------------------|
| `mysql`     | MySQL 8.4: DB `sentinel` (Laravel) + DB `ai_fix_orchestrator` (orchestrator), user/pass `sentinel`/`sentinel` | `127.0.0.1:3307` |
| `app`       | PHP 8.3 built-in server, tự `composer install`, `npm ci/build`, `migrate` | http://localhost:8000 |
| `watcher`   | `autofix_agent watch` — đọc `storage/logs/laravel.log`, tạo worktree, gọi Claude, verify, commit/PR | — |
| `dashboard` | `autofix_agent ui`                                          | http://localhost:8787    |

Image orchestrator có đủ toolchain để bootstrap/verify worktree Laravel: PHP 8.3 + Composer,
Node 22, Git và Claude Code CLI. Cả hai source đều bind-mount nên sửa code ở host có hiệu
lực ngay (restart `watcher`/`dashboard` sau khi sửa Python).

## Khởi động

Yêu cầu duy nhất ở host: Docker + Docker Compose. Hai repo phải nằm cạnh nhau:

```text
Projects/
├── sentinel-demo-app/   # repo này (chứa compose.yaml)
└── sentinel-agent/      # orchestrator Python
```

```bash
cd sentinel-demo-app
cp ../sentinel-agent/.env.example ../sentinel-agent/.env
chmod 600 ../sentinel-agent/.env                  # điền GitHub/Telegram token nếu cần

docker compose up -d --build

# Lần đầu: đăng nhập Claude Code trong container (credentials lưu ở volume orchestrator-home)
docker compose run --rm watcher claude
# gõ /login, hoàn tất, rồi /exit

docker compose exec watcher python -m autofix_agent preflight --config config/docker.json
docker compose exec app php artisan demo:user --email=demo@example.com
docker compose logs -f watcher
```

Container `app` tự tạo `.env` từ `.env.example` + `APP_KEY` nếu chưa có, rồi chạy `composer install`,
`npm ci/build` và `migrate`.

Dừng các process chạy tay ở host (`php artisan serve`, `make watch`, `make ui`) trước: hai
watcher cùng đọc một log sẽ xử lý trùng incident.

## Database

| Database              | Dùng bởi              | User       | Password   |
|-----------------------|-----------------------|------------|------------|
| `sentinel`            | Laravel (`app`)       | `sentinel` | `sentinel` |
| `ai_fix_orchestrator` | `watcher`, `dashboard`| `sentinel` | `sentinel` |

Orchestrator dùng database riêng vì Laravel cũng có bảng `incidents`. Kết nối từ host (TablePlus,
`mysql` CLI...): `mysql -h127.0.0.1 -P3307 -usentinel -psentinel sentinel`.

User và database chỉ được tạo khi volume `mysql-data` khởi tạo lần đầu. Nếu trước đó đã chạy
với user cũ (`laravel`/`aifix`), reset volume để áp dụng:

```bash
docker compose down -v
docker compose up -d --build
```

## Cấu hình

- Orchestrator dùng `config/docker.json` (path trong container) và vẫn đọc `.env` của
  backend cho GitHub/Telegram token. `AI_FIX_DATABASE_URL` do compose đặt, ghi đè `.env`.
- Laravel `.env` vẫn dùng; `DB_*` do compose đặt, ghi đè giá trị trong `.env`.
- Đổi root password/port bằng biến `DOCKER_MYSQL_ROOT_PASSWORD`, `DOCKER_MYSQL_PORT`,
  `DOCKER_APP_PORT`. Root password chỉ áp dụng khi volume `mysql-data` được tạo lần đầu.
- Dashboard bind `0.0.0.0` trong container (`AI_FIX_UI_BIND_HOST`) nhưng chỉ publish ra
  `127.0.0.1:8787`; Host header vẫn phải là loopback nên port host phải giữ 8787.
- Worktree nằm ở volume `orchestrator-worktrees`; branch `ai-fix/...` được tạo trong
  checkout Laravel của host, report ở `../sentinel-agent/var/reports`.

## Lưu ý

- Không dùng `php artisan serve` trong container: lệnh này xoá env của container (`DB_HOST`...)
  khỏi process con nên Laravel quay lại dùng `127.0.0.1` từ `.env`.
- `public/hot` (do `npm run dev` ở host để lại) làm Laravel load asset từ Vite dev server;
  xoá file này nếu không chạy Vite.
- `node_modules` của app nằm trong volume `app-node-modules` vì binary native của macOS
  không chạy trên Linux. `vendor/` dùng chung với host.
- Lần boot đầu, watcher và dashboard cùng migrate DB orchestrator; service thua có thể log
  `Duplicate column name` rồi tự restart thành công.
- Reset toàn bộ dữ liệu Docker: `docker compose down -v`.
