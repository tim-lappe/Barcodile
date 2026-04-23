<div align="center">

<img src="frontend/public/logo.png" alt="Barcodile" width="200" />

# Barcodile

**Catalog, inventory, carts, and scanner devices** — Symfony API plus a React admin UI, with optional Picnic integration and local filesystem storage for catalog images.

</div>

---

## Prerequisites

| Tool | Version | Notes |
|------|---------|--------|
| [PHP](https://www.php.net/) | **8.5+** | Extensions: `bcmath`, `ctype`, `iconv` |
| [Composer](https://getcomposer.org/) | 2.x | Backend dependencies |
| [Node.js](https://nodejs.org/) | **22** (recommended) | Frontend; LTS 20+ usually works |
| [Docker](https://docs.docker.com/get-docker/) | recent | Optional; full stack |

---

## Option A — Docker Compose (recommended)

Runs backend and frontend dev server. Catalog images are stored under `backend/var/storage` (bind-mounted with the backend).

### 1. Backend environment

Create `backend/.env.local` (this file is not committed). Symfony needs at least a database URL, app secret, and CORS pattern for the Vite origin:

```bash
# backend/.env.local
APP_SECRET=replace-with-a-long-random-string-at-least-32-chars
DATABASE_URL="sqlite:///%kernel.project_dir%/var/dev.db"
CORS_ALLOW_ORIGIN=^https?://localhost(:[0-9]+)?$
```

`docker-compose.yml` sets `FILE_STORAGE_ROOT` and `FILE_STORAGE_BUCKET` for the backend container.

### 2. Start services

From the repository root:

```bash
docker compose up --build
```

### 3. Run migrations

In another terminal:

```bash
docker compose exec backend php bin/console doctrine:migrations:migrate --no-interaction
```

### 4. Open the app

| Service | URL |
|---------|-----|
| Frontend (Vite) | [http://localhost:5173](http://localhost:5173) |
| Backend (PHP built-in server) | [http://localhost:8000](http://localhost:8000) |

The frontend proxies `/api`, `/bundles`, and Symfony profiler paths to the backend (see `frontend/vite.config.ts`).

### Shell inside backend container

```bash
./bin/docker.sh
```

---

## Option B — Local PHP + Node (no Docker)

Use this when you prefer native tooling. Image storage uses `var/storage` under the backend project by default (see `config/services.yaml`).

### Backend

```bash
cd backend
composer install
```

Create `backend/.env.local` as in Option A. Override paths only if needed:

```bash
FILE_STORAGE_ROOT=/absolute/path/to/storage
FILE_STORAGE_BUCKET=barcodile
```

Apply schema:

```bash
php bin/console doctrine:migrations:migrate --no-interaction
php -S 127.0.0.1:8000 -t public
```

### Frontend

```bash
cd frontend
npm install
npm run dev
```

Vite defaults to proxying the API to `http://127.0.0.1:8000`. Override with `DEV_PROXY_TARGET` if your backend listens elsewhere.

### Convenience script

From the repo root, `bin/dev.sh` starts the PHP server on port 8000 and then `npm run dev` in `frontend` (database must already be configured).

---

## Environment reference

Variables with defaults in `backend/config/services.yaml` can be omitted unless you want to override Picnic defaults.

| Variable | Purpose |
|----------|---------|
| `APP_SECRET` | Symfony secret (encryption, CSRF); required |
| `DATABASE_URL` | Doctrine connection (e.g. SQLite or PostgreSQL) |
| `CORS_ALLOW_ORIGIN` | Regex allowed origins for the browser UI |
| `FILE_STORAGE_ROOT` | Base directory for catalog images (default: `var/storage` under the backend project) |
| `FILE_STORAGE_BUCKET` | Subdirectory name under `FILE_STORAGE_ROOT` (default: `barcodile`) |
| `PICNIC_COUNTRY`, `PICNIC_API_VERSION`, `PICNIC_URL`, `PICNIC_AUTH_KEY` | Picnic grocery integration (optional) |

Existing image blobs are not migrated automatically if you change storage backends; copy files manually or re-upload.

---

## Quality checks (backend)

```bash
cd backend
composer qa
```

---

## Production-shaped stack

See `docker-compose.prod.yaml` and `Dockerfile.prod` for a consolidated production image and environment variables such as `DEFAULT_URI` and `DATABASE_URL`. Production persists uploads on the `barcodile_var` volume under `/var/www/html/var` by default (`FILE_STORAGE_ROOT`).

---

## Scanner tooling (Linux)

Console commands under `backend/src/Command/` that interact with input devices expect a Linux environment with evdev access (not available inside default macOS or generic Docker desktop setups). Run them on the host or a VM with the appropriate device permissions.
