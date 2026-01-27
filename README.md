# Chabrin Lease System

A comprehensive digital lease management platform built with Laravel 12 and Filament 4. The system provides end-to-end management of leases, properties, tenants, and landlords with features including digital signatures, lease template management with versioning, role-based access control, and a complete audit trail.

## Features

- **Lease Management**: Create, track, and manage lease agreements with full workflow support
- **Digital Signatures**: OTP-based digital signing with QR code verification
- **Template Management**: Customizable lease templates with version control
- **Property Management**: Track properties, units, and their status
- **Tenant & Landlord Portal**: Manage tenant and landlord information
- **Role-Based Access Control**: Granular permissions for different user roles
- **Dashboard & Analytics**: Visual dashboards with lease statistics and revenue charts
- **Excel Import**: Bulk import of landlords, properties, units, tenants, and staff

## Prerequisites

- PHP >= 8.2
- PostgreSQL 14+ (recommended) or MySQL 8+
- Node.js >= 18
- Composer >= 2.0
- Redis (optional, for caching and queues)

## Quick Start

### 1. Clone the repository

```bash
git clone https://github.com/your-org/chabrin-lease-system.git
cd chabrin-lease-system
```

### 2. Run the setup script

The project includes a convenient setup script that handles all initial configuration:

```bash
composer setup
```

This will:
- Install PHP dependencies
- Create `.env` file from `.env.example`
- Generate application key
- Run database migrations
- Install NPM dependencies
- Build frontend assets

### 3. Configure environment

Edit `.env` to configure your database and other settings:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=chabrin_lease
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 4. Seed the database (optional)

```bash
php artisan db:seed
```

### 5. Create an admin user

```bash
php artisan tinker --execute="App\Models\User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => bcrypt('password'), 'role' => 'super_admin']);"
```

## Development

Start all development services (web server, queue worker, log tailer, Vite):

```bash
composer dev
```

Or start services individually:

```bash
# Web server only
php artisan serve

# Queue worker
php artisan queue:work

# Vite dev server
npm run dev
```

## Testing

```bash
# Run all tests
composer test

# Run with coverage
php artisan test --coverage

# Run specific test
php artisan test --filter=LeaseWorkflowTest
```

## Code Quality

```bash
# Format code with Pint
composer pint

# Run static analysis
./vendor/bin/phpstan analyse
```

## Data Import

Import data from Excel files:

```bash
# Import all data using default paths
php artisan import:chabrin-data --default-paths

# Import specific files
php artisan import:chabrin-data --landlords="path/to/landlords.xlsx"
php artisan import:chabrin-data --properties="path/to/properties.xlsx"
php artisan import:chabrin-data --units="path/to/units.xlsx"
php artisan import:chabrin-data --tenants="path/to/tenants.xlsx"
php artisan import:chabrin-data --staff="path/to/staff.xlsx"

# Dry run (validate without importing)
php artisan import:chabrin-data --default-paths --dry-run
```

## User Roles

| Role | Description |
|------|-------------|
| `super_admin` | Full system access |
| `admin` | Administrative access |
| `zone_manager` | Manages specific zones |
| `manager` | Property management |
| `field_officer` | Field operations |
| `agent` | Basic lease operations |
| `viewer` | Read-only access |

## Documentation

Detailed documentation is available in the `/docs` directory:

### Getting Started
- [START_HERE.md](docs/START_HERE.md) - First steps guide
- [GETTING_STARTED.md](docs/GETTING_STARTED.md) - Detailed setup instructions
- [QUICK_START_LOGIN.md](docs/QUICK_START_LOGIN.md) - Login credentials and access

### Architecture & Development
- [ARCHITECTURE_DIAGRAMS.md](docs/ARCHITECTURE_DIAGRAMS.md) - System architecture
- [DEVELOPER_QUICK_REFERENCE.md](docs/DEVELOPER_QUICK_REFERENCE.md) - Developer guide
- [SRS.md](docs/SRS.md) - Software Requirements Specification

### Features
- [FEATURES_ADDED.md](docs/FEATURES_ADDED.md) - Complete feature list
- [DIGITAL_SIGNING_TESTING_GUIDE.md](docs/DIGITAL_SIGNING_TESTING_GUIDE.md) - Digital signing guide
- [LEASE_TEMPLATE_GUIDE.md](docs/LEASE_TEMPLATE_GUIDE.md) - Template management
- [RBAC_IMPLEMENTATION.md](docs/RBAC_IMPLEMENTATION.md) - Role-based access control

### Administration
- [ADMIN_QUICK_REFERENCE.md](docs/ADMIN_QUICK_REFERENCE.md) - Admin guide
- [ROLES_CONFIGURATION.md](docs/ROLES_CONFIGURATION.md) - Role configuration
- [DEPLOYMENT_GUIDE.md](docs/DEPLOYMENT_GUIDE.md) - Deployment instructions

### API
- [LANDLORD_FO_API_INTEGRATION.md](docs/LANDLORD_FO_API_INTEGRATION.md) - API integration guide

## Project Structure

```
app/
├── Console/Commands/     # Artisan commands
├── Filament/            # Filament admin panel
│   ├── Resources/       # CRUD resources
│   ├── Pages/           # Custom pages
│   └── Widgets/         # Dashboard widgets
├── Http/Controllers/    # API controllers
├── Models/              # Eloquent models
├── Services/            # Business logic services
└── Enums/               # Enumerations

database/
├── migrations/          # Database migrations
├── seeders/             # Database seeders
└── factories/           # Model factories

docs/                    # Documentation
resources/views/         # Blade templates
tests/                   # Test suites
```

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Make your changes
4. Run tests (`composer test`)
5. Run code style check (`composer pint`)
6. Commit your changes (`git commit -m 'Add amazing feature'`)
7. Push to the branch (`git push origin feature/amazing-feature`)
8. Open a Pull Request

## License

This project is proprietary software. All rights reserved.
