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
1. Open: http://77.42.64.214:8888/install.php
2. Database settings:
   - Host: `smf-mysql`
   - User: `smf`
   - Password: (from .env)
   - Database: `smf`
3. Complete wizard, delete install.php

### 4. Install MOHAA Plugin Tables
```bash
docker exec -i mohaa-smf-mysql mysql -u smf -p smf < /docker-entrypoint-initdb.d/01_mohaa_tables.sql
```

### 5. Register Plugin Hooks
Go to Admin → Configuration → Server Settings → Database and run:
```sql
INSERT INTO smf_settings (variable, value) VALUES 
('integrate_actions', 'MohaaStats_Actions'),
('integrate_menu_buttons', 'MohaaStats_MenuButtons'),
('integrate_admin_areas', 'MohaaStats_AdminAreas'),
('mohaa_stats_enabled', '1'),
('mohaa_stats_api_url', 'http://mohaa-api:8080');
```

## Ports

| Service | Port | URL |
|---------|------|-----|
| SMF Forum | 8888 | http://77.42.64.214:8888 |
| API | 8080 | http://77.42.64.214:8080 |
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
MOHAA_API_URL=http://mohaa-api:8080/api/v1
MOHAA_API_PUBLIC_URL=http://77.42.64.214:8080/api/v1
```

## Plugin Files

- `Sources/MohaaStats/` - API client and core logic
- `Sources/Mohaa*.php` - Action handlers
- `Themes/default/Mohaa*.template.php` - Templates
