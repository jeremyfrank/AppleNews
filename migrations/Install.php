<?php
namespace craft\applenews\migrations;

use craft\db\Migration;

class Install extends Migration
{
    public function safeUp()
    {
        $this->createTables();
        $this->createIndex(null, '{{%applenews_articles}}', ['sessionId', 'volumeId']);
        $this->createIndex(null, '{{%applenews_articlequeue}}', ['volumeId'], false);
    }

    public function safeDown()
    {
        // ...
    }
    protected function createTables()
    {
        $this->createTable('{{%applenews_articles}}', [
            'id' => $this->primaryKey(),
            'sessionId' => $this->string(36)->notNull()->defaultValue(''),
            'volumeId' => $this->integer()->notNull(),
            'uri' => $this->text(),
            'size' => $this->bigInteger()->unsigned(),
            'timestamp' => $this->dateTime(),
            'recordId' => $this->integer(),
            'inProgress' => $this->boolean()->defaultValue(false),
            'completed' => $this->boolean()->defaultValue(false),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable('{{%applenews_articlequeue}}', [
            'id' => $this->primaryKey(),
            'sessionId' => $this->string(36)->notNull()->defaultValue(''),
            'volumeId' => $this->integer()->notNull(),
            'uri' => $this->text(),
            'size' => $this->bigInteger()->unsigned(),
            'timestamp' => $this->dateTime(),
            'recordId' => $this->integer(),
            'inProgress' => $this->boolean()->defaultValue(false),
            'completed' => $this->boolean()->defaultValue(false),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
    }
}
