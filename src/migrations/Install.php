<?php
namespace craft\applenews\migrations;

use craft\db\Migration;

class Install extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): void
    {
        $this->createTables();
        $this->createIndexes();
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        return true;
    }

    /**
     * Creates the tables.
     *
     * @return void
     */
    protected function createTables(): void
    {
        $this->createTable('{{%apple_news__article}}', [
            'id' => $this->primaryKey(),
            'entryId' => $this->integer(),
            'channelId' => $this->string(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable('{{%applenews_articlequeue}}', [
            'id' => $this->primaryKey(),
            'job' => $this->binary()->notNull(),
            'description' => $this->text(),
            'entryId' => $this->integer(),
            'channelId' => $this->string(),
            'timePushed' => $this->integer()->notNull(),
            'ttr' => $this->integer()->notNull(),
            'delay' => $this->integer()->defaultValue(0)->notNull(),
            'priority' => $this->integer()->unsigned()->notNull()->defaultValue(1024),
            'dateReserved' => $this->dateTime(),
            'timeUpdated' => $this->integer(),
            'progress' => $this->smallInteger()->notNull()->defaultValue(0),
            'attempt' => $this->integer(),
            'fail' => $this->boolean()->defaultValue(false),
            'dateFailed' => $this->dateTime(),
            'error' => $this->text(),
        ]);
    }

    /**
     * Creates the indexes.
     *
     * @return void
     */
    protected function createIndexes(): void
    {
        $this->createIndex(null, '{{%apple_news__article}}', ['entryId', 'channelId']);
        $this->createIndex(null, '{{%applenews_articlequeue}}', ['entryId', 'channelId'], false);
    }
}
