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

## Architecture diagram
```mermaid
flowchart TD
    A[Browser / Client] --> B[public/index.php<br/>Front Controller]
    B --> C[routes.php]
    C --> D[Presentation Pages<br/>app/Presentation/pages]
    D --> E[Presentation Views<br/>app/Presentation/views]
    D --> F[AppContainer]

    F --> G[CommandBus]
    F --> H[QueryBus]
    F --> I[AuthService / RoleService]

    G --> J[Application Features<br/>Command Handlers]
    H --> K[Application Features<br/>Query Handlers]

    J --> L[Application Services]
    K --> L

    L --> M[Domain<br/>Entities / Models / ValueObjects]
    L --> N[Repository Interfaces]
    N --> O[Infrastructure Repositories<br/>PDO Implementations]
    O --> P[Database (PDO)]
    P --> Q[(MySQL)]
```

## Methodologies and patterns
- Layered separation (Domain / Application / Infrastructure / Presentation)
- Services for business rules
- Repositories for data access
- Simple view renderer for templates

## Main routes
- `index.php?r=home` (vacancies)
- `index.php?r=vacancies`
- `index.php?r=vacancies/new`
- `index.php?r=vacancies/edit&id=<uuid>`
- `index.php?r=vacancies/delete&id=<uuid>`
- `index.php?r=vacancies/apply`
- `index.php?r=users`
- `index.php?r=users/new`
- `index.php?r=users/edit&id=<uuid>`
- `index.php?r=users/delete&id=<uuid>`
- `index.php?r=login`
- `index.php?r=logout`

## Run with Docker
```bash
cp .env.example .env
docker compose build
docker compose up -d
```

Open: `http://localhost:8080/index.php?r=home`

> The database is initialized from `setup.sql` on first start.

## Tests
- Unit: `./vendor/bin/phpunit --testsuite Unit`
- Integration: `RUN_INTEGRATION=1 ./vendor/bin/phpunit --testsuite Integration`

Install test dependencies:
```bash
docker compose exec php composer install
```
