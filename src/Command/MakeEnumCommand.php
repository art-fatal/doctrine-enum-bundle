<?php

namespace ArtFatal\DoctrineEnumBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'make:enum',
    description: 'Creates a new PHP enum and its corresponding Doctrine type'
)]
class MakeEnumCommand extends Command
{
    private string $projectDir;

    public function __construct(string $projectDir)
    {
        parent::__construct();
        $this->projectDir = $projectDir;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $helper = $this->getHelper('question');

        $io->title('Enum Generator');
        $io->text('This command will help you create a new enum and its Doctrine type.');

        // Ask for enum name
        $enumNameQuestion = new Question('Enum name (e.g., UserStatus, OrderState): ');
        $enumNameQuestion->setValidator(function ($answer) {
            if (!$answer || !preg_match('/^[A-Z][a-zA-Z0-9]*$/', $answer)) {
                throw new \RuntimeException('Enum name must start with uppercase and contain only letters and numbers.');
            }
            return $answer;
        });
        $enumName = $helper->ask($input, $output, $enumNameQuestion);

        // Ask for enum cases
        $io->section('Enum Cases');
        $io->text('Enter the enum cases (values). Press enter on empty line to finish.');

        $cases = [];
        $caseIndex = 1;
        while (true) {
            $caseQuestion = new Question(sprintf('Case #%d (name=value, e.g., ACTIVE=active): ', $caseIndex), '');
            $case = $helper->ask($input, $output, $caseQuestion);

            if (empty($case)) {
                break;
            }

            // Parse case (format: NAME=value or just NAME)
            if (str_contains($case, '=')) {
                [$caseName, $caseValue] = explode('=', $case, 2);
                $caseName = trim($caseName);
                $caseValue = trim($caseValue);
            } else {
                $caseName = trim($case);
                $caseValue = strtolower($caseName);
            }

            // Validate case name
            if (!preg_match('/^[A-Z_][A-Z0-9_]*$/', $caseName)) {
                $io->error('Case name must be uppercase with underscores only (e.g., ACTIVE, IN_PROGRESS)');
                continue;
            }

            $cases[$caseName] = $caseValue;
            $caseIndex++;
        }

        if (empty($cases)) {
            $io->error('At least one case is required.');
            return Command::FAILURE;
        }

        // Ask for namespace customization
        $enumNamespaceQuestion = new Question('Enum namespace (default: App\Enum): ', 'App\Enum');
        $enumNamespace = $helper->ask($input, $output, $enumNamespaceQuestion);

        $typeNamespaceQuestion = new Question('Type namespace (default: App\Type): ', 'App\Type');
        $typeNamespace = $helper->ask($input, $output, $typeNamespaceQuestion);

        // Generate paths
        $enumPath = $this->namespaceToPath($enumNamespace) . '/' . $enumName . '.php';
        $typePath = $this->namespaceToPath($typeNamespace) . '/' . $enumName . 'EnumType.php';

        // Show summary
        $io->section('Summary');
        $io->listing([
            sprintf('Enum: %s\%s', $enumNamespace, $enumName),
            sprintf('Type: %s\%sEnumType', $typeNamespace, $enumName),
            sprintf('Cases: %s', implode(', ', array_keys($cases))),
        ]);

        $confirmQuestion = new ConfirmationQuestion('Create these files? (yes/no) ', false);
        if (!$helper->ask($input, $output, $confirmQuestion)) {
            $io->warning('Cancelled.');
            return Command::SUCCESS;
        }

        // Generate files
        try {
            $this->generateEnumFile($enumPath, $enumNamespace, $enumName, $cases);
            $io->success(sprintf('Created: %s', $enumPath));

            $this->generateTypeFile($typePath, $typeNamespace, $enumNamespace, $enumName);
            $io->success(sprintf('Created: %s', $typePath));

            // Show usage instructions
            $io->section('Next Steps');
            $typeName = $this->convertToSnakeCase($enumName);

            $io->text([
                'Use your new enum in an entity:',
                '',
                sprintf('use %s\%s;', $enumNamespace, $enumName),
                sprintf('use %s\%sEnumType;', $typeNamespace, $enumName),
                'use Doctrine\ORM\Mapping as ORM;',
                '',
                '#[ORM\Entity]',
                'class YourEntity',
                '{',
                sprintf('    #[ORM\Column(type: %sEnumType::NAME)]', $enumName),
                sprintf('    // or: #[ORM\Column(type: \'%s\')]', $typeName),
                sprintf('    private ?%s $status = null;', $enumName),
                '}',
                '',
                'Then run:',
                '  php bin/console doctrine:migrations:diff',
                '  php bin/console doctrine:migrations:migrate',
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Error creating files: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function namespaceToPath(string $namespace): string
    {
        // Convert namespace to path (App\Enum -> src/Enum)
        $path = str_replace('App\\', 'src/', $namespace);
        $path = str_replace('\\', '/', $path);
        return $this->projectDir . '/' . $path;
    }

    private function convertToSnakeCase(string $input): string
    {
        $result = preg_replace('/(?<!^)[A-Z]/', '_$0', $input);
        return strtolower($result);
    }

    private function generateEnumFile(string $path, string $namespace, string $enumName, array $cases): void
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $casesCode = [];
        $allCasesArray = [];

        foreach ($cases as $caseName => $caseValue) {
            $casesCode[] = sprintf("    case %s = '%s';", $caseName, $caseValue);
            $allCasesArray[] = sprintf("        self::%s,", $caseName);
        }

        $content = sprintf(
            "<?php\n\nnamespace %s;\n\nenum %s: string\n{\n%s\n\n    public const ALL = [\n%s\n    ];\n}\n",
            $namespace,
            $enumName,
            implode("\n", $casesCode),
            implode("\n", $allCasesArray)
        );

        file_put_contents($path, $content);
    }

    private function generateTypeFile(string $path, string $typeNamespace, string $enumNamespace, string $enumName): void
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $typeName = $this->convertToSnakeCase($enumName);

        $content = sprintf(
            "<?php\n\nnamespace %s;\n\nuse %s\%s;\nuse ArtFatal\DoctrineEnumBundle\Type\EnumType;\n\nclass %sEnumType extends EnumType\n{\n    public const NAME = '%s';\n\n    public static function getEnumsClass(): string\n    {\n        return %s::class;\n    }\n}\n",
            $typeNamespace,
            $enumNamespace,
            $enumName,
            $enumName,
            $typeName,
            $enumName
        );

        file_put_contents($path, $content);
    }
}