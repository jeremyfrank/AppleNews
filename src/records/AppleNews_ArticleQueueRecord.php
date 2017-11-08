<?php
namespace craft\applenews\records;

use craft\db\ActiveRecord;

/**
 * Class AppleNews_ArticleQueueRecord
 *
 * @license https://github.com/pixelandtonic/AppleNews/blob/master/LICENSE
 */
class AppleNews_ArticleQueueRecord extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc BaseRecord::getTableName()
     *
     * @return string
     */
    public function getTableName(): string
    {
        return 'applenews_articlequeue';
    }

    /**
     * @inheritDoc ActiveRecord::defineRelations()
     *
     * @return array
     */
    public function defineRelations(): array
    {
        return [
            'entry' => [
                self::BELONGS_TO,
                'EntryRecord',
                'onDelete' => self::CASCADE
            ],
        ];
    }

    /**
     * @inheritDoc ActiveRecord::defineIndexes()
     *
     * @return array
     */
    public function defineIndexes(): array
    {
        return [
            ['columns' => ['entryId', 'locale', 'channelId'], 'unique' => true],
        ];
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritDoc ActiveRecord::defineAttributes()
     *
     * @return array
     */
    protected function defineAttributes(): array
    {
        return [
            'locale' => ['required' => true],
            'channelId' => [
                'required' => true,
                'length' => 36
            ]
        ];
    }
}
