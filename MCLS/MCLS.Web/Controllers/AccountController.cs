using Microsoft.AspNetCore.Authentication;
using Microsoft.AspNetCore.Authentication.Cookies;
using Microsoft.AspNetCore.Mvc;
using System.Security.Claims;
using MCLS.Web.Services;
using MCLS.Web.Models;

namespace MCLS.Web.Controllers
{
    public class AccountController : Controller
    {
        private readonly IActiveDirectoryService _adService;
        private readonly IUserService _userService;
        private readonly ILogger<AccountController> _logger;

        public AccountController(
            IActiveDirectoryService adService,
            IUserService userService,
            ILogger<AccountController> logger)
        {
            _adService = adService;
            _userService = userService;
            _logger = logger;
        }

        [HttpGet]
        public IActionResult Login(string? returnUrl = null)
        {
            ViewData["ReturnUrl"] = returnUrl;
            return View();
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public async Task<IActionResult> Login(string username, string password, string? returnUrl = null)
        {
            if (string.IsNullOrWhiteSpace(username) || string.IsNullOrWhiteSpace(password))
            {
                ModelState.AddModelError("", "Please enter both username and password.");
                return View();
            }

            // Authenticate with Active Directory
            var (success, errorMessage, adUserData) = await _adService.AuthenticateAsync(username, password);

            if (!success)
            {
                ModelState.AddModelError("", errorMessage ?? "Authentication failed");
                return View();
            }

            if (adUserData == null)
            {
                ModelState.AddModelError("", "User data not found");
                return View();
            }

            // Get or create user in database
            var user = await _userService.GetUserByUsernameAsync(username);
            if (user == null)
            {
                user = await _userService.CreateOrUpdateUserAsync(adUserData);
            }

            // Update last login
            await _userService.UpdateLastLoginAsync(user.Id);

            // Create claims
            var claims = new List<Claim>
            {
                new Claim(ClaimTypes.NameIdentifier, user.Id.ToString()),
                new Claim(ClaimTypes.Name, user.AdUsername),
                new Claim(ClaimTypes.Email, user.Email),
                new Claim("FullName", user.FullName),
                new Claim(ClaimTypes.Role, user.Role)
            };

            if (user.DepartmentId.HasValue)
            {
                claims.Add(new Claim("DepartmentId", user.DepartmentId.Value.ToString()));
                if (user.Department != null)
                {
                    claims.Add(new Claim("DepartmentName", user.Department.Name));
                }
            }

            var claimsIdentity = new ClaimsIdentity(claims, CookieAuthenticationDefaults.AuthenticationScheme);
            var authProperties = new AuthenticationProperties
            {
                IsPersistent = true,
                ExpiresUtc = DateTimeOffset.UtcNow.AddHours(8)
            };

            await HttpContext.SignInAsync(
                CookieAuthenticationDefaults.AuthenticationScheme,
                new ClaimsPrincipal(claimsIdentity),
                authProperties);

            _logger.LogInformation("User {Username} logged in successfully", username);

            return LocalRedirect(returnUrl ?? "/");
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public async Task<IActionResult> Logout()
        {
            await HttpContext.SignOutAsync(CookieAuthenticationDefaults.AuthenticationScheme);
            _logger.LogInformation("User logged out");
            return RedirectToAction("Login");
        }

        public IActionResult AccessDenied()
        {
            return View();
        }
    }
}
