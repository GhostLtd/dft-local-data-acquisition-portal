<?php

namespace App\Command;

use App\DataFixtures\Definition\ProjectFund\CrstsProjectFundDefinition;
use App\DataFixtures\Definition\AuthorityDefinition;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\PropertyInfo\Type;

#[AsCommand(
    name: 'ldap:dev:fixture-definition-to-json',
    description: 'Exports a json representation of the current fixture definition',
)]
class FixtureDefinitionToJsonCommand extends Command
{
    protected array $queue = [];
    protected array $classesSeen = [];

    protected array $blacklist = [];

    public function __construct(
        #[Autowire('@property_info')]
        protected PropertyInfoExtractor $propertyInfoExtractor,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->forceClassesToLoad();
        $this->queue = [AuthorityDefinition::class];
        $this->classesSeen = $this->queue;

        for(;;) {
            $definitionClass = array_shift($this->queue);

            if (!$definitionClass) {
                break;
            }

            $io->writeln("# ".$this->getLastPartOfClassName($definitionClass));
            $io->writeln(json_encode($this->getDefinitionFor($definitionClass), JSON_PRETTY_PRINT));
            $io->writeln('');
        }

        return Command::SUCCESS;
    }

    /**
     * @param class-string $definitionClass
     */
    protected function getDefinitionFor(string $definitionClass): array|object
    {
        $jsonTypes = [];

        foreach($this->propertyInfoExtractor->getProperties($definitionClass) as $property) {
            if ($this->blacklist[$definitionClass][$property] ?? null) {
                continue;
            }

            $types = $this->propertyInfoExtractor->getTypes($definitionClass, $property);

            /** @var Type $type */
            foreach($types as $type) {
                $builtInType = $type->getBuiltinType();

                if ($builtInType) {
                    $nullablePart = $type->isNullable() ? '?' : '';
                    if ($builtInType === 'array') {
                        if (empty($type->getCollectionKeyTypes())) {
                            $arrayTypes = [];

                            foreach($type->getCollectionValueTypes() as $arrayType) {
                                if ($arrayType->getBuiltinType() === 'object') {
                                    $arrayTypes[$property] = $nullablePart . $this->decodeObjectType($arrayType->getClassName(), $arrayType);
                                } else if ($arrayType->getBuiltinType() === 'array') {
                                    dump("Unsupported 1");
                                    exit;
                                } else {
                                    $arrayTypes[$property] = $nullablePart . $arrayType->getBuiltinType();
                                }
                            }

                            $jsonTypes[$property] = [join('|', $arrayTypes)];
                        } else {
                            $arrayTypes = new \stdClass();
                            $valueTypes = $type->getCollectionValueTypes();
                            if (count($valueTypes) !== 1) {
                                dump("Unsupported 2");
                                exit;
                            }

                            foreach($type->getCollectionKeyTypes() as $k => $keyType) {
                                $valueType = $valueTypes[$k];
                                $arrayNullablePart = $valueType->isNullable() ? '?' : '';

                                if (in_array($keyType->getBuiltinType(), ['array', 'object'])) {
                                    dump("Error: key type must be scalar");
                                    exit;
                                }

                                if ($valueType->getBuiltinType() === 'array') {
                                    dump("Unsupported 3");
                                    exit;
                                }

                                $resolvedKey = "<". $keyType->getBuiltinType() . ">";

                                $resolvedValue = $valueType->getBuiltinType() === 'object' ?
                                    $this->decodeObjectType($valueType->getClassName(), $valueType) :
                                    "<" . $arrayNullablePart. $valueType->getBuiltinType() . ">";

                                $arrayTypes->$resolvedKey = $resolvedValue;
                            }

                            $jsonTypes[$property] = $arrayTypes;
                        }

                    } else if ($builtInType === 'object') {
                        $jsonTypes[$property] = $nullablePart.$this->decodeObjectType($type->getClassName(), $type);
                    } else {
                        $jsonTypes[$property] = "{$nullablePart}<{$builtInType}>";
                    }
                } else {
                    dump("Unsupported 4");
                    exit;
                }
            }
        }

        return $jsonTypes;
    }

    protected function decodeObjectType(string $className, Type $type): string
    {
        if (in_array($className, [\DateTimeInterface::class, \DateTime::class])) {
            return "<datetime-string>";
        }

        $reflClass = new \ReflectionClass($className);

        if ($reflClass->implementsInterface(\UnitEnum::class)) {
            $possibilities = array_map(
                fn($enum) => $enum->value,
                $className::cases()
            );

            $possibilitiesAsString = join('|', $possibilities);
            return count($possibilities) > 1 ? "({$possibilitiesAsString})" : $possibilitiesAsString;
        } else {
            if (str_contains($className, 'Abstract')) {
                $subClasses = $this->getSubclasses($className);
                $types = [];

                foreach($subClasses as $subClass) {
                    $types[] = '<'.$this->getLastPartOfClassName($subClass).'>';
                    $this->enqueue($subClass);
                }
            } else {
                $this->enqueue($className);
                $types = ['<'.$this->getLastPartOfClassName($className).'>'];
            }

            return join('|', $types);
        }
    }

    protected function getSubclasses(string $className): array
    {
        $children = [];

        foreach(get_declared_classes() as $class) {
            if (is_subclass_of($class, $className))  {
                $children[] = $class;
            }
        }

        return $children;
    }

    protected function enqueue(string $className): void
    {
        if (!in_array($className, $this->classesSeen)) {
            $this->queue[] = $className;
            $this->classesSeen[] = $className;
        }
    }

    /**
     * @param class-string $className
     */
    protected function getLastPartOfClassName(string $className): string
    {
        return str_contains($className, '\\') ?
            substr($className, strrpos($className, '\\') + 1) :
            $className;
    }

    protected function forceClassesToLoad(): void
    {
        // get_declared_classes() is used in getSubclasses(), but only returns classes that have been loaded.
        // As such, we'll need to force some important classes to load...
        foreach([
            CrstsProjectFundDefinition::class,
        ] as $class) {
            class_exists($class);
        }
    }
}
