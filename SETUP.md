# Installation & Setup Guide

## Quick Start

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server with PHP support

### Step 1: Database Setup

1. Open your MySQL client (phpMyAdmin, MySQL CLI, or any MySQL tool)

2. Run the SQL schema to create the database:
   ```sql
   SOURCE database-schema.sql;
   ```
   
   Or manually copy-paste the contents of `database-schema.sql` and execute it.

### Step 2: Configure Database Connection

Edit `config.php` and update with your database credentials:

```php
define('DB_HOST', 'localhost');      // Your MySQL server
define('DB_USER', 'root');            // MySQL username
define('DB_PASS', 'your_password');   // MySQL password (empty if none)
define('DB_NAME', 'training_management');
```

### Step 3: Deploy Files

1. Copy all files to your web server directory:
   ```
   /var/www/html/trainer-db/
   ```

2. Ensure proper file permissions:
   ```bash
   chmod 755 /var/www/html/trainer-db/
   chmod 644 /var/www/html/trainer-db/*.php
   ```

### Step 4: Access the Application

Open your browser and navigate to:
```
http://localhost/trainer-db/index.php
```

Or if you have a virtual host configured:
```
http://trainer-db.local/
```

## File Structure Explanation

```
/
├── config.php                  # Database config - UPDATE THIS
├── index.php                   # Entry point - routes to user/admin
├── README.md                   # Main documentation
├── SETUP.md                    # This file
├── database-schema.sql         # Database creation script
├── sample-upload.csv           # Example CSV for uploads
├── styles.css                  # All styling
│
├── /api/                       # API endpoints for JavaScript
│   ├── upload.php             # Handle file uploads
│   ├── get-data.php           # Fetch providers/trainers
│   ├── get-stats.php          # Fetch statistics
│   ├── get-provider.php       # Fetch provider details
│   └── set-role.php           # Switch user/admin role
│
├── /includes/                  # Shared components
│   ├── layout.php             # HTML template
│   └── db.php                 # Database functions
│
└── /views/                     # Role-specific content
    ├── user.php               # User view
    └── admin.php              # Admin view
```

## CSV/Excel Upload Format

### Required Columns

| Column | Type | Required | Description |
|--------|------|----------|-------------|
| TP_Name | String | Yes | Training Provider name |
| TP_Venue | String | No | Provider location/venue |
| Trainer_Name | String | Yes | Trainer name |
| Item_Name | String | Yes | Course/item name |
| Item_Category | String | No | Course category |
| Item_Date | Date (YYYY-MM-DD) | No | Course date |
| Participant_Name | String | No | Participant name |
| Completion_Date | Date (YYYY-MM-DD) | No | Completion date |

### CSV Template

```csv
TP_Name,TP_Venue,Trainer_Name,Item_Name,Item_Category,Item_Date,Participant_Name,Completion_Date
ProManage Academy,Kuala Lumpur,Ahmad Malik,PMP Fundamentals,Project Management,2024-01-15,John Doe,2024-01-20
ProManage Academy,Kuala Lumpur,Ahmad Malik,PMP Fundamentals,Project Management,2024-01-15,Jane Smith,2024-01-20
ProManage Academy,Kuala Lumpur,Siti Yusof,Agile Workshop,Agile,2024-02-10,John Doe,
TechCraft Institute,Singapore,Ravi Kumar,Python 101,Programming,2024-01-22,Alice Johnson,2024-01-29
```

### Excel Format

Create an Excel file with the same columns. Can save as:
- `.xlsx` (Excel 2007+)
- `.xls` (Excel 97-2003)

Then export as CSV for upload, or implement PhpSpreadsheet for direct .xlsx support (see notes below).

## Database Defaults

### Training Provider Status
- **Default**: "In Consideration"
- **Valid Values**: "Approved", "In Consideration", "Blacklisted"

### Trainer Status
- **Default**: NULL (No red flag)
- **When Flagged**: Stores reason for flag

## Users & Roles

### User View
- Browse training providers
- Search and filter
- View course history
- Sort alphabetically
- **No upload capability**

### Admin View
- All User features +
- Upload Excel/CSV data
- Manage provider status
- Flag/unflag trainers
- Download exports (future)

To switch roles, use the "View as:" toggle in top-right corner.

## API Integration

The system uses JavaScript to make API calls to PHP endpoints. All responses are JSON.

### Example: Get All Providers

```javascript
fetch('api/get-data.php?view=prov&status=all&sort=asc')
  .then(r => r.json())
  .then(data => console.log(data));
```

### Example: Upload File

```javascript
const formData = new FormData();
formData.append('file', fileInput.files[0]);

fetch('api/upload.php', {
  method: 'POST',
  body: formData
})
.then(r => r.json())
.then(data => console.log(data));
```

## Troubleshooting

### Issue: "Database connection failed"

**Solution:**
- Check `config.php` credentials
- Verify MySQL server is running
- Ensure database exists: `mysql -u root -p` then `SHOW DATABASES;`

### Issue: "File upload error"

**Solution:**
- Check file format (.csv, .xlsx, .xls only)
- Verify file size is not too large
- Ensure `tmp_upload_dir` is writable
- Check PHP `upload_max_filesize` in php.ini

### Issue: "CSV upload not working but Excel should"

**Solution:**
1. Convert Excel to CSV first, or
2. Install PhpSpreadsheet:
   ```bash
   composer require phpoffice/phpspreadsheet
   ```

### Issue: "404 errors on API endpoints"

**Solution:**
- Verify API files exist in `/api/` directory
- Check `index.php` file is in root directory
- Verify mod_rewrite is enabled (if using .htaccess)

### Issue: "Role switch not working"

**Solution:**
- Enable PHP sessions in `php.ini`
- Check browser cookies are enabled
- Clear browser cache

## Performance Optimization

1. **Database Indexing**: Add indexes on frequently queried columns
   ```sql
   ALTER TABLE TrainingProvider ADD INDEX idx_status (TP_Status);
   ALTER TABLE Trainer ADD INDEX idx_status (Trainer_Status);
   ```

2. **Caching**: Implement query caching for statistics

3. **Pagination**: Limit data queries with LIMIT/OFFSET for large datasets

## Security Considerations

1. **SQL Injection**: Using prepared statements (PDO) ✓
2. **File Upload**: Validate file types and size ✓
3. **CSRF**: Add tokens for state-changing requests (future)
4. **Input Validation**: Implement on all API endpoints (future)
5. **Access Control**: Add authentication layer (future)

## Advanced Configuration

### Enable XLSX Support (Optional)

Install Composer dependencies:
```bash
cd /path/to/trainer-db
composer require phpoffice/phpspreadsheet
```

Then uncomment the PhpSpreadsheet code in `api/upload.php` (lines ~65-80).

### Apache .htaccess Setup (Optional)

If using Apache, create `.htaccess` in root:
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php?path=$1 [QSA,L]
</IfModule>
```

## Support & Further Help

- Check the main `README.md` for feature documentation
- Review `sample-upload.csv` for data format examples
- Check browser console for JavaScript errors (F12)
- Check PHP error logs for server issues

---

**Last Updated**: May 2026
**Version**: 1.0
