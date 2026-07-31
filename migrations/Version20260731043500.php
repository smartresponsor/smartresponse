<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260731043500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the Cataloging record_index read-model table retired from component-local migrations.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS record_index (
    id VARCHAR(64) NOT NULL,
    brand VARCHAR(80) DEFAULT NULL,
    price NUMERIC(12, 2) DEFAULT NULL,
    stock INT DEFAULT NULL,
    tag_set JSON DEFAULT NULL,
    PRIMARY KEY(id)
)
SQL);
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_record_index_brand ON record_index (brand)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_record_index_price ON record_index (price)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_record_index_stock ON record_index (stock)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS record_index');
    }
}
