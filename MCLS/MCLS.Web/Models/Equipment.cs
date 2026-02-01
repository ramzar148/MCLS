using System;
using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace MCLS.Web.Models
{
    [Table("equipment")]
    public class Equipment
    {
        [Key]
        [Column("id")]
        public int Id { get; set; }

        [Required]
        [StringLength(100)]
        [Column("equipment_number")]
        public string EquipmentNumber { get; set; } = string.Empty;

        [Required]
        [StringLength(255)]
        [Column("name")]
        public string Name { get; set; } = string.Empty;

        [Column("category_id")]
        public int? CategoryId { get; set; }

        [Column("description", TypeName = "text")]
        public string? Description { get; set; }

        [Column("department_id")]
        public int? DepartmentId { get; set; }

        [ForeignKey("DepartmentId")]
        public virtual Department? Department { get; set; }

        [StringLength(255)]
        [Column("location")]
        public string? Location { get; set; }

        [Required]
        [StringLength(20)]
        [Column("status")]
        public string Status { get; set; } = "active";

        [Column("created_at")]
        public DateTime CreatedAt { get; set; } = DateTime.Now;

        [Column("updated_at")]
        public DateTime UpdatedAt { get; set; } = DateTime.Now;

        public virtual ICollection<MaintenanceCall> MaintenanceCalls { get; set; } = new List<MaintenanceCall>();
    }
}
