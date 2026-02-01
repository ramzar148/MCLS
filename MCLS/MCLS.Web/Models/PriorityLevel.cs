using System;
using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace MCLS.Web.Models
{
    [Table("priority_levels")]
    public class PriorityLevel
    {
        [Key]
        [Column("id")]
        public int Id { get; set; }

        [Required]
        [StringLength(50)]
        [Column("name")]
        public string Name { get; set; } = string.Empty;

        [Column("level")]
        public int Level { get; set; }

        [Column("response_time")]
        public int? ResponseTime { get; set; }

        [Column("description", TypeName = "text")]
        public string? Description { get; set; }

        [Column("created_at")]
        public DateTime CreatedAt { get; set; } = DateTime.Now;

        public virtual ICollection<MaintenanceCall> MaintenanceCalls { get; set; } = new List<MaintenanceCall>();
    }
}
