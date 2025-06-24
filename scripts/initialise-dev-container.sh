#!/usr/bin/env bash

function await_database() {
    # Wait for the database to be available, retrying every 2 seconds, up to 30 times
    local status=""
    local tries=1
    while [[ !($status == *"Ran"* || $status == *"Pending"* || $status == *"table not found"*) && $tries -le 30 ]]; do
        echo "Waiting for database to be available... Attempt $tries"
        # Catch any errors from the command to avoid script failure
        status=$(php artisan migrate:status 2>/dev/null || echo "")
        sleep 2
        ((tries++))
    done

    return $((tries > 30 ? 1 : 0))
}

function is_fresh_database() {
    local status=$(php artisan migrate:status 2>/dev/null || echo "")
    echo "$status"

    if [[ $status == *"table not found"* ]]; then
        return 0
    elif [[ $status == *"Ran"* ]]; then
        return 1
    elif [[ $status == *"Pending"* ]]; then
        return 0
    fi

    return 1 # Fail safely
}

function setup_laravel() {
    is_fresh_database
    fresh_database=$?

    if [[ $fresh_database > 0 ]]; then
        echo 'Database is not fresh, skipping application key generation and seeding'
        return 1
    fi

    echo 'Generating application key'
    php artisan key:generate

    echo 'Trying to seed database, in a fail-safely manner'
    echo 'No migrations seem to have have been run, migrating and seeding database'
    php artisan migrate:fresh --seed
    php artisan app:precommit
}

function main_setup() {
    echo 'Running composer install to make sure dependencies are installed and up to date'
    composer install

    await_database
    await_result=$?

    if [[ $await_result == 0 ]]; then
        setup_laravel
    else
        echo 'Database is not available, skipping application setup'
    fi
}

main_setup

echo 'Automatic initialization of dev container is done, check out project README.md for more information on manual steps and general development information'

# If any additional commands are passed to this script, execute them (ie. act as container entrypoint)
if [[ "$1" ]]; then
    eval "$@"
fi
