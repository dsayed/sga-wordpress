#!/usr/bin/env bash
# Deploy Azure infrastructure for SGA WordPress staging
# Usage: ./infrastructure/deploy.sh
#
# Prerequisites:
#   az login
#   az account set --subscription b5c4e6b0-e93e-47b8-ab37-1197d84b0064
#   az group create --name rg-sga --location westus2

set -euo pipefail

RESOURCE_GROUP="rg-sga"
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"

# Generate a random password for MySQL if not already set
if [ -z "${MYSQL_ADMIN_PASSWORD:-}" ]; then
  MYSQL_ADMIN_PASSWORD=$(openssl rand -base64 24 | tr -d '/+=' | head -c 24)
  echo "Generated MySQL admin password (save this!):"
  echo "  $MYSQL_ADMIN_PASSWORD"
  echo ""
fi

# Generate auth salts
generate_salt() {
  openssl rand -base64 48 | tr -d '/+=' | head -c 64
}

echo "Deploying infrastructure to Azure..."
echo ""

az deployment group create \
  --resource-group "$RESOURCE_GROUP" \
  --template-file "$SCRIPT_DIR/main.bicep" \
  --parameters "$SCRIPT_DIR/parameters.json" \
  --parameters \
    mysqlAdminPassword="$MYSQL_ADMIN_PASSWORD" \
    authKey="$(generate_salt)" \
    secureAuthKey="$(generate_salt)" \
    loggedInKey="$(generate_salt)" \
    nonceKey="$(generate_salt)" \
    authSalt="$(generate_salt)" \
    secureAuthSalt="$(generate_salt)" \
    loggedInSalt="$(generate_salt)" \
    nonceSalt="$(generate_salt)"

echo ""
echo "Deployment complete!"
echo ""
echo "Next steps:"
echo "  1. Get publish profile: az webapp deployment list-publishing-profiles --name sga-wordpress-staging --resource-group rg-sga --xml"
echo "  2. Add it as GitHub secret AZURE_WEBAPP_PUBLISH_PROFILE"
echo "  3. Push to main to trigger deployment"
