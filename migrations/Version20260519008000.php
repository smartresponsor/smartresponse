<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260519008000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Restore the messaging tables for the Messaging manage screen.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS message_thread (
            id UUID NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY (id)
        )');

        $this->addSql('CREATE TABLE IF NOT EXISTS message_message (
            id UUID NOT NULL,
            thread_id UUID NOT NULL,
            sender_user_id UUID NOT NULL,
            content TEXT NOT NULL,
            status VARCHAR(32) NOT NULL DEFAULT \'new\',
            position BIGINT NOT NULL DEFAULT 0,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            PRIMARY KEY (id),
            CONSTRAINT fk_message_message_thread FOREIGN KEY (thread_id) REFERENCES message_thread (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        )');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_message_message_thread_position ON message_message (thread_id, position)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_message_message_status ON message_message (status)');

        $this->addSql('CREATE TABLE IF NOT EXISTS message_thread_member (
            id VARCHAR(64) NOT NULL,
            thread_id UUID NOT NULL,
            user_id UUID NOT NULL,
            role VARCHAR(16) NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            joined_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            left_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            PRIMARY KEY (id),
            CONSTRAINT fk_message_thread_member_thread FOREIGN KEY (thread_id) REFERENCES message_thread (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        )');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_message_thread_member_thread ON message_thread_member (thread_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_message_thread_member_user ON message_thread_member (user_id)');

        $this->addSql("INSERT INTO message_thread (id, created_at) VALUES
            ('0d6d1a1a-8d3e-4e1e-a001-000000000001', NOW()),
            ('0d6d1a1a-8d3e-4e1e-a001-000000000002', NOW()),
            ('0d6d1a1a-8d3e-4e1e-a001-000000000003', NOW())
            ON CONFLICT (id) DO NOTHING");

        $this->addSql("INSERT INTO message_message (id, thread_id, sender_user_id, content, status, position, created_at, updated_at) VALUES
            ('1d6d1a1a-8d3e-4e1e-a001-000000000001', '0d6d1a1a-8d3e-4e1e-a001-000000000001', '2b8a1f66-8b6f-4f75-9b0b-000000000101', 'Order confirmation and delivery window', 'new', 1, NOW(), NOW()),
            ('1d6d1a1a-8d3e-4e1e-a001-000000000002', '0d6d1a1a-8d3e-4e1e-a001-000000000002', '2b8a1f66-8b6f-4f75-9b0b-000000000102', 'Shipping update for express parcel', 'sent', 2, NOW(), NOW()),
            ('1d6d1a1a-8d3e-4e1e-a001-000000000003', '0d6d1a1a-8d3e-4e1e-a001-000000000003', '2b8a1f66-8b6f-4f75-9b0b-000000000103', 'Refund status for returned item', 'read', 3, NOW(), NOW())
            ON CONFLICT (id) DO NOTHING");

        $this->addSql("INSERT INTO message_thread_member (id, thread_id, user_id, role, created_at, joined_at, left_at) VALUES
            ('member-01-01', '0d6d1a1a-8d3e-4e1e-a001-000000000001', '2b8a1f66-8b6f-4f75-9b0b-000000000101', 'owner', NOW(), NOW(), NULL),
            ('member-01-02', '0d6d1a1a-8d3e-4e1e-a001-000000000001', '2b8a1f66-8b6f-4f75-9b0b-000000000201', 'moderator', NOW(), NOW(), NULL),
            ('member-02-01', '0d6d1a1a-8d3e-4e1e-a001-000000000002', '2b8a1f66-8b6f-4f75-9b0b-000000000102', 'owner', NOW(), NOW(), NULL),
            ('member-02-02', '0d6d1a1a-8d3e-4e1e-a001-000000000002', '2b8a1f66-8b6f-4f75-9b0b-000000000202', 'moderator', NOW(), NOW(), NULL),
            ('member-03-01', '0d6d1a1a-8d3e-4e1e-a001-000000000003', '2b8a1f66-8b6f-4f75-9b0b-000000000103', 'owner', NOW(), NOW(), NULL),
            ('member-03-02', '0d6d1a1a-8d3e-4e1e-a001-000000000003', '2b8a1f66-8b6f-4f75-9b0b-000000000203', 'moderator', NOW(), NOW(), NULL)
            ON CONFLICT (id) DO NOTHING");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS message_thread_member');
        $this->addSql('DROP TABLE IF EXISTS message_message');
        $this->addSql('DROP TABLE IF EXISTS message_thread');
    }
}
