# DigiHome Deployment Guide

This project is a PHP + MySQL web app.

## Important Hosting Note
GitHub Pages does **not** support PHP/MySQL runtime.
Use GitHub for source control, then deploy to a PHP host.

Recommended free option: InfinityFree (or similar PHP host).

## 1) Push This Project to GitHub
Run these commands in project root after installing Git:

```powershell
git init
git add .
git commit -m "Initial commit - DigiHome"
git branch -M main
git remote add origin https://github.com/<your-username>/DigiHome.git
git push -u origin main
```

## 2) Create Live Hosting
1. Create a hosting account (InfinityFree/000webhost/AwardSpace).
2. Create a site/domain/subdomain.
3. Create a MySQL database and user.
4. Note DB details: host, db name, username, password.

## 3) Upload App Files
Upload project files to hosting web root (`htdocs`/`public_html`).

Do not upload local-only files if not needed:
- `test_*.html`
- `test_*.txt`
- local SQL backups

## 4) Configure Database Connection
This project supports both environment-based config and file override config.

Use these keys in your host environment panel (or in a local `.env` file if your setup loads it):
- `DIGIHOME_APP_ENV` (`production` or `local`)
- `DIGIHOME_DB_HOST`
- `DIGIHOME_DB_USER`
- `DIGIHOME_DB_PASS`
- `DIGIHOME_DB_NAME`

Reference templates:
- `.env.example`
- `includes/db.config.php.example`

Notes:
- Local XAMPP still defaults to `root` + empty password + `digihome` DB when env keys are not set.
- Production should explicitly set all DB keys.
- If your free host does not expose env variables, copy `includes/db.config.php.example` to `includes/db.config.php` and fill DB values there.

### InfinityFree-specific DB values
- `dbHost`: use your MySQL Host Name from InfinityFree control panel (not always `localhost`)
- `dbUser`: your InfinityFree DB username (often `epiz_...`)
- `dbPass`: your DB password
- `dbName`: your full DB name (often `epiz_...`)

## 5) Import Database Schema/Data
Use hosting phpMyAdmin:
1. Create/import SQL schema and seed data.
2. Confirm required tables exist (users, properties, chats, site_content, etc.).

## 6) Verify Production App
Test these flows:
1. Home and role dashboards.
2. Login/register for each role.
3. Listings and property details.
4. Favorites/unlocked actions.
5. Chat send/receive and unread badges.
6. Upload media and profile images.

## 7) Optional: Auto Deploy from GitHub
If host supports Git deployments, connect repo branch `main`.
Otherwise use FTP/File Manager for uploads.

## Troubleshooting
- Blank page/500 error: check PHP error log in hosting panel.
- DB errors: verify credentials and DB host (often not `localhost`).
- Missing uploads: confirm write permission on upload directories.
- Broken paths: ensure app is deployed at expected web root path.
