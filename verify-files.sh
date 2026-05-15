#!/bin/bash
# Quick verification script to check if all files are in place

echo "=== Trainer Database - File Structure Check ==="
echo ""

# Check root files
echo "Checking root files..."
files=("index.php" "config.php" "styles.css" "database-schema.sql" "README.md" "SETUP.md" "sample-upload.csv" "MIGRATION_NOTES.md")
for file in "${files[@]}"; do
  if [ -f "$file" ]; then
    echo "✓ $file"
  else
    echo "✗ MISSING: $file"
  fi
done

echo ""
echo "Checking /api directory..."
api_files=("upload.php" "get-data.php" "get-stats.php" "get-provider.php" "set-role.php")
for file in "${api_files[@]}"; do
  if [ -f "api/$file" ]; then
    echo "✓ api/$file"
  else
    echo "✗ MISSING: api/$file"
  fi
done

echo ""
echo "Checking /includes directory..."
inc_files=("layout.php" "db.php")
for file in "${inc_files[@]}"; do
  if [ -f "includes/$file" ]; then
    echo "✓ includes/$file"
  else
    echo "✗ MISSING: includes/$file"
  fi
done

echo ""
echo "Checking /views directory..."
view_files=("user.php" "admin.php")
for file in "${view_files[@]}"; do
  if [ -f "views/$file" ]; then
    echo "✓ views/$file"
  else
    echo "✗ MISSING: views/$file"
  fi
done

echo ""
echo "=== File Structure Check Complete ==="
echo ""
echo "Next steps:"
echo "1. Edit config.php with your database credentials"
echo "2. Import database-schema.sql into MySQL"
echo "3. Deploy files to web server"
echo "4. Visit http://localhost/trainer-db/index.php"
