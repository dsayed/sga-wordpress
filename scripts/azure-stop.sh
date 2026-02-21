#!/usr/bin/env bash
# Stop Azure resources to save costs when not in use
# Costs drop to near-zero for stopped resources
set -euo pipefail

echo "Stopping App Service..."
az webapp stop --name sga-wordpress-staging --resource-group rg-sga

echo "Stopping MySQL server..."
az mysql flexible-server stop --name mysql-sga-test --resource-group rg-sga

echo "Done. Resources stopped."
