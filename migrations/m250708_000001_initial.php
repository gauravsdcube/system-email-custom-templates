<?php
/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @author D Cube Consulting <info@dcubeconsulting.co.uk>
 */



use humhub\components\Migration;

class m250708_000001_initial extends Migration
{
    public function safeUp()
    {
        $this->createTable('system_email_template', [
            'id' => $this->primaryKey(),
            'template_key' => $this->string(100)->notNull()->unique(),
            'subject' => $this->string(255)->notNull(),
            'header' => $this->text(),
            'body' => $this->text()->notNull(),
            'footer' => $this->text(),
            'header_bg_color' => $this->string(7),
            'footer_bg_color' => $this->string(7),
            'header_font_color' => $this->string(7),
            'footer_font_color' => $this->string(7),
            'is_active' => $this->boolean()->notNull()->defaultValue(0),
            'created_at' => $this->dateTime(),
            'updated_at' => $this->dateTime(),
        ]);

        $this->createIndex('idx_system_email_template_active', 'system_email_template', 'is_active');
    }

    public function safeDown()
    {
        $this->dropTable('system_email_template');
    }
}
