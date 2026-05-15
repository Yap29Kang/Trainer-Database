@echo off
REM Quick verification script for Windows

echo.
echo === Trainer Database - File Structure Check ===
echo.

REM Check root files
echo Checking root files...
for %%F in (index.php config.php styles.css database-schema.sql README.md SETUP.md sample-upload.csv MIGRATION_NOTES.md) do (
    if exist "%%F" (
        echo [OK] %%F
    ) else (
        echo [MISSING] %%F
    )
)

echo.
echo Checking /api directory...
for %%F in (api\upload.php api\get-data.php api\get-stats.php api\get-provider.php api\set-role.php) do (
    if exist "%%F" (
        echo [OK] %%F
    ) else (
        echo [MISSING] %%F
    )
)

echo.
echo Checking /includes directory...
for %%F in (includes\layout.php includes\db.php) do (
    if exist "%%F" (
        echo [OK] %%F
    ) else (
        echo [MISSING] %%F
    )
)

echo.
echo Checking /views directory...
for %%F in (views\user.php views\admin.php) do (
    if exist "%%F" (
        echo [OK] %%F
    ) else (
        echo [MISSING] %%F
    )
)

echo.
echo === File Structure Check Complete ===
echo.
echo Next steps:
echo 1. Edit config.php with your database credentials
echo 2. Import database-schema.sql into MySQL
echo 3. Deploy files to web server
echo 4. Visit http://localhost/trainer-db/index.php
echo.
pause
