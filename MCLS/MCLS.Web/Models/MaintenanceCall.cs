using System;
using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace MCLS.Web.Models
{
    [Table("maintenance_calls")]
    public class MaintenanceCall
    {
        [Key]
        [Column("id")]
        public int Id { get; set; }

        [Required]
        [StringLength(50)]
        [Column("call_number")]
        public string CallNumber { get; set; } = string.Empty;

        [Column("equipment_id")]
        public int? EquipmentId { get; set; }

        [ForeignKey("EquipmentId")]
        public virtual Equipment? Equipment { get; set; }

        [Column("priority_id")]
        public int? PriorityId { get; set; }

        [ForeignKey("PriorityId")]
        public virtual PriorityLevel? Priority { get; set; }

        [Required]
        [Column("description", TypeName = "text")]
        public string Description { get; set; } = string.Empty;

        [Column("reported_by")]
        public int ReportedBy { get; set; }

        [ForeignKey("ReportedBy")]
        public virtual User? Reporter { get; set; }

        [Column("assigned_to")]
        public int? AssignedTo { get; set; }

        [ForeignKey("AssignedTo")]
        public virtual User? AssignedUser { get; set; }

        [Required]
        [StringLength(50)]
        [Column("status")]
        public string Status { get; set; } = "open";

        [Column("reported_date")]
        public DateTime ReportedDate { get; set; } = DateTime.Now;

        [Column("resolved_date")]
        public DateTime? ResolvedDate { get; set; }

        [Column("resolution_notes", TypeName = "text")]
        public string? ResolutionNotes { get; set; }

        [Column("created_at")]
        public DateTime CreatedAt { get; set; } = DateTime.Now;

        [Column("updated_at")]
        public DateTime UpdatedAt { get; set; } = DateTime.Now;
    }
}
