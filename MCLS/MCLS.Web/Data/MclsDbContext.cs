using Microsoft.EntityFrameworkCore;
using MCLS.Web.Models;

namespace MCLS.Web.Data
{
    public class MclsDbContext : DbContext
    {
        public MclsDbContext(DbContextOptions<MclsDbContext> options) : base(options)
        {
        }

        public DbSet<User> Users { get; set; }
        public DbSet<Department> Departments { get; set; }
        public DbSet<MaintenanceCall> MaintenanceCalls { get; set; }
        public DbSet<Equipment> Equipment { get; set; }
        public DbSet<PriorityLevel> PriorityLevels { get; set; }

        protected override void OnModelCreating(ModelBuilder modelBuilder)
        {
            base.OnModelCreating(modelBuilder);

            // Configure User entity
            modelBuilder.Entity<User>(entity =>
            {
                entity.HasIndex(e => e.AdUsername).IsUnique();
                entity.HasIndex(e => e.Email).IsUnique();
            });

            // Configure Department entity
            modelBuilder.Entity<Department>(entity =>
            {
                entity.HasIndex(e => e.Code).IsUnique();
                
                entity.HasOne(d => d.ParentDepartment)
                    .WithMany(d => d.ChildDepartments)
                    .HasForeignKey(d => d.ParentId)
                    .OnDelete(DeleteBehavior.Restrict);
            });

            // Configure MaintenanceCall entity
            modelBuilder.Entity<MaintenanceCall>(entity =>
            {
                entity.HasIndex(e => e.CallNumber).IsUnique();
                
                entity.HasOne(m => m.Reporter)
                    .WithMany(u => u.MaintenanceCalls)
                    .HasForeignKey(m => m.ReportedBy)
                    .OnDelete(DeleteBehavior.Restrict);
                
                entity.HasOne(m => m.AssignedUser)
                    .WithMany()
                    .HasForeignKey(m => m.AssignedTo)
                    .OnDelete(DeleteBehavior.Restrict);
            });

            // Configure Equipment entity
            modelBuilder.Entity<Equipment>(entity =>
            {
                entity.HasIndex(e => e.EquipmentNumber).IsUnique();
            });
        }
    }
}
