
# How to develop on Wannabe5-Core

## Configure pre-commits

Run the following command to add your pre-commit config:
```bash
php artisan app:precommit 
```

## Linting

We use Laravel Lint, and your pull-request will not be accepted by our project automations without running this before commiting. 

If the pre-commit (Note: Does not exist yet!) fails, run this before commiting:

```bash
./vendor/bin/pint
```

## Generate OpenAPI documentation

This needs to be done before a pull-request is approved.

```bash
php artisan scramble:export
```

## Creating a new model

We want to focus on using Resources, Requests and lean Controllers. Place validation in Requests and create Resources for all return objects. 

```bash
php artisan make:request <ModelName>Request
php artisan make:resource <ModelName>Resource
```

Refer to the Laravel documentation on how to use [Resources](https://laravel.com/docs/12.x/eloquent-resources) and [Requests](https://laravel.com/docs/12.x/validation#form-request-validation) in your controllers.

Also consider creating a Factory to be able to seed the model.

```bash
php artisan make:factory <ModelName>Factory
```