<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260508150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add sort_order column to category table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE category ADD sort_order INT NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE category DROP COLUMN sort_order');
    }
}
