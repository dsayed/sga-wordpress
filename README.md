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
- **GitHub Actions** — deployment to Azure (planned)

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

## Related

- [Organizational personas and development approach](https://github.com/dsayed/sga-community/blob/main/docs/plans/2026-02-21-sga-org-personas.md)
- [Design and accessibility assessment](https://github.com/dsayed/sga-community/blob/main/docs/plans/2026-02-17-sga-website-design-assessment.md)
