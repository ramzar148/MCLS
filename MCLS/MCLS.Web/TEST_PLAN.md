# MCLS Application Testing Plan

## Test Execution - Step by Step

### Prerequisites Check

Before testing, verify:
- [ ] MySQL database is running
- [ ] Database connection string is configured in `appsettings.json`
- [ ] Active Directory settings are configured
- [ ] .NET 10.0 SDK is installed

---

## Phase 1: Build Verification ? COMPLETED

```bash
Status: ? Build Successful
Errors: 0
Warnings: 13 (platform-specific, safe)
Output: bin\Debug\net10.0\MCLS.Web.dll
```

---

## Phase 2: Configuration Testing

### Test 1: Verify Database Connection String

**File**: `appsettings.json` (Line 9)

Current value:
```json
"DefaultConnection": "Server=localhost;Database=mcls_db;User=mcls_user;Password=mcls_password;Charset=utf8mb4;"
```

**Action Required**: Update with your actual MySQL credentials

**Test Command**:
```bash
# Test MySQL connectivity
mysql -h localhost -u mcls_user -p mcls_db
```

**Expected Result**: Successfully connect to MySQL database

---

### Test 2: Verify Active Directory Settings

**File**: `appsettings.json` (Lines 12-14)

Current values:
```json
"Domain": "YOURDOMAIN",
"BaseDN": "DC=yourdomain,DC=local",
"Server": "ldap://your-domain-controller.local"
```

**Action Required**: Update with your actual AD settings

**Test Command**:
```bash
# Test LDAP connectivity (if on Windows)
nltest /dsgetdc:YOURDOMAIN
```

**Expected Result**: Domain controller information displayed

---

## Phase 3: Application Startup Testing

### Test 3: Start the Application

**Command**:
```bash
dotnet run
```

**Expected Output**:
```
info: Microsoft.Hosting.Lifetime[14]
      Now listening on: http://localhost:5000
info: Microsoft.Hosting.Lifetime[14]
      Now listening on: https://localhost:5001
info: Microsoft.Hosting.Lifetime[0]
      Application started. Press Ctrl+C to shut down.
```

**URLs to test**:
- HTTP: http://localhost:5000
- HTTPS: https://localhost:5001

---

### Test 4: Check Application Logs

**Location**: Console output or `logs/` folder

**Look for**:
- Database connection established
- No error messages
- Application startup completed

---

## Phase 4: Functional Testing

### Test 5: Login Page

**URL**: https://localhost:5001/Account/Login

**Test Steps**:
1. Open browser and navigate to URL
2. Verify page loads without errors
3. Check form elements are visible:
   - Username field
   - Password field
   - Login button
4. Verify styling is correct (Bootstrap)

**Expected Result**: ? Login page displays correctly

**Screenshot**: (Take a screenshot for documentation)

---

### Test 6: Authentication Test

**Test Case 6.1**: Invalid Credentials
1. Enter invalid username/password
2. Click Login
3. **Expected**: Error message displayed

**Test Case 6.2**: Valid AD Credentials
1. Enter valid AD username and password
2. Click Login
3. **Expected**: Redirect to Dashboard

**Test Case 6.3**: Empty Fields
1. Leave fields empty
2. Click Login
3. **Expected**: Validation error

---

### Test 7: Dashboard Test

**URL**: https://localhost:5001/Dashboard/Index

**Test Steps**:
1. Login with valid credentials
2. Verify dashboard loads
3. Check statistics cards display:
   - [ ] Total Calls
   - [ ] Open Calls
   - [ ] Critical Calls
   - [ ] My Calls
   - [ ] Active Users
   - [ ] Active Equipment

4. Verify Recent Maintenance Calls table
5. Check navigation menu is visible

**Expected Result**: ? Dashboard displays all statistics

---

### Test 8: Maintenance Calls List

**URL**: https://localhost:5001/MaintenanceCalls/Index

**Test Steps**:
1. Click "Maintenance Calls" in navigation
2. Verify list page loads
3. Check table columns:
   - [ ] Call Number
   - [ ] Equipment
   - [ ] Description
   - [ ] Priority
   - [ ] Status
   - [ ] Reported By
   - [ ] Reported Date
   - [ ] Actions

4. Verify "New Call" button is visible

**Expected Result**: ? Maintenance calls list displays

---

### Test 9: Create Maintenance Call

**URL**: https://localhost:5001/MaintenanceCalls/Create

**Test Steps**:
1. Click "New Call" button
2. Verify form loads with fields:
   - [ ] Equipment dropdown
   - [ ] Priority dropdown
   - [ ] Description textarea
3. Fill out form
4. Click Submit
5. **Expected**: Call created, redirected to list

---

### Test 10: Navigation Test

**Test Steps**:
1. Click each menu item:
   - [ ] Dashboard
   - [ ] Maintenance Calls
   - [ ] Work Orders (placeholder)
   - [ ] Equipment (placeholder)
   - [ ] Reports (placeholder)
2. Verify navigation works without errors

---

### Test 11: User Profile Menu

**Test Steps**:
1. Click user dropdown in navbar
2. Verify menu items:
   - [ ] Profile link
   - [ ] Logout button
3. Click Logout
4. **Expected**: Redirect to Login page

---

## Phase 5: Database Testing

### Test 12: Verify Database Operations

**Run SQL Verification Script**:
```bash
mysql -u mcls_user -p mcls_db < database_verification.sql > test_results.txt
```

**Check Results**:
```bash
cat test_results.txt
```

**Expected Output**:
- All tables exist (? EXISTS)
- Record counts displayed
- Sample data shown
- No errors

---

### Test 13: Data Integrity

**SQL Queries to Run**:

```sql
-- Check user data
SELECT COUNT(*) as user_count FROM users WHERE status = 'active';

-- Check maintenance calls
SELECT COUNT(*) as call_count FROM maintenance_calls;

-- Check foreign key relationships
SELECT 
    COUNT(*) as calls_with_equipment
FROM maintenance_calls mc
WHERE mc.equipment_id IS NOT NULL;
```

**Expected**: All queries execute without errors

---

## Phase 6: Performance Testing

### Test 14: Page Load Times

**Measure Response Times**:

```bash
# Using curl (if available)
curl -w "@curl-format.txt" -o /dev/null -s "https://localhost:5001/Dashboard/Index"
```

**Expected**:
- Login page: < 500ms
- Dashboard: < 1000ms
- Maintenance calls list: < 1500ms

---

### Test 15: Concurrent Users

**Test Steps**:
1. Open application in 3-5 different browsers/tabs
2. Login with different users simultaneously
3. Navigate to different pages
4. Create maintenance calls

**Expected**: No errors, all users can work simultaneously

---

## Phase 7: Error Handling Testing

### Test 16: Database Connection Failure

**Test Steps**:
1. Stop MySQL service temporarily
2. Try to access application
3. **Expected**: Friendly error message (not crash)
4. Start MySQL service
5. **Expected**: Application recovers

---

### Test 17: Invalid Routes

**Test URLs**:
- https://localhost:5001/Invalid/Route
- https://localhost:5001/Dashboard/NonExistent

**Expected**: 404 error page or redirect

---

### Test 18: Session Timeout

**Test Steps**:
1. Login to application
2. Wait 30+ minutes (or modify session timeout in code)
3. Try to navigate
4. **Expected**: Redirect to login page

---

## Phase 8: Security Testing

### Test 19: Authentication Required

**Test Steps**:
1. Open browser in incognito/private mode
2. Try to access protected URLs directly:
   - https://localhost:5001/Dashboard/Index
   - https://localhost:5001/MaintenanceCalls/Index

**Expected**: Redirect to Login page

---

### Test 20: CSRF Protection

**Test Steps**:
1. Inspect form elements
2. Verify anti-forgery token present
3. Try to submit form without token (using browser dev tools)
4. **Expected**: Request rejected

---

## Test Results Summary

### Quick Test Checklist

Execute this minimal test suite to verify basic functionality:

```bash
# 1. Build the application
dotnet build
# Status: ?

# 2. Run the application
dotnet run
# Expected: Application starts, listening on ports 5000/5001

# 3. Test login page (open browser)
# Navigate to: https://localhost:5001
# Expected: Login page loads

# 4. Test authentication
# Login with AD credentials
# Expected: Dashboard loads

# 5. Test navigation
# Click through menu items
# Expected: All pages load

# 6. Test maintenance calls
# View list, create new call
# Expected: Operations successful

# 7. Test logout
# Click logout
# Expected: Return to login page
```

---

## Automated Test Script

Save this as `run_tests.ps1`:

```powershell
# MCLS Quick Test Script

Write-Host "?? MCLS Application Testing" -ForegroundColor Cyan
Write-Host "================================" -ForegroundColor Cyan

# Test 1: Build
Write-Host "`n? Test 1: Building application..." -ForegroundColor Yellow
dotnet build --configuration Debug
if ($LASTEXITCODE -eq 0) {
    Write-Host "? Build successful" -ForegroundColor Green
} else {
    Write-Host "? Build failed" -ForegroundColor Red
    exit 1
}

# Test 2: Configuration check
Write-Host "`n? Test 2: Checking configuration..." -ForegroundColor Yellow
if (Test-Path "appsettings.json") {
    Write-Host "? Configuration file exists" -ForegroundColor Green
} else {
    Write-Host "? Configuration file not found" -ForegroundColor Red
}

# Test 3: Database connectivity (optional)
Write-Host "`n? Test 3: Testing database connectivity..." -ForegroundColor Yellow
# Add your MySQL test here
Write-Host "??  Manual verification required" -ForegroundColor Yellow

# Test 4: Start application
Write-Host "`n? Test 4: Starting application..." -ForegroundColor Yellow
Write-Host "?? Open browser and navigate to:" -ForegroundColor Cyan
Write-Host "   https://localhost:5001" -ForegroundColor White
Write-Host "`nPress Ctrl+C to stop the application" -ForegroundColor Yellow

dotnet run
```

---

## Test Report Template

### Test Execution Summary

**Date**: [DATE]
**Tester**: [NAME]
**Environment**: [Local/Dev/Staging]

| Test # | Test Name | Status | Notes |
|--------|-----------|--------|-------|
| 1 | Build Verification | ? | No errors |
| 2 | Database Connection | ? | Pending |
| 3 | Application Startup | ? | Pending |
| 4 | Login Page | ? | Pending |
| 5 | Authentication | ? | Pending |
| 6 | Dashboard | ? | Pending |
| 7 | Maintenance Calls List | ? | Pending |
| 8 | Create Call | ? | Pending |
| 9 | Navigation | ? | Pending |
| 10 | Logout | ? | Pending |

**Legend**:
- ? Passed
- ? Failed
- ? Pending
- ?? Warning

---

## Issues Found

Document any issues here:

1. **Issue**: [Description]
   - **Severity**: Critical/High/Medium/Low
   - **Steps to Reproduce**: 
   - **Expected Result**: 
   - **Actual Result**: 
   - **Fix**: 

---

## Next Steps After Testing

Once testing is complete:

1. ? Fix any issues found
2. ? Update documentation
3. ? Prepare for deployment
4. ? Train users
5. ? Plan rollout strategy

---

**Ready to test? Start with the build verification and work through each phase!**
