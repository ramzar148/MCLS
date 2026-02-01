# ASP.NET MCLS Conversion - IIS Deployment Guide

## Overview
This project converts the PHP-based MCLS (Maintenance Call Logging System) to ASP.NET Core for IIS hosting.

## Prerequisites
1. Windows Server with IIS installed
2. .NET 8.0 Runtime and Hosting Bundle for IIS
3. SQL Server (for database)
4. Active Directory configured for Windows Authentication

## Installation Steps

### 1. Install .NET Hosting Bundle
Download and install the ASP.NET Core Runtime & Hosting Bundle from:
https://dotnet.microsoft.com/download/dotnet/8.0

After installation, restart IIS:
```
iisreset
```

### 2. Configure IIS

#### Create Application Pool
1. Open IIS Manager
2. Right-click "Application Pools" ? "Add Application Pool"
3. Name: `MCLS_AppPool`
4. .NET CLR Version: `No Managed Code`
5. Managed Pipeline Mode: `Integrated`
6. Click OK

#### Configure Application Pool Identity
1. Select `MCLS_AppPool`
2. Click "Advanced Settings"
3. Set "Identity" to use appropriate account with AD access
4. Click OK

#### Create Website
1. Right-click "Sites" ? "Add Website"
2. Site name: `MCLS`
3. Application pool: `MCLS_AppPool`
4. Physical path: Path to published application (e.g., `C:\inetpub\wwwroot\MCLS`)
5. Binding: Select HTTP/HTTPS and port
6. Click OK

### 3. Enable Windows Authentication
1. Select your MCLS site in IIS
2. Double-click "Authentication"
3. Enable "Windows Authentication"
4. Disable "Anonymous Authentication"

### 4. Configure Database
Update the connection string in `appsettings.json`:
```json
{
  "ConnectionStrings": {
    "DefaultConnection": "Server=YOUR_SQL_SERVER;Database=MCLS;Integrated Security=True;"
  }
}
```

### 5. Publish Application
From Visual Studio or command line:
```
dotnet publish -c Release -o C:\inetpub\wwwroot\MCLS
```

### 6. Set Folder Permissions
Grant the Application Pool identity read/execute permissions to the application folder:
```powershell
icacls "C:\inetpub\wwwroot\MCLS" /grant "IIS AppPool\MCLS_AppPool:(OI)(CI)RX"
```

### 7. Configure Active Directory
Update `appsettings.json` with your AD settings:
```json
{
  "Authentication": {
    "ActiveDirectory": {
      "Domain": "yourdomain.local",
      "LdapServer": "ldap://yourdomain.local"
    }
  }
}
```

## Project Structure
```
MCLS.Web/
??? Controllers/
?   ??? HomeController.cs
?   ??? AccountController.cs
?   ??? DashboardController.cs
??? Views/
?   ??? Account/
?   ?   ??? Login.cshtml
?   ??? Dashboard/
?   ?   ??? Index.cshtml
?   ??? Shared/
?       ??? _Layout.cshtml
??? wwwroot/
?   ??? css/
?   ??? js/
??? Program.cs
??? appsettings.json
??? web.config
```

## Features Implemented
- ? Windows Authentication / Active Directory integration
- ? Session management
- ? Login/Logout functionality
- ? Dashboard view
- ? IIS configuration (web.config)
- ? Bootstrap 5 UI framework

## Next Steps
To complete the conversion, you'll need to:

1. **Create Data Models** - Convert PHP database models to C# Entity Framework models
2. **Migrate Database** - Convert MySQL/PHP database to SQL Server
3. **Convert Additional Pages** - Convert remaining PHP pages to Razor views:
   - Maintenance calls management
   - Work orders
   - Equipment management
   - Reports
   - User management

4. **Implement Business Logic** - Port PHP business logic to C# services

## Testing
1. Browse to your IIS site (e.g., `http://localhost` or `https://yourdomain.com`)
2. You should be prompted for Windows credentials
3. After authentication, you'll be redirected to the dashboard

## Troubleshooting

### 500.19 Error
- Ensure .NET Hosting Bundle is installed
- Run `iisreset` after installation

### 500.30 Error
- Check application logs in Event Viewer
- Verify web.config is correct
- Ensure application pool is running

### Authentication Issues
- Verify Windows Authentication is enabled in IIS
- Check Application Pool identity has appropriate permissions
- Ensure AD domain is accessible from the server

## Support
For issues, check the logs folder or Event Viewer ? Windows Logs ? Application
