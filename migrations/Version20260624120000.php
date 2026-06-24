<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260624120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add product_option_group, product_option_value tables and options_summary column on order_item';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE product_option_group (
                id INT AUTO_INCREMENT NOT NULL,
                product_id INT NOT NULL,
                name VARCHAR(100) NOT NULL,
                sort_order INT NOT NULL DEFAULT 0,
                INDEX IDX_product_option_group_product (product_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE product_option_value (
                id INT AUTO_INCREMENT NOT NULL,
                group_id INT NOT NULL,
                label VARCHAR(100) NOT NULL,
                price_adjustment INT NOT NULL DEFAULT 0,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                sort_order INT NOT NULL DEFAULT 0,
                INDEX IDX_product_option_value_group (group_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE product_option_group
                ADD CONSTRAINT FK_product_option_group_product
                FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE product_option_value
                ADD CONSTRAINT FK_product_option_value_group
                FOREIGN KEY (group_id) REFERENCES product_option_group (id) ON DELETE CASCADE
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE order_item
                ADD options_summary VARCHAR(255) DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE order_item DROP COLUMN options_summary');
        $this->addSql('ALTER TABLE product_option_value DROP FOREIGN KEY FK_product_option_value_group');
        $this->addSql('ALTER TABLE product_option_group DROP FOREIGN KEY FK_product_option_group_product');
        $this->addSql('DROP TABLE product_option_value');
        $this->addSql('DROP TABLE product_option_group');
    }
}
