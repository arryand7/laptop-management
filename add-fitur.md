Sure! Here’s the design and system concept for the new feature — the **“One-Page Laptop Borrow & Return System”** — including logic flow, UI/UX design, and backend structure.

---

## 🧠 **1. Core Concept of the Feature**

### 🎯 Goal:

To merge the *borrowing* and *returning* processes into **a single, interactive page**, requiring only two main inputs:

1. **Student Data (via QR/Barcode)**
2. **Laptop Data (via QR/Barcode)**

The system will automatically:

* Detect whether the action is a **new borrowing** or a **return**.
* Display the appropriate form dynamically.
* Record the data with the correct status (*Borrowed* or *Returned*).

---

## 🔄 **2. System Logic Flow**

Here’s how the backend logic works:

```
[Staff opens "Laptop Transaction" page]
        ↓
[Scan Student QR Code]  → retrieve student data
        ↓
[Scan Laptop QR Code]   → retrieve laptop data
        ↓
┌───────────────────────────────────┐
│  Check laptop status in database  │
├───────────────────────────────────┤
│  If laptop.status = "available" → BORROW PROCESS  │
│  If laptop.status = "borrowed"  → RETURN PROCESS  │
└───────────────────────────────────┘
        ↓
[Execute the appropriate action]
        ↓
[Display confirmation and notification]
```

---

## 🧩 **3. UI/UX Page Design**

### 🖥️ **Page: “Laptop Transaction”**

This layout is designed for speed, clarity, and efficiency for school staff.

---

### 🔹 **Header**

* Title: **Laptop Borrow/Return System**
* Subtitle: *“Scan student and laptop QR codes to automatically borrow or return laptops.”*
* Header color: **Primary Blue (#1E88E5)**

---

### 🔹 **Main Input Section**

A large centered card with two columns side-by-side.

| Left Column                         | Right Column                        |
| ----------------------------------- | ----------------------------------- |
| **Scan Student QR**                 | **Scan Laptop QR**                  |
| User icon + input field             | Laptop icon + input field           |
| Automatically filled after scanning | Automatically filled after scanning |
| Small *Clear / Reset* button below  | Small *Clear / Reset* button below  |

💡 **Input Design:**

* Background: `#FFFFFF`
* Border: `#E2E8F0`
* Focus line: `#1E88E5`
* Small icons inside (`<User />`, `<Laptop />`)

---

### 🔹 **Automatic Information Section**

After both QR codes are scanned:

* The system shows an **information card** based on detection.

#### If laptop status = *Available → Borrowing Process*

**Information Card:**

```
Student: Ahmad Fauzi (X-2)
Laptop: HP Elitebook 840 G5
Status: READY TO BORROW
---------------------------------------
Purpose of Use: [Text Input Field]
Borrow Time: auto (now)
Expected Return: auto (now + X hours)
[Confirm Borrow Button]
```

#### If laptop status = *Borrowed by same student → Return Process*

```
Student: Ahmad Fauzi (X-2)
Laptop: HP Elitebook 840 G5
Status: RETURN IN PROGRESS
---------------------------------------
Borrowed At: 2025-10-13 08:00
Due At: 2025-10-13 15:00
Return Time: auto (now)
[Confirm Return Button]
```

#### If laptop status = *Borrowed by another student*

```
⚠️ Error: Laptop is currently borrowed by another student (Aisha Putri - X-3)
```

---

### 🔹 **Notifications & Feedback**

Use *toast notifications* for quick responses:

* ✅ *“Borrowing successfully recorded.”*
* 🔁 *“Laptop successfully returned.”*
* ⚠️ *“Laptop is already borrowed by another student.”*

---

### 🔹 **Recent Transactions (optional, bottom section)**

A small table displaying the 5 most recent records:

| Time  | Student | Laptop | Action | Status  |
| ----- | ------- | ------ | ------ | ------- |
| 08:05 | Ahmad   | HP G5  | Borrow | Success |
| 09:30 | Ahmad   | HP G5  | Return | On Time |

---

## ⚙️ **4. Backend Logic (Laravel Example)**

### Main Routes:

```php
Route::get('/transaction', [TransactionController::class, 'index'])->name('transaction.index');
Route::post('/transaction/process', [TransactionController::class, 'process'])->name('transaction.process');
```

### Controller Logic:

```php
public function process(Request $request)
{
    $student = User::where('qr_value', $request->student_qr)->first();
    $laptop = Laptop::where('qr_value', $request->laptop_qr)->first();

    if (!$student || !$laptop) {
        return response()->json(['status' => 'error', 'message' => 'Invalid QR code.']);
    }

    if ($laptop->status == 'available') {
        // → Borrowing process
        $borrow = Borrowing::create([
            'user_id' => $student->id,
            'laptop_id' => $laptop->id,
            'petugas_id' => auth()->id(),
            'usage_description' => $request->usage,
            'borrow_time' => now(),
            'due_time' => now()->addHours(6),
            'status' => 'borrowed'
        ]);
        $laptop->update(['status' => 'borrowed']);

        return response()->json(['status' => 'success', 'message' => 'Laptop borrowed successfully.']);
    } else {
        // → Return process
        $activeBorrow = Borrowing::where('laptop_id', $laptop->id)
            ->where('status', 'borrowed')->first();

        if ($activeBorrow && $activeBorrow->user_id == $student->id) {
            $status = now()->greaterThan($activeBorrow->due_time) ? 'late' : 'returned';
            $activeBorrow->update([
                'return_time' => now(),
                'status' => $status
            ]);
            $laptop->update(['status' => 'available']);

            if ($status == 'late') {
                Violation::create([
                    'user_id' => $student->id,
                    'borrowing_id' => $activeBorrow->id,
                    'violation_type' => 'Late Return',
                    'violation_date' => now(),
                    'sanction_status' => 'not_applied'
                ]);
            }

            return response()->json(['status' => 'success', 'message' => 'Laptop returned successfully.']);
        }

        return response()->json(['status' => 'error', 'message' => 'Laptop borrowed by another user.']);
    }
}
```

---

## 📊 **5. UX Advantages**

✅ Only **one page** for both processes — fast and efficient.
✅ No need to choose “borrow” or “return” mode — auto-detected by system.
✅ Lower error risk (QR-based).
✅ Compatible with *USB barcode scanners* or *mobile camera input*.
✅ Can serve as a **cashier-like full-screen mode** for staff use.

---

## 🧩 **6. Color Palette & Aesthetic**

To match your **blue primary + light navy accent** theme:

* Background: `#F8FAFC`
* Card background: `#FFFFFF`
* Primary button: `#1E88E5`
* Status colors:

  * READY → Light Green `#DCFCE7`
  * RETURN → Light Blue `#DBEAFE`
  * ERROR → Light Red `#FEE2E2`

---