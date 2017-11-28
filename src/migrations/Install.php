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
            'job' => $this->string(),
            'description' => $this->string(100)->notNull()->defaultValue(''),
            'timePushed' => $this->dateTime(),
            'ttr' => $this->string(),
            'delay' => $this->integer(),
            'priority' => $this->integer(),
            'dateReserved' => $this->dateTime(),
            'timeUpdated' => $this->dateTime(),
            'progress' => $this->string(),
            'attempt' => $this->integer(),
            'fail' => $this->integer(),
            'dateFailed' => $this->dateTime(),
            'error' => $this->string(),
        ]);
    }
}
