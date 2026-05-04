<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260504181951 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create order_archive table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE order_archive (id INT AUTO_INCREMENT NOT NULL, original_order_id INT NOT NULL, reference VARCHAR(50) NOT NULL, status VARCHAR(30) NOT NULL, customer_email VARCHAR(255) NOT NULL, payload JSON NOT NULL, archived_by VARCHAR(255) DEFAULT NULL, reason LONGTEXT DEFAULT NULL, archived_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE order_archive');
    }
}
