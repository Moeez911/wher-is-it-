# Where Is It? 🎓

**Campus Lost & Found Portal — University Property Loss & Recovery Ledger**

A web-based Lost & Found system built for university campuses (UCP Core), allowing students and staff to report found items, declare missing belongings, search the inventory, and verify ownership via QR codes.

---

## 📸 Screenshots

### Home Page
The landing page displays recently found items along with quick stats on unclaimed inventory and active lost declarations.

![Home Page](images/01-home.png)

### Found Items Inventory
Browse all items that have been found and logged into the system, including electronics, accessories, documents, and more.

![Found Items Inventory](images/02-inventory.png)

### Missing Objects Board
View items reported as lost by other students, each marked with a "SEARCHING" status until recovered.

![Missing Objects](images/03-missing-items.png)

### Item Detail / Case Registry
Each item has a dedicated detail page showing case metadata (reference track, classification, status, contact info) and a QR-based ownership verification option.

![Item Detail Page](images/04-item-detail.png)

### Search Functionality
Quickly search through found items using keywords (e.g., "laptop") to find matching results.

![Search Results](images/09-search.png)

### Sign In
Existing users can log in using their university email and password to access full features like commenting and reporting.

![Sign In](images/05-signin.png)

### Sign Up
New users can create an account using their username, phone number, university email, and password.

![Sign Up](images/06-signup.png)

### Report a Found Item
Users can log a found item with a title, category, image upload, and discovery details.

![Found Item Form](images/07-found-form.png)

### Report a Missing Item
Users can report their own missing belongings, including unique identifying details to help with verification.

![Missing Item Form](images/08-missing-form.png)

---

## ✨ Features

- 🔍 **Search Functionality** — Quickly find found items by keyword
- 📦 **Found Items Inventory** — Browse all logged found items with images, dates, and descriptions
- 🚨 **Missing Objects Board** — View active lost item declarations from other users
- 📝 **Report Found Items** — Log items you've found with category, image, and description
- 📢 **Report Missing Items** — Declare lost belongings with unique identifying details
- 🔐 **Authentication** — Sign up / sign in with university email
- ✅ **QR Code Ownership Verification** — Verify and claim ownership of an item via QR scan
- 📊 **Case Registry Metadata** — Each item has a reference number, category, status, and contact info
- 🟢 **Status Tracking** — Items are tracked as `PENDING` (found, unclaimed) or `SEARCHING` (reported missing)

---

## 🛠️ Tech Stack

- **Frontend:** HTML, CSS, JavaScript
- **Backend:** PHP
- **Database:** MySQL
- **Hosting:** InfinityFree (production) / XAMPP (local development)
- **Verification:** QR Code generation for ownership claims

---

## 🚀 Getting Started (Local Setup)

1. Clone this repository
   ```bash
   git clone https://github.com/<your-username>/where-is-it.git
   ```

2. Set up a local server environment (e.g., **XAMPP**)
   - Place the project folder inside `htdocs/`

3. Import the database
   - Create a MySQL database
   - Import the provided `.sql` file via phpMyAdmin

4. Configure database connection
   - Update database credentials in the config file (e.g., `db.php` / `config.php`)

5. Start Apache & MySQL via XAMPP, then visit:
   ```
   http://localhost/where-is-it
   ```

---

## 📂 Categories Supported

- Electronics
- Bags
- Documents / ID Cards
- Jewelry & Accessories
- Sports Equipment
- Keys

---

## 📋 Status Types

| Status      | Meaning                                  |
|-------------|-------------------------------------------|
| `PENDING`   | Item has been found and is awaiting claim |
| `SEARCHING` | Item has been reported missing by a user  |

---

## 🤝 Contributing

Contributions, issues, and feature requests are welcome! Feel free to check the [issues page](../../issues) or submit a pull request.

---

## 📄 License

This project is open-source and available for educational use.

---

## 👤 Author

Built by **Moeez** as part of a university database systems project.
