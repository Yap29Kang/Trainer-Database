# Deploying the schema to Supabase (Postgres)

This file describes how to apply `database-schema.sql` to a Supabase Postgres instance and how to configure your app.

1. Get your database connection details
   - In the Supabase dashboard, go to Project Settings → Database → Connection Pooling or Credentials.
   - Note the host, port (usually 5432), database name, user, and password.

2. Using `psql` (recommended)
   - Ensure `psql` is installed (Postgres client).
   - Export the password and run the helper script:

```bash
export PGPASSWORD="<DB_PASSWORD>"
./apply_schema.sh <DB_HOST> <DB_PORT> <DB_NAME> <DB_USER>
```

Example:

```bash
export PGPASSWORD="hunter2"
./apply_schema.sh db.abcd1234.supabase.co 5432 postgres postgres
```

3. Using the Supabase SQL editor
   - Open the SQL Editor in the Supabase dashboard and paste the contents of `database-schema.sql` and run it.

4. Configure your app environment variables
   - Set the following environment variables for your deployment (Render/Fly/Railway or container):

```
DB_DRIVER=pgsql
DB_HOST=<DB_HOST>
DB_PORT=5432
DB_NAME=<DB_NAME>
DB_USER=<DB_USER>
DB_PASS=<DB_PASSWORD>
```

5. Notes
   - Supabase provides a `postgres` user by default; for security, create a dedicated DB user with limited privileges for your app.
   - Avoid exposing the `service_role` key in the frontend. Use server-side secrets only.
