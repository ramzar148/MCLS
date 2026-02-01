using System;
using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace MCLS.Web.Models
{
    [Table("departments")]
    public class Department
    {
        [Key]
        [Column("id")]
        public int Id { get; set; }

        [Required]
        [StringLength(255)]
        [Column("name")]
        public string Name { get; set; } = string.Empty;

        [Required]
        [StringLength(50)]
        [Column("code")]
        public string Code { get; set; } = string.Empty;

        [StringLength(255)]
        [Column("location")]
        public string? Location { get; set; }

        [Column("parent_id")]
        public int? ParentId { get; set; }

        [ForeignKey("ParentId")]
        public virtual Department? ParentDepartment { get; set; }

        [Required]
        [StringLength(20)]
        [Column("status")]
        public string Status { get; set; } = "active";

        [Column("created_at")]
        public DateTime CreatedAt { get; set; } = DateTime.Now;

        [Column("updated_at")]
        public DateTime UpdatedAt { get; set; } = DateTime.Now;

        public virtual ICollection<User> Users { get; set; } = new List<User>();
        public virtual ICollection<Department> ChildDepartments { get; set; } = new List<Department>();
    }
}
