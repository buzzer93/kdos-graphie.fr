<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260907090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add product_image table for the product gallery/slider';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE product_image (
                id INT AUTO_INCREMENT NOT NULL,
                product_id INT NOT NULL,
                filename VARCHAR(255) NOT NULL,
                sort_order INT NOT NULL DEFAULT 0,
                INDEX IDX_product_image_product (product_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE product_image
                ADD CONSTRAINT FK_product_image_product
                FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product_image DROP FOREIGN KEY FK_product_image_product');
        $this->addSql('DROP TABLE product_image');
    }
}
