#!/usr/bin/env bash
# Helper script to apply `database-schema.sql` to a Postgres database.
# Usage:
#   export PGPASSWORD=yourpassword
#   ./apply_schema.sh "host" port dbname user
# Example:
#   export PGPASSWORD=yourpassword
#   ./apply_schema.sh db.supabase.co 5432 postgres postgres

if [ "$#" -lt 4 ]; then
  echo "Usage: $0 HOST PORT DBNAME USER"
  exit 2
fi

HOST="$1"
PORT="$2"
DBNAME="$3"
USER="$4"

echo "Applying database-schema.sql to ${USER}@${HOST}:${PORT}/${DBNAME}"

psql -h "$HOST" -p "$PORT" -U "$USER" -d "$DBNAME" -f database-schema.sql
