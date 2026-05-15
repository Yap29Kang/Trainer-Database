# Trainer Database System

A PHP-based web application for managing training providers, trainers, courses, and participants.

## Project Structure

```
/
├── config.php                  # Database configuration
├── index.php                   # Main entry point
├── database-schema.sql         # Database schema
├── sample-upload.csv           # Sample CSV for upload reference
├── styles.css                  # Stylesheet (unchanged)
│
├── /api/
│   ├── upload.php             # Excel/CSV upload handler
│   ├── get-data.php           # Get filtered data
│   ├── get-stats.php          # Get statistics
│   ├── get-provider.php       # Get provider details
│   └── set-role.php           # Set user role
│
├── /includes/
│   ├── layout.php             # Main layout template
│   └── db.php                 # Database helper functions
│
└── /views/
    ├── user.php               # User view (limited access)
    └── admin.php              # Admin view (full access)
```

## Setup Instructions

### 1. Database Setup

1. Create the database by running the SQL schema:
   ```sql
   mysql -u root -p < database-schema.sql
   ```
   Or manually execute the contents of `database-schema.sql` in your MySQL client.

### 2. Configuration

Edit `config.php` and update the database credentials:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'your_password');
define('DB_NAME', 'training_management');
```

### 3. Web Server Setup

- Place all files in your web root directory (e.g., `/var/www/html/trainer-db/`)
- Ensure PHP is installed and configured
- Access the application at `http://localhost/trainer-db/index.php`

## File Upload Format

### CSV Format

Upload a CSV file with the following columns:

| TP_Name | TP_Venue | Trainer_Name | Item_Name | Item_Category | Item_Date | Participant_Name | Completion_Date |
|---------|----------|--------------|-----------|---------------|-----------|------------------|-----------------|
| Provider Name | Location | Trainer Name | Course Name | Category | 2024-01-15 | Participant Name | 2024-01-20 |

**Example:**
```csv
TP_Name,TP_Venue,Trainer_Name,Item_Name,Item_Category,Item_Date,Participant_Name,Completion_Date
ProManage Academy,Kuala Lumpur,Ahmad Malik,PMP Fundamentals,Project Management,2024-01-15,John Doe,2024-01-20
```

### Excel Format (.xlsx, .xls)

Same columns as CSV - convert Excel to CSV format for upload.

## Default Values

- **TP_Status**: Defaults to "In Consideration" for new providers
- **Trainer_Status**: Defaults to NULL (not a red flag)

## Features

### User View
- Browse training providers
- View trainer information
- Search and filter providers
- Sort alphabetically
- View course history

### Admin View
- All user features plus:
- Upload Excel/CSV data
- Update provider status
- Flag trainers
- Download database export

## Data Relationships

```
TrainingProvider (1) --< Assignment >-- (1) Trainer
TrainingProvider (1) --< Item >-- (1) Trainer
Item (1) --< Enrollment >-- (1) Participant
```

## API Endpoints

All endpoints return JSON responses.

### `GET /api/get-data.php?view=prov&search=&status=all&sort=asc`
Get filtered provider or trainer data.

**Parameters:**
- `view`: 'prov' or 'train'
- `search`: Search query
- `status`: 'all', 'approved', 'consideration', 'blacklisted'
- `sort`: 'asc' or 'desc'

### `GET /api/get-stats.php`
Get database statistics.

### `GET /api/get-provider.php?id=1`
Get single provider details with courses and trainers.

### `POST /api/upload.php`
Upload and process Excel/CSV file.

**Expected FormData:**
- `file`: File to upload

### `POST /api/set-role.php`
Set user role (user or admin).

**Expected JSON:**
```json
{ "role": "admin" }
```

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)

## Notes

- The system uses sessions to manage user roles
- All uploads are processed server-side
- No external JavaScript libraries are required
- CSS is custom (no framework dependencies)

## Future Enhancements

1. **PhpSpreadsheet Integration**: Install `composer require phpoffice/phpspreadsheet` for native .xlsx support
2. **Authentication**: Add user login system
3. **Export**: Download database as Excel
4. **Bulk Operations**: Update multiple records at once
5. **Search**: Advanced search filters
6. **Reports**: Generate custom reports

## License

[Your License Here]
