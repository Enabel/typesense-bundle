# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

PHP Composer package (`enabel/typesense`) providing a Typesense search integration library with attribute-based document mapping. Built in three layers:

1. **Core** (`Enabel\Typesense\`) — Framework-agnostic. Depends only on `typesense/typesense-php`.
2. **Doctrine** (`Enabel\Typesense\Doctrine\`) — Optional. Entity denormalizer and lifecycle event listener for auto-indexing.
3. **Symfony Bundle** (`Enabel\Typesense\Bundle\`) — Optional. DI configuration, console commands, Messenger messages/handlers.

The full specification lives in `plan.md`.

## Development Environment

```bash
# Start services (FrankenPHP + Typesense)
docker compose up -d

# Run commands inside the app container
docker compose exec app <command>

# Composer
docker compose exec app composer install

# Tests (PHPUnit with pcov for coverage)
docker compose exec app vendor/bin/phpunit
docker compose exec app vendor/bin/phpunit --filter=TestClassName
docker compose exec app vendor/bin/phpunit tests/path/to/SpecificTest.php

# Static analysis
docker compose exec app phpstan analyse

# Code style (if php-cs-fixer is configured)
docker compose exec app vendor/bin/php-cs-fixer fix
```

**Stack:** PHP 8.4, Typesense 29.0 (API key: `123` for dev), FrankenPHP, Composer, PHPStan, PHPUnit.

## Architecture

- PHP 8 attributes (`#[Document]`, `#[Id]`, `#[Field]`) define Typesense schema mappings on plain PHP classes
- Type system (`TypeInterface`) handles value conversion between PHP and Typesense (string, int, float, bool, datetime, enums, arrays)
- `MetadataReader` extracts attribute metadata; `MetadataRegistry` caches it
- `SchemaBuilder` generates Typesense collection schemas from metadata
- `DocumentNormalizer` / `ObjectDenormalizer` convert between PHP objects and Typesense documents
- `Query` builder provides fluent search API; `Filter` provides type-safe filter expressions
- Response DTOs (`Response`, `GroupedResponse`, `Hit`, `FacetCount`, etc.) use PHP 8.4 property hooks for computed values
- All response DTOs expose `raw` property for unmapped Typesense fields

## Development Methodology

Follow TDD: write tests first, implement to pass, refactor while green. All components require test coverage.

## Key Conventions

- PHP 8.4 features: property hooks, typed properties, enums
- Public properties over getter methods throughout
- Root namespace: `Enabel\Typesense`
- Response DTOs are pure data containers with no service dependencies
- Type inference from PHP property types where possible; explicit `type` parameter on attributes for arrays and custom conversions
- Nullable properties (`?type`) automatically set `optional: true` on the Typesense field
