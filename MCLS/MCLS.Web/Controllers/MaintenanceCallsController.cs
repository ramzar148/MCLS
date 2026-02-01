using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;
using MCLS.Web.Data;
using MCLS.Web.Models;
using System.Security.Claims;

namespace MCLS.Web.Controllers
{
    [Authorize]
    public class MaintenanceCallsController : Controller
    {
        private readonly MclsDbContext _context;
        private readonly ILogger<MaintenanceCallsController> _logger;

        public MaintenanceCallsController(MclsDbContext context, ILogger<MaintenanceCallsController> logger)
        {
            _context = context;
            _logger = logger;
        }

        public async Task<IActionResult> Index()
        {
            var calls = await _context.MaintenanceCalls
                .Include(m => m.Equipment)
                .Include(m => m.Priority)
                .Include(m => m.Reporter)
                .Include(m => m.AssignedUser)
                .OrderByDescending(m => m.ReportedDate)
                .ToListAsync();

            return View(calls);
        }

        public async Task<IActionResult> Details(int id)
        {
            var call = await _context.MaintenanceCalls
                .Include(m => m.Equipment)
                .Include(m => m.Priority)
                .Include(m => m.Reporter)
                .Include(m => m.AssignedUser)
                .FirstOrDefaultAsync(m => m.Id == id);

            if (call == null)
            {
                return NotFound();
            }

            return View(call);
        }

        [HttpGet]
        public async Task<IActionResult> Create()
        {
            ViewBag.Equipment = await _context.Equipment.Where(e => e.Status == "active").ToListAsync();
            ViewBag.Priorities = await _context.PriorityLevels.OrderBy(p => p.Level).ToListAsync();
            return View();
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public async Task<IActionResult> Create(MaintenanceCall model)
        {
            if (ModelState.IsValid)
            {
                var userId = int.Parse(User.FindFirstValue(ClaimTypes.NameIdentifier) ?? "0");
                
                model.CallNumber = await GenerateCallNumberAsync();
                model.ReportedBy = userId;
                model.ReportedDate = DateTime.Now;
                model.CreatedAt = DateTime.Now;
                model.UpdatedAt = DateTime.Now;

                _context.MaintenanceCalls.Add(model);
                await _context.SaveChangesAsync();

                _logger.LogInformation("Maintenance call {CallNumber} created by user {UserId}", model.CallNumber, userId);

                return RedirectToAction(nameof(Index));
            }

            ViewBag.Equipment = await _context.Equipment.Where(e => e.Status == "active").ToListAsync();
            ViewBag.Priorities = await _context.PriorityLevels.OrderBy(p => p.Level).ToListAsync();
            return View(model);
        }

        private async Task<string> GenerateCallNumberAsync()
        {
            var year = DateTime.Now.Year;
            var lastCall = await _context.MaintenanceCalls
                .Where(m => m.CallNumber.StartsWith($"MC{year}"))
                .OrderByDescending(m => m.CallNumber)
                .FirstOrDefaultAsync();

            int nextNumber = 1;
            if (lastCall != null)
            {
                var lastNumber = lastCall.CallNumber.Substring(6);
                if (int.TryParse(lastNumber, out int num))
                {
                    nextNumber = num + 1;
                }
            }

            return $"MC{year}{nextNumber:D4}";
        }
    }
}
