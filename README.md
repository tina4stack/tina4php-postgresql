# tina4php-postgresql

PostgreSQL database driver for the Tina4 PHP framework.

## Installation

```bash
composer require tina4stack/tina4php-postgresql
```

## Requirements

- PHP >= 8.1
- ext-pgsql
- tina4stack/tina4php-database ^2.0

## Usage

```php
// Connection format: host/port:database
$DBA = new \Tina4\DataPostgresql("localhost/5432:database", "postgres", "password");

// Execute queries
$DBA->exec("create table users (id integer primary key, name varchar(200))");
$DBA->commit();

// Insert with parameters
$DBA->exec("insert into users (id, name) values (?, ?)", 1, "Alice");

// Fetch records
$result = $DBA->fetch("select * from users");
$records = $result->asArray();

// Fetch with limit and offset
$result = $DBA->fetch("select * from users", 10, 0);

// Fetch single record
$record = $DBA->fetchOne("select * from users where id = 1");

// Check if table exists
$exists = $DBA->tableExists("users");

// Get database metadata
$metadata = $DBA->getDatabase();
```

## Testing

```bash
docker compose up -d
composer test
```

## License

MIT - see [LICENSE](LICENSE)

---

## Our Sponsors

**Sponsored with 🩵 by Code Infinity**

[<img src="https://codeinfinity.co.za/wp-content/uploads/2025/09/c8e-logo-github.png" alt="Code Infinity" width="100">](https://codeinfinity.co.za/about-open-source-policy?utm_source=github&utm_medium=website&utm_campaign=opensource_campaign&utm_id=opensource)

*Supporting open source communities <span style="color: #1DC7DE;">•</span> Innovate <span style="color: #1DC7DE;">•</span> Code <span style="color: #1DC7DE;">•</span> Empower*
