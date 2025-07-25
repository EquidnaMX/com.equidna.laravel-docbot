# Laravel Docbot

> **Modern API documentation and custom command listing for Laravel 11/12**

[!NOTE]

> Laravel Docbot is a developer-focused package for generating API docs and listing custom Artisan commands, with configuration and code quality built-in.

---

## 🚀 Introduction

Laravel Docbot automates API documentation and custom command discovery for Laravel projects. It generates Markdown and Postman collections for your routes, and lists all custom Artisan commands—helping teams keep docs up-to-date and developer onboarding frictionless.

---

## Main Use Cases

- **API Documentation:** Instantly generate Markdown and Postman collections for all API routes, segmented by prefix.
- **Custom Command Listing:** List all custom Artisan commands, excluding built-in Laravel/Artisan commands.
- **Configurable Output:** Control output directories, route segments, and command exclusions via config.

---

## Quick Installation

```bash
composer require equidnamx/laravel-docbot
```

> Requires Laravel 11 or 12 and PHP >=8.0

---

## Minimal Usage Example

### Generate API Documentation

```bash
php artisan docbot:routes
```

### List Custom Commands

```bash
php artisan docbot:commands
```

### Publish and Edit Configuration

```bash
php artisan vendor:publish --provider="Equidna\LaravelDocbot\LaravelDocbotServiceProvider" --tag=config
```

Edit `config/docbot.php` to customize segments, output directories, and command exclusions:

```php
'segments' => [
    ['key' => 'api', 'prefix' => 'api/', 'token' => 'API_TOKEN'],
    // ...
],
'exclude_namespaces' => [ ... ],
'exclude_commands' => [ ... ],
```

---

## Technical Overview

- **Command-based workflow:** All features are implemented as Artisan commands.
- **API docs:** Reads route definitions via `Artisan::call('route:list', ['--json' => true])`, segments by prefix, outputs to `doc/routes/<segment>/`.
- **Custom commands:** Filters built-in commands using config, outputs to `doc/commands/project_commands.md`.
- **Config-driven:** All exclusions and segments are set in `config/docbot.php`.
- **Code quality:** PSR-12 enforced, static analysis with PHPStan, tests with PHPUnit.

[!TIP]

> Use `phpcbf` and `vendor/bin/phpstan analyse src/ --level=max` to maintain code quality before submitting PRs.

---

## Development Instructions

- **Run tests:**
  ```bash
  vendor/bin/phpunit
  ```
- **Static analysis:**
  ```bash
  vendor/bin/phpstan analyse src/ --level=max
  ```
- **Code style:**
  ```bash
  phpcbf src/
  ```

---

## Repository Standards

- PHP code follows PSR-12 (see `Codding Standards.instructions.md`)
- `.gitignore` includes standard Laravel ignores
- No front-end assets or JS/TS code in project source

[!NOTE]

> For full coding standards and workflow rules, see `Codding Standards.instructions.md`.
