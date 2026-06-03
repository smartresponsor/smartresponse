<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260519012200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Replace legacy placeholder Payment gateway and method slugs with real UUID values.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE payment_gateway SET slug = '2d1f5a7c-7b8a-4f1f-8b77-1d4b0a300001' WHERE code = 'STRIPE' AND slug = '4d1f5a7c-7b8a-4f1f-8b77-1d4b0a300001'");
        $this->addSql("UPDATE payment_gateway SET slug = '2d1f5a7c-7b8a-4f1f-8b77-1d4b0a300002' WHERE code = 'PAYPAL' AND slug = '4d1f5a7c-7b8a-4f1f-8b77-1d4b0a300002'");
        $this->addSql("UPDATE payment_gateway SET slug = '2d1f5a7c-7b8a-4f1f-8b77-1d4b0a300003' WHERE code = 'MANUAL' AND slug = '4d1f5a7c-7b8a-4f1f-8b77-1d4b0a300003'");
        $this->addSql("UPDATE payment_gateway SET slug = '2d1f5a7c-7b8a-4f1f-8b77-1d4b0a300004' WHERE code = 'ADYEN' AND slug = '4d1f5a7c-7b8a-4f1f-8b77-1d4b0a300004'");

        $this->addSql("UPDATE payment_method SET slug = '2d1f5a7c-7b8a-4f1f-8b77-1d4b0a300001' WHERE code = 'stripe' AND slug = '4d1f5a7c-7b8a-4f1f-8b77-1d4b0a300001'");
        $this->addSql("UPDATE payment_method SET slug = '2d1f5a7c-7b8a-4f1f-8b77-1d4b0a300002' WHERE code = 'paypal' AND slug = '4d1f5a7c-7b8a-4f1f-8b77-1d4b0a300002'");
        $this->addSql("UPDATE payment_method SET slug = '2d1f5a7c-7b8a-4f1f-8b77-1d4b0a300003' WHERE code = 'manual' AND slug = '4d1f5a7c-7b8a-4f1f-8b77-1d4b0a300003'");
        $this->addSql("UPDATE payment_method SET slug = '2d1f5a7c-7b8a-4f1f-8b77-1d4b0a300004' WHERE code = 'adyen' AND slug = '4d1f5a7c-7b8a-4f1f-8b77-1d4b0a300004'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE payment_gateway SET slug = '4d1f5a7c-7b8a-4f1f-8b77-1d4b0a300001' WHERE code = 'STRIPE' AND slug = '2d1f5a7c-7b8a-4f1f-8b77-1d4b0a300001'");
        $this->addSql("UPDATE payment_gateway SET slug = '4d1f5a7c-7b8a-4f1f-8b77-1d4b0a300002' WHERE code = 'PAYPAL' AND slug = '2d1f5a7c-7b8a-4f1f-8b77-1d4b0a300002'");
        $this->addSql("UPDATE payment_gateway SET slug = '4d1f5a7c-7b8a-4f1f-8b77-1d4b0a300003' WHERE code = 'MANUAL' AND slug = '2d1f5a7c-7b8a-4f1f-8b77-1d4b0a300003'");
        $this->addSql("UPDATE payment_gateway SET slug = '4d1f5a7c-7b8a-4f1f-8b77-1d4b0a300004' WHERE code = 'ADYEN' AND slug = '2d1f5a7c-7b8a-4f1f-8b77-1d4b0a300004'");

        $this->addSql("UPDATE payment_method SET slug = '4d1f5a7c-7b8a-4f1f-8b77-1d4b0a300001' WHERE code = 'stripe' AND slug = '2d1f5a7c-7b8a-4f1f-8b77-1d4b0a300001'");
        $this->addSql("UPDATE payment_method SET slug = '4d1f5a7c-7b8a-4f1f-8b77-1d4b0a300002' WHERE code = 'paypal' AND slug = '2d1f5a7c-7b8a-4f1f-8b77-1d4b0a300002'");
        $this->addSql("UPDATE payment_method SET slug = '4d1f5a7c-7b8a-4f1f-8b77-1d4b0a300003' WHERE code = 'manual' AND slug = '2d1f5a7c-7b8a-4f1f-8b77-1d4b0a300003'");
        $this->addSql("UPDATE payment_method SET slug = '4d1f5a7c-7b8a-4f1f-8b77-1d4b0a300004' WHERE code = 'adyen' AND slug = '2d1f5a7c-7b8a-4f1f-8b77-1d4b0a300004'");
    }
}
