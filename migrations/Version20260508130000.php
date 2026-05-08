<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260508130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create site_setting table for global admin parameters';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE site_setting (id INT AUTO_INCREMENT NOT NULL, setting_key VARCHAR(100) NOT NULL, label VARCHAR(255) NOT NULL, setting_value LONGTEXT DEFAULT NULL, updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_SITE_SETTING_KEY (setting_key), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE site_setting');
    }
}
