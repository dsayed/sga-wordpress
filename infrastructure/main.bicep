// SGA WordPress — Azure Infrastructure
// Deploys: App Service (PHP 8.2) + MySQL Flexible Server
//
// Note: MySQL was manually provisioned in West US 3 due to subscription quota
// constraints. This template manages the App Service resources. MySQL is
// referenced as an existing resource.
//
// Usage: az deployment group create --resource-group rg-sga --template-file infrastructure/main.bicep

@description('Azure region for App Service resources')
param location string = 'centralus'

@description('App Service name (becomes <name>.azurewebsites.net)')
param appServiceName string = 'sga-wordpress-staging'

@description('App Service Plan name')
param appServicePlanName string = 'plan-sga'

@description('MySQL server hostname')
param mysqlHost string = 'mysql-sga-test.mysql.database.azure.com'

@description('MySQL database name')
param mysqlDatabaseName string = 'sga_wordpress'

@description('MySQL admin username')
param mysqlAdminUser string = 'sgaadmin'

@secure()
@description('MySQL admin password')
param mysqlAdminPassword string

@description('WordPress environment')
param wpEnv string = 'staging'

// Auth salts — pass these in or generate before deploying
@secure()
param authKey string
@secure()
param secureAuthKey string
@secure()
param loggedInKey string
@secure()
param nonceKey string
@secure()
param authSalt string
@secure()
param secureAuthSalt string
@secure()
param loggedInSalt string
@secure()
param nonceSalt string

// ---------------------------------------------------------------------------
// App Service Plan — Linux B1 (~$13/month)
// ---------------------------------------------------------------------------
resource appServicePlan 'Microsoft.Web/serverfarms@2023-12-01' = {
  name: appServicePlanName
  location: location
  kind: 'linux'
  properties: {
    reserved: true // required for Linux
  }
  sku: {
    name: 'B1'
    tier: 'Basic'
  }
}

// ---------------------------------------------------------------------------
// App Service — PHP 8.2 on Linux
// ---------------------------------------------------------------------------
resource appService 'Microsoft.Web/sites@2023-12-01' = {
  name: appServiceName
  location: location
  properties: {
    serverFarmId: appServicePlan.id
    httpsOnly: true
    siteConfig: {
      linuxFxVersion: 'PHP|8.2'
      // Custom nginx config sets document root to Bedrock's web/ directory
      appCommandLine: 'cp /home/site/wwwroot/nginx.conf /etc/nginx/sites-available/default && service nginx reload'
      appSettings: [
        // Bedrock environment
        { name: 'WP_ENV', value: wpEnv }
        { name: 'WP_HOME', value: 'https://${appServiceName}.azurewebsites.net' }
        { name: 'WP_SITEURL', value: 'https://${appServiceName}.azurewebsites.net/wp' }

        // Database (MySQL Flexible Server in West US 3)
        { name: 'DB_HOST', value: mysqlHost }
        { name: 'DB_NAME', value: mysqlDatabaseName }
        { name: 'DB_USER', value: mysqlAdminUser }
        { name: 'DB_PASSWORD', value: mysqlAdminPassword }
        { name: 'DB_SSL', value: 'true' }

        // Auth salts
        { name: 'AUTH_KEY', value: authKey }
        { name: 'SECURE_AUTH_KEY', value: secureAuthKey }
        { name: 'LOGGED_IN_KEY', value: loggedInKey }
        { name: 'NONCE_KEY', value: nonceKey }
        { name: 'AUTH_SALT', value: authSalt }
        { name: 'SECURE_AUTH_SALT', value: secureAuthSalt }
        { name: 'LOGGED_IN_SALT', value: loggedInSalt }
        { name: 'NONCE_SALT', value: nonceSalt }

        { name: 'WEBSITES_ENABLE_APP_SERVICE_STORAGE', value: 'true' }
      ]
    }
  }
}

// ---------------------------------------------------------------------------
// Outputs
// ---------------------------------------------------------------------------
output appServiceUrl string = 'https://${appService.properties.defaultHostName}'
output mysqlHost string = mysqlHost
output appServiceName string = appService.name
