# Doctrine Enum Bundle - Context & Development Guide

## Table des matières
1. [Origine du projet](#origine-du-projet)
2. [Architecture du bundle](#architecture-du-bundle)
3. [Comment ça fonctionne](#comment-ça-fonctionne)
4. [Fichiers du bundle](#fichiers-du-bundle)
5. [Utilisation](#utilisation)
6. [Publication et distribution](#publication-et-distribution)
7. [Développement futur](#développement-futur)
8. [Projet source](#projet-source)

---

## Origine du projet

### Problème initial
Dans le projet Howdens (`cp-app/backend`), un pattern était utilisé de manière répétitive pour gérer les enums PHP 8.1+ avec Doctrine:

1. Création d'un enum PHP natif (`BackedEnum`)
2. Création d'un type Doctrine personnalisé (`EnumType`)
3. Configuration manuelle dans `Kernel.php` et `services.yaml`

### Pattern utilisé dans le projet source

**Enum PHP** (`src/Constant/Enum/Project/DocumentStatus.php`):
```php
enum DocumentStatus: string
{
    case QUOTATION = 'quotation';
    case INVOICED = 'invoiced';
    case ORDER = 'order';

    const ALL = [self::QUOTATION, self::INVOICED, self::ORDER];
}
```

**Type Doctrine** (`src/Type/Enum/ProjectDocumentStatusEnumType.php`):
```php
class ProjectDocumentStatusEnumType extends EnumType
{
    public const NAME = 'project_document_status_type';

    public static function getEnumsClass(): string
    {
        return DocumentStatus::class;
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
```

**Classe abstraite** (`src/Type/EnumType.php`):
```php
abstract class EnumType extends Type
{
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        /** @var BackedEnum $enum */
        $enum = $this::getEnumsClass();
        $values = array_map(function($enum) {
            return "'".$enum->value."'";
        }, $enum::cases());

        return "ENUM(".implode(", ", $values).")";
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): mixed
    {
        if ($value instanceof BackedEnum) {
            return $value->value;
        }
        return null;
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): mixed
    {
        if (false === enum_exists($this::getEnumsClass())) {
            throw new LogicException("Class {$this::getEnumsClass()} should be an enum");
        }

        return $this::getEnumsClass()::tryFrom($value);
    }

    abstract public static function getEnumsClass(): string;
}
```

**Configuration** (`src/Kernel.php`):
```php
class Kernel extends BaseKernel implements CompilerPassInterface
{
    use MicroKernelTrait;

    public function process(ContainerBuilder $container): void
    {
        $typesDefinition = [];
        if ($container->hasParameter('doctrine.dbal.connection_factory.types')) {
            $typesDefinition = $container->getParameter('doctrine.dbal.connection_factory.types');
        }

        $taggedEnums = $container->findTaggedServiceIds('app.doctrine_enum_type');

        foreach ($taggedEnums as $enumType => $definition) {
            $typesDefinition[$enumType::NAME] = ['class' => $enumType];
        }
        $container->setParameter('doctrine.dbal.connection_factory.types', $typesDefinition);
    }
}
```

**Configuration** (`config/services.yaml`):
```yaml
_instanceof:
    App\Type\EnumType:
        tags: [ 'app.doctrine_enum_type' ]
```

**Utilisation dans les entités** (`src/Entity/Project/ProjectDocument.php`):
```php
#[ORM\Column(type: ProjectDocumentStatusEnumType::NAME, options: ['default' => DocumentStatus::QUOTATION->value])]
#[Groups([ProjectDocumentGroup::READ, ProjectDocumentGroup::WRITE])]
#[Assert\NotNull]
#[Assert\Choice(choices: DocumentStatus::ALL)]
private ?DocumentStatus $status = DocumentStatus::QUOTATION;
```

### Solution: Créer un bundle réutilisable

L'objectif était de **transformer ce pattern en bundle Symfony réutilisable** pour éviter de copier-coller cette configuration dans chaque nouveau projet.

---

## Architecture du bundle

### Structure des fichiers

```
doctrine-enum-bundle/
├── src/
│   ├── DoctrineEnumBundle.php                          # Classe principale du bundle
│   ├── Type/
│   │   └── EnumType.php                                # Classe abstraite pour types ENUM
│   └── DependencyInjection/
│       ├── DoctrineEnumExtension.php                   # Extension Symfony (auto-config)
│       └── Compiler/
│           └── RegisterEnumTypesPass.php               # CompilerPass (auto-registration)
├── composer.json                                        # Métadonnées du package
├── README.md                                            # Documentation utilisateur
├── LICENSE                                              # Licence MIT
├── CONTEXT.md                                           # Ce fichier
└── .gitignore
```

### Namespace

Tous les fichiers utilisent le namespace: `ArtFatal\DoctrineEnumBundle`

### Dépendances

```json
{
    "require": {
        "php": ">=8.1",
        "symfony/dependency-injection": "^6.0|^7.0",
        "symfony/http-kernel": "^6.0|^7.0",
        "doctrine/dbal": "^3.0|^4.0"
    }
}
```

---

## Comment ça fonctionne

### 1. Extension Symfony (`DoctrineEnumExtension.php`)

```php
public function load(array $configs, ContainerBuilder $container): void
{
    // Auto-tag toutes les classes qui étendent EnumType
    $container->registerForAutoconfiguration(EnumType::class)
        ->addTag('doctrine_enum_bundle.enum_type');
}
```

**Rôle**: Lors du build du container, toutes les classes qui étendent `EnumType` sont automatiquement taggées avec `doctrine_enum_bundle.enum_type`.

### 2. Compiler Pass (`RegisterEnumTypesPass.php`)

```php
public function process(ContainerBuilder $container): void
{
    $typesDefinition = [];
    if ($container->hasParameter('doctrine.dbal.connection_factory.types')) {
        $typesDefinition = $container->getParameter('doctrine.dbal.connection_factory.types');
    }

    $taggedEnums = $container->findTaggedServiceIds('doctrine_enum_bundle.enum_type');

    foreach ($taggedEnums as $enumType => $definition) {
        $typesDefinition[$enumType::NAME] = ['class' => $enumType];
    }

    $container->setParameter('doctrine.dbal.connection_factory.types', $typesDefinition);
}
```

**Rôle**: Récupère tous les services taggués et les enregistre auprès de Doctrine DBAL comme custom types.

### 3. Bundle principal (`DoctrineEnumBundle.php`)

```php
public function build(ContainerBuilder $container): void
{
    parent::build($container);
    $container->addCompilerPass(new RegisterEnumTypesPass());
}
```

**Rôle**: Enregistre le compiler pass lors du build du bundle.

### 4. Type abstrait (`EnumType.php`)

Fournit la logique de conversion entre PHP BackedEnum et MySQL ENUM:

- `getSQLDeclaration()`: Génère `ENUM('value1', 'value2', ...)` pour MySQL
- `convertToDatabaseValue()`: Convertit `BackedEnum` → `string`
- `convertToPHPValue()`: Convertit `string` → `BackedEnum`
- `getEnumsClass()`: Méthode abstraite à implémenter

---

## Fichiers du bundle

### `src/DoctrineEnumBundle.php`
Point d'entrée principal du bundle. Enregistre le compiler pass.

### `src/Type/EnumType.php`
Classe abstraite qui gère:
- Génération SQL des colonnes ENUM MySQL
- Conversion BackedEnum ↔ string (base de données)
- Validation que la classe ciblée est bien un enum

### `src/DependencyInjection/DoctrineEnumExtension.php`
Extension Symfony qui configure l'auto-tagging des classes EnumType.

### `src/DependencyInjection/Compiler/RegisterEnumTypesPass.php`
Compiler pass qui enregistre tous les types enum auprès de Doctrine DBAL au moment du build du container.

### `composer.json`
- **Package name**: `art-fatal/doctrine-enum-bundle`
- **Type**: `symfony-bundle`
- **License**: MIT
- **Autoload**: PSR-4 avec namespace `ArtFatal\DoctrineEnumBundle`

### `README.md`
Documentation complète pour les utilisateurs:
- Installation
- Exemples d'utilisation
- Explication du fonctionnement

### `LICENSE`
Licence MIT standard.

---

## Utilisation

### Installation

```bash
composer require art-fatal/doctrine-enum-bundle
```

### Enregistrement du bundle (si pas Flex)

```php
// config/bundles.php
return [
    // ...
    ArtFatal\DoctrineEnumBundle\DoctrineEnumBundle::class => ['all' => true],
];
```

### Créer un enum PHP

```php
// src/Constant/Enum/User/Status.php
namespace App\Constant\Enum\User;

enum Status: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case BANNED = 'banned';

    const ALL = [self::ACTIVE, self::INACTIVE, self::BANNED];
}
```

### Créer un type Doctrine

```php
// src/Type/Enum/UserStatusEnumType.php
namespace App\Type\Enum;

use App\Constant\Enum\User\Status;
use ArtFatal\DoctrineEnumBundle\Type\EnumType;

class UserStatusEnumType extends EnumType
{
    public const NAME = 'user_status_type';

    public static function getEnumsClass(): string
    {
        return Status::class;
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
```

### Utiliser dans une entité

```php
use App\Constant\Enum\User\Status;
use App\Type\Enum\UserStatusEnumType;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class User
{
    #[ORM\Column(type: UserStatusEnumType::NAME)]
    private ?Status $status = Status::ACTIVE;
}
```

### Générer la migration

```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

**SQL généré**:
```sql
ALTER TABLE user ADD status ENUM('active', 'inactive', 'banned') DEFAULT 'active' NOT NULL;
```

---

## Publication et distribution

### 1. Publier sur GitHub

```bash
cd /Users/macbook/Desktop/Dev/doctrine-enum-bundle

# Créer le repository sur GitHub: https://github.com/art-fatal/doctrine-enum-bundle

# Ajouter remote
git remote add origin https://github.com/art-fatal/doctrine-enum-bundle.git
git branch -M main
git push -u origin main
```

### 2. Créer un tag de version

```bash
git tag -a v1.0.0 -m "First stable release"
git push origin v1.0.0
```

### 3. Publier sur Packagist

1. Aller sur https://packagist.org
2. Se connecter avec GitHub
3. Cliquer sur "Submit"
4. Entrer l'URL du repository: `https://github.com/art-fatal/doctrine-enum-bundle`
5. Packagist détectera automatiquement le `composer.json` et créera le package

### 4. Auto-update Packagist (optionnel)

Configurer le webhook GitHub → Packagist pour auto-update à chaque push:
- GitHub Settings → Webhooks → Add webhook
- URL: `https://packagist.org/api/github?username=art-fatal`
- Content type: `application/json`

### 5. Utilisation dans d'autres projets

**Avant publication (local)**:
```json
// composer.json
{
    "repositories": [
        {
            "type": "path",
            "url": "../doctrine-enum-bundle"
        }
    ],
    "require": {
        "art-fatal/doctrine-enum-bundle": "dev-main"
    }
}
```

**Après publication sur Packagist**:
```bash
composer require art-fatal/doctrine-enum-bundle
```

---

## Développement futur

### Améliorations possibles

#### 1. Support PostgreSQL ENUM
Actuellement, le bundle génère des ENUM MySQL. Ajouter le support PostgreSQL:

```php
public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
{
    $enum = $this::getEnumsClass();
    $values = array_map(fn($e) => "'".$e->value."'", $enum::cases());

    if ($platform instanceof PostgreSQLPlatform) {
        // CREATE TYPE syntax pour PostgreSQL
        return "VARCHAR(255)"; // Temporaire, nécessite CREATE TYPE en migration
    }

    return "ENUM(".implode(", ", $values).")";
}
```

#### 2. Tests unitaires et fonctionnels

Ajouter PHPUnit:
```bash
composer require --dev phpunit/phpunit
```

Structure:
```
tests/
├── Unit/
│   └── Type/
│       └── EnumTypeTest.php
└── Functional/
    └── BundleIntegrationTest.php
```

#### 3. GitHub Actions CI/CD

Créer `.github/workflows/tests.yml`:
```yaml
name: Tests
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    strategy:
      matrix:
        php: ['8.1', '8.2', '8.3']
        symfony: ['6.4', '7.0']
    steps:
      - uses: actions/checkout@v3
      - uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
      - run: composer install
      - run: vendor/bin/phpunit
```

#### 4. Documentation avancée

- Ajouter des exemples plus complexes
- Créer un site de documentation (GitHub Pages)
- Ajouter des diagrammes d'architecture

#### 5. Validation des valeurs enum

Ajouter une option pour valider automatiquement les valeurs:

```php
public function convertToDatabaseValue($value, AbstractPlatform $platform): mixed
{
    if ($value instanceof BackedEnum) {
        // Valider que la valeur fait partie des cases définies
        $validCases = $this::getEnumsClass()::cases();
        if (!in_array($value, $validCases, true)) {
            throw new InvalidArgumentException("Invalid enum value");
        }
        return $value->value;
    }
    return null;
}
```

#### 6. Cache des métadonnées enum

Pour optimiser les performances en production:

```php
private static array $enumMetadataCache = [];

public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
{
    $enumClass = $this::getEnumsClass();

    if (!isset(self::$enumMetadataCache[$enumClass])) {
        $values = array_map(fn($e) => "'".$e->value."'", $enumClass::cases());
        self::$enumMetadataCache[$enumClass] = "ENUM(".implode(", ", $values).")";
    }

    return self::$enumMetadataCache[$enumClass];
}
```

---

## Projet source

### Informations sur le projet Howdens

**Localisation**: `/Users/macbook/Desktop/Dev/Howdens/cp-app/backend`

**Branche**: `preprod`

**Stack technique**:
- PHP 8.1+
- Symfony 6.x/7.x
- Doctrine ORM/DBAL
- MySQL avec colonnes ENUM

### Enums utilisés dans le projet

Le projet source contient de nombreux enums:

**Project-related**:
- `DocumentStatus` (quotation, invoiced, order)
- `DocumentState` (published, deleted)
- `DocumentType` (quotation, invoice, ...)
- `EvolutionRequestState`
- `ProjectState`
- `RequestType`
- `TransactionState`

**Order-related**:
- `State` (Order state)

**Company-related**:
- `State` (Company state)

**Customer-related**:
- `State` (Customer state)

**Request-related**:
- `IbanBicModificationRequest\State`
- `SepaActivationRequest\State`
- `CompanyInfoUpdateRequest\State`

**Transaction-related**:
- `FreeTransaction\State`

**Other**:
- `DayOfWeek`
- `OperationType`
- `Roles`

### Migration du projet source vers le bundle

Pour utiliser le bundle dans le projet source:

1. **Installer le bundle** (via Composer path ou après publication)
2. **Supprimer** `src/Type/EnumType.php`
3. **Modifier** `src/Kernel.php`: retirer `implements CompilerPassInterface` et la méthode `process()`
4. **Modifier** `config/services.yaml`: retirer le tag `app.doctrine_enum_type`
5. **Ajouter** dans `config/bundles.php`:
   ```php
   ArtFatal\DoctrineEnumBundle\DoctrineEnumBundle::class => ['all' => true],
   ```
6. **Mettre à jour** tous les imports dans `src/Type/Enum/*EnumType.php`:
   ```php
   // Ancien
   use App\Type\EnumType;

   // Nouveau
   use ArtFatal\DoctrineEnumBundle\Type\EnumType;
   ```
7. **Tester** que tout fonctionne: `php bin/console debug:container --show-private | grep enum`
8. **Régénérer le cache**: `php bin/console cache:clear`

---

## Commandes utiles

### Vérifier que les types sont enregistrés

```bash
php bin/console debug:container --show-private | grep enum
```

### Voir les types Doctrine disponibles

```bash
php bin/console dbal:run-sql "SHOW COLUMNS FROM project_document"
```

### Générer une migration après modifications

```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate --dry-run
```

### Valider le composer.json

```bash
cd /Users/macbook/Desktop/Dev/doctrine-enum-bundle
composer validate
```

---

## Contact et support

**Package**: `art-fatal/doctrine-enum-bundle`
**Author**: Art Fatal
**License**: MIT
**Repository**: https://github.com/art-fatal/doctrine-enum-bundle (à créer)

Pour toute question ou contribution, créer une issue sur GitHub.

---

**Date de création**: 2025-10-31
**Dernière mise à jour**: 2025-10-31
**Version initiale**: 1.0.0