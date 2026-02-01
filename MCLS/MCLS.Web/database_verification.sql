-- MCLS Database Verification Script
-- This script checks if your existing MySQL database is compatible with the ASP.NET Core application

-- Check if required tables exist
SELECT 
    'users' as table_name,
    COUNT(*) as record_count,
    CASE WHEN COUNT(*) > 0 THEN '? EXISTS' ELSE '? MISSING' END as status
FROM information_schema.tables 
WHERE table_schema = DATABASE() AND table_name = 'users'
UNION ALL
SELECT 
    'departments',
    (SELECT COUNT(*) FROM departments),
    CASE WHEN EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'departments') 
         THEN '? EXISTS' ELSE '? MISSING' END
UNION ALL
SELECT 
    'maintenance_calls',
    (SELECT COUNT(*) FROM maintenance_calls),
    CASE WHEN EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'maintenance_calls') 
         THEN '? EXISTS' ELSE '? MISSING' END
UNION ALL
SELECT 
    'equipment',
    (SELECT COUNT(*) FROM equipment),
    CASE WHEN EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'equipment') 
         THEN '? EXISTS' ELSE '? MISSING' END
UNION ALL
SELECT 
    'priority_levels',
    (SELECT COUNT(*) FROM priority_levels),
    CASE WHEN EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'priority_levels') 
         THEN '? EXISTS' ELSE '? MISSING' END;

-- Check users table structure
DESCRIBE users;

-- Check departments table structure  
DESCRIBE departments;

-- Check maintenance_calls table structure
DESCRIBE maintenance_calls;

-- Check equipment table structure
DESCRIBE equipment;

-- Check priority_levels table structure
DESCRIBE priority_levels;

-- Verify relationships
SELECT 
    'User -> Department' as relationship,
    COUNT(DISTINCT u.department_id) as linked_departments,
    COUNT(*) as users_with_department
FROM users u
WHERE u.department_id IS NOT NULL;

SELECT 
    'MaintenanceCall -> Equipment' as relationship,
    COUNT(DISTINCT mc.equipment_id) as linked_equipment,
    COUNT(*) as calls_with_equipment
FROM maintenance_calls mc
WHERE mc.equipment_id IS NOT NULL;

SELECT 
    'MaintenanceCall -> Priority' as relationship,
    COUNT(DISTINCT mc.priority_id) as linked_priorities,
    COUNT(*) as calls_with_priority
FROM maintenance_calls mc
WHERE mc.priority_id IS NOT NULL;

-- Check for any active users
SELECT 
    'Active Users' as metric,
    COUNT(*) as count
FROM users 
WHERE status = 'active';

-- Check for any open maintenance calls
SELECT 
    'Open Maintenance Calls' as metric,
    COUNT(*) as count
FROM maintenance_calls 
WHERE status IN ('open', 'assigned', 'in_progress');

-- Sample data from each table
SELECT 'Sample Users' as info;
SELECT id, ad_username, full_name, email, role, status, created_at 
FROM users 
LIMIT 5;

SELECT 'Sample Departments' as info;
SELECT id, name, code, location, status, created_at 
FROM departments 
LIMIT 5;

SELECT 'Sample Equipment' as info;
SELECT id, equipment_number, name, status, created_at 
FROM equipment 
LIMIT 5;

SELECT 'Sample Priority Levels' as info;
SELECT id, name, level, response_time 
FROM priority_levels 
ORDER BY level;

SELECT 'Sample Maintenance Calls' as info;
SELECT id, call_number, description, status, reported_date 
FROM maintenance_calls 
ORDER BY reported_date DESC 
LIMIT 5;
