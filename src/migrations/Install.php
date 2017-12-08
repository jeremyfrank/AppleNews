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
            'id' => $this->primaryKey()->notNull(),
            'entryId' => $this->integer()->notNull(),
            'channelId' => $this->string()->notNull(),
            'articleId' => $this->string()->notNull(),
            'revisionId'=> $this->string()->notNull(),
            'isSponsored' => $this->smallInteger(1)->notNull()->unsigned(),
            'isPreview' => $this->smallInteger(1)->notNull()->unsigned(),
            'state'=> $this->string(),
            'shareUrl'=> $this->string(),
            'response' => $this->text(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid()->notNull(),
        ]);
        $this->createTable('{{%applenews_articlequeue}}', [
            'id' => $this->primaryKey()->notNull(),
            'entryId' => $this->integer(),
            'channelId' => $this->string()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid()->notNull(),
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
