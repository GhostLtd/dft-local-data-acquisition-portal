<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\Enum\ExpenseType;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250430091029 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'PropertyChangeLog: Log expenses using the enum value rather than enum name';
    }

    public function up(Schema $schema): void
    {
        $qb = $this->connection->createQueryBuilder();
        $entries = $qb
            ->select('p.*')
            ->from('property_change_log', 'p')
            ->where($qb->expr()->like('p.property_name', $qb->expr()->literal('expenses.%')))
            ->executeQuery();

        foreach($entries->fetchAllAssociative() as $entry) {
            if (!preg_match('/^(expenses\.[a-z0-9\-]+\.[a-z0-9]+\.)([a-z_]+)$/i', $entry['property_name'], $matches)) {
                throw new \RuntimeException("Could not parse property name: {$entry['property_name']}");
            }

            $prefix = $matches[1];
            $name = $matches[2];

            $enum = null;
            foreach(ExpenseType::cases() as $case) {
                if (strtolower($case->name) === $name) {
                    $enum = $case;
                    break;
                }
            }

            if (!$enum) {
                if (ExpenseType::tryFrom($name) !== null) {
                    // Already using the new format (is enum value rather than enum name)
                    continue;
                }

                throw new \RuntimeException("No such enum for name: {$name}");
            }

            $this->addSql("UPDATE property_change_log SET property_name = :name WHERE id = :id", [
                'id' => $entry['id'],
                'name' => $prefix.$enum->value,
            ]);
        }
    }

    public function down(Schema $schema): void
    {

    }
}
