# Symfony 7 Blog Project with Docker

A Docker-based Symfony 7 blog project using PHP-FPM, NGINX, PostgreSQL, Redis, and PgAdmin. Includes support for migrations, fixtures, queues, Xdebug, and Mailtrap for email testing.

---

## 🧰 Stack

- **PHP-FPM** (with Xdebug)
- **NGINX**
- **PostgreSQL 15**
- **Redis 7**
- **PgAdmin 4**
- **Symfony 7**
- **Docker Compose 3.8**

---

## 🚀 Quick Start

### 1. Move into the `docker` directory

```bash
cd docker
```

### 2. Start all Docker containers

```bash
docker compose up -d
```

### 3. Enter the `php-fpm` container

```bash
docker exec -it php-fpm sh -c "composer install && sh"
```

### 4. Inside the container, run the custom Symfony command

```bash
php bin/console app:refresh
```

This command executes:
php bin/console doctrine:migrations:migrate -n
php bin/console doctrine:fixtures:load -n
php bin/console cache:clear -n

### 5. Start the async message queue worker (inside the container)

```bash
php bin/console messenger:consume async --time-limit=60
```

---

## ⚙️ Environment Configuration

Add the following to your root `.env` file to configure cache TTL:

```
###> custom settings ###
CACHE_TTL=3600
###< custom settings ###
```

Set to `0` to disable cache:

```
CACHE_TTL=0
```

---

## 🔐 Admin Credentials

Login for the Symfony admin user:

- **Email:** `admin-symfony-blog@gmail.com`
- **Password:** `admin-symfony-blog@gmail.com`

---

## 🌐 PgAdmin Access

- URL: [http://localhost:9090](http://localhost:9090)
- **Email:** `admin@example.com`
- **Password:** `admin`

---

## 🌍 App Access

- URL: [http://localhost](http://localhost)

---

## ✉️ Mailtrap Setup for Email Testing

1. Sign up at [https://mailtrap.io](https://mailtrap.io) and create a free account.
2. Create a new **Inbox**.
3. Open the inbox and go to **SMTP Settings → Laravel**.
4. Add the following configuration to your `.env.dev` (or `.env.local`) file:

```
MAILER_DSN=smtp://<USERNAME>:<PASSWORD>@sandbox.smtp.mailtrap.io:2525
```

> Replace `<USERNAME>` and `<PASSWORD>` with your actual Mailtrap SMTP credentials.

---

## 🗂 Docker Volumes and Mounts


| Service    | Host Path                  | Container Path                |
| ---------- | -------------------------- | ----------------------------- |
| App        | `./../`                    | `/var/www/html`               |
| NGINX      | `./nginx/conf.d`           | `/etc/nginx/conf.d`           |
| PostgreSQL | Named volume`pg_data`      | `/var/lib/postgresql/data`    |
| PgAdmin    | Named volume`pgadmin_data` | `/var/lib/pgadmin`            |
| Init SQL   | `./init`                   | `/docker-entrypoint-initdb.d` |

---

## 🧠 Tips

- PHP-FPM has Xdebug enabled with host set to `host.docker.internal`
- `extra_hosts` is used for host resolution inside Docker
- Always run `app:refresh` after first launch or schema update
