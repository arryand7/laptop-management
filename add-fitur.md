**Laptop Rack Checklist Page** — where all laptops are displayed in a table with checkboxes pre-selected by default, and staff can uncheck those that are missing from the rack.

---

## 🧠 **1. Core Concept of the Feature**

### 🎯 **Goal:**

Provide a page that:

* Displays **all laptops** from the database.
* By default, **all laptops are pre-checked** ✅ (meaning they are present on the rack).
* Staff only need to **uncheck** ❌ laptops that are **not on the rack**.
* When the checklist is submitted, the system will:

  * Record the results (found/missing laptops).
  * Automatically mark **violations** for laptops missing without permission.

---

## 🔄 **2. System Workflow**

```
[Staff opens "Laptop Rack Checklist" page]
        ↓
[System loads all laptops pre-checked by default]
        ↓
[Staff unchecks any laptop not found on the rack]
        ↓
[Click “Save Checklist” button]
        ↓
[System compares unchecked laptops]
        ↓
[Mark them as “missing” or “borrowed”]
        ↓
[Automatically log violations if missing without reason]
        ↓
[Save results and display summary]
```

---

## 🧩 **3. UI/UX Design**

### 🖥️ **Page: “Laptop Rack Checklist”**

---

### 🔹 **Header**

* Title: **Laptop Rack Checklist**
* Subtitle: *“Check and ensure all laptops have returned to the rack. Uncheck those that are missing.”*
* Top-right buttons:

  * 🔄 **Start New Checklist**
  * 📜 **Checklist History**

---

### 🔹 **Laptop List Table**

| No | Checkbox | Laptop Code | Laptop Name         | System Status | Notes            |
| -- | -------- | ----------- | ------------------- | ------------- | ---------------- |
| 1  | ☑️       | LPT-001     | HP Elitebook 840 G5 | Available     | —                |
| 2  | ☑️       | LPT-002     | Asus VivoBook       | Borrowed      | Currently in use |
| 3  | ☑️       | LPT-003     | Lenovo ThinkPad     | Available     | —                |
| 4  | ☑️       | LPT-004     | Acer Aspire         | Available     | —                |

> ✅ All laptops are checked by default.
> ❌ The staff only **unchecks** laptops that are **missing from the rack**.

---

### 🔹 **Below the Table**

A real-time summary updates automatically when boxes are unchecked:

```
Total Laptops: 40
Found: 38
Missing: 2
Currently Borrowed: 3
```

Right-aligned action button:

* **[💾 Save Checklist]** — primary color `#1E88E5`

---

### 🔹 **After Saving**

Show a confirmation modal or alert:

```
Checklist successfully saved!
📦 Found: 38
⚠️ Missing: 2
🕓 Borrowed: 3
```

If missing laptops have no borrow or permission record, automatically generate violations:

```
Violations added for:
- LPT-012: Laptop not found
- LPT-020: Laptop not found
```

---

## ⚙️ **4. Database Structure**

Simplified version (compared to the scanning-based checklist).

### 📘 Table: `checklist_sessions`

| Field         | Type     | Description                |
| ------------- | -------- | -------------------------- |
| id            | INT      | Primary key                |
| staff_id      | INT      | Staff performing checklist |
| start_time    | DATETIME | When checklist started     |
| end_time      | DATETIME | When checklist ended       |
| total_laptops | INT      | Total number of laptops    |
| found_count   | INT      | Number found               |
| missing_count | INT      | Number missing             |
| note          | TEXT     | Optional notes             |

---

### 📘 Table: `checklist_details`

| Field                | Type                               | Description                      |
| -------------------- | ---------------------------------- | -------------------------------- |
| id                   | INT                                | Primary key                      |
| checklist_session_id | INT                                | Foreign key to checklist session |
| laptop_id            | INT                                | Linked laptop                    |
| status               | ENUM('found','missing','borrowed') | Result status                    |
| note                 | TEXT                               | Optional note                    |

---

### Routes:

```php
Route::get('/checklist', [ChecklistController::class, 'index'])->name('checklist.index');
Route::post('/checklist/save', [ChecklistController::class, 'save'])->name('checklist.save');
```

### Controller Example:

```php
public function save(Request $request)
{
    $checkedLaptops = $request->input('found_laptops', []);
    $allLaptops = Laptop::all();
    $session = ChecklistSession::create([
        'staff_id' => auth()->id(),
        'start_time' => now(),
        'end_time' => now(),
        'total_laptops' => $allLaptops->count(),
    ]);

    foreach ($allLaptops as $laptop) {
        if (in_array($laptop->id, $checkedLaptops)) {
            // Laptop found
            ChecklistDetail::create([
                'checklist_session_id' => $session->id,
                'laptop_id' => $laptop->id,
                'status' => 'found'
            ]);
        } else {
            // Laptop missing or borrowed
            $status = $laptop->status === 'borrowed' ? 'borrowed' : 'missing';

            ChecklistDetail::create([
                'checklist_session_id' => $session->id,
                'laptop_id' => $laptop->id,
                'status' => $status
            ]);

            if ($status === 'missing') {
                Violation::create([
                    'laptop_id' => $laptop->id,
                    'violation_type' => 'Laptop not found in rack',
                    'violation_date' => now(),
                    'sanction_status' => 'not_applied'
                ]);
            }
        }
    }

    $session->update([
        'found_count' => ChecklistDetail::where('checklist_session_id', $session->id)->where('status', 'found')->count(),
        'missing_count' => ChecklistDetail::where('checklist_session_id', $session->id)->where('status', 'missing')->count(),
    ]);

    return response()->json(['message' => 'Checklist successfully saved']);
}
```

---

## 🎨 **6. Color Scheme & UI Elements**

| Element          | Color                           | Description |
| ---------------- | ------------------------------- | ----------- |
| Header           | `#1E88E5`                       | Main blue   |
| Table background | `#FFFFFF`                       | Clean white |
| Odd row          | `#F8FAFC`                       | Light gray  |
| Checkbox active  | `#1E88E5`                       | Bright blue |
| Found            | Green `#10B981`                 |             |
| Missing          | Red `#EF4444`                   |             |
| Borrowed         | Yellow `#F59E0B`                |             |
| Save button      | Blue `#1E88E5`, hover `#1565C0` |             |

---

## 💡 **7. Advantages of This Design**

✅ Fast checklist — simply uncheck missing laptops
✅ No need for individual QR scans
✅ Automatically updates database and generates reports
✅ Can be done anytime (ad-hoc inventory check)
✅ Automatically creates **violation records** for missing laptops

---