Bedrock scaffold (instructions)
=================================

This folder is a scaffold for using Bedrock (roots/bedrock) as the project structure for the AMA E project.

What I created here
- `.env.example` — example environment file for Bedrock
- `.gitignore` — ignore vendor and generated WordPress core

How to create a real Bedrock installation

1. From the `app/amae` directory run:

```bash
# create bedrock in ./bedrock
composer create-project roots/bedrock bedrock
```

2. Copy values from your current `.env` (if any) into `bedrock/.env` or use the `.env.example` as a starting point.

3. Update `docker-compose.yml` (already adjusted) — services now mount `./bedrock/web` as webroot.

4. Move your current theme(s) and mu-plugins into `bedrock/web/app/themes/` and `bedrock/web/app/mu-plugins/` respectively. For plugins, prefer managing them with Composer (wpackagist) and add to `bedrock/composer.json`.

5. Install composer dependencies inside bedrock:

```bash
cd bedrock
composer install
```

6. Start services and import DB (use existing scripts):

```bash
cd ..
docker compose up -d
./linux_import_amae.sh /path/to/backup-dir
```

CI/CD notes
- In CI build use `composer install --no-dev --optimize-autoloader` inside `bedrock/` and package `bedrock/web` + `vendor` as artifact or build a Docker image.

If you want, I can run the create-project command and finish scaffolding Bedrock here (it will add many files). Reply `scaffold now` to proceed.
