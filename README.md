# Laravel Docbot

A Laravel package for generating API documentation and listing custom Artisan commands.

## Installation

```bash
composer require equidnamx/laravel-docbot
```

## Usage

### Commands

- `php artisan docbot:generate` — Generates API documentation and Postman collections.
- `php artisan project:commands:list` — Lists all custom Artisan commands in your project.

### Configuration

To publish the config file for customization:

```bash
php artisan vendor:publish --provider="Equidna\LaravelDocbot\LaravelDocbotServiceProvider" --tag=config
```

Edit `config/docbot.php` to customize tokens and output directory.

## Contributing

PRs and issues welcome!

## License

MIT
