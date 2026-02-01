using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;
using MCLS.Web.Data;
using MCLS.Web.Models;
using System.Security.Claims;

namespace MCLS.Web.Controllers
{
    [Authorize]
    public class DashboardController : Controller
    {
        private readonly MclsDbContext _context;
        private readonly ILogger<DashboardController> _logger;

        public DashboardController(MclsDbContext context, ILogger<DashboardController> logger)
        {
            _context = context;
            _logger = logger;
        }

        public async Task<IActionResult> Index()
        {
            var userId = int.Parse(User.FindFirstValue(ClaimTypes.NameIdentifier) ?? "0");
            var userRole = User.FindFirstValue(ClaimTypes.Role);

            var stats = new DashboardViewModel
            {
                TotalCalls = await _context.MaintenanceCalls.CountAsync(),
                OpenCalls = await _context.MaintenanceCalls.CountAsync(m => 
                    m.Status == "open" || m.Status == "assigned" || m.Status == "in_progress"),
                CriticalCalls = await _context.MaintenanceCalls
                    .Include(m => m.Priority)
                    .CountAsync(m => m.Priority != null && m.Priority.Name == "Critical"),
                MyCalls = await _context.MaintenanceCalls.CountAsync(m => m.ReportedBy == userId),
                ActiveUsers = await _context.Users.CountAsync(u => u.Status == "active"),
                ActiveEquipment = await _context.Equipment.CountAsync(e => e.Status == "active")
            };

            // Get recent maintenance calls
            var recentCalls = await _context.MaintenanceCalls
                .Include(m => m.Equipment)
                .Include(m => m.Priority)
                .Include(m => m.Reporter)
                .OrderByDescending(m => m.ReportedDate)
                .Take(10)
                .ToListAsync();

            ViewBag.RecentCalls = recentCalls;
            ViewBag.UserRole = userRole;

            return View(stats);
        }
    }

    public class DashboardViewModel
    {
        public int TotalCalls { get; set; }
        public int OpenCalls { get; set; }
        public int CriticalCalls { get; set; }
        public int MyCalls { get; set; }
        public int ActiveUsers { get; set; }
        public int ActiveEquipment { get; set; }
    }
}
