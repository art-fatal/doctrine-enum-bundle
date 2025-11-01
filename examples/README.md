# Examples

This directory contains complete, working examples showing how to use the Doctrine Enum Bundle in your Symfony project.

## DayOfWeek Example

A complete example demonstrating the use of a `DayOfWeek` enum representing days of the week.

### Files Included

```
examples/
├── Enum/
│   └── DayOfWeek.php           # The PHP BackedEnum definition
├── Type/
│   └── DayOfWeekEnumType.php   # The Doctrine custom type
├── Entity/
│   └── Schedule.php            # Example entity using the enum
└── README.md                   # This file
```

### How to Use These Examples

#### 1. Copy the Enum

Copy `examples/Enum/DayOfWeek.php` to your project:

```bash
cp examples/Enum/DayOfWeek.php /path/to/your/project/src/Constant/Enum/
```

**Update the namespace**:
```php
// Change from:
namespace YourApp\Constant\Enum;

// To:
namespace App\Constant\Enum;  // or your project namespace
```

#### 2. Copy the Doctrine Type

Copy `examples/Type/DayOfWeekEnumType.php` to your project:

```bash
cp examples/Type/DayOfWeekEnumType.php /path/to/your/project/src/Type/Enum/
```

**Update the namespace and import**:
```php
// Change from:
namespace YourApp\Type\Enum;
use YourApp\Constant\Enum\DayOfWeek;

// To:
namespace App\Type\Enum;
use App\Constant\Enum\DayOfWeek;
```

#### 3. Use in Your Entity

See `examples/Entity/Schedule.php` for a complete entity example.

**Key parts**:
```php
use App\Constant\Enum\DayOfWeek;
use App\Type\Enum\DayOfWeekEnumType;

class YourEntity
{
    #[ORM\Column(
        type: DayOfWeekEnumType::NAME,
        options: ['default' => DayOfWeek::MONDAY->value]
    )]
    #[Assert\NotNull]
    #[Assert\Choice(choices: DayOfWeek::ALL)]
    private ?DayOfWeek $dayOfWeek = DayOfWeek::MONDAY;
}
```

#### 4. Generate Migration

```bash
php bin/console doctrine:migrations:diff
```

This will generate a migration with:
```sql
ALTER TABLE your_table ADD day_of_week ENUM('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday') DEFAULT 'monday' NOT NULL;
```

#### 5. Run Migration

```bash
php bin/console doctrine:migrations:migrate
```

### What Makes This Example Special

The `DayOfWeek` example includes:

1. **Basic enum cases**: All 7 days of the week
2. **ALL constant**: For use with validation constraints
3. **Helper methods**:
   - `weekendDays()`: Returns array of weekend days
   - `weekDays()`: Returns array of weekdays
   - `isWeekend()`: Checks if the day is a weekend
   - `label()`: Returns human-readable label

4. **Complete entity integration**:
   - Proper Doctrine annotations/attributes
   - Symfony validation constraints
   - Business logic methods using the enum

### Testing the Example

After copying to your project, you can test it:

```php
// In a controller or service
$schedule = new Schedule();
$schedule->setDayOfWeek(DayOfWeek::SATURDAY);
$schedule->setActivity('Weekend hiking');

// Use enum methods
if ($schedule->getDayOfWeek()->isWeekend()) {
    echo "This is a weekend activity!";
}

echo $schedule->getDayLabel(); // "Saturday"

// Persist
$entityManager->persist($schedule);
$entityManager->flush();
```

### Database Result

The `day_of_week` column will be:
- **Type**: MySQL ENUM
- **Values**: 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'
- **Default**: 'monday'
- **Stored as**: String in database (e.g., 'saturday')
- **Retrieved as**: PHP DayOfWeek enum object

### Creating Your Own Enums

Use this example as a template:

1. **Define your enum** in `src/Constant/Enum/YourEnum.php`
   - Use `BackedEnum` with `string` or `int` backing type
   - Add an `ALL` constant for validation
   - Add helper methods as needed

2. **Create the Doctrine type** in `src/Type/Enum/YourEnumType.php`
   - Extend `ArtFatal\DoctrineEnumBundle\Type\EnumType`
   - Define a `NAME` constant
   - Implement `getEnumsClass()` and `getName()`

3. **Use in entities**
   - Use `type: YourEnumType::NAME` in column definition
   - Add `Assert\Choice(choices: YourEnum::ALL)` for validation
   - Set default value with `options: ['default' => YourEnum::CASE->value]`

4. **That's it!** The bundle handles the rest automatically.

## Real-World Examples from Howdens Project

This example is extracted from a real production project. Other enums used in that project include:

- **DocumentStatus**: quotation, invoiced, order
- **DocumentState**: published, deleted
- **OrderState**: pending, processing, completed, cancelled
- **CompanyState**: active, inactive, suspended
- **CustomerState**: active, inactive, banned

You can create similar enums for your business domain following this pattern.

## Need Help?

- See the main [README.md](../README.md) for installation instructions
- See [CONTEXT.md](../CONTEXT.md) for detailed technical documentation
- Open an issue on GitHub if you encounter problems