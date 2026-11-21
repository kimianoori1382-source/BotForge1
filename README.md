# BotForge

A lightweight and extensible PHP framework for building Telegram bots.

## About

BotForge is a PHP framework designed to simplify Telegram bot development by providing reusable components and a structured architecture.

## Features

- Telegram Bot API integration
- HTTP client abstraction
- Command and message routing
- Callback query handling
- Middleware system
- Dependency injection container
- Service providers
- Context management
- Conversation management
- File, PDO, and Redis storage drivers
- Webhook support
- Long polling support
- Keyboard management
- Exception handling

## Requirements

- PHP 8.2 or higher
- Composer
- Guzzle HTTP 8
- PHP dotenv 5
- PSR Container 2

Optional requirements:
- PDO extension for database storage
- Redis extension for Redis storage

## Installation

Clone the repository:

```bash
git clone https://github.com/kimianoori1382/BotForge1.git
cd BotForge
```

Install dependencies:

```bash
composer install
```

## Configuration

Copy `.env.example` to `.env` and configure your environment:

```env
BOT_TOKEN=
BOT_WEBHOOK_SECRET=
BOT_DEBUG=false
BOT_LANGUAGE=fa
```

Add your Telegram bot token and webhook secret to the `.env` file.

**Never commit your `.env` file or expose your bot token.**

## Project Structure

```text
BotForge/
├── src/
│   ├── Api/
│   ├── Container/
│   ├── Context/
│   ├── Conversation/
│   ├── Entities/
│   ├── Exception/
│   ├── Keyboard/
│   ├── Middleware/
│   ├── Providers/
│   ├── Routing/
│   ├── Storage/
│   └── Support/
├── tests/
├── .env.example
├── .gitignore
├── composer.json
├── phpunit.xml
├── phpstan.neon
└── README.md
```

## Development

Validate Composer configuration:

```bash
composer validate
```

Run tests:

```bash
composer test
```

Run static analysis:

```bash
composer analyse
```

Check code formatting:

```bash
composer format:check
```

## License

This project is licensed under the MIT License.