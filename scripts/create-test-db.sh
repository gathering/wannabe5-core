#!/usr/bin/env bash
set -e

function create_database_grant_privilege() {
        local database=$1
        echo "  Creating database '$database' and granting privileges to '$POSTGRES_USER'"
        psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname "$POSTGRES_DB" <<-EOSQL
            CREATE DATABASE $database;
            GRANT ALL PRIVILEGES ON DATABASE $database TO $POSTGRES_USER;
EOSQL
}

create_database_grant_privilege wannabe5test
