<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250101101010 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE authority (name VARCHAR(255) NOT NULL, id UUID NOT NULL, admin_id UUID NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_4AF96AFC642B8210 ON authority (admin_id)');
        $this->addSql('CREATE TABLE bsip_scheme_fund (id UUID NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE crsts_fund_return (progress_summary TEXT DEFAULT NULL, delivery_confidence TEXT DEFAULT NULL, overall_confidence VARCHAR(255) DEFAULT NULL, local_contribution TEXT DEFAULT NULL, resource_funding TEXT DEFAULT NULL, comments TEXT DEFAULT NULL, expense_division_comments JSON DEFAULT NULL, id UUID NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE crsts_fund_return_expense_entry (crsts_fund_return_id UUID NOT NULL, expense_entry_id UUID NOT NULL, PRIMARY KEY(crsts_fund_return_id, expense_entry_id))');
        $this->addSql('CREATE INDEX IDX_7DAFAD60E5278F55 ON crsts_fund_return_expense_entry (crsts_fund_return_id)');
        $this->addSql('CREATE INDEX IDX_7DAFAD607C578BBB ON crsts_fund_return_expense_entry (expense_entry_id)');
        $this->addSql('CREATE TABLE crsts_scheme_fund (retained BOOLEAN NOT NULL, funded_mostly_as VARCHAR(255) DEFAULT NULL, previously_tcf BOOLEAN DEFAULT NULL, benefit_cost_ratio_value NUMERIC(10, 2) DEFAULT NULL, benefit_cost_ratio_type VARCHAR(5) DEFAULT NULL, id UUID NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE crsts_scheme_return (total_cost NUMERIC(12, 0) DEFAULT NULL, agreed_funding NUMERIC(12, 0) DEFAULT NULL, on_track_rating VARCHAR(255) DEFAULT NULL, business_case VARCHAR(255) DEFAULT NULL, expected_business_case_approval DATE DEFAULT NULL, progress_update TEXT DEFAULT NULL, expense_division_comments JSON DEFAULT NULL, id UUID NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE crsts_scheme_return_milestone (crsts_scheme_return_id UUID NOT NULL, milestone_id UUID NOT NULL, PRIMARY KEY(crsts_scheme_return_id, milestone_id))');
        $this->addSql('CREATE INDEX IDX_658805D75396B24D ON crsts_scheme_return_milestone (crsts_scheme_return_id)');
        $this->addSql('CREATE INDEX IDX_658805D74B3E2EDA ON crsts_scheme_return_milestone (milestone_id)');
        $this->addSql('CREATE TABLE crsts_scheme_return_expense_entry (crsts_scheme_return_id UUID NOT NULL, expense_entry_id UUID NOT NULL, PRIMARY KEY(crsts_scheme_return_id, expense_entry_id))');
        $this->addSql('CREATE INDEX IDX_BB118A985396B24D ON crsts_scheme_return_expense_entry (crsts_scheme_return_id)');
        $this->addSql('CREATE INDEX IDX_BB118A987C578BBB ON crsts_scheme_return_expense_entry (expense_entry_id)');
        $this->addSql('CREATE TABLE expense_entry (type VARCHAR(255) NOT NULL, division VARCHAR(16) NOT NULL, col VARCHAR(16) NOT NULL, value NUMERIC(12, 0) DEFAULT NULL, forecast BOOLEAN NOT NULL, id UUID NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE fund_award (type VARCHAR(255) NOT NULL, id UUID NOT NULL, authority_id UUID NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_8E7E237C81EC865B ON fund_award (authority_id)');
        $this->addSql('CREATE TABLE fund_return (year INT NOT NULL, quarter SMALLINT DEFAULT NULL, signoff_name VARCHAR(255) DEFAULT NULL, signoff_email VARCHAR(255) DEFAULT NULL, signoff_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, id UUID NOT NULL, fund_award_id UUID NOT NULL, signoff_user_id UUID DEFAULT NULL, type VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_C04750CE32BE7FD0 ON fund_return (fund_award_id)');
        $this->addSql('CREATE INDEX IDX_C04750CED632A32D ON fund_return (signoff_user_id)');
        $this->addSql('CREATE TABLE maintenance_lock (whitelisted_ips JSON DEFAULT NULL, id UUID NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE maintenance_warning (start_datetime TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, end_time TIME(0) WITHOUT TIME ZONE NOT NULL, id UUID NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE milestone (type VARCHAR(255) NOT NULL, date DATE DEFAULT NULL, id UUID NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE scheme (name VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, risks TEXT DEFAULT NULL, transport_mode VARCHAR(255) DEFAULT NULL, active_travel_element VARCHAR(255) DEFAULT NULL, includes_clean_air_elements BOOLEAN DEFAULT NULL, includes_charging_points BOOLEAN DEFAULT NULL, scheme_identifier VARCHAR(255) DEFAULT NULL, id UUID NOT NULL, authority_id UUID NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_BFE3854B81EC865B ON scheme (authority_id)');
        $this->addSql('CREATE TABLE scheme_fund (id UUID NOT NULL, scheme_id UUID NOT NULL, type VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_BCEABDD465797862 ON scheme_fund (scheme_id)');
        $this->addSql('CREATE TABLE scheme_return (ready_for_signoff BOOLEAN NOT NULL, id UUID NOT NULL, scheme_fund_id UUID NOT NULL, fund_return_id UUID NOT NULL, type VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_7CB99FA7FD7583F3 ON scheme_return (scheme_fund_id)');
        $this->addSql('CREATE INDEX IDX_7CB99FA7AF73F7DD ON scheme_return (fund_return_id)');
        $this->addSql('CREATE TABLE "user" (last_login TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, name VARCHAR(255) NOT NULL, position VARCHAR(255) DEFAULT NULL, phone VARCHAR(255) DEFAULT NULL, email VARCHAR(255) NOT NULL, id UUID NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON "user" (email)');
        $this->addSql('CREATE TABLE user_permission (permission VARCHAR(255) NOT NULL, entity_class VARCHAR(255) NOT NULL, entity_id UUID NOT NULL, fund_types TEXT DEFAULT NULL, id UUID NOT NULL, user_id UUID NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_472E544681257D5D ON user_permission (entity_id)');
        $this->addSql('CREATE INDEX IDX_472E5446A76ED395 ON user_permission (user_id)');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT GENERATED BY DEFAULT AS IDENTITY NOT NULL, body TEXT NOT NULL, headers TEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, available_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, delivered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)');
        $this->addSql('CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)');
        $this->addSql('CREATE TABLE cache_items (item_id VARCHAR(255) NOT NULL, item_data BYTEA NOT NULL, item_lifetime INT DEFAULT NULL, item_time INT NOT NULL, PRIMARY KEY(item_id))');
        $this->addSql('CREATE TABLE sessions (sess_id VARCHAR(128) NOT NULL, sess_data BYTEA NOT NULL, sess_lifetime INT NOT NULL, sess_time INT NOT NULL, PRIMARY KEY(sess_id))');
        $this->addSql('CREATE INDEX sess_lifetime_idx ON sessions (sess_lifetime)');
        $this->addSql('ALTER TABLE authority ADD CONSTRAINT FK_4AF96AFC642B8210 FOREIGN KEY (admin_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE bsip_scheme_fund ADD CONSTRAINT FK_448696CCBF396750 FOREIGN KEY (id) REFERENCES scheme_fund (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE crsts_fund_return ADD CONSTRAINT FK_B7A5D3D3BF396750 FOREIGN KEY (id) REFERENCES fund_return (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE crsts_fund_return_expense_entry ADD CONSTRAINT FK_7DAFAD60E5278F55 FOREIGN KEY (crsts_fund_return_id) REFERENCES crsts_fund_return (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE crsts_fund_return_expense_entry ADD CONSTRAINT FK_7DAFAD607C578BBB FOREIGN KEY (expense_entry_id) REFERENCES expense_entry (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE crsts_scheme_fund ADD CONSTRAINT FK_CB083EC9BF396750 FOREIGN KEY (id) REFERENCES scheme_fund (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE crsts_scheme_return ADD CONSTRAINT FK_F76456C3BF396750 FOREIGN KEY (id) REFERENCES scheme_return (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE crsts_scheme_return_milestone ADD CONSTRAINT FK_658805D75396B24D FOREIGN KEY (crsts_scheme_return_id) REFERENCES crsts_scheme_return (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE crsts_scheme_return_milestone ADD CONSTRAINT FK_658805D74B3E2EDA FOREIGN KEY (milestone_id) REFERENCES milestone (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE crsts_scheme_return_expense_entry ADD CONSTRAINT FK_BB118A985396B24D FOREIGN KEY (crsts_scheme_return_id) REFERENCES crsts_scheme_return (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE crsts_scheme_return_expense_entry ADD CONSTRAINT FK_BB118A987C578BBB FOREIGN KEY (expense_entry_id) REFERENCES expense_entry (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE fund_award ADD CONSTRAINT FK_8E7E237C81EC865B FOREIGN KEY (authority_id) REFERENCES authority (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE fund_return ADD CONSTRAINT FK_C04750CE32BE7FD0 FOREIGN KEY (fund_award_id) REFERENCES fund_award (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE fund_return ADD CONSTRAINT FK_C04750CED632A32D FOREIGN KEY (signoff_user_id) REFERENCES "user" (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE scheme ADD CONSTRAINT FK_BFE3854B81EC865B FOREIGN KEY (authority_id) REFERENCES authority (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE scheme_fund ADD CONSTRAINT FK_BCEABDD465797862 FOREIGN KEY (scheme_id) REFERENCES scheme (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE scheme_return ADD CONSTRAINT FK_7CB99FA7FD7583F3 FOREIGN KEY (scheme_fund_id) REFERENCES scheme_fund (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE scheme_return ADD CONSTRAINT FK_7CB99FA7AF73F7DD FOREIGN KEY (fund_return_id) REFERENCES fund_return (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_permission ADD CONSTRAINT FK_472E5446A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE authority DROP CONSTRAINT FK_4AF96AFC642B8210');
        $this->addSql('ALTER TABLE bsip_scheme_fund DROP CONSTRAINT FK_448696CCBF396750');
        $this->addSql('ALTER TABLE crsts_fund_return DROP CONSTRAINT FK_B7A5D3D3BF396750');
        $this->addSql('ALTER TABLE crsts_fund_return_expense_entry DROP CONSTRAINT FK_7DAFAD60E5278F55');
        $this->addSql('ALTER TABLE crsts_fund_return_expense_entry DROP CONSTRAINT FK_7DAFAD607C578BBB');
        $this->addSql('ALTER TABLE crsts_scheme_fund DROP CONSTRAINT FK_CB083EC9BF396750');
        $this->addSql('ALTER TABLE crsts_scheme_return DROP CONSTRAINT FK_F76456C3BF396750');
        $this->addSql('ALTER TABLE crsts_scheme_return_milestone DROP CONSTRAINT FK_658805D75396B24D');
        $this->addSql('ALTER TABLE crsts_scheme_return_milestone DROP CONSTRAINT FK_658805D74B3E2EDA');
        $this->addSql('ALTER TABLE crsts_scheme_return_expense_entry DROP CONSTRAINT FK_BB118A985396B24D');
        $this->addSql('ALTER TABLE crsts_scheme_return_expense_entry DROP CONSTRAINT FK_BB118A987C578BBB');
        $this->addSql('ALTER TABLE fund_award DROP CONSTRAINT FK_8E7E237C81EC865B');
        $this->addSql('ALTER TABLE fund_return DROP CONSTRAINT FK_C04750CE32BE7FD0');
        $this->addSql('ALTER TABLE fund_return DROP CONSTRAINT FK_C04750CED632A32D');
        $this->addSql('ALTER TABLE scheme DROP CONSTRAINT FK_BFE3854B81EC865B');
        $this->addSql('ALTER TABLE scheme_fund DROP CONSTRAINT FK_BCEABDD465797862');
        $this->addSql('ALTER TABLE scheme_return DROP CONSTRAINT FK_7CB99FA7FD7583F3');
        $this->addSql('ALTER TABLE scheme_return DROP CONSTRAINT FK_7CB99FA7AF73F7DD');
        $this->addSql('ALTER TABLE user_permission DROP CONSTRAINT FK_472E5446A76ED395');
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
        $this->addSql('DROP TABLE "user"');
        $this->addSql('DROP TABLE user_permission');
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('DROP TABLE cache_items');
        $this->addSql('DROP TABLE sessions');
    }
}
