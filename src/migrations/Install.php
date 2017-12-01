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
            'entryId' => $this->integer(),
            'channelId' => $this->string(),
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
