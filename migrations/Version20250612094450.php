<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\FundReturn\FundReturn;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250612094450 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'FundReturn: add state column';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE fund_return ADD state VARCHAR(10) NOT NULL');
        $this->addSql('UPDATE fund_return SET state=:state', ['state' => FundReturn::STATE_OPEN]);
        $this->addSql('UPDATE fund_return SET state=:state WHERE signoff_date IS NOT NULL', ['state' => FundReturn::STATE_SUBMITTED]);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE fund_return DROP state');
    }
}
