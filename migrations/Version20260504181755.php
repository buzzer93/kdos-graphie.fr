<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260504181755 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `order` ADD customer_last_name VARCHAR(255) NOT NULL DEFAULT \'\', ADD customer_phone VARCHAR(50) NOT NULL DEFAULT \'\', ADD shipping_address LONGTEXT NOT NULL, ADD additional_info LONGTEXT DEFAULT NULL, ADD decision_reason LONGTEXT DEFAULT NULL, ADD tracking_number VARCHAR(100) DEFAULT NULL, ADD shipping_carrier VARCHAR(100) DEFAULT NULL, CHANGE customer_name customer_first_name VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE order_item ADD customization_text LONGTEXT DEFAULT NULL, ADD customization_file_path VARCHAR(255) DEFAULT NULL');
        $this->addSql("UPDATE `order` SET status = 'a_confirmer' WHERE status = 'pending'");
        $this->addSql("UPDATE `order` SET status = 'en_attente_paiement' WHERE status = 'awaiting_payment'");
        $this->addSql("UPDATE `order` SET status = 'a_faire' WHERE status = 'paid'");
        $this->addSql("UPDATE `order` SET status = 'termine' WHERE status = 'done'");
        $this->addSql("UPDATE `order` SET status = 'refuse' WHERE status = 'rejected'");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("UPDATE `order` SET status = 'pending' WHERE status = 'a_confirmer'");
        $this->addSql("UPDATE `order` SET status = 'awaiting_payment' WHERE status = 'en_attente_paiement'");
        $this->addSql("UPDATE `order` SET status = 'paid' WHERE status = 'a_faire'");
        $this->addSql("UPDATE `order` SET status = 'done' WHERE status = 'termine'");
        $this->addSql("UPDATE `order` SET status = 'rejected' WHERE status IN ('refuse', 'annule')");
        $this->addSql('ALTER TABLE `order` ADD customer_name VARCHAR(255) NOT NULL, DROP customer_first_name, DROP customer_last_name, DROP customer_phone, DROP shipping_address, DROP additional_info, DROP decision_reason, DROP tracking_number, DROP shipping_carrier');
        $this->addSql('ALTER TABLE order_item DROP customization_text, DROP customization_file_path');
    }
}
