# SGA WordPress

Public website for Saving Great Animals, a Seattle-based dog rescue.

## Project Context
- **Bedrock** (Roots) — WordPress managed via Composer
- **Docker Compose** — local dev environment
- **Theme** — SGA child theme of Twenty Twenty-Five (custom theme.json, block patterns, header/footer)
- **MU-plugins** — sga-foster-dogs.php (Foster Dog CPT), sga-rescuegroups/ (RescueGroups API)
- **Shortcodes** — [available_dogs], [dog_count], [foster_dogs]
- **Companion repo** — github.com/dsayed/sga-community (community app, docs, prototypes)

## Key Commands
- `docker compose up -d --build` — start local environment
- `docker compose down -v` — tear down (wipes database, auto-restored from seed on next start)
- `docker compose run --rm wpcli <command> --path=/var/www/html/web/wp` — run WP-CLI
- `docker run --rm -v $(pwd):/app -w /app composer <command>` — run Composer

## File Structure
- `web/app/themes/` — themes (Composer-managed)
- `web/app/plugins/` — plugins (Composer-managed)
- `config/application.php` — WordPress config (reads from .env)
- `scripts/seed.sql` — database seed (auto-imported on fresh start, version-controlled)
- `scripts/setup.sh` — bootstraps fresh install (manual alternative to seed)
- `.env` — local environment variables (not committed)

## Railway Staging
- **URL**: https://sga-wordpress-production.up.railway.app
- **Admin**: https://sga-wordpress-production.up.railway.app/wp/wp-admin/
- **Platform**: Railway (Hobby plan, $5/month)
- **Services**: WordPress (PHP 8.2 Apache from Dockerfile) + MySQL 8.0
- **Deploy**: push to main → Railway auto-deploy (~1-2 min)
- **Volume**: `web/app/uploads/` (persistent across deploys)
- **Secrets**: stored in 1Password + Railway dashboard environment variables
- **Environment banner**: `web/app/mu-plugins/staging-notice.php` — orange on staging, blue on development
- **First boot**: `docker-entrypoint.sh` auto-installs WordPress, activates theme/plugins, creates pages and editor accounts

<details>
<summary>Azure (deprecated — stopped)</summary>

- **Subscription**: b5c4e6b0-e93e-47b8-ab37-1197d84b0064
- **Resource group**: rg-sga (westus2)
- **App Service**: sga-wordpress-staging (Central US, B1 Linux, PHP 8.2 + nginx)
- **MySQL**: mysql-sga-test.mysql.database.azure.com (West US 3, B1s Burstable)
- **Infrastructure**: `infrastructure/main.bicep` (Bicep IaC)
- **Cost was**: ~$33/month (B1 App Service + B1s MySQL)
- **nginx**: custom `nginx.conf` in repo root (Azure-specific, not used by Railway)

</details>

## Local Development
- **Site**: http://localhost:8080
- **Admin**: http://localhost:8080/wp/wp-admin/ (admin / admin)
- **Stack**: Docker Compose → PHP 8.2 (Apache) + MySQL 8.0
- **Database seed**: `scripts/seed.sql` auto-imported on fresh start — no manual setup needed
- **Save content changes**: `docker compose exec db mysqldump -usga -psga_pass sga_wordpress > scripts/seed.sql`
- Local and Railway have separate databases — content does not sync

## Git
- Repo-local identity: dsayed
- Remote uses PAT in URL for auth
