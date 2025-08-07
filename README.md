# Laravel Model Documenter

[![Tests](https://github.com/patressz/laravel-model-documenter/actions/workflows/tests.yml/badge.svg)](https://github.com/patressz/laravel-model-documenter/actions/workflows/tests.yml)

A simple Laravel package for automatic generation of PHPDoc comments for Eloquent models. The package automatically analyzes database columns, relations, accessors, and scope methods of your models and generates properly typed PHPDoc annotations.

## Requirements

- PHP 8.3+
- Laravel 11.0+

## Installation

Install the package via Composer:

```bash
composer require patressz/laravel-model-documenter --dev
```

## Usage

### Basic Usage

Generate documentation for all models in the `app/Models` directory:

```bash
php artisan model-doc:generate
```

### Specific Model

Generate documentation for a specific model only:

```bash
php artisan model-doc:generate --model=App\\Models\\User
```

### Custom Directory

Specify a custom directory containing models:

```bash
php artisan model-doc:generate --path=/path/to/your/models
```

Compare existing documentation with expected documentation (without modifying files):

```bash
php artisan model-doc:generate --test
```

## What it Generates

The package automatically generates PHPDoc annotations for:

- **Database columns** - `@property` with correct types based on database schema
- **Casts** - Automatically detects model casts and overrides database types with cast types (e.g., `datetime`, `array`, `json`, `boolean`)
- **Relations** - `@property-read` for all relation types (hasOne, hasMany, belongsTo, etc.) - **Note: Only generates properties for relationship methods that have proper return type declarations** (e.g., `HasMany`, `BelongsTo`)
- **Accessors/Mutators** - `@property`, `@property-read` or `@property-write` depending on type (supports both old-style and new Attribute-based accessors)
- **Local Scope methods** - `@method` annotations for scope methods

## Example Output

For a `User` model with a table containing `id`, `name`, `email`, `email_verified_at` columns, casts, and a `posts()` relation:

```php
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property array $settings
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Post> $posts
 * 
 * @method static Builder<static>|User active()
 */
class User extends Model
{
    protected $casts = [
        'email_verified_at' => 'datetime',
        'settings' => 'array',
    ];

    // your model code...

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
```
## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Credits

- [Patrik Strišovský](https://github.com/patressz)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.