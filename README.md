# Laravel Docbot

Generate beautiful API documentation and custom command listings for your Laravel 11/12 projects — effortlessly.

## 🚀 Introduction

**Laravel Docbot** is a developer-focused package that automates the generation of API route documentation (Markdown & Postman collections) and lists all custom Artisan commands in your Laravel project. Designed for modern teams, it streamlines onboarding, API sharing, and internal documentation.

## Main Use Cases

- **API Documentation:** Instantly generate Markdown and Postman v2.1 collections for all your API routes, segmented by prefix.
- **Custom Command Listing:** List all custom Artisan commands, excluding built-in Laravel commands, for easy project overview.
- **Configurable Output:** Customize output directories, route segments, and command exclusions via `config/docbot.php`.

## Quick Installation

```bash
composer require equidnamx/laravel-docbot
```

> The package uses Laravel's auto-discovery. To customize configuration:

```bash
php artisan vendor:publish --tag=config
```

## Usage Examples

### Generate API Documentation

```bash
php artisan docbot:routes
```

- Outputs Markdown and Postman JSON files to `doc/routes/<segment>/`

### List Custom Artisan Commands

```bash
php artisan docbot:commands
```

- Outputs Markdown to `doc/commands/project_commands.md`

## Configuration

Edit `config/docbot.php` to:

- Set output directories
- Define route segments (prefixes, tokens)
- Exclude namespaces/commands from listings

Example segment config:

```php
'segments' => [
    ['key' => 'api', 'prefix' => 'api/', 'token' => 'API_TOKEN'],
    // ...
],
```

## Technical Overview

- **Command-based workflow:** All features are implemented as Artisan commands.
- **API docs:** Reads route definitions via `Artisan::call('route:list', ['--json' => true])`, segments by prefix, and extracts controller docblocks for descriptions.
- **Postman collections:** Generated per segment, compatible with Postman v2.1 schema.
- **Custom command listing:** Filters out built-in commands using config-driven lists.
- **Output:**
  - API docs: `doc/routes/<segment>/`
  - Command docs: `doc/commands/`

## Development Instructions

- **Code Style:** PSR-12 (see Coding Standards)
- **Testing:**
  - Run tests: `vendor/bin/phpunit`
  - Static analysis: `vendor/bin/phpstan analyse src/ --level=max`
- **Configuration:** Edit `config/docbot.php` for custom output and exclusions.

---

For more details, see the source code and configuration files. Contributions welcome!
