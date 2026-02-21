# SGA WordPress

Public website for Saving Great Animals, a Seattle-based dog rescue.

## Project Context
- **Bedrock** (Roots) — WordPress managed via Composer
- **Docker Compose** — local dev environment
- **Gutenverse** — FSE block library for the theme
- **Target theme** — Veterna FSE (not yet purchased; using Twenty Twenty-Five as placeholder)
- **Companion repo** — github.com/dsayed/sga-community (community app, docs, prototypes)

## Key Commands
- `docker compose up -d --build` — start local environment
- `docker compose down -v` — tear down (including database)
- `docker compose run --rm wpcli <command> --path=/var/www/html/web/wp` — run WP-CLI
- `docker run --rm -v $(pwd):/app -w /app composer <command>` — run Composer

## File Structure
- `web/app/themes/` — themes (Composer-managed)
- `web/app/plugins/` — plugins (Composer-managed)
- `config/application.php` — WordPress config (reads from .env)
- `scripts/setup.sh` — bootstraps fresh install
- `.env` — local environment variables (not committed)

## Git
- Repo-local identity: dsayed (not bal-spec)
- Remote uses PAT in URL for auth
