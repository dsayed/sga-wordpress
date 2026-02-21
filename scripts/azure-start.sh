#!/usr/bin/env bash
# Start Azure resources back up
# MySQL must start before the App Service
set -euo pipefail

echo "Starting MySQL server..."
az mysql flexible-server start --name mysql-sga-test --resource-group rg-sga

echo "Starting App Service..."
az webapp start --name sga-wordpress-staging --resource-group rg-sga

echo "Done. Site: https://sga-wordpress-staging.azurewebsites.net"
