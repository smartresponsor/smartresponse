<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260915174500RelatingCurrentBaseline extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Materialize the current Relating ORM baseline in the Host database.';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform, 'Relating baseline supports PostgreSQL only.');
        $tables = [
            'relating_activity',
            'relating_activity_participant',
            'relating_activity_target',
            'relating_ai_decision_log',
            'relating_ai_draft',
            'relating_ai_enrichment_run',
            'relating_ai_scoring_run',
            'relating_ai_signal',
            'relating_ai_suggestion',
            'relating_automation_action',
            'relating_automation_condition',
            'relating_automation_error',
            'relating_automation_rule',
            'relating_automation_run',
            'relating_automation_run_step',
            'relating_automation_trigger',
            'relating_calendar_definition',
            'relating_call',
            'relating_campaign',
            'relating_campaign_member',
            'relating_campaign_response',
            'relating_campaign_touch',
            'relating_case_escalation',
            'relating_case_record',
            'relating_case_resolution',
            'relating_case_sla',
            'relating_case_status_definition',
            'relating_case_thread',
            'relating_case_type',
            'relating_commercial_term',
            'relating_contact_point_snapshot',
            'relating_dashboard_definition',
            'relating_discount_approval',
            'relating_duplicate_candidate',
            'relating_field_definition',
            'relating_field_option',
            'relating_kanban_definition',
            'relating_layout_definition',
            'relating_lead',
            'relating_lead_assignment',
            'relating_lead_conversion',
            'relating_lead_qualification',
            'relating_lead_rejection',
            'relating_lead_score',
            'relating_lead_source',
            'relating_lead_type',
            'relating_meeting',
            'relating_merge_proposal',
            'relating_note',
            'relating_object_definition',
            'relating_opportunity',
            'relating_opportunity_competitor',
            'relating_opportunity_contact_role',
            'relating_opportunity_forecast',
            'relating_opportunity_loss_reason',
            'relating_opportunity_product_interest',
            'relating_opportunity_stage_history',
            'relating_pipeline',
            'relating_pipeline_stage',
            'relating_quote_intent',
            'relating_quote_line_intent',
            'relating_relationship',
            'relating_relationship_definition',
            'relating_relationship_participant',
            'relating_relationship_relation',
            'relating_relationship_signal',
            'relating_reminder',
            'relating_source',
            'relating_target_list',
            'relating_target_list_member',
            'relating_task',
            'relating_timeline_event',
            'relating_utm_attribution',
            'relating_view_column',
            'relating_view_definition',
            'relating_view_filter',
            'relating_view_sort',
        ];
        $existing = array_values(array_filter($tables, static fn (string $table): bool => $schema->hasTable($table)));
        if (count($existing) === count($tables)) { return; }
        $this->abortIf([] !== $existing, sprintf('Partial Relating baseline detected (%d/%d tables); refusing mixed create/adopt execution.', count($existing), count($tables)));

        $this->addSql('CREATE TABLE relating_activity (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, type VARCHAR(64) NOT NULL, status VARCHAR(64) NOT NULL, direction VARCHAR(64) NOT NULL, target_type VARCHAR(64) NOT NULL, target_reference VARCHAR(128) NOT NULL, relationship_reference VARCHAR(128) DEFAULT NULL, owner_reference VARCHAR(128) DEFAULT NULL, subject VARCHAR(255) DEFAULT NULL, body TEXT DEFAULT NULL, due_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, completed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_relating_activity_tenant_target ON relating_activity (tenant_reference, target_type, target_reference)');
        $this->addSql('CREATE INDEX idx_relating_activity_relationship ON relating_activity (relationship_reference)');
        $this->addSql('CREATE INDEX idx_relating_activity_status ON relating_activity (status)');
        $this->addSql('CREATE INDEX idx_relating_activity_due ON relating_activity (due_at)');
        $this->addSql('CREATE TABLE relating_activity_participant (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, code VARCHAR(128) NOT NULL, name VARCHAR(255) DEFAULT NULL, data JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE relating_activity_target (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, code VARCHAR(128) NOT NULL, name VARCHAR(255) DEFAULT NULL, data JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE relating_ai_decision_log (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, code VARCHAR(128) NOT NULL, name VARCHAR(255) DEFAULT NULL, data JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE relating_ai_draft (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, code VARCHAR(128) NOT NULL, name VARCHAR(255) DEFAULT NULL, data JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE relating_ai_enrichment_run (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, code VARCHAR(128) NOT NULL, name VARCHAR(255) DEFAULT NULL, data JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE relating_ai_scoring_run (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, code VARCHAR(128) NOT NULL, name VARCHAR(255) DEFAULT NULL, data JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE relating_ai_signal (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, code VARCHAR(128) NOT NULL, name VARCHAR(255) DEFAULT NULL, data JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE relating_ai_suggestion (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, code VARCHAR(128) NOT NULL, name VARCHAR(255) DEFAULT NULL, data JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE relating_automation_action (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, code VARCHAR(128) NOT NULL, name VARCHAR(255) DEFAULT NULL, data JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE relating_automation_condition (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, code VARCHAR(128) NOT NULL, name VARCHAR(255) DEFAULT NULL, data JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE relating_automation_error (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, code VARCHAR(128) NOT NULL, name VARCHAR(255) DEFAULT NULL, data JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE relating_automation_rule (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, code VARCHAR(128) NOT NULL, name VARCHAR(255) DEFAULT NULL, data JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE relating_automation_run (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, code VARCHAR(128) NOT NULL, name VARCHAR(255) DEFAULT NULL, data JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE relating_automation_run_step (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, code VARCHAR(128) NOT NULL, name VARCHAR(255) DEFAULT NULL, data JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE relating_automation_trigger (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, code VARCHAR(128) NOT NULL, name VARCHAR(255) DEFAULT NULL, data JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE relating_calendar_definition (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, code VARCHAR(128) NOT NULL, name VARCHAR(255) DEFAULT NULL, data JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE relating_call (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, code VARCHAR(128) NOT NULL, name VARCHAR(255) DEFAULT NULL, data JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE relating_campaign (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, code VARCHAR(128) NOT NULL, name VARCHAR(255) DEFAULT NULL, data JSON NOT NULL, status VARCHAR(64) NOT NULL, channel VARCHAR(64) NOT NULL, starts_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, ends_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_relating_campaign_tenant_status ON relating_campaign (tenant_reference, status)');
        $this->addSql('CREATE INDEX idx_relating_campaign_channel ON relating_campaign (channel)');
        $this->addSql('CREATE TABLE relating_campaign_member (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, code VARCHAR(128) NOT NULL, name VARCHAR(255) DEFAULT NULL, data JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE relating_campaign_response (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, code VARCHAR(128) NOT NULL, name VARCHAR(255) DEFAULT NULL, data JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE relating_campaign_touch (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, code VARCHAR(128) NOT NULL, name VARCHAR(255) DEFAULT NULL, data JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE relating_case_escalation (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, code VARCHAR(128) NOT NULL, name VARCHAR(255) DEFAULT NULL, data JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE relating_case_record (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, relationship_reference VARCHAR(128) NOT NULL, title VARCHAR(255) NOT NULL, status VARCHAR(64) NOT NULL, priority VARCHAR(64) NOT NULL, sla_status VARCHAR(64) NOT NULL, sla_deadline_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, resolved_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, context JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_relating_case_tenant_relationship ON relating_case_record (tenant_reference, relationship_reference)');
        $this->addSql('CREATE INDEX idx_relating_case_status ON relating_case_record (status)');
        $this->addSql('CREATE INDEX idx_relating_case_priority ON relating_case_record (priority)');
        $this->addSql('CREATE INDEX idx_relating_case_sla_deadline ON relating_case_record (sla_deadline_at)');
        $this->addSql('CREATE TABLE relating_case_resolution (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, code VARCHAR(128) NOT NULL, name VARCHAR(255) DEFAULT NULL, data JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE relating_case_sla (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, code VARCHAR(128) NOT NULL, name VARCHAR(255) DEFAULT NULL, data JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE relating_case_status_definition (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, code VARCHAR(128) NOT NULL, name VARCHAR(255) DEFAULT NULL, data JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE relating_case_thread (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, code VARCHAR(128) NOT NULL, name VARCHAR(255) DEFAULT NULL, data JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE relating_case_type (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, code VARCHAR(128) NOT NULL, name VARCHAR(255) DEFAULT NULL, data JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE relating_commercial_term (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, code VARCHAR(128) NOT NULL, name VARCHAR(255) DEFAULT NULL, data JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE relating_contact_point_snapshot (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, owner_type VARCHAR(64) NOT NULL, owner_reference VARCHAR(128) NOT NULL, contact_points JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE relating_dashboard_definition (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, code VARCHAR(128) NOT NULL, name VARCHAR(255) DEFAULT NULL, data JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE relating_discount_approval (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, code VARCHAR(128) NOT NULL, name VARCHAR(255) DEFAULT NULL, data JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE relating_duplicate_candidate (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, code VARCHAR(128) NOT NULL, name VARCHAR(255) DEFAULT NULL, data JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE relating_field_definition (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, code VARCHAR(128) NOT NULL, name VARCHAR(255) DEFAULT NULL, data JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE relating_field_option (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, code VARCHAR(128) NOT NULL, name VARCHAR(255) DEFAULT NULL, data JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE relating_kanban_definition (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, code VARCHAR(128) NOT NULL, name VARCHAR(255) DEFAULT NULL, data JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE relating_layout_definition (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, code VARCHAR(128) NOT NULL, name VARCHAR(255) DEFAULT NULL, data JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE relating_lead (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, status VARCHAR(64) NOT NULL, temperature VARCHAR(64) NOT NULL, display_name VARCHAR(255) DEFAULT NULL, company_name VARCHAR(255) DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, phone VARCHAR(64) DEFAULT NULL, source_reference VARCHAR(128) DEFAULT NULL, relationship_reference VARCHAR(128) DEFAULT NULL, score INT NOT NULL, conversion_status VARCHAR(64) NOT NULL, payload JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_relating_lead_tenant_status ON relating_lead (tenant_reference, status)');
        $this->addSql('CREATE INDEX idx_relating_lead_email ON relating_lead (email)');
        $this->addSql('CREATE INDEX idx_relating_lead_source ON relating_lead (source_reference)');
        $this->addSql('CREATE INDEX idx_relating_lead_relationship ON relating_lead (relationship_reference)');
        $this->addSql('CREATE TABLE relating_lead_assignment (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, code VARCHAR(128) NOT NULL, name VARCHAR(255) DEFAULT NULL, data JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE relating_lead_conversion (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, code VARCHAR(128) NOT NULL, name VARCHAR(255) DEFAULT NULL, data JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE relating_lead_qualification (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, code VARCHAR(128) NOT NULL, name VARCHAR(255) DEFAULT NULL, data JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE relating_lead_rejection (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, code VARCHAR(128) NOT NULL, name VARCHAR(255) DEFAULT NULL, data JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE relating_lead_score (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, code VARCHAR(128) NOT NULL, name VARCHAR(255) DEFAULT NULL, data JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE relating_lead_source (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, code VARCHAR(128) NOT NULL, name VARCHAR(255) DEFAULT NULL, data JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE relating_lead_type (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, code VARCHAR(128) NOT NULL, name VARCHAR(255) DEFAULT NULL, data JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE relating_meeting (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, code VARCHAR(128) NOT NULL, name VARCHAR(255) DEFAULT NULL, data JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE relating_merge_proposal (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, code VARCHAR(128) NOT NULL, name VARCHAR(255) DEFAULT NULL, data JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE relating_note (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, target_type VARCHAR(64) NOT NULL, target_reference VARCHAR(128) NOT NULL, body TEXT NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE relating_object_definition (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, code VARCHAR(128) NOT NULL, name VARCHAR(255) DEFAULT NULL, data JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE relating_opportunity (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, relationship_reference VARCHAR(128) NOT NULL, pipeline_reference VARCHAR(128) NOT NULL, stage_reference VARCHAR(128) NOT NULL, name VARCHAR(255) NOT NULL, status VARCHAR(64) NOT NULL, forecast_category VARCHAR(64) NOT NULL, currency VARCHAR(3) NOT NULL, amount_minor BIGINT NOT NULL, probability INT NOT NULL, expected_close_date DATE DEFAULT NULL, primary_product_reference VARCHAR(128) DEFAULT NULL, loss_reason_code VARCHAR(128) DEFAULT NULL, context JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_relating_opportunity_tenant_relationship ON relating_opportunity (tenant_reference, relationship_reference)');
        $this->addSql('CREATE INDEX idx_relating_opportunity_pipeline ON relating_opportunity (pipeline_reference)');
        $this->addSql('CREATE INDEX idx_relating_opportunity_stage ON relating_opportunity (stage_reference)');
        $this->addSql('CREATE INDEX idx_relating_opportunity_status ON relating_opportunity (status)');
        $this->addSql('CREATE INDEX idx_relating_opportunity_close_date ON relating_opportunity (expected_close_date)');
        $this->addSql('CREATE TABLE relating_opportunity_competitor (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, code VARCHAR(128) NOT NULL, name VARCHAR(255) DEFAULT NULL, data JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE relating_opportunity_contact_role (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, code VARCHAR(128) NOT NULL, name VARCHAR(255) DEFAULT NULL, data JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE relating_opportunity_forecast (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, code VARCHAR(128) NOT NULL, name VARCHAR(255) DEFAULT NULL, data JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE relating_opportunity_loss_reason (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, code VARCHAR(128) NOT NULL, name VARCHAR(255) DEFAULT NULL, data JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE relating_opportunity_product_interest (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, code VARCHAR(128) NOT NULL, name VARCHAR(255) DEFAULT NULL, data JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE relating_opportunity_stage_history (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, code VARCHAR(128) NOT NULL, name VARCHAR(255) DEFAULT NULL, data JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE relating_pipeline (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, code VARCHAR(128) NOT NULL, name VARCHAR(255) DEFAULT NULL, data JSON NOT NULL, default_pipeline BOOLEAN NOT NULL, active BOOLEAN NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_relating_pipeline_tenant_code ON relating_pipeline (tenant_reference, code)');
        $this->addSql('CREATE TABLE relating_pipeline_stage (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, code VARCHAR(128) NOT NULL, name VARCHAR(255) DEFAULT NULL, data JSON NOT NULL, pipeline_reference VARCHAR(128) NOT NULL, position INT NOT NULL, probability INT NOT NULL, forecast_category VARCHAR(64) NOT NULL, terminal BOOLEAN NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_relating_stage_tenant_pipeline ON relating_pipeline_stage (tenant_reference, pipeline_reference)');
        $this->addSql('CREATE INDEX idx_relating_stage_position ON relating_pipeline_stage (pipeline_reference, position)');
        $this->addSql('CREATE TABLE relating_quote_intent (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, opportunity_reference VARCHAR(36) NOT NULL, currency VARCHAR(3) NOT NULL, estimated_amount_minor INT NOT NULL, terms JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE relating_quote_line_intent (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, code VARCHAR(128) NOT NULL, name VARCHAR(255) DEFAULT NULL, data JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE relating_relationship (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, vendor_reference VARCHAR(128) NOT NULL, kind VARCHAR(64) NOT NULL, status VARCHAR(64) NOT NULL, lifecycle_stage VARCHAR(64) NOT NULL, owner_reference VARCHAR(128) DEFAULT NULL, source_reference VARCHAR(128) DEFAULT NULL, health_score INT NOT NULL, engagement_score INT NOT NULL, fit_score INT NOT NULL, first_touch_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, last_touch_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, next_action_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, context JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_relating_relationship_tenant_vendor ON relating_relationship (tenant_reference, vendor_reference)');
        $this->addSql('CREATE INDEX idx_relating_relationship_kind ON relating_relationship (kind)');
        $this->addSql('CREATE INDEX idx_relating_relationship_status ON relating_relationship (status)');
        $this->addSql('CREATE INDEX idx_relating_relationship_lifecycle ON relating_relationship (lifecycle_stage)');
        $this->addSql('CREATE INDEX idx_relating_relationship_owner ON relating_relationship (owner_reference)');
        $this->addSql('CREATE INDEX idx_relating_relationship_next_action ON relating_relationship (next_action_at)');
        $this->addSql('CREATE TABLE relating_relationship_definition (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, code VARCHAR(128) NOT NULL, name VARCHAR(255) DEFAULT NULL, data JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE relating_relationship_participant (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, relationship_reference VARCHAR(128) NOT NULL, vendor_reference VARCHAR(128) NOT NULL, role VARCHAR(64) NOT NULL, primary_participant BOOLEAN NOT NULL, context JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_relating_participant_tenant_relationship ON relating_relationship_participant (tenant_reference, relationship_reference)');
        $this->addSql('CREATE INDEX idx_relating_participant_vendor ON relating_relationship_participant (vendor_reference)');
        $this->addSql('CREATE INDEX idx_relating_participant_role ON relating_relationship_participant (role)');
        $this->addSql('CREATE TABLE relating_relationship_relation (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, source_relationship_reference VARCHAR(36) NOT NULL, target_relationship_reference VARCHAR(36) NOT NULL, relation_kind VARCHAR(64) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_relating_relation_source ON relating_relationship_relation (source_relationship_reference)');
        $this->addSql('CREATE INDEX idx_relating_relation_target ON relating_relationship_relation (target_relationship_reference)');
        $this->addSql('CREATE TABLE relating_relationship_signal (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, relationship_reference VARCHAR(128) NOT NULL, signal_type VARCHAR(64) NOT NULL, source_component VARCHAR(64) DEFAULT NULL, source_reference VARCHAR(128) DEFAULT NULL, weight INT NOT NULL, payload JSON NOT NULL, occurred_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_relating_signal_tenant_relationship ON relating_relationship_signal (tenant_reference, relationship_reference)');
        $this->addSql('CREATE INDEX idx_relating_signal_type ON relating_relationship_signal (signal_type)');
        $this->addSql('CREATE INDEX idx_relating_signal_source ON relating_relationship_signal (source_component, source_reference)');
        $this->addSql('CREATE INDEX idx_relating_signal_occurred ON relating_relationship_signal (occurred_at)');
        $this->addSql('CREATE TABLE relating_reminder (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, code VARCHAR(128) NOT NULL, name VARCHAR(255) DEFAULT NULL, data JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE relating_source (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, code VARCHAR(128) NOT NULL, name VARCHAR(255) DEFAULT NULL, data JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE relating_target_list (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, code VARCHAR(128) NOT NULL, name VARCHAR(255) DEFAULT NULL, data JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE relating_target_list_member (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, code VARCHAR(128) NOT NULL, name VARCHAR(255) DEFAULT NULL, data JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE relating_task (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, activity_reference VARCHAR(128) NOT NULL, title VARCHAR(255) NOT NULL, status VARCHAR(64) NOT NULL, due_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE relating_timeline_event (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, target_type VARCHAR(64) NOT NULL, target_reference VARCHAR(128) NOT NULL, relationship_reference VARCHAR(128) DEFAULT NULL, event_type VARCHAR(128) NOT NULL, source_component VARCHAR(64) DEFAULT NULL, source_reference VARCHAR(128) DEFAULT NULL, payload JSON NOT NULL, occurred_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_relating_timeline_tenant_target ON relating_timeline_event (tenant_reference, target_type, target_reference)');
        $this->addSql('CREATE INDEX idx_relating_timeline_relationship ON relating_timeline_event (relationship_reference)');
        $this->addSql('CREATE INDEX idx_relating_timeline_event_type ON relating_timeline_event (event_type)');
        $this->addSql('CREATE INDEX idx_relating_timeline_occurred ON relating_timeline_event (occurred_at)');
        $this->addSql('CREATE TABLE relating_utm_attribution (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, code VARCHAR(128) NOT NULL, name VARCHAR(255) DEFAULT NULL, data JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE relating_view_column (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, code VARCHAR(128) NOT NULL, name VARCHAR(255) DEFAULT NULL, data JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE relating_view_definition (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, code VARCHAR(128) NOT NULL, name VARCHAR(255) DEFAULT NULL, data JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE relating_view_filter (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, code VARCHAR(128) NOT NULL, name VARCHAR(255) DEFAULT NULL, data JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE relating_view_sort (id VARCHAR(36) NOT NULL, tenant_reference VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, code VARCHAR(128) NOT NULL, name VARCHAR(255) DEFAULT NULL, data JSON NOT NULL, PRIMARY KEY (id))');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Current Relating ORM baseline is the forward Host schema contract.');
    }
}
