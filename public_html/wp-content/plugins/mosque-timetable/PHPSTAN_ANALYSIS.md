# PHPStan Analysis: Critical vs Non-Critical Issues

## 🔍 **Issue Classification**

### 🔴 **HIGH PRIORITY - Potential Real Issues**

#### 1. **Missing Methods (Lines 4616, 4623, 4639, etc.)**
```
Call to an undefined method MosqueTimetablePlugin::get_prayer_times()
Call to an undefined method MosqueTimetablePlugin::get_terminology_overrides()
Call to an undefined method MosqueTimetablePlugin::get_hijri_date()
```
**Impact**: HIGH - These methods are called but may not exist
**Investigation Needed**: ✅ CRITICAL

#### 2. **Undefined Variables (Lines 8253, 8256)**
```
Variable $month_data might not be defined.
Variable $ok might not be defined.
```
**Impact**: MEDIUM - Could cause runtime errors
**Investigation Needed**: ✅ IMPORTANT

#### 3. **Type Mismatch Issues (Lines 5953, 5956)**
```
Cannot assign offset 'day_number' to string.
Parameter #1 $array of function usort contains unresolvable type.
```
**Impact**: MEDIUM - Could cause data corruption
**Investigation Needed**: ✅ IMPORTANT

### 🟡 **MEDIUM PRIORITY - Quality Issues**

#### 4. **External Library Detection (Lines 8174, 8176, 8556)**
```
Call to static method parse() on an unknown class Shuchkin\SimpleXLSX.
```
**Impact**: LOW - PHPStan configuration issue, not code issue
**Resolution**: Configure PHPStan to understand Composer autoloading

#### 5. **WordPress Path Issues (Lines 218, 8607)**
```
Path in require_once() "./wp-admin/includes/file.php" is not a file
```
**Impact**: LOW - WordPress context paths work differently
**Resolution**: PHPStan WordPress stubs needed

### 🟢 **LOW PRIORITY - Documentation Only**

#### 6. **Missing Type Hints (180+ instances)**
All the `missingType.return`, `missingType.parameter` errors are documentation issues that don't affect functionality but improve code quality.

---

## 🔍 **Critical Issue Investigation**

Let me check the most critical issues: