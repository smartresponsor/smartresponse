<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260519012100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Replace legacy placeholder Ordering slugs with real UUID values.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE orders SET slug = '7f2a4d8e-1c23-4f5d-8f90-2b3c4d5e1001' WHERE number = 'ORD-20001' AND slug = '9f2a4d8e-1c23-4f5d-8f90-2b3c4d5e1001'");
        $this->addSql("UPDATE orders SET slug = '7f2a4d8e-1c23-4f5d-8f90-2b3c4d5e1002' WHERE number = 'ORD-20002' AND slug = '9f2a4d8e-1c23-4f5d-8f90-2b3c4d5e1002'");
        $this->addSql("UPDATE orders SET slug = '7f2a4d8e-1c23-4f5d-8f90-2b3c4d5e1003' WHERE number = 'ORD-20003' AND slug = '9f2a4d8e-1c23-4f5d-8f90-2b3c4d5e1003'");
        $this->addSql("UPDATE orders SET slug = '7f2a4d8e-1c23-4f5d-8f90-2b3c4d5e1004' WHERE number = 'ORD-20004' AND slug = '9f2a4d8e-1c23-4f5d-8f90-2b3c4d5e1004'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE orders SET slug = '9f2a4d8e-1c23-4f5d-8f90-2b3c4d5e1001' WHERE number = 'ORD-20001' AND slug = '7f2a4d8e-1c23-4f5d-8f90-2b3c4d5e1001'");
        $this->addSql("UPDATE orders SET slug = '9f2a4d8e-1c23-4f5d-8f90-2b3c4d5e1002' WHERE number = 'ORD-20002' AND slug = '7f2a4d8e-1c23-4f5d-8f90-2b3c4d5e1002'");
        $this->addSql("UPDATE orders SET slug = '9f2a4d8e-1c23-4f5d-8f90-2b3c4d5e1003' WHERE number = 'ORD-20003' AND slug = '7f2a4d8e-1c23-4f5d-8f90-2b3c4d5e1003'");
        $this->addSql("UPDATE orders SET slug = '9f2a4d8e-1c23-4f5d-8f90-2b3c4d5e1004' WHERE number = 'ORD-20004' AND slug = '7f2a4d8e-1c23-4f5d-8f90-2b3c4d5e1004'");
    }
}
