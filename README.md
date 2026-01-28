# MOHAA Stats SMF Forum

SMF Forum with MOHAA Stats Plugin, ready for Docker deployment.

## Quick Start (Docker)

### Prerequisites
- Docker & Docker Compose installed
- API stack running (creates the shared network)

### 1. Start API First
```bash
cd ../opm-stats-api
docker compose up -d
```

### 2. Start SMF Forum
```bash
cd ../opm-stats-web
cp .env.example .env
# Edit .env with your passwords
docker compose up -d
```

### 3. Complete SMF Installation
1. Open: http://77.42.64.214:8083/install.php
2. Database settings:
   - Host: `smf-mysql`
   - User: `smf`
   - Password: (from .env)
   - Database: `smf`
3. Complete wizard, delete install.php

### 4. Install MOHAA Stats Plugin
1. Open: http://77.42.64.214:8083/mohaa_install.php
2. Wait for success message
3. **Delete the installer**: Access container and run `rm /var/www/html/mohaa_install.php`

## Ports

| Service | Port | URL |
|---------|------|-----|
| SMF Forum | 8083 | http://77.42.64.214:8083 |
| API | 8084 | http://77.42.64.214:8084 |
| phpMyAdmin | 8889 | http://77.42.64.214:8889 |
| MySQL | 3307 | Internal only |

## Portainer Deployment

1. Add Stack → Repository
2. URL: `https://github.com/MOHCentral/opm-stats-system`
3. Compose path: `opm-stats-web/docker-compose.yml`
4. Deploy

## Environment Variables

```env
MYSQL_ROOT_PASSWORD=your_root_password
SMF_DB_NAME=smf
SMF_DB_USER=smf
SMF_DB_PASS=your_smf_password
MOHAA_API_URL=http://mohaa-api:8084/api/v1
MOHAA_API_PUBLIC_URL=http://77.42.64.214:8084/api/v1
```

## Plugin Files

- `Sources/MohaaStats/` - API client and core logic
- `Sources/Mohaa*.php` - Action handlers
- `Themes/default/Mohaa*.template.php` - Templates

