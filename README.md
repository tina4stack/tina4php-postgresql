# tina4php-firebird

### Installation
```
composer require tina4stack/tina4php-postgresql
```

### Testing with Docker
```
docker run --name tina4-postgres --platform linux/x86_64 -p 54321:5432 -e POSTGRES_PASSWORD=pass1234 postgres
```

```
composer test
```