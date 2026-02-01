# MCLS - Maintenance Call Logging System (ASP.NET Core)

## Overview
This is the ASP.NET Core MVC conversion of the PHP-based MCLS (Maintenance Call Logging System) for the Department of Forestry, Fisheries and the Environment.

## Technology Stack
- **Framework**: ASP.NET Core 10.0 MVC
- **Database**: MySQL (compatible with existing PHP database)
- **Authentication**: Active Directory + Cookie Authentication
- **ORM**: Entity Framework Core with Pomelo MySQL Provider

## Project Structure

### Models
- `User` - System users with AD integration
- `Department` - Organizational departments
- `MaintenanceCall` - Maintenance call records
- `Equipment` - Equipment inventory
- `PriorityLevel` - Priority classifications

### Services
- `ActiveDirectoryService` - AD authentication and user lookup
- `UserService` - User management and database operations

### Controllers
- `AccountController` - Login/logout functionality
- `DashboardController` - Dashboard statistics and recent calls
- `MaintenanceCallsController` - Maintenance call CRUD operations

## Configuration

### Database Connection
Update `appsettings.json` with your MySQL database credentials:
```json
"ConnectionStrings": {
  "DefaultConnection": "Server=localhost;Database=mcls_db;User=mcls_user;Password=mcls_password;Charset=utf8mb4;"
}
```

### Active Directory Settings
Configure AD settings in `appsettings.json`:
```json
"ActiveDirectory": {
  "Domain": "YOURDOMAIN",
  "BaseDN": "DC=yourdomain,DC=local",
  "Server": "ldap://your-domain-controller.local"
}
```

## Database Migration

The application uses the existing MySQL database schema from the PHP version. The Entity Framework models are configured to map to the existing tables:
- `users`
- `departments`
- `maintenance_calls`
- `equipment`
- `priority_levels`

## Key Features Converted

### Authentication
- ? Active Directory authentication
- ? Session management with cookies
- ? User role-based access
- ? Last login tracking

### Dashboard
- ? Statistics (Total calls, Open calls, Critical calls, My calls)
- ? Active users and equipment counts
- ? Recent maintenance calls display

### Maintenance Calls
- ? List all maintenance calls
- ? View call details
- ? Create new calls
- ? Automatic call number generation

## Running the Application

1. **Restore packages**:
   ```bash
   dotnet restore
   ```

2. **Update database connection** in `appsettings.json`

3. **Run the application**:
   ```bash
   dotnet run
   ```

4. **Access the application**:
   - Navigate to `https://localhost:5001` or `http://localhost:5000`
   - Login with your Active Directory credentials

## Differences from PHP Version

### Architecture
- **PHP**: Procedural with includes ? **ASP.NET**: MVC pattern with dependency injection
- **PHP**: Session files ? **ASP.NET**: Cookie-based authentication
- **PHP**: PDO ? **ASP.NET**: Entity Framework Core
- **PHP**: Direct SQL ? **ASP.NET**: LINQ queries

### Security
- Built-in CSRF protection with anti-forgery tokens
- Password hashing handled by AD
- Secure cookie authentication with sliding expiration
- Input validation through data annotations

### Performance
- Connection pooling with EF Core
- Asynchronous database operations
- Compiled code vs interpreted PHP

## Future Enhancements
- Equipment management module
- Work orders module
- Reports generation
- File upload functionality
- Email notifications
- Real-time updates with SignalR

## Development Notes
- The application maintains compatibility with the existing MySQL database schema
- All existing data from the PHP version can be used directly
- User roles and permissions are preserved
- Call numbering format remains consistent (MC{YEAR}{####})

## Support
For issues or questions, please contact the system administrator.

---
*Converted from PHP to ASP.NET Core - 2026*
