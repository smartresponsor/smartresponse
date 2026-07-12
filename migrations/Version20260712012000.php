<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260712012000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Materialize the Carting aggregate, lines, adjustments, and checkout handoff schema.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE IF NOT EXISTS cart_cart (id SERIAL PRIMARY KEY, cart_token VARCHAR(96) NOT NULL, owner_reference VARCHAR(191) DEFAULT NULL, currency_code VARCHAR(3) NOT NULL, status VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, version INT NOT NULL DEFAULT 1)");
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_cart_cart_cart_token ON cart_cart (cart_token)');
        $this->addSql('CREATE INDEX IF NOT EXISTS cart_cart_token_idx ON cart_cart (cart_token)');
        $this->addSql('CREATE INDEX IF NOT EXISTS cart_cart_owner_reference_idx ON cart_cart (owner_reference)');

        $this->addSql("CREATE TABLE IF NOT EXISTS cart_item (id SERIAL PRIMARY KEY, cart_id INT NOT NULL, offer_reference VARCHAR(191) NOT NULL, title_snapshot VARCHAR(255) NOT NULL, unit_price_minor INT NOT NULL, currency_code VARCHAR(3) NOT NULL, quantity INT NOT NULL, metadata JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, CONSTRAINT fk_cart_item_cart FOREIGN KEY (cart_id) REFERENCES cart_cart (id) ON DELETE CASCADE)");
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_cart_item_cart_id ON cart_item (cart_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS cart_item_offer_reference_idx ON cart_item (offer_reference)');

        $this->addSql("CREATE TABLE IF NOT EXISTS cart_adjustment (id SERIAL PRIMARY KEY, cart_id INT NOT NULL, type VARCHAR(255) NOT NULL, label VARCHAR(191) NOT NULL, amount_minor INT NOT NULL, CONSTRAINT fk_cart_adjustment_cart FOREIGN KEY (cart_id) REFERENCES cart_cart (id) ON DELETE CASCADE)");
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_cart_adjustment_cart_id ON cart_adjustment (cart_id)');

        $this->addSql("CREATE TABLE IF NOT EXISTS cart_checkout_handoff (id SERIAL PRIMARY KEY, cart_id INT NOT NULL, handoff_reference VARCHAR(96) NOT NULL, payload JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, CONSTRAINT fk_cart_checkout_handoff_cart FOREIGN KEY (cart_id) REFERENCES cart_cart (id) ON DELETE CASCADE)");
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS cart_checkout_handoff_cart_unique ON cart_checkout_handoff (cart_id)');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_cart_checkout_handoff_reference ON cart_checkout_handoff (handoff_reference)');
        $this->addSql('CREATE INDEX IF NOT EXISTS cart_checkout_handoff_reference_idx ON cart_checkout_handoff (handoff_reference)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS cart_checkout_handoff');
        $this->addSql('DROP TABLE IF EXISTS cart_adjustment');
        $this->addSql('DROP TABLE IF EXISTS cart_item');
        $this->addSql('DROP TABLE IF EXISTS cart_cart');
    }
}
