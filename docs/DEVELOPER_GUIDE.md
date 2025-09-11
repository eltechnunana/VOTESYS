# VOTESYS Developer Guide

## Table of Contents
1. [Development Environment Setup](#development-environment-setup)
2. [Project Structure](#project-structure)
3. [Architecture Overview](#architecture-overview)
4. [Coding Standards](#coding-standards)
5. [Database Development](#database-development)
6. [API Development](#api-development)
7. [Frontend Development](#frontend-development)
8. [Security Guidelines](#security-guidelines)
9. [Testing](#testing)
10. [Deployment](#deployment)
11. [Contributing](#contributing)
12. [Troubleshooting](#troubleshooting)

## Development Environment Setup

### Prerequisites
- **PHP**: Version 7.4 or higher
- **MySQL/MariaDB**: Version 5.7 or higher
- **Web Server**: Apache or Nginx
- **Composer**: For PHP dependency management
- **Node.js**: For frontend build tools (optional)
- **Git**: For version control

### Local Development Setup

#### Using XAMPP (Recommended for beginners)
1. **Install XAMPP**
   ```bash
   # Download from https://www.apachefriends.org/
   # Install with PHP 7.4+ and MySQL
   ```

2. **Clone Repository**
   ```bash
   cd C:\xampp\htdocs
   git clone <repository-url> VOTESYS
   cd VOTESYS
   ```

3. **Install Dependencies**
   ```bash
   composer install
   ```

4. **Database Setup**
   ```bash
   # Import database schema
   mysql -u root -p < database_schema.sql
   ```

5. **Configuration**
   ```php
   // Copy config/database.example.php to config/database.php
   // Update database credentials
   ```

#### Using Docker (Recommended for advanced users)
```dockerfile
# Dockerfile example
FROM php:7.4-apache

# Install PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable Apache modules
RUN a2enmod rewrite

# Copy application
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html/
```

```yaml
# docker-compose.yml
version: '3.8'
services:
  web:
    build: .
    ports:
      - "80:80"
    volumes:
      - .:/var/www/html
    depends_on:
      - db
  
  db:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: root
      MYSQL_DATABASE: votesys
    ports:
      - "3306:3306"
```

### IDE Configuration

#### VS Code Extensions
- PHP Intelephense
- PHP Debug
- MySQL
- GitLens
- Prettier
- ESLint

#### PhpStorm Configuration
- Configure PHP interpreter
- Set up database connection
- Configure code style
- Enable inspections

## Project Structure

```
VOTESYS/
├── admin/                  # Admin panel
│   ├── api/               # API endpoints
│   │   ├── audit.php
│   │   ├── candidates.php
│   │   ├── elections.php
│   │   ├── positions.php
│   │   ├── voters.php
│   │   └── ...
│   ├── assets/            # Admin assets
│   │   ├── css/
│   │   ├── js/
│   │   └── images/
│   ├── includes/          # Admin includes
│   │   ├── header.php
│   │   ├── footer.php
│   │   ├── sidebar.php
│   │   └── functions.php
│   ├── pages/             # Admin pages
│   │   ├── dashboard.php
│   │   ├── elections.php
│   │   ├── candidates.php
│   │   └── ...
│   └── index.php          # Admin entry point
├── voter/                 # Voter interface
│   ├── assets/            # Voter assets
│   ├── includes/          # Voter includes
│   ├── pages/             # Voter pages
│   └── index.php          # Voter entry point
├── config/                # Configuration files
│   ├── database.php       # Database configuration
│   ├── session.php        # Session configuration
│   ├── security.php       # Security settings
│   └── constants.php      # Application constants
├── includes/              # Shared includes
│   ├── functions.php      # Common functions
│   ├── validation.php     # Input validation
│   ├── security.php       # Security functions
│   └── database.php       # Database connection
├── uploads/               # File uploads
│   ├── candidates/        # Candidate photos
│   └── temp/              # Temporary files
├── logs/                  # Application logs
│   ├── error.log
│   ├── access.log
│   └── audit.log
├── docs/                  # Documentation
│   ├── API.md
│   ├── DATABASE.md
│   ├── USER_GUIDE_ADMIN.md
│   ├── USER_GUIDE_VOTER.md
│   └── DEVELOPER_GUIDE.md
├── tests/                 # Test files
│   ├── unit/
│   ├── integration/
│   └── fixtures/
├── vendor/                # Composer dependencies
├── .htaccess              # Apache configuration
├── composer.json          # PHP dependencies
├── database_schema.sql    # Database schema
└── README.md              # Project overview
```

### Directory Conventions

#### File Naming
- **PHP Files**: `snake_case.php`
- **CSS Files**: `kebab-case.css`
- **JavaScript Files**: `camelCase.js`
- **Images**: `descriptive-name.ext`

#### Directory Organization
- **Separation of Concerns**: Admin and voter interfaces are separate
- **Modular Structure**: Related files grouped together
- **Asset Organization**: CSS, JS, and images in dedicated folders
- **Configuration Centralization**: All config files in one location

## Architecture Overview

### System Architecture

```
┌─────────────────┐    ┌─────────────────┐
│   Admin Panel   │    │  Voter Portal   │
└─────────┬───────┘    └─────────┬───────┘
          │                      │
          └──────────┬───────────┘
                     │
            ┌────────▼────────┐
            │   API Layer     │
            └────────┬────────┘
                     │
            ┌────────▼────────┐
            │ Business Logic  │
            └────────┬────────┘
                     │
            ┌────────▼────────┐
            │ Data Access     │
            └────────┬────────┘
                     │
            ┌────────▼────────┐
            │    Database     │
            └─────────────────┘
```

### Election Routing System

The VOTESYS application implements a flexible election routing system that allows voters to access specific elections through URL parameters while maintaining backward compatibility with single-election deployments.

#### Core Components

##### 1. URL Parameter Detection
```php
// voter_page.php - Election ID detection
$election_id = CURRENT_ELECTION_ID; // Default fallback

if (isset($_GET['election_id']) && !empty($_GET['election_id'])) {
    $provided_id = intval($_GET['election_id']);
    
    // Validate election exists and is accessible
    $stmt = $pdo->prepare("SELECT id FROM elections WHERE id = ?");
    $stmt->execute([$provided_id]);
    
    if ($stmt->fetch()) {
        $election_id = $provided_id;
    }
    // If invalid, falls back to CURRENT_ELECTION_ID
}
```

##### 2. Configuration Constants
```php
// constants.php - Default election configuration
define('CURRENT_ELECTION_ID', 26); // Fallback election ID
```

##### 3. Dynamic Election Context
```php
// Election-specific data fetching
$stmt = $pdo->prepare("
    SELECT id, title, description, start_date, end_date, is_active 
    FROM elections 
    WHERE id = ?
");
$stmt->execute([$election_id]);
$election = $stmt->fetch(PDO::FETCH_ASSOC);

// Position and candidate fetching
$stmt = $pdo->prepare("
    SELECT p.*, COUNT(c.id) as candidate_count 
    FROM positions p 
    LEFT JOIN candidates c ON p.id = c.position_id AND c.election_id = ? 
    WHERE p.election_id = ? 
    GROUP BY p.id 
    ORDER BY p.priority
");
$stmt->execute([$election_id, $election_id]);
```

#### Implementation Flow

##### Voter Access Flow
1. **URL Processing**: System detects `election_id` parameter in URL
2. **Validation**: Verifies election exists and is accessible
3. **Context Setting**: Establishes election context for the session
4. **Data Retrieval**: Fetches election-specific positions and candidates
5. **Interface Rendering**: Displays appropriate voting interface

##### Vote Processing Flow
```php
// process_vote.php - Dynamic election handling
$election_id = CURRENT_ELECTION_ID; // Default

if (isset($_POST['election_id']) && !empty($_POST['election_id'])) {
    $provided_id = intval($_POST['election_id']);
    
    // Validate election ID
    $stmt = $pdo->prepare("SELECT id FROM elections WHERE id = ? AND is_active = 1");
    $stmt->execute([$provided_id]);
    
    if ($stmt->fetch()) {
        $election_id = $provided_id;
    }
}

// Use $election_id for vote processing
$stmt = $pdo->prepare("
    INSERT INTO votes (voter_id, candidate_id, position_id, election_id, created_at) 
    VALUES (?, ?, ?, ?, NOW())
");
```

#### URL Patterns

##### Standard Access Patterns
```bash
# Default election access
GET /voter_page.php
# Uses CURRENT_ELECTION_ID from constants.php

# Specific election access
GET /voter_page.php?election_id=12
# Uses election ID 12 if valid, falls back to default if invalid

# Multiple parameter support (future extension)
GET /voter_page.php?election_id=12&lang=en
# Supports additional parameters alongside election_id
```

##### Form Integration
```html
<!-- Hidden field in voting form -->
<form method="POST" action="process_vote.php">
    <input type="hidden" name="election_id" value="<?php echo htmlspecialchars($election_id); ?>">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
    
    <!-- Position and candidate selections -->
    <input type="radio" name="position_1" value="candidate_5">
    <input type="radio" name="position_2" value="candidate_8">
    
    <button type="submit">Submit Vote</button>
</form>
```

#### Security Considerations

##### Input Validation
```php
// Strict integer validation
$election_id = filter_input(INPUT_GET, 'election_id', FILTER_VALIDATE_INT);
if ($election_id === false || $election_id <= 0) {
    $election_id = CURRENT_ELECTION_ID;
}
```

##### Database Validation
```php
// Verify election exists and is accessible
$stmt = $pdo->prepare("
    SELECT id, is_active, start_date, end_date 
    FROM elections 
    WHERE id = ? AND is_active = 1
");
$stmt->execute([$election_id]);

if (!$stmt->fetch()) {
    // Election not found or inactive - use default
    $election_id = CURRENT_ELECTION_ID;
}
```

##### Session Security
```php
// Store validated election ID in session
$_SESSION['current_election_id'] = $election_id;
$_SESSION['election_validated_at'] = time();

// Validate session election context
if (!isset($_SESSION['current_election_id']) || 
    (time() - $_SESSION['election_validated_at']) > 3600) {
    // Re-validate election context
    unset($_SESSION['current_election_id']);
    header('Location: voter_page.php');
    exit;
}
```

#### Configuration Management

##### Environment-Specific Settings
```php
// constants.php - Environment-aware configuration
switch (getenv('ENVIRONMENT')) {
    case 'production':
        define('CURRENT_ELECTION_ID', 26);
        break;
    case 'staging':
        define('CURRENT_ELECTION_ID', 12);
        break;
    case 'development':
        define('CURRENT_ELECTION_ID', 1);
        break;
    default:
        define('CURRENT_ELECTION_ID', 1);
}
```

##### Multi-Election Support
```php
// Helper function for election context
function getCurrentElectionId() {
    static $election_id = null;
    
    if ($election_id === null) {
        $election_id = CURRENT_ELECTION_ID;
        
        if (isset($_GET['election_id'])) {
            $provided_id = filter_input(INPUT_GET, 'election_id', FILTER_VALIDATE_INT);
            if ($provided_id && isValidElection($provided_id)) {
                $election_id = $provided_id;
            }
        }
    }
    
    return $election_id;
}

function isValidElection($election_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT 1 FROM elections WHERE id = ? AND is_active = 1");
    $stmt->execute([$election_id]);
    return $stmt->fetch() !== false;
}
```

#### Best Practices

##### URL Generation
```php
// Generate election-specific URLs
function generateElectionUrl($election_id, $base_url = 'voter_page.php') {
    if ($election_id == CURRENT_ELECTION_ID) {
        return $base_url; // Use clean URL for default election
    }
    return $base_url . '?election_id=' . urlencode($election_id);
}

// Usage in admin panel
$voter_link = generateElectionUrl($election['id']);
echo "<a href='{$voter_link}'>Voter Access Link</a>";
```

##### Error Handling
```php
// Graceful fallback handling
try {
    $election_id = validateElectionId($_GET['election_id'] ?? null);
} catch (InvalidElectionException $e) {
    error_log("Invalid election access attempt: " . $e->getMessage());
    $election_id = CURRENT_ELECTION_ID;
    
    // Optional: Redirect with clean URL
    if (isset($_GET['election_id'])) {
        header('Location: voter_page.php');
        exit;
    }
}
```

##### Testing Considerations
```php
// Unit test example
class ElectionRoutingTest extends PHPUnit\Framework\TestCase {
    public function testValidElectionIdParameter() {
        $_GET['election_id'] = '12';
        $election_id = getCurrentElectionId();
        $this->assertEquals(12, $election_id);
    }
    
    public function testInvalidElectionIdFallback() {
        $_GET['election_id'] = '999';
        $election_id = getCurrentElectionId();
        $this->assertEquals(CURRENT_ELECTION_ID, $election_id);
    }
    
    public function testMissingElectionIdParameter() {
        unset($_GET['election_id']);
        $election_id = getCurrentElectionId();
        $this->assertEquals(CURRENT_ELECTION_ID, $election_id);
    }
}
```

### Design Patterns

#### MVC Pattern (Simplified)
- **Model**: Database interaction classes
- **View**: HTML templates and pages
- **Controller**: API endpoints and page controllers

#### Repository Pattern
```php
// Example: VoterRepository
class VoterRepository {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM voters WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO voters (student_id, name, email, course, year_level, password) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['student_id'],
            $data['name'],
            $data['email'],
            $data['course'],
            $data['year_level'],
            password_hash($data['password'], PASSWORD_DEFAULT)
        ]);
    }
}
```

#### Service Layer Pattern
```php
// Example: ElectionService
class ElectionService {
    private $electionRepo;
    private $auditService;
    
    public function __construct($electionRepo, $auditService) {
        $this->electionRepo = $electionRepo;
        $this->auditService = $auditService;
    }
    
    public function createElection($data, $adminId) {
        // Validate data
        $this->validateElectionData($data);
        
        // Create election
        $electionId = $this->electionRepo->create($data);
        
        // Log audit
        $this->auditService->log('CREATE_ELECTION', $adminId, [
            'election_id' => $electionId,
            'title' => $data['title']
        ]);
        
        return $electionId;
    }
}
```

## Coding Standards

### PHP Standards (PSR-12)

#### Code Formatting
```php
<?php

declare(strict_types=1);

namespace VoteSys\Admin;

use VoteSys\Database\Connection;
use VoteSys\Security\CSRF;

class ElectionController
{
    private Connection $db;
    private CSRF $csrf;
    
    public function __construct(Connection $db, CSRF $csrf)
    {
        $this->db = $db;
        $this->csrf = $csrf;
    }
    
    public function createElection(array $data): array
    {
        // Validate CSRF token
        if (!$this->csrf->validate($data['csrf_token'])) {
            throw new SecurityException('Invalid CSRF token');
        }
        
        // Validate input data
        $validatedData = $this->validateElectionData($data);
        
        try {
            $this->db->beginTransaction();
            
            $electionId = $this->insertElection($validatedData);
            $this->logAuditTrail('CREATE_ELECTION', $electionId);
            
            $this->db->commit();
            
            return [
                'success' => true,
                'election_id' => $electionId,
                'message' => 'Election created successfully'
            ];
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    private function validateElectionData(array $data): array
    {
        $rules = [
            'title' => 'required|string|max:200',
            'description' => 'string',
            'start_date' => 'required|datetime',
            'end_date' => 'required|datetime|after:start_date'
        ];
        
        return $this->validator->validate($data, $rules);
    }
}
```

#### Naming Conventions
- **Classes**: `PascalCase`
- **Methods**: `camelCase`
- **Variables**: `camelCase`
- **Constants**: `UPPER_SNAKE_CASE`
- **Database Tables**: `snake_case`
- **Database Columns**: `snake_case`

#### Documentation
```php
/**
 * Creates a new election with the provided data
 * 
 * @param array $data Election data including title, description, dates
 * @return array Response with success status and election ID
 * @throws ValidationException When data validation fails
 * @throws SecurityException When CSRF validation fails
 * @throws DatabaseException When database operation fails
 */
public function createElection(array $data): array
{
    // Implementation
}
```

### JavaScript Standards

#### ES6+ Features
```javascript
// Use const/let instead of var
const API_BASE_URL = '/VOTESYS/admin/api';
let currentElection = null;

// Arrow functions
const fetchElections = async () => {
    try {
        const response = await fetch(`${API_BASE_URL}/elections.php`);
        const data = await response.json();
        return data;
    } catch (error) {
        console.error('Failed to fetch elections:', error);
        throw error;
    }
};

// Template literals
const createElectionHTML = (election) => `
    <div class="election-card" data-id="${election.id}">
        <h3>${election.title}</h3>
        <p>${election.description}</p>
        <span class="status ${election.status}">${election.status}</span>
    </div>
`;

// Destructuring
const { title, description, status } = election;

// Modules
export class ElectionManager {
    constructor(apiClient) {
        this.api = apiClient;
    }
    
    async createElection(data) {
        return await this.api.post('/elections.php', data);
    }
}
```

#### Error Handling
```javascript
class APIClient {
    async request(url, options = {}) {
        try {
            const response = await fetch(url, {
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.getCSRFToken()
                },
                ...options
            });
            
            if (!response.ok) {
                throw new APIError(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            return await response.json();
        } catch (error) {
            if (error instanceof APIError) {
                throw error;
            }
            throw new NetworkError('Network request failed', error);
        }
    }
}
```

### CSS Standards

#### BEM Methodology
```css
/* Block */
.election-card {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 16px;
}

/* Element */
.election-card__title {
    font-size: 1.5rem;
    font-weight: bold;
    margin-bottom: 8px;
}

.election-card__description {
    color: #666;
    margin-bottom: 12px;
}

.election-card__status {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.875rem;
    font-weight: 500;
}

/* Modifier */
.election-card__status--active {
    background-color: #d4edda;
    color: #155724;
}

.election-card__status--completed {
    background-color: #f8d7da;
    color: #721c24;
}
```

#### CSS Custom Properties
```css
:root {
    /* Colors */
    --primary-color: #007bff;
    --secondary-color: #6c757d;
    --success-color: #28a745;
    --danger-color: #dc3545;
    --warning-color: #ffc107;
    
    /* Typography */
    --font-family-base: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    --font-size-base: 1rem;
    --line-height-base: 1.5;
    
    /* Spacing */
    --spacing-xs: 0.25rem;
    --spacing-sm: 0.5rem;
    --spacing-md: 1rem;
    --spacing-lg: 1.5rem;
    --spacing-xl: 3rem;
    
    /* Breakpoints */
    --breakpoint-sm: 576px;
    --breakpoint-md: 768px;
    --breakpoint-lg: 992px;
    --breakpoint-xl: 1200px;
}
```

## Database Development

### Migration System

#### Creating Migrations
```php
// migrations/001_create_elections_table.php
class CreateElectionsTable {
    public function up($pdo) {
        $sql = "
            CREATE TABLE elections (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(200) NOT NULL,
                description TEXT,
                start_date DATETIME NOT NULL,
                end_date DATETIME NOT NULL,
                status ENUM('draft', 'active', 'paused', 'completed', 'cancelled') DEFAULT 'draft',
                created_by INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (created_by) REFERENCES admins(id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $pdo->exec($sql);
    }
    
    public function down($pdo) {
        $pdo->exec("DROP TABLE IF EXISTS elections");
    }
}
```

#### Running Migrations
```php
// scripts/migrate.php
class MigrationRunner {
    private $pdo;
    private $migrationsPath;
    
    public function __construct($pdo, $migrationsPath) {
        $this->pdo = $pdo;
        $this->migrationsPath = $migrationsPath;
        $this->createMigrationsTable();
    }
    
    public function runMigrations() {
        $files = glob($this->migrationsPath . '/*.php');
        sort($files);
        
        foreach ($files as $file) {
            $migrationName = basename($file, '.php');
            
            if (!$this->isMigrationRun($migrationName)) {
                $this->runMigration($file, $migrationName);
            }
        }
    }
    
    private function runMigration($file, $name) {
        require_once $file;
        $className = $this->getClassNameFromFile($file);
        $migration = new $className();
        
        try {
            $this->pdo->beginTransaction();
            $migration->up($this->pdo);
            $this->recordMigration($name);
            $this->pdo->commit();
            echo "Migrated: {$name}\n";
        } catch (Exception $e) {
            $this->pdo->rollback();
            throw $e;
        }
    }
}
```

### Query Builder

```php
class QueryBuilder {
    private $pdo;
    private $table;
    private $select = ['*'];
    private $where = [];
    private $joins = [];
    private $orderBy = [];
    private $limit;
    private $offset;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function table($table) {
        $this->table = $table;
        return $this;
    }
    
    public function select($columns) {
        $this->select = is_array($columns) ? $columns : func_get_args();
        return $this;
    }
    
    public function where($column, $operator, $value = null) {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
        
        $this->where[] = [$column, $operator, $value];
        return $this;
    }
    
    public function join($table, $first, $operator, $second) {
        $this->joins[] = "INNER JOIN {$table} ON {$first} {$operator} {$second}";
        return $this;
    }
    
    public function orderBy($column, $direction = 'ASC') {
        $this->orderBy[] = "{$column} {$direction}";
        return $this;
    }
    
    public function limit($limit, $offset = 0) {
        $this->limit = $limit;
        $this->offset = $offset;
        return $this;
    }
    
    public function get() {
        $sql = $this->buildSelectQuery();
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->getBindValues());
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function first() {
        $results = $this->limit(1)->get();
        return $results ? $results[0] : null;
    }
    
    private function buildSelectQuery() {
        $sql = 'SELECT ' . implode(', ', $this->select);
        $sql .= ' FROM ' . $this->table;
        
        if ($this->joins) {
            $sql .= ' ' . implode(' ', $this->joins);
        }
        
        if ($this->where) {
            $sql .= ' WHERE ' . $this->buildWhereClause();
        }
        
        if ($this->orderBy) {
            $sql .= ' ORDER BY ' . implode(', ', $this->orderBy);
        }
        
        if ($this->limit) {
            $sql .= ' LIMIT ' . $this->limit;
            if ($this->offset) {
                $sql .= ' OFFSET ' . $this->offset;
            }
        }
        
        return $sql;
    }
}
```

## API Development

### RESTful API Design

#### Endpoint Structure
```
GET    /api/elections           # List all elections
POST   /api/elections           # Create new election
GET    /api/elections/{id}      # Get specific election
PUT    /api/elections/{id}      # Update election
DELETE /api/elections/{id}      # Delete election

GET    /api/elections/{id}/candidates    # Get election candidates
POST   /api/elections/{id}/candidates    # Add candidate to election
```

#### API Response Format
```php
class APIResponse {
    public static function success($data = null, $message = null, $code = 200) {
        http_response_code($code);
        header('Content-Type: application/json');
        
        $response = ['success' => true];
        
        if ($message) {
            $response['message'] = $message;
        }
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        echo json_encode($response);
        exit;
    }
    
    public static function error($message, $code = 400, $details = null) {
        http_response_code($code);
        header('Content-Type: application/json');
        
        $response = [
            'success' => false,
            'message' => $message
        ];
        
        if ($details) {
            $response['details'] = $details;
        }
        
        echo json_encode($response);
        exit;
    }
    
    public static function paginated($data, $total, $page, $limit) {
        return self::success([
            'items' => $data,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil($total / $limit)
            ]
        ]);
    }
}
```

#### Input Validation
```php
class Validator {
    private $rules = [];
    private $errors = [];
    
    public function validate($data, $rules) {
        $this->rules = $rules;
        $this->errors = [];
        
        foreach ($rules as $field => $rule) {
            $this->validateField($field, $data[$field] ?? null, $rule);
        }
        
        if (!empty($this->errors)) {
            throw new ValidationException('Validation failed', $this->errors);
        }
        
        return $this->sanitizeData($data, $rules);
    }
    
    private function validateField($field, $value, $rules) {
        $ruleList = explode('|', $rules);
        
        foreach ($ruleList as $rule) {
            $this->applyRule($field, $value, $rule);
        }
    }
    
    private function applyRule($field, $value, $rule) {
        if (strpos($rule, ':') !== false) {
            [$ruleName, $parameter] = explode(':', $rule, 2);
        } else {
            $ruleName = $rule;
            $parameter = null;
        }
        
        switch ($ruleName) {
            case 'required':
                if (empty($value)) {
                    $this->errors[$field][] = "{$field} is required";
                }
                break;
                
            case 'string':
                if (!is_string($value)) {
                    $this->errors[$field][] = "{$field} must be a string";
                }
                break;
                
            case 'max':
                if (strlen($value) > $parameter) {
                    $this->errors[$field][] = "{$field} must not exceed {$parameter} characters";
                }
                break;
                
            case 'email':
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->errors[$field][] = "{$field} must be a valid email address";
                }
                break;
                
            case 'datetime':
                if (!strtotime($value)) {
                    $this->errors[$field][] = "{$field} must be a valid datetime";
                }
                break;
        }
    }
}
```

## Frontend Development

### Component-Based Architecture

```javascript
// components/ElectionCard.js
class ElectionCard {
    constructor(election, container) {
        this.election = election;
        this.container = container;
        this.element = null;
        this.render();
    }
    
    render() {
        this.element = document.createElement('div');
        this.element.className = 'election-card';
        this.element.innerHTML = this.template();
        this.attachEventListeners();
        this.container.appendChild(this.element);
    }
    
    template() {
        return `
            <div class="election-card__header">
                <h3 class="election-card__title">${this.election.title}</h3>
                <span class="election-card__status election-card__status--${this.election.status}">
                    ${this.election.status}
                </span>
            </div>
            <div class="election-card__body">
                <p class="election-card__description">${this.election.description}</p>
                <div class="election-card__dates">
                    <span>Start: ${this.formatDate(this.election.start_date)}</span>
                    <span>End: ${this.formatDate(this.election.end_date)}</span>
                </div>
            </div>
            <div class="election-card__actions">
                <button class="btn btn-primary" data-action="edit">Edit</button>
                <button class="btn btn-secondary" data-action="view">View</button>
                <button class="btn btn-danger" data-action="delete">Delete</button>
            </div>
        `;
    }
    
    attachEventListeners() {
        this.element.addEventListener('click', (e) => {
            const action = e.target.dataset.action;
            if (action) {
                this.handleAction(action);
            }
        });
    }
    
    handleAction(action) {
        switch (action) {
            case 'edit':
                this.editElection();
                break;
            case 'view':
                this.viewElection();
                break;
            case 'delete':
                this.deleteElection();
                break;
        }
    }
    
    formatDate(dateString) {
        return new Date(dateString).toLocaleDateString();
    }
}
```

### State Management

```javascript
// utils/StateManager.js
class StateManager {
    constructor() {
        this.state = {};
        this.listeners = {};
    }
    
    setState(key, value) {
        const oldValue = this.state[key];
        this.state[key] = value;
        
        if (this.listeners[key]) {
            this.listeners[key].forEach(callback => {
                callback(value, oldValue);
            });
        }
    }
    
    getState(key) {
        return this.state[key];
    }
    
    subscribe(key, callback) {
        if (!this.listeners[key]) {
            this.listeners[key] = [];
        }
        this.listeners[key].push(callback);
        
        // Return unsubscribe function
        return () => {
            const index = this.listeners[key].indexOf(callback);
            if (index > -1) {
                this.listeners[key].splice(index, 1);
            }
        };
    }
}

// Usage
const state = new StateManager();

// Subscribe to election changes
state.subscribe('elections', (elections) => {
    renderElections(elections);
});

// Update elections
state.setState('elections', newElections);
```

## Security Guidelines

### Input Sanitization

```php
class SecurityHelper {
    public static function sanitizeInput($input, $type = 'string') {
        switch ($type) {
            case 'string':
                return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
                
            case 'email':
                return filter_var(trim($input), FILTER_SANITIZE_EMAIL);
                
            case 'int':
                return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
                
            case 'float':
                return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                
            case 'url':
                return filter_var(trim($input), FILTER_SANITIZE_URL);
                
            default:
                return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
        }
    }
    
    public static function validateCSRF($token) {
        if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
            throw new SecurityException('Invalid CSRF token');
        }
    }
    
    public static function generateCSRF() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}
```

### SQL Injection Prevention

```php
// GOOD: Using prepared statements
class VoterRepository {
    public function findByStudentId($studentId) {
        $stmt = $this->pdo->prepare("SELECT * FROM voters WHERE student_id = ?");
        $stmt->execute([$studentId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function updateVoter($id, $data) {
        $stmt = $this->pdo->prepare("
            UPDATE voters 
            SET name = ?, email = ?, course = ?, year_level = ? 
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['name'],
            $data['email'],
            $data['course'],
            $data['year_level'],
            $id
        ]);
    }
}

// BAD: Direct string concatenation (vulnerable to SQL injection)
// $sql = "SELECT * FROM voters WHERE student_id = '" . $_POST['student_id'] . "'";
```

### Password Security

```php
class PasswordHelper {
    public static function hash($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    
    public static function verify($password, $hash) {
        return password_verify($password, $hash);
    }
    
    public static function validateStrength($password) {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }
        
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Password must contain at least one special character';
        }
        
        return $errors;
    }
}
```

## Testing

### Unit Testing with PHPUnit

```php
// tests/Unit/VoterRepositoryTest.php
use PHPUnit\Framework\TestCase;

class VoterRepositoryTest extends TestCase {
    private $pdo;
    private $repository;
    
    protected function setUp(): void {
        // Create in-memory SQLite database for testing
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->exec(file_get_contents(__DIR__ . '/../../database_schema.sql'));
        $this->repository = new VoterRepository($this->pdo);
    }
    
    public function testCreateVoter() {
        $data = [
            'student_id' => 'STU001',
            'name' => 'John Doe',
            'email' => 'john@university.edu',
            'course' => 'Computer Science',
            'year_level' => '3rd Year',
            'password' => 'password123'
        ];
        
        $result = $this->repository->create($data);
        $this->assertTrue($result);
        
        $voter = $this->repository->findByStudentId('STU001');
        $this->assertNotNull($voter);
        $this->assertEquals('John Doe', $voter['name']);
    }
    
    public function testFindNonExistentVoter() {
        $voter = $this->repository->findByStudentId('NONEXISTENT');
        $this->assertNull($voter);
    }
    
    public function testUpdateVoter() {
        // Create voter first
        $this->repository->create([
            'student_id' => 'STU002',
            'name' => 'Jane Smith',
            'email' => 'jane@university.edu',
            'course' => 'Engineering',
            'year_level' => '2nd Year',
            'password' => 'password456'
        ]);
        
        $voter = $this->repository->findByStudentId('STU002');
        $voterId = $voter['id'];
        
        // Update voter
        $updateData = [
            'name' => 'Jane Doe',
            'email' => 'jane.doe@university.edu',
            'course' => 'Computer Engineering',
            'year_level' => '3rd Year'
        ];
        
        $result = $this->repository->update($voterId, $updateData);
        $this->assertTrue($result);
        
        $updatedVoter = $this->repository->findById($voterId);
        $this->assertEquals('Jane Doe', $updatedVoter['name']);
        $this->assertEquals('Computer Engineering', $updatedVoter['course']);
    }
}
```

### Integration Testing

```php
// tests/Integration/ElectionAPITest.php
class ElectionAPITest extends TestCase {
    private $client;
    
    protected function setUp(): void {
        $this->client = new TestClient();
        $this->client->authenticate('admin', 'password');
    }
    
    public function testCreateElection() {
        $data = [
            'title' => 'Test Election',
            'description' => 'A test election',
            'start_date' => '2024-02-01 08:00:00',
            'end_date' => '2024-02-03 18:00:00'
        ];
        
        $response = $this->client->post('/api/elections.php', $data);
        
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->json()['success']);
        $this->assertArrayHasKey('election_id', $response->json()['data']);
    }
    
    public function testGetElections() {
        $response = $this->client->get('/api/elections.php');
        
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->json()['success']);
        $this->assertIsArray($response->json()['data']);
    }
}
```

### Frontend Testing with Jest

```javascript
// tests/frontend/ElectionCard.test.js
import { ElectionCard } from '../../admin/assets/js/components/ElectionCard.js';

describe('ElectionCard', () => {
    let container;
    let election;
    
    beforeEach(() => {
        container = document.createElement('div');
        document.body.appendChild(container);
        
        election = {
            id: 1,
            title: 'Test Election',
            description: 'A test election',
            status: 'active',
            start_date: '2024-02-01 08:00:00',
            end_date: '2024-02-03 18:00:00'
        };
    });
    
    afterEach(() => {
        document.body.removeChild(container);
    });
    
    test('renders election card with correct data', () => {
        const card = new ElectionCard(election, container);
        
        expect(container.querySelector('.election-card__title').textContent)
            .toBe('Test Election');
        expect(container.querySelector('.election-card__description').textContent)
            .toBe('A test election');
        expect(container.querySelector('.election-card__status').textContent)
            .toBe('active');
    });
    
    test('handles edit button click', () => {
        const card = new ElectionCard(election, container);
        const editButton = container.querySelector('[data-action="edit"]');
        
        const editSpy = jest.spyOn(card, 'editElection');
        editButton.click();
        
        expect(editSpy).toHaveBeenCalled();
    });
});
```

## Deployment

### Production Deployment

#### Environment Configuration
```php
// config/environment.php
class Environment {
    public static function getConfig() {
        $env = $_ENV['APP_ENV'] ?? 'production';
        
        $configs = [
            'development' => [
                'debug' => true,
                'database' => [
                    'host' => 'localhost',
                    'name' => 'votesys_dev',
                    'user' => 'dev_user',
                    'pass' => 'dev_pass'
                ]
            ],
            'production' => [
                'debug' => false,
                'database' => [
                    'host' => $_ENV['DB_HOST'],
                    'name' => $_ENV['DB_NAME'],
                    'user' => $_ENV['DB_USER'],
                    'pass' => $_ENV['DB_PASS']
                ]
            ]
        ];
        
        return $configs[$env];
    }
}
```

#### Deployment Script
```bash
#!/bin/bash
# deploy.sh

set -e

echo "Starting deployment..."

# Pull latest code
git pull origin main

# Install/update dependencies
composer install --no-dev --optimize-autoloader

# Run database migrations
php scripts/migrate.php

# Clear cache
php scripts/clear-cache.php

# Set permissions
chmod -R 755 uploads/
chown -R www-data:www-data uploads/

# Restart services
sudo systemctl restart apache2

echo "Deployment completed successfully!"
```

### Docker Deployment

```dockerfile
# Dockerfile
FROM php:8.0-apache

# Install PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable Apache modules
RUN a2enmod rewrite ssl

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy application
COPY . /var/www/html/

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Set permissions
RUN chown -R www-data:www-data /var/www/html/
RUN chmod -R 755 /var/www/html/uploads/

# Configure Apache
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

EXPOSE 80 443

CMD ["apache2-foreground"]
```

## Contributing

### Git Workflow

#### Branch Naming
- `feature/feature-name` - New features
- `bugfix/bug-description` - Bug fixes
- `hotfix/critical-fix` - Critical production fixes
- `refactor/component-name` - Code refactoring

#### Commit Messages
```
type(scope): description

[optional body]

[optional footer]
```

Types:
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `style`: Code style changes
- `refactor`: Code refactoring
- `test`: Adding or updating tests
- `chore`: Maintenance tasks

Examples:
```
feat(elections): add election scheduling functionality

fix(voters): resolve duplicate voter registration issue

docs(api): update API documentation for new endpoints
```

#### Pull Request Process
1. Create feature branch from `main`
2. Implement changes with tests
3. Update documentation
4. Submit pull request
5. Code review and approval
6. Merge to `main`

### Code Review Guidelines

#### Checklist
- [ ] Code follows project standards
- [ ] All tests pass
- [ ] Documentation updated
- [ ] Security considerations addressed
- [ ] Performance impact assessed
- [ ] Backward compatibility maintained

#### Review Focus Areas
- **Security**: Input validation, SQL injection, XSS
- **Performance**: Query optimization, caching
- **Maintainability**: Code clarity, documentation
- **Testing**: Test coverage, edge cases

## Troubleshooting

### Common Development Issues

#### Database Connection Issues
```php
// Debug database connection
try {
    $pdo = new PDO($dsn, $username, $password, $options);
    echo "Database connected successfully";
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    
    // Check common issues:
    // 1. Database server running?
    // 2. Correct credentials?
    // 3. Database exists?
    // 4. Firewall blocking connection?
}
```

#### Session Issues
```php
// Debug session problems
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "Session ID: " . session_id() . "\n";
echo "Session data: " . print_r($_SESSION, true) . "\n";
echo "Session save path: " . session_save_path() . "\n";

// Common fixes:
// 1. Check session.save_path permissions
// 2. Verify session.cookie_domain setting
// 3. Check for session_start() conflicts
```

#### File Upload Issues
```php
// Debug file upload problems
echo "Upload max filesize: " . ini_get('upload_max_filesize') . "\n";
echo "Post max size: " . ini_get('post_max_size') . "\n";
echo "Max file uploads: " . ini_get('max_file_uploads') . "\n";
echo "Upload tmp dir: " . ini_get('upload_tmp_dir') . "\n";

if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    switch ($_FILES['file']['error']) {
        case UPLOAD_ERR_INI_SIZE:
            echo "File too large (php.ini limit)";
            break;
        case UPLOAD_ERR_FORM_SIZE:
            echo "File too large (form limit)";
            break;
        case UPLOAD_ERR_PARTIAL:
            echo "File partially uploaded";
            break;
        case UPLOAD_ERR_NO_FILE:
            echo "No file uploaded";
            break;
        case UPLOAD_ERR_NO_TMP_DIR:
            echo "No temporary directory";
            break;
        case UPLOAD_ERR_CANT_WRITE:
            echo "Cannot write to disk";
            break;
    }
}
```

### Performance Optimization

#### Database Optimization
```sql
-- Add indexes for frequently queried columns
CREATE INDEX idx_voters_student_id ON voters(student_id);
CREATE INDEX idx_votes_election_id ON votes(election_id);
CREATE INDEX idx_candidates_position_id ON candidates(position_id);

-- Optimize queries
EXPLAIN SELECT * FROM votes 
JOIN candidates ON votes.candidate_id = candidates.id 
WHERE votes.election_id = 1;
```

#### Caching Strategy
```php
class CacheManager {
    private $redis;
    
    public function __construct() {
        $this->redis = new Redis();
        $this->redis->connect('127.0.0.1', 6379);
    }
    
    public function get($key) {
        $data = $this->redis->get($key);
        return $data ? json_decode($data, true) : null;
    }
    
    public function set($key, $data, $ttl = 3600) {
        return $this->redis->setex($key, $ttl, json_encode($data));
    }
    
    public function delete($key) {
        return $this->redis->del($key);
    }
    
    public function flush() {
        return $this->redis->flushAll();
    }
}
```

---

**Note**: This developer guide is a living document that evolves with the project. Always refer to the latest version and contribute improvements as you discover better practices or encounter new challenges.