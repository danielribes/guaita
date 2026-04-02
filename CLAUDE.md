# Guaita — CLAUDE.md

> "Guaita is the Catalan word for lookout — the one who watches and alerts."

This file defines the coding standards and conventions for this project.
Claude Code must follow these rules in every file it generates or modifies.

---

## Stack

- **PHP 8.4+** (required)
- **Symfony 8.0+** (required)
- Use Symfony components whenever one is available before reaching for a third-party library.
- Use **Composer** for dependency management and autoloading (PSR-4)
- All classes must use proper namespaces matching the directory structure
- Never use `require` or `include` for project classes — rely on autoloading

---

## PHP Modern Standards

### Always declare strict types
Every PHP file must start with:
```php
<?php

declare(strict_types=1);
```

### Type everything
- All class properties must be typed
- All method parameters must have type hints
- All methods must declare a return type
- Use nullable types (`?string`) only when `null` is a meaningful value, not for convenience

```php
// Correct
private string $url;
public function fetch(string $url): Response { }

// Wrong
private $url;
public function fetch($url) { }
```

### Use union types when appropriate
```php
public function find(int|string $id): User|null { }
```

### Use readonly for immutable properties
```php
public function __construct(
    public readonly string $url,
    public readonly string $selector,
) {}
```

### Use constructor property promotion
```php
// Correct
public function __construct(
    private readonly HttpClientInterface $httpClient,
    private readonly LoggerInterface $logger,
) {}

// Wrong
private HttpClientInterface $httpClient;
private LoggerInterface $logger;

public function __construct(HttpClientInterface $httpClient, LoggerInterface $logger)
{
    $this->httpClient = $httpClient;
    $this->logger = $logger;
}
```

### Use enums instead of class constants for domain values
```php
enum MonitorStatus: string
{
    case Active = 'active';
    case Paused = 'paused';
    case Error = 'error';
}
```

### Use match instead of switch
```php
$message = match($status) {
    MonitorStatus::Active => 'Monitor is active',
    MonitorStatus::Paused => 'Monitor is paused',
    MonitorStatus::Error  => 'Monitor encountered an error',
};
```

### Use named arguments for clarity
```php
$this->render('monitor/show.html.twig', context: ['monitor' => $monitor]);
```

### Use arrow functions for short callbacks
```php
$urls = array_map(fn(Monitor $m) => $m->url, $monitors);
```

---

## Code Style

Follow **PSR-12** and Symfony coding standards.

- 4 spaces for indentation, no tabs
- Opening braces on the same line for control structures, new line for classes and methods
- One blank line between methods
- Class names in `PascalCase`, methods and variables in `camelCase`, constants in `UPPER_SNAKE_CASE`

---

## Object Calisthenics

Apply these principles in all code:

1. **One level of indentation per method** — extract methods if you need to nest deeper
2. **Never use the `else` keyword** — use early returns instead
3. **Wrap all primitives and strings** — encapsulate domain values in value objects
4. **First-class collections** — wrap arrays in dedicated collection classes
5. **One dot (method call) per line** — do not chain beyond one call per line
6. **No abbreviations** — use full, descriptive names (`$httpClient`, not `$client`; `$selector`, not `$sel`)
7. **Keep all entities small** — classes under 50 lines, methods under 10 lines
8. **No more than two instance variables per class** — inject dependencies, do not accumulate state
9. **No getters/setters or public properties** — expose behaviour, not data

---

## Early Returns

Always handle edge cases first and return early. Never use `else` after a `return`.

```php
// Correct
public function process(?string $url): Response
{
    if ($url === null) {
        return $this->error('URL is required');
    }

    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return $this->error('Invalid URL');
    }

    return $this->fetch($url);
}

// Wrong
public function process(?string $url): Response
{
    if ($url !== null) {
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return $this->fetch($url);
        } else {
            return $this->error('Invalid URL');
        }
    } else {
        return $this->error('URL is required');
    }
}
```

---

## Symfony Conventions

- Use **Attributes** for routing, validation, and service configuration — never YAML or XML for these
- Use **constructor injection** — never use the service locator pattern
- Use **Symfony Console** for all CLI commands
- Use **Symfony HttpClient** for HTTP requests
- Use **Symfony DomCrawler + CssSelector** for HTML parsing
- Commands must use `#[AsCommand]` attribute

```php
#[AsCommand(name: 'guaita:check', description: 'Check a monitored URL for changes')]
class CheckUrlCommand
{
    public function __construct(
        private readonly MonitorService $monitorService,
    ) {}

    public function __invoke(
        #[InputArgument] string $url,
        #[InputOption] ?string $selector = null,
    ): void {
        // ...
    }
}
```

---

## Testing

Every class must have a corresponding test. Use **PHPUnit 13+**.

Run the test suite with:
```bash
composer test
```

### Test structure

Mirror the `src/` structure under `tests/`:
```
tests/
  Domain/       — pure unit tests, no I/O
  Service/      — use real filesystem with sys_get_temp_dir(), or MockHttpClient for HTTP
  Command/      — use CommandTester + stubs for dependencies
```

### Rules

- Test class names end in `Test` and are `final`
- Use `#[Test]` attribute instead of `test` prefix on method names
- Use `createStub()` when you only need a method to return a value
- Use `createMock()` only when you need to assert a method was called (`expects()`)
- Use `setUp()` and `tearDown()` to create and clean up temporary directories
- Test method names describe behaviour: `it_returns_null_when_no_snapshot_exists`

---

## What to avoid

- No `else` keyword — ever
- No untyped properties or parameters
- No `array` as a return type when a specific type or collection class is possible
- No inline HTML in controllers — always use Twig templates
- No hardcoded strings for domain values — use enums
- No abbreviations in names