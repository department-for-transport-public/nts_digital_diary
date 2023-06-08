<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230426115557 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Alter parking/ticket cost fields';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE stage CHANGE parking_cost parking_cost_cost INT DEFAULT NULL, CHANGE ticket_cost ticket_cost_cost INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE stage CHANGE parking_cost_cost parking_cost INT DEFAULT NULL, CHANGE ticket_cost_cost ticket_cost INT DEFAULT NULL');
    }
}
