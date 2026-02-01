using Microsoft.EntityFrameworkCore;
using MCLS.Web.Data;
using MCLS.Web.Models;

namespace MCLS.Web.Services
{
    public interface IUserService
    {
        Task<User?> GetUserByUsernameAsync(string username);
        Task<User?> GetUserByIdAsync(int id);
        Task<User> CreateOrUpdateUserAsync(User user);
        Task UpdateLastLoginAsync(int userId);
    }

    public class UserService : IUserService
    {
        private readonly MclsDbContext _context;
        private readonly ILogger<UserService> _logger;

        public UserService(MclsDbContext context, ILogger<UserService> logger)
        {
            _context = context;
            _logger = logger;
        }

        public async Task<User?> GetUserByUsernameAsync(string username)
        {
            return await _context.Users
                .Include(u => u.Department)
                .FirstOrDefaultAsync(u => u.AdUsername == username && u.Status == "active");
        }

        public async Task<User?> GetUserByIdAsync(int id)
        {
            return await _context.Users
                .Include(u => u.Department)
                .FirstOrDefaultAsync(u => u.Id == id);
        }

        public async Task<User> CreateOrUpdateUserAsync(User user)
        {
            var existingUser = await GetUserByUsernameAsync(user.AdUsername);

            if (existingUser != null)
            {
                existingUser.FullName = user.FullName;
                existingUser.Email = user.Email;
                existingUser.UpdatedAt = DateTime.Now;
                _context.Users.Update(existingUser);
            }
            else
            {
                user.CreatedAt = DateTime.Now;
                user.UpdatedAt = DateTime.Now;
                await _context.Users.AddAsync(user);
            }

            await _context.SaveChangesAsync();
            return existingUser ?? user;
        }

        public async Task UpdateLastLoginAsync(int userId)
        {
            var user = await GetUserByIdAsync(userId);
            if (user != null)
            {
                user.LastLogin = DateTime.Now;
                _context.Users.Update(user);
                await _context.SaveChangesAsync();
            }
        }
    }
}
