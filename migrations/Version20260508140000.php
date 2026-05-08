<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260508140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add payment_link column to order table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `order` ADD payment_link VARCHAR(500) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `order` DROP COLUMN payment_link');
    }
}
