# JobVault - Job Vacancy Management System

A modern, dockerized job vacancy management platform built with PHP, MySQL, and Apache. Designed for HR teams to manage, organize, and track job opportunities with ease.

## 📋 Project Overview

**JobVault** is a lightweight yet powerful web application that enables organizations to:

- Create and manage job vacancies
- Search and filter job listings
- Track vacancy status (active/inactive)
- Manage user authentication and sessions
- Paginate large vacancy lists efficiently
- Monitor application activity through comprehensive logging

### Key Features

✨ **User Authentication** - Secure login/logout with session management
🔍 **Advanced Search** - Search vacancies by title and filter by status
📄 **CRUD Operations** - Create, Read, Update, Delete job vacancies
📑 **Pagination** - Efficient listing of large datasets
📊 **Activity Logging** - Track user actions and system events
🐳 **Docker Ready** - Complete containerization for easy deployment
🔒 **SQL Injection Prevention** - Prepared statements and PDO bindings
📱 **Responsive Design** - Works on desktop and mobile devices

---

## 🏗️ Project Structure

```
JobVault/
┣ 📂app
 ┃ ┣ 📂Db
 ┃ ┃ ┣ 📜Database.php
 ┃ ┃ ┗ 📜Pagination.php
 ┃ ┣ 📂Entity
 ┃ ┃ ┣ 📜Permission.php
 ┃ ┃ ┣ 📜Role.php
 ┃ ┃ ┣ 📜RolePermission.php
 ┃ ┃ ┣ 📜Usuario.php
 ┃ ┃ ┗ 📜Vaga.php
 ┃ ┣ 📂Session
 ┃ ┃ ┗ 📜Login.php
 ┃ ┗ 📂Util
 ┃ ┃ ┣ 📜Logger.php
 ┃ ┃ ┗ 📜RoleManager.php
 ┣ 📂includes
 ┃ ┣ 📜confirmar-exlusao.php
 ┃ ┣ 📜footer.php
 ┃ ┣ 📜formulario-login.php
 ┃ ┣ 📜formulario.php
 ┃ ┣ 📜header.php
 ┃ ┗ 📜listagem.php
 ┣ 📂init.sql
 ┣ 📂roles_schema.sql
 ┣ 📂vendor
 ┃ ┣ 📂composer
 ┃ ┃ ┣ 📜ClassLoader.php
 ┃ ┃ ┣ 📜InstalledVersions.php
 ┃ ┃ ┣ 📜LICENSE
 ┃ ┃ ┣ 📜autoload_classmap.php
 ┃ ┃ ┣ 📜autoload_namespaces.php
 ┃ ┃ ┣ 📜autoload_psr4.php
 ┃ ┃ ┣ 📜autoload_real.php
 ┃ ┃ ┣ 📜autoload_static.php
 ┃ ┃ ┣ 📜installed.json
 ┃ ┃ ┗ 📜installed.php
 ┃ ┗ 📜autoload.php
 ┣ 📜.editorconfig
 ┣ 📜.gitignore
 ┣ 📜Dockerfile
 ┣ 📜README.md
 ┣ 📜cadastrar.php
 ┣ 📜composer.json
 ┣ 📜composer.lock
 ┣ 📜docker-compose.yml
 ┣ 📜editar.php
 ┣ 📜excluir.php
 ┣ 📜index.php
 ┣ 📜login.php
 ┣ 📜logout.php
 ┗ 📜setup.sql
```

---

## 🛠️ Technology Stack

| Layer                | Technology              | Version |
| -------------------- | ----------------------- | ------- |
| **Frontend**         | HTML5, CSS3, JavaScript | Latest  |
| **Backend**          | PHP                     | 8.2     |
| **Web Server**       | Apache                  | 2.4     |
| **Database**         | MySQL                   | 8.0     |
| **Containerization** | Docker & Docker Compose | Latest  |
| **Package Manager**  | Composer                | Latest  |

---

## 📦 Prerequisites

Before running JobVault, ensure you have:

- **Docker** (v20.10+) - [Install Docker](https://docs.docker.com/get-docker/)
- **Docker Compose** (v1.29+) - [Install Docker Compose](https://docs.docker.com/compose/install/)
- **Git** - For version control

### System Requirements

- **RAM**: Minimum 2GB
- **Disk Space**: Minimum 2GB
- **OS**: Linux, macOS, or Windows (with Docker Desktop)

---

## 🚀 Quick Start

### 1. Clone the Repository

```bash
git clone https://github.com/yourusername/JobVault.git
cd JobVault
```

### 2. Build and Start Containers

```bash
# Build images and start all services
docker-compose up -d --build

# Wait 10-15 seconds for MySQL to initialize
sleep 15

# Verify all services are running
docker-compose ps
```

### 3. Access the Application

```
Homepage:     http://localhost:8080
Login:        http://localhost:8080/login.php
Create:       http://localhost:8080/cadastrar.php
Edit:         http://localhost:8080/editar.php?id=1
Delete:       http://localhost:8080/excluir.php?id=1
```

### 4. Create Your First Account

1. Go to `http://localhost:8080/login.php`
2. Click the register tab
3. Fill in name, email, and password
4. Submit to create account
5. You'll be automatically logged in

---

## 🔐 Default Credentials

The application comes with no pre-created accounts. You must register your own.

**Database Connection** (for development):

```
Host:     mysql
Port:     3306
Username: appuser
Password: app_password
Database: myapp_db
```

⚠️ **Security Note**: Change these credentials before deploying to production!

---

## 📚 Docker Compose Configuration

### Services Overview

**PHP/Apache Container**

- Service Name: `php`
- Image: `php:8.2-apache`
- Port: `8080`
- Volumes: Project root → `/var/www/html`
- Extensions: MySQLi, PDO, PDO_MySQL

**MySQL Container**

- Service Name: `mysql`
- Image: `mysql:8.0`
- Port: `3306`
- Volume: Persistent data storage in `mysql_data`
- Authentication: `caching_sha2_password` disabled for compatibility

### Docker Compose Commands

```bash
# Start all services in background
docker-compose up -d

# View running services
docker-compose ps

# View logs
docker-compose logs -f

# Stop all services
docker-compose stop

# Stop and remove containers
docker-compose down

# Rebuild images after code changes
docker-compose up -d --build

# Access PHP container shell
docker-exec -it php_app bash

# Access MySQL container shell
docker-compose exec mysql bash

# View database
docker-compose exec mysql mysql -u appuser -p myapp_db
```

---

## 🗄️ Database Schema

### Users Table (usuarios)

```sql
CREATE TABLE usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(255) NOT NULL,
  email VARCHAR(255) UNIQUE NOT NULL,
  senha VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Vacancies Table (vagas)

```sql
CREATE TABLE vagas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  titulo VARCHAR(255) NOT NULL,
  descricao LONGTEXT NOT NULL,
  ativo CHAR(1) DEFAULT 's',
  data DATETIME NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

---

## 📝 Configuration Files

### `docker-compose.yml`

```yaml
version: "3.8"

services:
  php:
    build: . # Uses Dockerfile
    container_name: php_app
    ports:
      - "8080:80"
    volumes:
      - .:/var/www/html # Mount entire project
    environment:
      DB_HOST: mysql
      DB_USER: appuser
      DB_PASSWORD: app_password
      DB_NAME: myapp_db
    depends_on:
      mysql:
        condition: service_healthy

  mysql:
    image: mysql:8.0
    container_name: mysql_database
    ports:
      - "3306:3306"
    environment:
      MYSQL_ROOT_PASSWORD: root_password
      MYSQL_DATABASE: myapp_db
      MYSQL_USER: appuser
      MYSQL_PASSWORD: app_password
    volumes:
      - mysql_data:/var/lib/mysql
      - ./init.sql:/docker-entrypoint-initdb.d/init.sql
      - ./sistem_vagas.sql:/docker-entrypoint-initdb.d/sistem_vagas.sql
    command: --default-authentication-plugin=mysql_native_password

volumes:
  mysql_data:
    driver: local

networks:
  default:
    driver: bridge
```

### `Dockerfile`

```dockerfile
FROM php:8.2-apache

# Install MySQL extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable mod_rewrite for URL rewriting
RUN a2enmod rewrite

WORKDIR /var/www/html

# Create logs directory with proper permissions
RUN mkdir -p /var/www/html/logs
RUN chmod 777 /var/www/html/logs

# Set ownership
RUN chown -R www-data:www-data /var/www/html

# Final permissions
RUN chmod -R 755 /var/www/html && chmod -R 777 /var/www/html/logs
```

---

## 🔍 Application Logging

JobVault automatically logs all important events to files in the `logs/` directory.

### Log Files

```
logs/
├── login_2026-01-28.log       # Authentication events
├── vaga_2026-01-28.log        # Vacancy CRUD operations
└── [module]_[date].log        # Other module logs
```

### Log Levels

- **DEBUG**: Detailed debugging information
- **INFO**: General informational messages (login, create, update)
- **WARNING**: Warning messages (unauthorized access attempts)
- **ERROR**: Error messages (database failures, validation errors)

### View Logs

```bash
# Real-time login logs
docker-compose exec php_app tail -f /var/www/html/logs/login_*.log

# All logs of a module
docker-compose exec php_app cat /var/www/html/logs/vaga_*.log

# Last 50 lines
docker-compose exec php_app tail -50 /var/www/html/logs/login_2026-01-28.log
```

---

## 🔑 Key Features Explained

### User Authentication (app/Session/Login.php)

- Session-based authentication
- Secure password hashing with `password_hash()`
- Login state verification
- Automatic redirect to login if not authenticated
- Logout with session destruction

```php
// Require login on protected pages
Login::requireLogin();

// Get logged-in user info
$usuario = Login::getUsuarioLogado();

// Check if logged in
if (Login::isLogged()) {
    echo "User is authenticated";
}
```

### Database Layer (app/Db/Database.php)

- PDO-based abstraction layer
- Prepared statements to prevent SQL injection
- CRUD operations: Create, Read, Update, Delete
- Configurable connection parameters

```php
$db = new Database('vagas');

// Select with filters
$vagas = $db->select('ativo = "s"', 'data DESC', '0,10');

// Insert
$id = $db->insert([
    'titulo' => 'PHP Developer',
    'descricao' => 'Senior role...'
]);

// Update
$db->update('id = 1', ['titulo' => 'New Title']);

// Delete
$db->delete('id = 1');

// Execute raw queries
$result = $db->execute('SELECT * FROM vagas WHERE ativo = ?', ['s']);
```

### Pagination (app/Db/Pagination.php)

- Automatic page calculation
- URL-safe pagination links
- Configurable records per page
- Previous/Next page detection

```php
$pagination = new Pagination(100, $currentPage, 10);

// Get LIMIT clause for query
$limit = $pagination->getLimit();  // "0,10"

// Get available pages
$pages = $pagination->getPages();

// Check navigation
if ($pagination->hasNextPage()) { /* ... */ }
if ($pagination->hasPreviousPage()) { /* ... */ }
```

### Entity Classes (app/Entity/)

- Object-oriented representation of database records
- Encapsulation of business logic
- Type hints and documentation
- Error handling and logging

```php
// Create vacancy
$vaga = new Vaga();
$vaga->titulo = 'Job Title';
$vaga->descricao = 'Description...';
$vaga->ativo = 's';
$vaga->cadastrar();

// Update vacancy
$vaga = Vaga::getVaga(1);
$vaga->titulo = 'Updated Title';
$vaga->atualizar();

// Delete vacancy
$vaga->exluir();

// Query vacancies
$vagas = Vaga::getVagas('ativo = "s"', 'data DESC', '0,10');
$total = Vaga::getQuantidadeVagas('ativo = "s"');
```

---

## 🔒 Security Features

### SQL Injection Prevention

✅ Prepared statements with parameter binding
✅ Parameterized queries in all database operations
✅ Input sanitization with `filter_input()`

### Authentication Security

✅ Password hashing with `PASSWORD_DEFAULT`
✅ Session-based user tracking
✅ Automatic login requirement on protected pages
✅ Secure logout with session destruction

### Access Control

✅ Role-based access control structure
✅ User permission verification
✅ Activity logging for audit trails

---

## 🐛 Troubleshooting

### MySQL Connection Errors

```
Error: SQLSTATE[HY000] [2002] No such file or directory
```

**Solution**: Use `mysql` as host (not `localhost`) inside containers:

```php
// Inside container
$host = 'mysql';

// From your machine
$host = 'localhost';
```

### Permission Errors

```
Warning: mkdir(): Permission denied
```

**Solution**: Verify Docker permissions:

```bash
docker-compose exec php_app ls -la /var/www/html/logs
docker-compose exec php_app chmod 777 /var/www/html/logs
```

### Port Already in Use

```
Error: bind: address already in use
```

**Solution**: Change ports in `docker-compose.yml`:

```yaml
ports:
  - "8081:80" # Changed from 8080 to 8081
  - "3307:3306" # Changed from 3306 to 3307
```

### Logs Not Being Created

```
Warning: file_put_contents(...): Failed to open stream
```

**Solution**: Recreate logs directory:

```bash
docker-compose exec php_app mkdir -p /var/www/html/logs
docker-compose exec php_app chmod 777 /var/www/html/logs
```

---

## 📖 API Documentation

### GET Routes

| Endpoint               | Description                       | Auth Required |
| ---------------------- | --------------------------------- | ------------- |
| `/index.php`           | List vacancies with search/filter | Yes           |
| `/login.php`           | Login/register page               | No            |
| `/logout.php`          | Logout user                       | Yes           |
| `/cadastrar.php`       | Create vacancy form               | Yes           |
| `/editar.php?id={id}`  | Edit vacancy form                 | Yes           |
| `/excluir.php?id={id}` | Delete vacancy confirmation       | Yes           |

### POST Parameters

**Login/Register** (`login.php`):

```
acao=logar
  email=user@example.com
  senha=password

acao=cadastrar
  nome=Full Name
  email=user@example.com
  senha=password
```

**Vacancy CRUD** (`cadastrar.php`, `editar.php`):

```
titulo=Job Title
descricao=Full Description
ativo=s|n
```

---

## 🚀 Deployment Guide

### Environment Variables (.env)

```bash
# Copy and modify for production
cp .env.example .env

# Update with production values
DB_HOST=mysql
DB_USER=prod_user
DB_PASSWORD=strong_password_here
DB_NAME=jobjault_prod
APP_ENV=production
```

---

## 📝 Development Workflow

### Making Code Changes

```bash
# Edit your PHP files locally
# Changes are reflected immediately due to volume mounting

# Rebuild Docker image if dependencies change
docker-compose up -d --build

# View container logs for errors
docker-compose logs -f php
```

### Testing

```bash
# Access PHP container for testing
docker-compose exec php_app bash

# Run tests
php vendor/bin/phpunit

# Check code quality
php vendor/bin/phpcs src/
```

### Database Debugging

```bash
# Connect to MySQL
docker-compose exec mysql mysql -u appuser -p myapp_db
# Password: app_password

# Useful MySQL commands
SHOW TABLES;
DESCRIBE usuarios;
DESCRIBE vagas;
SELECT * FROM usuarios;
SELECT * FROM vagas;
```

---

## 📄 License

This project is licensed under the MIT License. See LICENSE file for details.

---

## 🤝 Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit changes (`git commit -m 'Add amazing feature'`)
4. Push to branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

---

## 📧 Support & Contact

For issues, questions, or suggestions:

- **Email**:
- **Documentation**: Check the inline code comments and PHPDoc blocks

---

## 🎯 Roadmap

Planned features for future releases:

- [ ] User roles and permissions system
- [ ] Email notifications for job applications
- [ ] Advanced search with multiple filters
- [ ] Export functionality (PDF, CSV)
- [ ] REST API endpoints
- [ ] Unit test suite
- [ ] Dark mode UI
- [ ] Multi-language support
- [ ] Database query optimization

---

**Happy Coding! 🚀**

For more information, visit the [GitHub repository]() or read the [documentation](./docs/).

Last Updated: January 28, 2026
Version: 1.0.0
