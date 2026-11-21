# Contributing to BotForge

Thank you for your interest in contributing to BotForge.

## Getting Started

1. Fork the repository.
2. Clone your fork.
3. Install dependencies:

```bash
composer install
```

4. Create a feature branch:

```bash
git checkout -b feature/your-feature-name
```

## Development Guidelines

- Use PHP 8.2 or higher.
- Use strict types in every PHP file.
- Follow PSR-12 coding standards.
- Follow PSR-4 autoloading conventions.
- Keep the code simple, readable, and maintainable.
- Avoid unnecessary dependencies.
- Do not commit `.env` files, tokens, or secrets.

## Testing

Before submitting changes, run:

```bash
composer test
composer analyse
composer format:check
```

All checks should pass before submitting a pull request.

## Pull Requests

1. Create a focused feature branch.
2. Make small, meaningful commits.
3. Explain the purpose of your changes.
4. Include tests for new functionality.
5. Update documentation when necessary.
6. Submit a pull request for review.

## Reporting Issues

When reporting an issue, include:

- A clear description of the problem.
- Steps to reproduce it.
- Expected behavior.
- Actual behavior.
- Relevant error messages.

## Code of Conduct

Be respectful, constructive, and professional when interacting with other contributors.

Thank you for helping improve BotForge.