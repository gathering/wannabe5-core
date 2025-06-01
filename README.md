# wannabe5-core
Wannabe5 Core - under development

### Development user
After running the seed a User 'testbruker' with the id `eaf9efc2-adbb-4b27-b5a9-f6c60197ab56` is created and a AccessToken with the token `testbruker`  
This can be used in `/docs/api` to test api endpoints  

![testbruker in api docs](docs/images/testbruker.png)


#### Local development - 2025-05-13
Needs docker and local php installation

```bash
cp .env.development .env
docker compose up -d
composer install
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve
```

Or using Visual Studio Code dev-containers ([requirements and setup-guide](https://code.visualstudio.com/docs/devcontainers/containers#_installation)):

First, set-up the env variables and the database:

```bash
cp .env.development .env
docker compose up -d
```

After the database is up and running, you can start the dev-container from your VS Code interface. 

Start VS Code, run the Dev Containers: Open Folder in Container... command from the Command Palette. For more information, see [here](https://code.visualstudio.com/docs/devcontainers/containers#_quick-start-open-an-existing-folder-in-a-container).

After the dev-container is running, you need to run the following inside the container to get started:

```bash
composer install
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve
```

After completing the final command above, the API should be available to you at `localhost:8000`.