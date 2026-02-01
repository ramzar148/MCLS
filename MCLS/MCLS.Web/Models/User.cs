using System;
using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace MCLS.Web.Models
{
    [Table("users")]
    public class User
    {
        [Key]
        [Column("id")]
        public int Id { get; set; }

        [Required]
        [StringLength(100)]
        [Column("ad_username")]
        public string AdUsername { get; set; } = string.Empty;

        [Required]
        [StringLength(255)]
        [Column("full_name")]
        public string FullName { get; set; } = string.Empty;

        [Required]
        [EmailAddress]
        [StringLength(255)]
        [Column("email")]
        public string Email { get; set; } = string.Empty;

        [Column("department_id")]
        public int? DepartmentId { get; set; }

        [ForeignKey("DepartmentId")]
        public virtual Department? Department { get; set; }

        [Required]
        [StringLength(50)]
        [Column("role")]
        public string Role { get; set; } = "user";

        [Required]
        [StringLength(20)]
        [Column("status")]
        public string Status { get; set; } = "active";

        [Column("created_at")]
        public DateTime CreatedAt { get; set; } = DateTime.Now;

        [Column("updated_at")]
        public DateTime UpdatedAt { get; set; } = DateTime.Now;

        [Column("last_login")]
        public DateTime? LastLogin { get; set; }

        public virtual ICollection<MaintenanceCall> MaintenanceCalls { get; set; } = new List<MaintenanceCall>();
    }
}
