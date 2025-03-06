<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250101101010 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial migration';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE authority (name VARCHAR(255) NOT NULL, id BINARY(16) NOT NULL, admin_id BINARY(16) NOT NULL, INDEX IDX_4AF96AFC642B8210 (admin_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8');
        $this->addSql('CREATE TABLE bsip_scheme_fund (id BINARY(16) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8');
        $this->addSql('CREATE TABLE crsts_fund_return (progress_summary LONGTEXT DEFAULT NULL, delivery_confidence LONGTEXT DEFAULT NULL, overall_confidence VARCHAR(255) DEFAULT NULL, local_contribution LONGTEXT DEFAULT NULL, resource_funding LONGTEXT DEFAULT NULL, comments LONGTEXT DEFAULT NULL, expense_division_comments JSON DEFAULT NULL, id BINARY(16) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8');
        $this->addSql('CREATE TABLE crsts_fund_return_expense_entry (crsts_fund_return_id BINARY(16) NOT NULL, expense_entry_id BINARY(16) NOT NULL, INDEX IDX_7DAFAD60E5278F55 (crsts_fund_return_id), INDEX IDX_7DAFAD607C578BBB (expense_entry_id), PRIMARY KEY(crsts_fund_return_id, expense_entry_id)) DEFAULT CHARACTER SET utf8');
        $this->addSql('CREATE TABLE crsts_scheme_fund (retained TINYINT(1) NOT NULL, funded_mostly_as VARCHAR(255) DEFAULT NULL, previously_tcf TINYINT(1) DEFAULT NULL, benefit_cost_ratio_value NUMERIC(10, 2) DEFAULT NULL, benefit_cost_ratio_type VARCHAR(5) DEFAULT NULL, id BINARY(16) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8');
        $this->addSql('CREATE TABLE crsts_scheme_return (total_cost NUMERIC(12, 0) DEFAULT NULL, agreed_funding NUMERIC(12, 0) DEFAULT NULL, on_track_rating VARCHAR(255) DEFAULT NULL, business_case VARCHAR(255) DEFAULT NULL, expected_business_case_approval DATE DEFAULT NULL, progress_update LONGTEXT DEFAULT NULL, expense_division_comments JSON DEFAULT NULL, id BINARY(16) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8');
        $this->addSql('CREATE TABLE crsts_scheme_return_milestone (crsts_scheme_return_id BINARY(16) NOT NULL, milestone_id BINARY(16) NOT NULL, INDEX IDX_658805D75396B24D (crsts_scheme_return_id), INDEX IDX_658805D74B3E2EDA (milestone_id), PRIMARY KEY(crsts_scheme_return_id, milestone_id)) DEFAULT CHARACTER SET utf8');
        $this->addSql('CREATE TABLE crsts_scheme_return_expense_entry (crsts_scheme_return_id BINARY(16) NOT NULL, expense_entry_id BINARY(16) NOT NULL, INDEX IDX_BB118A985396B24D (crsts_scheme_return_id), INDEX IDX_BB118A987C578BBB (expense_entry_id), PRIMARY KEY(crsts_scheme_return_id, expense_entry_id)) DEFAULT CHARACTER SET utf8');
        $this->addSql('CREATE TABLE expense_entry (type VARCHAR(255) NOT NULL, division VARCHAR(16) NOT NULL, col VARCHAR(16) NOT NULL, value NUMERIC(12, 0) DEFAULT NULL, forecast TINYINT(1) NOT NULL, id BINARY(16) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8');
        $this->addSql('CREATE TABLE fund_award (type VARCHAR(255) NOT NULL, id BINARY(16) NOT NULL, authority_id BINARY(16) NOT NULL, INDEX IDX_8E7E237C81EC865B (authority_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8');
        $this->addSql('CREATE TABLE fund_return (year INT NOT NULL, quarter SMALLINT DEFAULT NULL, signoff_name VARCHAR(255) DEFAULT NULL, signoff_email VARCHAR(255) DEFAULT NULL, signoff_date DATETIME DEFAULT NULL, id BINARY(16) NOT NULL, fund_award_id BINARY(16) NOT NULL, signoff_user_id BINARY(16) DEFAULT NULL, type VARCHAR(255) NOT NULL, INDEX IDX_C04750CE32BE7FD0 (fund_award_id), INDEX IDX_C04750CED632A32D (signoff_user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8');
        $this->addSql('CREATE TABLE maintenance_lock (whitelisted_ips JSON DEFAULT NULL, id BINARY(16) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8');
        $this->addSql('CREATE TABLE maintenance_warning (start_datetime DATETIME NOT NULL, end_time TIME NOT NULL, id BINARY(16) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8');
        $this->addSql('CREATE TABLE milestone (type VARCHAR(255) NOT NULL, date DATE DEFAULT NULL, id BINARY(16) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8');
        $this->addSql('CREATE TABLE scheme (name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, risks LONGTEXT DEFAULT NULL, transport_mode VARCHAR(255) DEFAULT NULL, active_travel_element VARCHAR(255) DEFAULT NULL, includes_clean_air_elements TINYINT(1) DEFAULT NULL, includes_charging_points TINYINT(1) DEFAULT NULL, scheme_identifier VARCHAR(255) DEFAULT NULL, id BINARY(16) NOT NULL, authority_id BINARY(16) NOT NULL, INDEX IDX_BFE3854B81EC865B (authority_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8');
        $this->addSql('CREATE TABLE scheme_fund (id BINARY(16) NOT NULL, scheme_id BINARY(16) NOT NULL, type VARCHAR(255) NOT NULL, INDEX IDX_BCEABDD465797862 (scheme_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8');
        $this->addSql('CREATE TABLE scheme_return (ready_for_signoff TINYINT(1) NOT NULL, id BINARY(16) NOT NULL, scheme_fund_id BINARY(16) NOT NULL, fund_return_id BINARY(16) NOT NULL, type VARCHAR(255) NOT NULL, INDEX IDX_7CB99FA7FD7583F3 (scheme_fund_id), INDEX IDX_7CB99FA7AF73F7DD (fund_return_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8');
        $this->addSql('CREATE TABLE `user` (last_login DATETIME DEFAULT NULL, name VARCHAR(255) NOT NULL, position VARCHAR(255) DEFAULT NULL, phone VARCHAR(255) DEFAULT NULL, email VARCHAR(255) NOT NULL, id BINARY(16) NOT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8');
        $this->addSql('CREATE TABLE user_permission (permission VARCHAR(255) NOT NULL, entity_class VARCHAR(255) NOT NULL, entity_id BINARY(16) NOT NULL, fund_types LONGTEXT DEFAULT NULL, id BINARY(16) NOT NULL, user_id BINARY(16) NOT NULL, UNIQUE INDEX UNIQ_472E544681257D5D (entity_id), INDEX IDX_472E5446A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8');
        $this->addSql('CREATE TABLE cache_items (item_id VARBINARY(255) NOT NULL, item_data MEDIUMBLOB NOT NULL, item_lifetime INT UNSIGNED DEFAULT NULL, item_time INT UNSIGNED NOT NULL, PRIMARY KEY(item_id)) DEFAULT CHARACTER SET utf8');
        $this->addSql('CREATE TABLE sessions (sess_id VARBINARY(128) NOT NULL, sess_data LONGBLOB NOT NULL, sess_lifetime INT UNSIGNED NOT NULL, sess_time INT UNSIGNED NOT NULL, INDEX sess_lifetime_idx (sess_lifetime), PRIMARY KEY(sess_id)) DEFAULT CHARACTER SET utf8 ENGINE = InnoDB');
        $this->addSql('ALTER TABLE authority ADD CONSTRAINT FK_4AF96AFC642B8210 FOREIGN KEY (admin_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE bsip_scheme_fund ADD CONSTRAINT FK_448696CCBF396750 FOREIGN KEY (id) REFERENCES scheme_fund (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE crsts_fund_return ADD CONSTRAINT FK_B7A5D3D3BF396750 FOREIGN KEY (id) REFERENCES fund_return (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE crsts_fund_return_expense_entry ADD CONSTRAINT FK_7DAFAD60E5278F55 FOREIGN KEY (crsts_fund_return_id) REFERENCES crsts_fund_return (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE crsts_fund_return_expense_entry ADD CONSTRAINT FK_7DAFAD607C578BBB FOREIGN KEY (expense_entry_id) REFERENCES expense_entry (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE crsts_scheme_fund ADD CONSTRAINT FK_CB083EC9BF396750 FOREIGN KEY (id) REFERENCES scheme_fund (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE crsts_scheme_return ADD CONSTRAINT FK_F76456C3BF396750 FOREIGN KEY (id) REFERENCES scheme_return (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE crsts_scheme_return_milestone ADD CONSTRAINT FK_658805D75396B24D FOREIGN KEY (crsts_scheme_return_id) REFERENCES crsts_scheme_return (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE crsts_scheme_return_milestone ADD CONSTRAINT FK_658805D74B3E2EDA FOREIGN KEY (milestone_id) REFERENCES milestone (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE crsts_scheme_return_expense_entry ADD CONSTRAINT FK_BB118A985396B24D FOREIGN KEY (crsts_scheme_return_id) REFERENCES crsts_scheme_return (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE crsts_scheme_return_expense_entry ADD CONSTRAINT FK_BB118A987C578BBB FOREIGN KEY (expense_entry_id) REFERENCES expense_entry (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE fund_award ADD CONSTRAINT FK_8E7E237C81EC865B FOREIGN KEY (authority_id) REFERENCES authority (id)');
        $this->addSql('ALTER TABLE fund_return ADD CONSTRAINT FK_C04750CE32BE7FD0 FOREIGN KEY (fund_award_id) REFERENCES fund_award (id)');
        $this->addSql('ALTER TABLE fund_return ADD CONSTRAINT FK_C04750CED632A32D FOREIGN KEY (signoff_user_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE scheme ADD CONSTRAINT FK_BFE3854B81EC865B FOREIGN KEY (authority_id) REFERENCES authority (id)');
        $this->addSql('ALTER TABLE scheme_fund ADD CONSTRAINT FK_BCEABDD465797862 FOREIGN KEY (scheme_id) REFERENCES scheme (id)');
        $this->addSql('ALTER TABLE scheme_return ADD CONSTRAINT FK_7CB99FA7FD7583F3 FOREIGN KEY (scheme_fund_id) REFERENCES scheme_fund (id)');
        $this->addSql('ALTER TABLE scheme_return ADD CONSTRAINT FK_7CB99FA7AF73F7DD FOREIGN KEY (fund_return_id) REFERENCES fund_return (id)');
        $this->addSql('ALTER TABLE user_permission ADD CONSTRAINT FK_472E5446A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE authority DROP FOREIGN KEY FK_4AF96AFC642B8210');
        $this->addSql('ALTER TABLE bsip_scheme_fund DROP FOREIGN KEY FK_448696CCBF396750');
        $this->addSql('ALTER TABLE crsts_fund_return DROP FOREIGN KEY FK_B7A5D3D3BF396750');
        $this->addSql('ALTER TABLE crsts_fund_return_expense_entry DROP FOREIGN KEY FK_7DAFAD60E5278F55');
        $this->addSql('ALTER TABLE crsts_fund_return_expense_entry DROP FOREIGN KEY FK_7DAFAD607C578BBB');
        $this->addSql('ALTER TABLE crsts_scheme_fund DROP FOREIGN KEY FK_CB083EC9BF396750');
        $this->addSql('ALTER TABLE crsts_scheme_return DROP FOREIGN KEY FK_F76456C3BF396750');
        $this->addSql('ALTER TABLE crsts_scheme_return_milestone DROP FOREIGN KEY FK_658805D75396B24D');
        $this->addSql('ALTER TABLE crsts_scheme_return_milestone DROP FOREIGN KEY FK_658805D74B3E2EDA');
        $this->addSql('ALTER TABLE crsts_scheme_return_expense_entry DROP FOREIGN KEY FK_BB118A985396B24D');
        $this->addSql('ALTER TABLE crsts_scheme_return_expense_entry DROP FOREIGN KEY FK_BB118A987C578BBB');
        $this->addSql('ALTER TABLE fund_award DROP FOREIGN KEY FK_8E7E237C81EC865B');
        $this->addSql('ALTER TABLE fund_return DROP FOREIGN KEY FK_C04750CE32BE7FD0');
        $this->addSql('ALTER TABLE fund_return DROP FOREIGN KEY FK_C04750CED632A32D');
        $this->addSql('ALTER TABLE scheme DROP FOREIGN KEY FK_BFE3854B81EC865B');
        $this->addSql('ALTER TABLE scheme_fund DROP FOREIGN KEY FK_BCEABDD465797862');
        $this->addSql('ALTER TABLE scheme_return DROP FOREIGN KEY FK_7CB99FA7FD7583F3');
        $this->addSql('ALTER TABLE scheme_return DROP FOREIGN KEY FK_7CB99FA7AF73F7DD');
        $this->addSql('ALTER TABLE user_permission DROP FOREIGN KEY FK_472E5446A76ED395');
        $this->addSql('DROP TABLE authority');
        $this->addSql('DROP TABLE bsip_scheme_fund');
        $this->addSql('DROP TABLE crsts_fund_return');
        $this->addSql('DROP TABLE crsts_fund_return_expense_entry');
        $this->addSql('DROP TABLE crsts_scheme_fund');
        $this->addSql('DROP TABLE crsts_scheme_return');
        $this->addSql('DROP TABLE crsts_scheme_return_milestone');
        $this->addSql('DROP TABLE crsts_scheme_return_expense_entry');
        $this->addSql('DROP TABLE expense_entry');
        $this->addSql('DROP TABLE fund_award');
        $this->addSql('DROP TABLE fund_return');
        $this->addSql('DROP TABLE maintenance_lock');
        $this->addSql('DROP TABLE maintenance_warning');
        $this->addSql('DROP TABLE milestone');
        $this->addSql('DROP TABLE scheme');
        $this->addSql('DROP TABLE scheme_fund');
        $this->addSql('DROP TABLE scheme_return');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE user_permission');
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('DROP TABLE cache_items');
        $this->addSql('DROP TABLE sessions');
    }
}
