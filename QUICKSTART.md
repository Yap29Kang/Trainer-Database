# Quick Start Guide

## 3-Minute Setup

### 1️⃣ Update Database Config
```php
// config.php
define('DB_HOST', 'localhost');      // Your MySQL server
define('DB_USER', 'root');           // Your username
define('DB_PASS', '');               // Your password (empty = no password)
define('DB_NAME', 'training_management');
```

### 2️⃣ Create Database
Run this in MySQL client (phpMyAdmin, MySQL CLI, etc.):
```sql
SOURCE database-schema.sql;
```

Or copy-paste the entire `database-schema.sql` file into your MySQL client and execute.

### 3️⃣ Deploy & Access
- Copy all files to your web server (e.g., `/var/www/html/trainer-db/`)
- Open browser: `http://localhost/trainer-db/index.php`
- ✅ Done!

## Using the Application

### 👤 User View (Default)
1. Open index.php
2. Browse training providers
3. Use search, filter, sort features
4. Click on provider for details
5. View course history

### 👨‍💼 Admin View
1. Toggle to "Admin" in top-right corner
2. Gain access to:
   - **Upload Excel**: Click "Upload Excel" button
   - **Manage Status**: Update provider status
   - **Flag Trainers**: Mark trainers with red flags

### 📤 Uploading Data

#### Via CSV (Easiest)
1. Prepare CSV with columns:
   ```
   TP_Name, TP_Venue, Trainer_Name, Item_Name, 
   Item_Category, Item_Date, Participant_Name, Completion_Date
   ```

2. Switch to Admin view
3. Click "Upload Excel" button
4. Drop file or browse
5. Click "Update Database"
6. ✅ Data imported!

#### Via Excel
- Save as `.xlsx` or `.xls`
- Export to CSV format
- Follow CSV steps above

#### Sample Data
See `sample-upload.csv` for exact format and examples.

## Default Values (Auto-Set)

When uploading new providers and trainers:
- **Provider Status**: "In Consideration"
- **Trainer Status**: NULL (no red flag)

These can be changed in Admin view after upload.

## Data Structure

```
TrainingProvider (1) ──< Assignment >── (1) Trainer
TrainingProvider (1) ──< Item >── (1) Trainer
                                      ↓
Item (1) ──< Enrollment >── (1) Participant
```

## Column Reference

| Table | Column | Type | Notes |
|-------|--------|------|-------|
| TrainingProvider | TP_Name | String | Required |
| | TP_Venue | String | Optional |
| | TP_Status | String | Default: "In Consideration" |
| Trainer | Trainer_Name | String | Required |
| | Trainer_Status | String | Default: NULL |
| Item | Item_Name | String | Required |
| | Item_Category | String | Optional |
| | Item_Date | Date | Format: YYYY-MM-DD |
| Participant | Participant_Name | String | Required |
| Enrollment | Completion_Date | Date | Format: YYYY-MM-DD |

## Troubleshooting Quick Fixes

### "Cannot connect to database"
- Check `config.php` credentials
- Verify MySQL is running
- Test with: `mysql -u root -p`

### "Upload file not accepted"
- Use .csv, .xlsx, or .xls only
- Keep file size < 5MB (by default)
- Follow CSV format exactly

### "After upload, data doesn't appear"
- Refresh page (F5)
- Check database in MySQL
- Review error messages in browser console (F12)

### "Status change not saving"
- Verify you're in Admin view
- Click provider card first
- Try again

## File Guide

| File | Purpose |
|------|---------|
| `index.php` | Entry point - start here |
| `config.php` | **UPDATE WITH YOUR CREDENTIALS** |
| `database-schema.sql` | Run this first to create tables |
| `sample-upload.csv` | Example data format |
| `styles.css` | All styling (no changes needed) |
| `README.md` | Full documentation |
| `SETUP.md` | Detailed setup guide |
| `MIGRATION_NOTES.md` | Technical migration details |
| `/api/*.php` | API endpoints for data |
| `/includes/*.php` | Backend logic |
| `/views/*.php` | Role-specific views |

## Common Tasks

### Upload Provider Data
1. Admin view → Upload Excel
2. Select CSV file with providers + trainers
3. Click "Update Database"
4. Refresh page

### Update Provider Status
1. Admin view
2. Click provider card
3. Change status via modal
4. Done!

### Flag a Trainer
1. Admin view
2. Find trainer in "Trainers" tab
3. Click card → Mark as red flag
4. Select reason

### Export Data
- Feature coming soon!
- For now: Export directly from MySQL

### Search Providers
1. Type in search box
2. Results auto-filter
3. Can combine with status filter

## Browser Support

✅ Chrome 90+
✅ Firefox 88+
✅ Safari 14+
✅ Edge 90+

## Need Help?

1. Check `SETUP.md` for detailed setup
2. Review `README.md` for features
3. See `MIGRATION_NOTES.md` for technical details
4. Check browser console (F12 → Console) for errors
5. Review MySQL error logs

## What's New vs Old Version

| Feature | Old (index.html) | New (PHP) |
|---------|------------------|-----------|
| Mock Data | ✓ | ✗ Removed |
| Real Database | ✗ | ✓ MySQL |
| Excel Upload | ✗ Simulated | ✓ Real |
| User/Admin Roles | ✓ | ✓ Enhanced |
| Code Organization | Poor | Excellent |
| Maintainability | Hard | Easy |
| Scalability | Limited | Full |

---

**Ready to go!** 🚀

Start with `config.php`, then run the SQL, then access `index.php`
