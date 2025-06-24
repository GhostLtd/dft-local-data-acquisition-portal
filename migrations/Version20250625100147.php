<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\Enum\Permission;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250625100147 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'migrate permissions entries (create additional ones as the permissions are no longer hierarchical)';
    }

    public function up(Schema $schema): void
    {
        $this->addPermissionFor(Permission::MARK_AS_READY, Permission::SIGN_OFF);
        $this->addPermissionFor(Permission::EDITOR, Permission::MARK_AS_READY);
        $this->addPermissionFor(Permission::VIEWER, Permission::EDITOR);
    }

    public function down(Schema $schema): void
    {
    }

    protected function addPermissionFor(Permission $permissionToAdd, Permission $permissionFrom): void
    {
        $this->addSql(<<<EOQ
insert into user_permission (id, permission, entity_class, entity_id, user_id)
select uuid_to_bin(uuid()), '{$permissionToAdd->value}', entity_class, entity_id, user_id
from user_permission up
where permission = '{$permissionFrom->value}'
and not exists (
    select id from user_permission where
        user_id=up.user_id
        and entity_id=up.entity_id
        and entity_class=up.entity_class
        and permission='{$permissionToAdd->value}'
)
EOQ
);
    }
}
