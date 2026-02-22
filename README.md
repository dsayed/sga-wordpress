# SGA WordPress

Public website for [Saving Great Animals](https://savinggreatanimals.org), a dog rescue in Seattle.

## Quick Start (first time only)

1. Install [Docker Desktop](https://www.docker.com/products/docker-desktop/) if you don't have it
2. Open Docker Desktop and wait for it to finish starting (whale icon in menu bar stops animating)
3. Run these commands in Terminal:

```bash
git clone https://github.com/dsayed/sga-wordpress.git
cd sga-wordpress
cp .env.example .env
docker compose up -d --build
docker compose run --rm -v $(pwd)/scripts:/scripts --entrypoint sh wpcli /scripts/setup.sh
```

4. Open http://localhost:8080 — you should see the site with a blue DEVELOPMENT banner
5. Admin: http://localhost:8080/wp/wp-admin (username: **admin**, password: **admin**)

## Local Development

### Starting your dev session

1. **Open Docker Desktop** (from Applications or Spotlight). Wait for it to finish starting.
2. **Start the containers:**
   ```bash
   cd ~/repos/sga-wordpress
   docker compose up -d
   ```
3. **Open** http://localhost:8080

That's it. Your database and content are still there from last time.

### Stopping your dev session

When you're done working on the WordPress site:

```bash
docker compose down              # Stops containers, keeps your database
```

Then **quit Docker Desktop** (right-click the whale icon in the menu bar → Quit, or Cmd+Q). This frees up ~400-500MB of RAM. You only need Docker Desktop running when working on the WordPress site.

> **Warning:** `docker compose down -v` (with `-v`) **deletes your database**. Only use this if you want a completely fresh start. Without `-v`, your content is safe.

### What's running

`docker compose up -d` starts three containers:

| Container | Image | Purpose |
|-----------|-------|---------|
| **db** | MySQL 8.0 | WordPress database (`sga_wordpress`) |
| **wordpress** | PHP 8.2 + Apache (custom Dockerfile) | Serves WordPress from Bedrock's `web/` directory |
| **wpcli** | wordpress:cli | WP-CLI commands (runs on demand, not persistent) |

The setup script (`scripts/setup.sh`) installs WordPress with these defaults:
- Admin credentials: **admin / admin**
- Site title: "Saving Great Animals"
- Theme: Twenty Twenty-Five
- Timezone: America/Los_Angeles
- Permalinks: `/%postname%/`

A blue **DEVELOPMENT** banner appears at the top of both the site and admin dashboard.

### Viewing logs

```bash
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

### Theme development

The SGA child theme lives in `web/app/themes/sga/`. Changes take effect immediately.

| File | Purpose |
|---|---|
| `theme.json` | Colors, fonts, spacing, layout — the entire brand system |
| `functions.php` | Pattern registration, Events Calendar styling, mobile bar |
| `parts/header.html` | Site header with 5-item nav + Donate button |
| `parts/footer.html` | Site footer with contact, links, social |
| `templates/front-page.html` | Homepage layout (hero + tiles + trust) |
| `templates/page.html` | Generic page layout |
| `patterns/*.php` | Reusable block patterns (hero, cards, CTA, etc.) |

Custom functionality lives in `web/app/mu-plugins/`:

| File | Purpose |
|---|---|
| `sga-foster-dogs.php` | Foster Dog CPT — admin form + `[foster_dogs]` shortcode |
| `sga-rescuegroups/` | RescueGroups API — `[available_dogs]` and `[dog_count]` shortcodes |

## Architecture

```mermaid
flowchart TD
    subgraph local["YOUR MACHINE"]
        subgraph bedrock["sga-wordpress/ — Bedrock project"]
            files["themes, plugins, mu-plugins, composer.json, .env"]
        end
        subgraph docker["Docker Compose"]
            db["db<br/>MySQL 8.0"]
            wp["wordpress<br/>PHP 8.2 + Apache<br/>port 8080"]
            wpcli["wpcli<br/>on demand"]
            wp -->|queries| db
        end
        localurl["localhost:8080<br/>DEVELOPMENT banner — blue"]
        wp --> localurl
    end

    subgraph railway["RAILWAY"]
        rwp["WordPress<br/>PHP 8.2 + Apache<br/>from Dockerfile"]
        rdb["MySQL 8.0<br/>Railway-managed"]
        rwp -->|queries| rdb
        railurl["sga-wordpress-production.up.railway.app<br/>STAGING banner — orange"]
        rwp --> railurl
        vol["Volume<br/>web/app/uploads/"]
        envvars["Environment Variables<br/>DB creds, WP salts, WP_ENV=staging"]
    end

    local -->|"git push to main<br/>auto-deploy ~1-2 min"| rwp
```

### How the pieces fit together

**[Bedrock](https://roots.io/bedrock/)** is a WordPress boilerplate by Roots that treats WordPress like a modern application. Instead of downloading WordPress and dumping plugins into `wp-content/`, Bedrock manages everything through Composer (PHP's package manager). WordPress core, themes, and plugins are all declared as dependencies in `composer.json`. This means the entire stack is version-controlled and reproducible — anyone can clone the repo and get an identical environment.

**Docker Compose** runs the local development environment. It spins up a MySQL database and a PHP web server as isolated containers. Your project files are mounted into the WordPress container, so edits show up instantly without rebuilding. The `wpcli` container exists only to run administrative commands (install plugins, reset passwords, etc.) and exits immediately after each command.

**Railway** hosts the staging site and handles continuous deployment. When you push to `main`, Railway auto-deploys by building the Dockerfile (which runs `composer install --no-dev` inside the image) and starting the container. The whole pipeline takes about 1-2 minutes. Database credentials and WordPress salts are stored as Railway environment variables, following the [twelve-factor app](https://12factor.net/config) principle of keeping config out of code. A `docker-entrypoint.sh` script generates `.htaccess` for pretty permalinks, configures Apache's port, and auto-installs WordPress on first boot.

**Local and Railway are independent environments.** They have separate databases with separate content. The environment banner — blue for development, orange for staging — makes it obvious which environment you're looking at.

## Railway Staging

Staging site: https://sga-wordpress-production.up.railway.app

### Deploy

Push to `main` — Railway auto-builds the Dockerfile and deploys (~1-2 min).

### First boot

The `docker-entrypoint.sh` auto-installs WordPress on the first container start:
- Installs WordPress core, activates the SGA theme and Events Calendar plugin
- Creates all pages (Adopt, Foster, Dogs Needing Fosters, etc.)
- Creates editor accounts (Lily, Jacintha)
- Sets timezone, permalinks, and site options

### Admin access

- **URL**: https://sga-wordpress-production.up.railway.app/wp/wp-admin/
- **Admin password**: stored in Railway environment variables (`WP_ADMIN_PASSWORD`)

## Related

- [Organizational personas and development approach](https://github.com/dsayed/sga-community/blob/main/docs/plans/2026-02-21-sga-org-personas.md)
- [Design and accessibility assessment](https://github.com/dsayed/sga-community/blob/main/docs/plans/2026-02-17-sga-website-design-assessment.md)
