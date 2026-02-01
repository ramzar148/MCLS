# MCLS ASP.NET Core - Deployment Guide

## Prerequisites

### Required Software
1. **.NET 10.0 SDK or Runtime**
   - Download from: https://dotnet.microsoft.com/download
   - Verify installation: `dotnet --version`

2. **MySQL Server** (5.7+ or 8.0+)
   - Existing database from PHP version
   - OR new MySQL installation

3. **Active Directory Access**
   - Domain controller accessible
   - LDAP connectivity

4. **Web Server** (Choose one):
   - IIS (Windows Server) - Recommended for Windows
   - Kestrel (Cross-platform) - Built-in
   - Nginx (Linux) - Reverse proxy
   - Apache (Linux) - Reverse proxy

## Configuration Steps

### 1. Database Configuration

#### Option A: Use Existing PHP Database
Your existing MySQL database from the PHP version is compatible. No migration needed!

```bash
# Just update the connection string in appsettings.json
```

#### Option B: Fresh Installation
If starting fresh, import the schema from your PHP installation:

```bash
mysql -u root -p mcls_db < /path/to/php/database/schema.sql
```

### 2. Application Configuration

Edit `appsettings.json`:

```json
{
  "ConnectionStrings": {
    "DefaultConnection": "Server=YOUR_MYSQL_SERVER;Database=mcls_db;User=YOUR_USER;Password=YOUR_PASSWORD;Charset=utf8mb4;"
  },
  "ActiveDirectory": {
    "Domain": "YOUR_DOMAIN",
    "BaseDN": "DC=yourdomain,DC=local",
    "Server": "ldap://your-dc.yourdomain.local"
  }
}
```

### 3. Environment-Specific Settings

Create `appsettings.Production.json` for production:

```json
{
  "Logging": {
    "LogLevel": {
      "Default": "Warning",
      "Microsoft.AspNetCore": "Warning"
    }
  },
  "ConnectionStrings": {
    "DefaultConnection": "Server=PROD_SERVER;Database=mcls_db;User=prod_user;Password=STRONG_PASSWORD;Charset=utf8mb4;"
  }
}
```

## Deployment Options

### Option 1: Windows Server with IIS

#### Step 1: Install Prerequisites
1. Install .NET 10.0 Runtime (Hosting Bundle)
2. Enable IIS with ASP.NET Core Module

#### Step 2: Publish Application
```bash
cd MCLS.Web
dotnet publish -c Release -o C:\inetpub\wwwroot\mcls
```

#### Step 3: Configure IIS
1. Open IIS Manager
2. Create new Application Pool:
   - Name: MCLS
   - .NET CLR Version: No Managed Code
   - Managed Pipeline Mode: Integrated
3. Create new Website:
   - Name: MCLS
   - Application Pool: MCLS
   - Physical Path: C:\inetpub\wwwroot\mcls
   - Binding: http://*:80 or https://*:443
4. Set Permissions:
   - Grant IIS_IUSRS read access to application folder

#### Step 4: Configure SSL (Recommended)
1. Obtain SSL certificate
2. Add HTTPS binding in IIS
3. Update `appsettings.json`:
```json
"Kestrel": {
  "EndPoints": {
    "Https": {
      "Url": "https://*:443"
    }
  }
}
```

### Option 2: Linux with Nginx

#### Step 1: Install Prerequisites
```bash
# Ubuntu/Debian
sudo apt-get update
sudo apt-get install -y dotnet-runtime-10.0 nginx

# CentOS/RHEL
sudo yum install -y dotnet-runtime-10.0 nginx
```

#### Step 2: Publish Application
```bash
cd MCLS.Web
dotnet publish -c Release -o /var/www/mcls
```

#### Step 3: Create Systemd Service
Create `/etc/systemd/system/mcls.service`:

```ini
[Unit]
Description=MCLS ASP.NET Core Application
After=network.target

[Service]
WorkingDirectory=/var/www/mcls
ExecStart=/usr/bin/dotnet /var/www/mcls/MCLS.Web.dll
Restart=always
RestartSec=10
KillSignal=SIGINT
SyslogIdentifier=mcls
User=www-data
Environment=ASPNETCORE_ENVIRONMENT=Production
Environment=DOTNET_PRINT_TELEMETRY_MESSAGE=false

[Install]
WantedBy=multi-user.target
```

Enable and start service:
```bash
sudo systemctl enable mcls
sudo systemctl start mcls
sudo systemctl status mcls
```

#### Step 4: Configure Nginx
Create `/etc/nginx/sites-available/mcls`:

```nginx
server {
    listen 80;
    server_name your-domain.com;
    
    location / {
        proxy_pass http://localhost:5000;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection keep-alive;
        proxy_set_header Host $host;
        proxy_cache_bypass $http_upgrade;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

Enable site:
```bash
sudo ln -s /etc/nginx/sites-available/mcls /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

### Option 3: Docker Container

#### Create Dockerfile
Create `MCLS.Web/Dockerfile`:

```dockerfile
FROM mcr.microsoft.com/dotnet/aspnet:10.0 AS base
WORKDIR /app
EXPOSE 80
EXPOSE 443

FROM mcr.microsoft.com/dotnet/sdk:10.0 AS build
WORKDIR /src
COPY ["MCLS.Web.csproj", "./"]
RUN dotnet restore "MCLS.Web.csproj"
COPY . .
RUN dotnet build "MCLS.Web.csproj" -c Release -o /app/build

FROM build AS publish
RUN dotnet publish "MCLS.Web.csproj" -c Release -o /app/publish

FROM base AS final
WORKDIR /app
COPY --from=publish /app/publish .
ENTRYPOINT ["dotnet", "MCLS.Web.dll"]
```

#### Build and Run
```bash
docker build -t mcls:latest .
docker run -d -p 8080:80 --name mcls \
  -e ConnectionStrings__DefaultConnection="Server=mysql;Database=mcls_db;User=root;Password=password" \
  -e ActiveDirectory__Domain="YOURDOMAIN" \
  mcls:latest
```

#### Docker Compose
Create `docker-compose.yml`:

```yaml
version: '3.8'
services:
  mcls:
    build: ./MCLS.Web
    ports:
      - "8080:80"
    environment:
      - ASPNETCORE_ENVIRONMENT=Production
      - ConnectionStrings__DefaultConnection=Server=mysql;Database=mcls_db;User=root;Password=password
      - ActiveDirectory__Domain=YOURDOMAIN
    depends_on:
      - mysql
      
  mysql:
    image: mysql:8.0
    environment:
      - MYSQL_ROOT_PASSWORD=password
      - MYSQL_DATABASE=mcls_db
    volumes:
      - mysql_data:/var/lib/mysql
    ports:
      - "3306:3306"

volumes:
  mysql_data:
```

Run with: `docker-compose up -d`

### Option 4: Azure App Service

#### Step 1: Create Azure Resources
```bash
# Install Azure CLI
# Create resource group
az group create --name mcls-rg --location eastus

# Create App Service plan
az appservice plan create --name mcls-plan --resource-group mcls-rg --sku B1 --is-linux

# Create web app
az webapp create --name mcls-app --resource-group mcls-rg --plan mcls-plan --runtime "DOTNETCORE:10.0"
```

#### Step 2: Configure Connection String
```bash
az webapp config connection-string set --name mcls-app --resource-group mcls-rg \
  --connection-string-type MySql \
  --settings DefaultConnection="Server=YOUR_MYSQL;Database=mcls_db;User=user;Password=pass"
```

#### Step 3: Deploy
```bash
cd MCLS.Web
dotnet publish -c Release
cd bin/Release/net10.0/publish
zip -r publish.zip .
az webapp deployment source config-zip --resource-group mcls-rg --name mcls-app --src publish.zip
```

## Post-Deployment Verification

### 1. Health Checks

Create a simple health check endpoint:

```bash
# Check if application is running
curl http://your-server/

# Should return login page or redirect
```

### 2. Database Connectivity

```bash
# Run the verification script
mysql -u username -p mcls_db < database_verification.sql
```

### 3. Active Directory Connectivity

Test login with a known AD user account.

### 4. Logs

Check application logs:

**Windows (IIS):**
```
C:\inetpub\wwwroot\mcls\logs\
```

**Linux (Systemd):**
```bash
sudo journalctl -u mcls -f
```

**Docker:**
```bash
docker logs mcls -f
```

## Security Hardening

### 1. HTTPS Only
```json
// appsettings.Production.json
"Kestrel": {
  "EndPoints": {
    "Https": {
      "Url": "https://*:443"
    }
  }
}
```

### 2. Security Headers
Add to `Program.cs`:

```csharp
app.Use(async (context, next) =>
{
    context.Response.Headers.Add("X-Content-Type-Options", "nosniff");
    context.Response.Headers.Add("X-Frame-Options", "DENY");
    context.Response.Headers.Add("X-XSS-Protection", "1; mode=block");
    await next();
});
```

### 3. Database User Permissions
```sql
-- Create dedicated user with minimum permissions
CREATE USER 'mcls_app'@'%' IDENTIFIED BY 'strong_password';
GRANT SELECT, INSERT, UPDATE, DELETE ON mcls_db.* TO 'mcls_app'@'%';
FLUSH PRIVILEGES;
```

### 4. Firewall Rules
```bash
# Allow only necessary ports
# MySQL: 3306 (from app server only)
# HTTP: 80
# HTTPS: 443
# LDAP: 389 (to domain controller only)
```

## Monitoring & Maintenance

### 1. Application Insights (Azure)
```bash
dotnet add package Microsoft.ApplicationInsights.AspNetCore
```

### 2. Log Management
Configure structured logging in `appsettings.json`:

```json
"Logging": {
  "LogLevel": {
    "Default": "Information",
    "Microsoft": "Warning"
  },
  "Console": {
    "IncludeScopes": true
  },
  "File": {
    "Path": "logs/mcls.log",
    "FileSizeLimitBytes": 10485760,
    "RetainedFileCountLimit": 10
  }
}
```

### 3. Backup Strategy
```bash
# Database backup (daily cron job)
0 2 * * * mysqldump -u backup_user -p mcls_db > /backups/mcls_$(date +\%Y\%m\%d).sql

# Application backup
0 3 * * * tar -czf /backups/mcls_app_$(date +\%Y\%m\%d).tar.gz /var/www/mcls
```

## Rollback Procedure

If issues occur:

### Quick Rollback
```bash
# Stop new version
sudo systemctl stop mcls

# Restore previous version
cp -r /var/www/mcls.backup /var/www/mcls

# Start service
sudo systemctl start mcls
```

### Database Rollback
```bash
# Restore from backup
mysql -u root -p mcls_db < /backups/mcls_20260201.sql
```

## Performance Tuning

### 1. Enable Response Compression
Already configured in `Program.cs`

### 2. Database Connection Pooling
Update connection string:
```
Server=localhost;Database=mcls_db;User=user;Password=pass;MinimumPoolSize=5;MaximumPoolSize=100;
```

### 3. Caching
Add in `Program.cs`:
```csharp
builder.Services.AddResponseCaching();
app.UseResponseCaching();
```

## Troubleshooting

### Issue: Application won't start
```bash
# Check logs
journalctl -u mcls -n 50

# Check if port is available
netstat -tulpn | grep :5000

# Test configuration
dotnet MCLS.Web.dll --environment=Production
```

### Issue: Database connection fails
```bash
# Test MySQL connectivity
mysql -h YOUR_SERVER -u YOUR_USER -p

# Check firewall
telnet YOUR_MYSQL_SERVER 3306
```

### Issue: AD authentication fails
```bash
# Test LDAP connectivity
ldapsearch -x -H ldap://your-dc.yourdomain.local -D "user@domain" -W -b "DC=yourdomain,DC=local"
```

## Support

For issues or questions:
1. Check application logs
2. Run database verification script
3. Review this deployment guide
4. Contact system administrator

---

**Deployment Guide Version 1.0**
*Last Updated: 2026*
