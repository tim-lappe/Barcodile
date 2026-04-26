<div align="center">

<img src="frontend/public/logo.png" alt="Barcodile" width="200" />

# Barcodile

**Catalog, inventory, carts, and scanner devices** — a web app with a Symfony API and a React admin UI, with optional Picnic integration. Item images are stored in the database (PostgreSQL).

</div>

---

## Self-hosting with Docker

You need [Docker](https://docs.docker.com/get-docker/) and [Docker Compose](https://docs.docker.com/compose/) on your machine. No separate database install is required: one image includes PostgreSQL, the app, and the web server.

**1. Create a folder** for the configuration files. Compose will download the image when you start the stack. If the download is denied, sign in to [GitHub Container Registry](https://docs.github.com/en/packages/working-with-a-github-packages-registry/working-with-the-container-registry) — for a public package you usually do not need to sign in.

```bash
mkdir barcodile && cd barcodile
```

**2. Set secrets and URLs.** The app must have a long random `APP_SECRET`, strong database credentials, a `DATABASE_URL` that uses the same user and password, and a browser origin rule that matches the address you will type in the web browser. Replace the example host and password with your own. Save the file as `.env` in the same folder as `docker-compose.yml` (and keep that file private; do not post it online):

```bash
APP_SECRET=at-least-32-random-characters
POSTGRES_USER=barcodile
POSTGRES_PASSWORD=choose-a-strong-password
POSTGRES_DB=barcodile
DATABASE_URL=postgresql://barcodile:choose-a-strong-password@127.0.0.1:5432/barcodile?serverVersion=16&charset=utf8
DEFAULT_URI=https://barcodile.example.com
CORS_ALLOW_ORIGIN=^https://barcodile\.example\.com$
```

`DEFAULT_URI` should be the URL people use to open the app. `CORS_ALLOW_ORIGIN` is a [regular expression](https://www.php.net/manual/en/reference.pcre.pattern.syntax.php) for allowed browser origins; at minimum it must cover that same address.

**3. Create `docker-compose.yml`** (update the image name to match the package published for this project — on GitHub: **Packages** for the repository, usually `ghcr.io/tim-lappe/barcodile` in lowercase):

```yaml
services:
  app:
    image: ghcr.io/tim-lappe/barcodile:latest
    restart: unless-stopped
    ports:
      - "8080:8000"
      - "5173:5173"
      - "5432:5432"
    env_file: .env
    environment:
      APP_ENV: dev
      APP_DEBUG: "1"
    privileged: true
    volumes:
      - barcodile_pgdata:/var/lib/postgresql/data
      - barcodile_var:/var/www/html/var
      - /dev/input:/dev/input

volumes:
  barcodile_pgdata:
  barcodile_var:
```

**4. Start the stack**

```bash
docker compose up -d
```

Open `http://localhost:5173` (or your server’s hostname and port) in a browser. The first start can take a minute while the database and application start.

`5432` is exposed for backups or external tools; you can remove the `ports` line for it if you do not need that. The `/dev/input` mount and `privileged: true` are for USB barcode scanners on Linux; if you do not use that feature, you can try omitting `privileged` and the `/dev/input` volume (some hosts require these for input devices to work inside the container).

**Optional: Picnic grocery integration** — set `PICNIC_COUNTRY`, `PICNIC_API_VERSION`, `PICNIC_URL`, and `PICNIC_AUTH_KEY` in the same `.env` file if you use that integration.

---

## Build the image from source

If you prefer to build locally instead of pulling from the registry, clone the repository, replace the `image:` line in the compose file with a `build` section pointing at the repo root, and run `docker compose up -d --build` from that directory.

For local builds, the repository’s own `docker-compose.yml` uses the same supervisor layout while building the image from source.
