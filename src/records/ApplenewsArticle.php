<?php

namespace craft\applenews\records;

use craft\db\ActiveRecord;
use Url;
use yii\db\ActiveQueryInterface;
use craft\elements\Entry;


/**
 * Class AppleNews_ArticleRecord record.
 *
 * @property string                       $channelId         Channel ID
 * @property int                          $entryId           Entry ID
 * @property string                       $articleId         Article ID
 * @property string                       $revisionId        Revision ID
 * @property bool                         $isSponsored       Is Sponsored
 * @property bool                         $isPreview         Is Preview
 * @property string                       $state             State
 * @property Url                          $shareUrl          Share Url
 * @property string                       $tableName
 * @property ActiveQueryInterface         $entry
 * @property Mixed                        $response          Response
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class ApplenewsArticle extends ActiveRecord
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
        return '{{%applenews_article}}';
    }

    /**
     * Returns the entry’s entry.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getEntry(): ActiveQueryInterface
    {
        return $this->hasOne(Entry::class, ['id' => 'entryId']);
    }

    /**
     * Returns static class instance, which can be used to obtain meta information.
     *
     * @param bool $refresh whether to re-create static instance even, if it is already cached.
     *
     * @return static class instance.
     */
    public static function instance($refresh = false)
    {
        return new self();
    }
}
