<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260624130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add quoted_price, quote_description on order; add special_request on order_item (devis flow)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `order` ADD quoted_price INT DEFAULT NULL, ADD quote_description LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE order_item ADD special_request LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `order` DROP quoted_price, DROP quote_description');
        $this->addSql('ALTER TABLE order_item DROP special_request');
    }
}
