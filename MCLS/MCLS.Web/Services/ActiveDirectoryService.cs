using System.DirectoryServices;
using System.DirectoryServices.AccountManagement;
using MCLS.Web.Models;

namespace MCLS.Web.Services
{
    public interface IActiveDirectoryService
    {
        Task<(bool Success, string? ErrorMessage, User? UserData)> AuthenticateAsync(string username, string password);
        Task<User?> GetUserDetailsAsync(string username);
    }

    public class ActiveDirectoryService : IActiveDirectoryService
    {
        private readonly IConfiguration _configuration;
        private readonly ILogger<ActiveDirectoryService> _logger;

        public ActiveDirectoryService(IConfiguration configuration, ILogger<ActiveDirectoryService> logger)
        {
            _configuration = configuration;
            _logger = logger;
        }

        public async Task<(bool Success, string? ErrorMessage, User? UserData)> AuthenticateAsync(string username, string password)
        {
            try
            {
                var domain = _configuration["ActiveDirectory:Domain"];
                var baseDn = _configuration["ActiveDirectory:BaseDN"];

                using var context = new PrincipalContext(ContextType.Domain, domain);
                
                // Validate credentials
                if (!context.ValidateCredentials(username, password))
                {
                    return (false, "Invalid username or password", null);
                }

                // Get user details
                var userDetails = await GetUserDetailsAsync(username);
                
                return (true, null, userDetails);
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "AD authentication failed for user {Username}", username);
                return (false, "Authentication service error", null);
            }
        }

        public async Task<User?> GetUserDetailsAsync(string username)
        {
            try
            {
                var domain = _configuration["ActiveDirectory:Domain"];

                using var context = new PrincipalContext(ContextType.Domain, domain);
                using var userPrincipal = UserPrincipal.FindByIdentity(context, IdentityType.SamAccountName, username);

                if (userPrincipal == null)
                    return null;

                return await Task.FromResult(new User
                {
                    AdUsername = userPrincipal.SamAccountName,
                    FullName = userPrincipal.DisplayName ?? userPrincipal.Name ?? username,
                    Email = userPrincipal.EmailAddress ?? $"{username}@{domain}",
                    Status = "active",
                    Role = "user"
                });
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "Failed to get AD user details for {Username}", username);
                return null;
            }
        }
    }
}
