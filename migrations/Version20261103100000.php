<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261103100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Replace quote_description (text) with quote_pdf_path (varchar) on order table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `order` ADD quote_pdf_path VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE `order` DROP COLUMN quote_description');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `order` ADD quote_description LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE `order` DROP COLUMN quote_pdf_path');
    }
}
