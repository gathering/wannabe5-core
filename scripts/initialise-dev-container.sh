#!/usr/bin/env bash

function setup_composer() {
	echo 'Running composer install to make sure dependencies are installed and up to date'
	composer install
}

function seed_database() {
	echo 'Trying to seed database, in a fail-safely manner'
	# Call migration status command until we get a response, abort if waiting for more than 30 tries
	# local status=$(php artisan migrate:status)
	local status=""
	local tries=1
	while [[ !($status == *"Ran"* || $status == *"Pending"* || $status == *"table not found"*) && $tries -le 30 ]]; do
		echo "Waiting for database to be available... Attempt $tries"
		# Catch any errors from the command to avoid script failure
		status=$(php artisan migrate:status 2>/dev/null || echo "")
		sleep 2
		((tries++))
	done

	if [[ $tries -gt 30 ]]; then
		echo "Failed to get existing migration status after 30 attempts, aborting database initialization"
		return
	fi

	echo "$status"

	# Check if any migrations have status of "Ran"
	# If so, we assume the database is already seeded
	if [[ $status == *"Ran"* ]]; then
		echo 'Some migrations have already been run, assuming database have already been seeded, skipping'
	# Make sure we see indication of "Pending" migrations
	elif [[ $status == *"Pending"* || $status == *"table not found"* ]]; then
		echo 'No migrations seem to have have been run, migrating and seeding database'
		php artisan migrate:fresh --seed
	fi
}

setup_composer
seed_database

echo 'Automatic initialization of dev container is done, check out project README.md for more information on manual steps and general development information'
