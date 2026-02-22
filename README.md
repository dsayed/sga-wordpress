# SGA WordPress

Public website for [Saving Great Animals](https://savinggreatanimals.org), a dog rescue in Seattle.

## Quick Start

Prerequisites: [Docker Desktop](https://www.docker.com/products/docker-desktop/)

```bash
git clone https://github.com/dsayed/sga-wordpress.git
cd sga-wordpress
cp .env.example .env
docker compose up -d --build
docker compose run --rm -v $(pwd)/scripts:/scripts --entrypoint sh wpcli /scripts/setup.sh
```

- Site: http://localhost:8080
- Admin: http://localhost:8080/wp/wp-admin (admin / admin)

## Local Development

### What the setup does

`docker compose up -d --build` starts three containers:

| Container | Image | Purpose |
|-----------|-------|---------|
| **db** | MySQL 8.0 | WordPress database (`sga_wordpress`) |
| **wordpress** | PHP 8.2 + Apache (custom Dockerfile) | Serves WordPress from Bedrock's `web/` directory |
| **wpcli** | wordpress:cli | WP-CLI commands (runs on demand, not persistent) |

The setup script (`scripts/setup.sh`) then installs WordPress with these defaults:
- Admin credentials: **admin / admin**
- Site title: "Saving Great Animals"
- Theme: Twenty Twenty-Five (placeholder until Veterna FSE is purchased)
- Plugin: Gutenverse (FSE block library)
- Timezone: America/Los_Angeles
- Permalinks: `/%postname%/`

A blue **DEVELOPMENT** banner appears at the top of both the site and admin dashboard.

### Day-to-day commands

```bash
docker compose up -d             # Start containers
docker compose down              # Stop containers (keeps database)
docker compose down -v           # Stop and DELETE database
docker compose logs wordpress    # View WordPress/Apache logs
docker compose logs db           # View MySQL logs
```

### WP-CLI

All WP-CLI commands need `--path=/var/www/html/web/wp` because Bedrock nests WordPress under `web/wp/`:

```bash
docker compose run --rm wpcli theme list --path=/var/www/html/web/wp
docker compose run --rm wpcli plugin list --path=/var/www/html/web/wp
docker compose run --rm wpcli user list --path=/var/www/html/web/wp
```

### Fresh start

To wipe the database and reinstall from scratch:

```bash
docker compose down -v
docker compose up -d --build
docker compose run --rm -v $(pwd)/scripts:/scripts --entrypoint sh wpcli /scripts/setup.sh
```

### File structure

Theme and plugin files live under `web/app/` (not the default `wp-content/`):

```
web/
├── app/
│   ├── themes/          # Themes (Composer-managed)
│   ├── plugins/         # Plugins (Composer-managed)
│   └── mu-plugins/      # Must-use plugins (auto-loaded, no activation needed)
├── wp/                  # WordPress core (Composer-managed, don't edit)
└── index.php            # Front controller
```

Changes to theme/plugin files take effect immediately — no container restart needed.

## Architecture

- **[Bedrock](https://roots.io/bedrock/)** — WordPress as a Composer-managed project
- **Docker Compose** — local development (PHP 8.2 + MySQL 8.0)
- **Gutenverse** — FSE block library
- **GitHub Actions** — CI/CD deployment to Azure App Service

## Azure Staging

Staging site: https://sga-wordpress-staging.azurewebsites.net

### First-time setup

```bash
# Install Azure CLI and login
brew install azure-cli
az login
az account set --subscription b5c4e6b0-e93e-47b8-ab37-1197d84b0064

# Create resource group and deploy infrastructure
az group create --name rg-sga --location westus2
./infrastructure/deploy.sh

# Get publish profile and add as GitHub secret
az webapp deployment list-publishing-profiles --name sga-wordpress-staging --resource-group rg-sga --xml
# Copy output → GitHub repo → Settings → Secrets → AZURE_WEBAPP_PUBLISH_PROFILE
```

### Deploy

Push to `main` — GitHub Actions builds and deploys automatically.

### Save costs

```bash
./scripts/azure-stop.sh    # Stop resources (~$0/month when stopped)
./scripts/azure-start.sh   # Start back up
```

## Related

- [Organizational personas and development approach](https://github.com/dsayed/sga-community/blob/main/docs/plans/2026-02-21-sga-org-personas.md)
- [Design and accessibility assessment](https://github.com/dsayed/sga-community/blob/main/docs/plans/2026-02-17-sga-website-design-assessment.md)
