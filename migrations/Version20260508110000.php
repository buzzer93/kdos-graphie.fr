<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260508110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add refund tracking fields to order table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `order` ADD refund_note LONGTEXT DEFAULT NULL, ADD refunded_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `order` DROP COLUMN refund_note, DROP COLUMN refunded_at');
    }
}
