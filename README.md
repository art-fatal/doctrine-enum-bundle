# Doctrine Enum Bundle

A Symfony bundle that provides automatic registration of PHP 8.1+ BackedEnum as Doctrine DBAL custom types with MySQL ENUM columns.

## Features

- **Zero Configuration**: Auto-registers all enum types with Doctrine
- **Type-Safe**: Uses native PHP BackedEnum
- **MySQL ENUM Support**: Generates proper MySQL ENUM columns
- **Easy to Use**: Just extend `EnumType` and you're done
- **Built-in Enums**: Includes ready-to-use enums like `DayOfWeek`
- **Symfony 6/7 Compatible**: Works with modern Symfony versions

## Installation

```bash
composer require art-fatal/doctrine-enum-bundle
```

### Manual Bundle Registration (Symfony without Flex)

If you're not using Symfony Flex, add the bundle to `config/bundles.php`:

```php
return [
    // ...
    ArtFatal\DoctrineEnumBundle\DoctrineEnumBundle::class => ['all' => true],
];
```

## Built-in Enums

The bundle includes production-ready enums that you can use directly without creating your own.

### DayOfWeek

A complete implementation of days of the week with helpful utility methods.

**Usage in Entity:**

```php
use ArtFatal\DoctrineEnumBundle\Enum\DayOfWeek;
use ArtFatal\DoctrineEnumBundle\Type\DayOfWeekEnumType;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Schedule
{
    // You can use either the static method or string literal
    #[ORM\Column(type: DayOfWeekEnumType::getTypeName())]
    // OR
    // #[ORM\Column(type: 'day_of_week')]
    private ?DayOfWeek $dayOfWeek = null;

    public function getDayOfWeek(): ?DayOfWeek
    {
        return $this->dayOfWeek;
    }

    public function setDayOfWeek(?DayOfWeek $dayOfWeek): self
    {
        $this->dayOfWeek = $dayOfWeek;
        return $this;
    }
}
```

**Available Methods:**

```php
// Set a day
$schedule->setDayOfWeek(DayOfWeek::MONDAY);

// Check if it's a weekend
if ($schedule->getDayOfWeek()->isWeekend()) {
    echo "It's the weekend!"; // true for Saturday/Sunday
}

// Check if it's a weekday
if ($schedule->getDayOfWeek()->isWeekday()) {
    echo "It's a weekday!"; // true for Monday-Friday
}

// Get human-readable label
echo $schedule->getDayOfWeek()->label(); // "Monday"

// Get ISO-8601 day number
$number = DayOfWeek::MONDAY->toIsoNumber(); // 1

// Create from ISO-8601 day number
$day = DayOfWeek::fromIsoNumber(7); // DayOfWeek::SUNDAY

// Get all weekdays or weekend days
$weekdays = DayOfWeek::weekDays(); // [MONDAY, TUESDAY, ..., FRIDAY]
$weekend = DayOfWeek::weekendDays(); // [SATURDAY, SUNDAY]
```

**Database Column:**

```sql
ENUM('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday')
```

## Custom Enums

You can also create your own custom enums:

### 1. Create a PHP Enum

```php
// src/Constant/Enum/User/Status.php
namespace App\Constant\Enum\User;

enum Status: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case BANNED = 'banned';

    const ALL = [
        self::ACTIVE,
        self::INACTIVE,
        self::BANNED,
    ];
}
```

### 2. Create a Doctrine Enum Type

```php
// src/Type/Enum/UserStatusEnumType.php
namespace App\Type\Enum;

use App\Constant\Enum\User\Status;
use ArtFatal\DoctrineEnumBundle\Type\EnumType;

class UserStatusEnumType extends EnumType
{
    public static function getEnumsClass(): string
    {
        return Status::class;
    }
}
```

The type name is automatically generated from the class name in snake_case:
- `UserStatusEnumType` → `user_status`
- `DayOfWeekEnumType` → `day_of_week`
- `OrderStateEnumType` → `order_state`

### 3. Use in Your Entity

You can reference the type name in two ways:

**Option 1: Using the static method (recommended for refactoring safety)**
```php
#[ORM\Column(type: UserStatusEnumType::getTypeName())]
private ?Status $status = null;
```

**Option 2: Using the string literal**
```php
#[ORM\Column(type: 'user_status')]
private ?Status $status = null;
```

Both are equivalent, but the static method provides IDE autocomplete and refactoring support.

**Full example:**

```php
// src/Entity/User.php
namespace App\Entity;

use App\Constant\Enum\User\Status;
use App\Type\Enum\UserStatusEnumType;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
class User
{
    #[ORM\Column(type: 'user_status', options: ['default' => Status::ACTIVE->value])]
    #[Assert\NotNull]
    #[Assert\Choice(choices: Status::ALL)]
    private ?Status $status = Status::ACTIVE;

    public function getStatus(): ?Status
    {
        return $this->status;
    }

    public function setStatus(?Status $status): self
    {
        $this->status = $status;
        return $this;
    }
}
```

### 4. Generate Migration

```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

The migration will create a proper MySQL ENUM column:

```sql
ALTER TABLE user ADD status ENUM('active', 'inactive', 'banned') DEFAULT 'active' NOT NULL;
```

## How It Works

1. **Extension**: The `DoctrineEnumExtension` auto-tags all classes extending `EnumType`
2. **Compiler Pass**: The `RegisterEnumTypesPass` registers all tagged enum types with Doctrine DBAL
3. **Type Conversion**: `EnumType` handles conversion between PHP BackedEnum and database string values
4. **SQL Generation**: Creates proper MySQL ENUM columns with all enum cases

## Examples

Complete, working examples are available in the [`examples/`](examples/) directory:

- **DayOfWeek enum**: A real-world example with helper methods
- **Complete entity**: Shows integration with Doctrine entities
- **Step-by-step guide**: Detailed instructions for using the examples

See [`examples/README.md`](examples/README.md) for detailed usage instructions.

### Quick Example Preview

The `DayOfWeek` example includes useful helper methods:

```php
enum DayOfWeek: string
{
    case MONDAY = 'monday';
    // ... other days

    public function isWeekend(): bool
    {
        return in_array($this, [self::SATURDAY, self::SUNDAY], true);
    }

    public function label(): string
    {
        return ucfirst($this->value); // "Monday"
    }
}
```

Used in an entity:

```php
$schedule->setDayOfWeek(DayOfWeek::SATURDAY);
if ($schedule->getDayOfWeek()->isWeekend()) {
    echo "Weekend activity!";
}
```

## Advantages

- **No Manual Configuration**: No need to modify `services.yaml` or `Kernel.php`
- **Reusable**: Just install the bundle in any Symfony project
- **Type Safety**: Full IDE autocomplete and type checking with PHP enums
- **Database Constraints**: MySQL ENUM provides database-level validation
- **Clean Code**: Separation between enum definition, type definition, and entity usage

## Requirements

- PHP >= 8.1
- Symfony 6.x or 7.x
- Doctrine DBAL 3.x or 4.x

## License

MIT License. See [LICENSE](LICENSE) file for details.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Support

If you encounter any issues, please open an issue on the GitHub repository.