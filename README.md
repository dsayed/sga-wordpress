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

## Architecture

- **[Bedrock](https://roots.io/bedrock/)** — WordPress as a Composer-managed project
- **Docker Compose** — local development (PHP 8.2 + MySQL 8.0)
- **Gutenverse** — FSE block library
- **GitHub Actions** — CI/CD deployment to Azure App Service

## Commands

```bash
docker compose up -d             # Start containers
docker compose down              # Stop containers
docker compose down -v           # Stop and delete database
docker compose logs wordpress    # View WordPress logs

# WP-CLI
docker compose run --rm wpcli theme list --path=/var/www/html/web/wp
docker compose run --rm wpcli plugin list --path=/var/www/html/web/wp
```

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
