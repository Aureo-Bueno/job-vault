# JobVault

Job vacancy and user management system built with PHP + MySQL, featuring authentication, permissions, and a web UI.

## Overview
This project provides:
- Vacancy CRUD
- User CRUD
- Permission-based access control
- Authentication and sessions
- Activity logging

## Structure and architecture
Layered organization (Clean Architecture-inspired):
- `app/Domain`: models and contracts
- `app/Application`: business services (Auth, Vacancies, Users, Roles)
- `app/Infrastructure`: persistence and container
- `app/Presentation`:
  - `pages/` (route controllers)
  - `views/` (layouts and pages)
- `public/index.php`: front controller (routes via `?r=`)

## Methodologies and patterns
- Layered separation (Domain / Application / Infrastructure / Presentation)
- Services for business rules
- Repositories for data access
- Simple view renderer for templates

## Main routes
- `index.php?r=home` (vacancies)
- `index.php?r=vagas/novo`
- `index.php?r=vagas/editar&id=1`
- `index.php?r=vagas/excluir&id=1`
- `index.php?r=usuarios`
- `index.php?r=usuarios/novo`
- `index.php?r=usuarios/editar&id=1`
- `index.php?r=usuarios/excluir&id=1`
- `index.php?r=login`
- `index.php?r=logout`

## Run with Docker
```bash
docker-compose build
docker-compose up -d
```

Open: `http://localhost:8080/index.php?r=home`

> The database is initialized from `setup.sql` on first start.

## Tests
- Unit: `./vendor/bin/phpunit --testsuite Unit`
- Integration: `RUN_INTEGRATION=1 ./vendor/bin/phpunit --testsuite Integration`

Install test dependencies:
```bash
docker-compose exec php_app composer install
```
