<?php

namespace craft\applenews\records;

use craft\db\ActiveRecord;
use yii\db\ActiveQueryInterface;
use craft\elements\Entry;


/**
 * Class AppleNews_ArticleRecord record.
 *
 * @property string $channelId         Channel ID
 * @property int    $entryId           Entry ID
 * @property string $articleId         Article ID
 * @property string $revisionId        Revision ID
 * @property bool   $isSponsored       Is Sponsored
 * @property bool   $isPreview         Is Preview
 * @property string $state             State
 * @property \Url   $shareUrl          Share Url
 * @property Mixed  $response          Response
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class AppleNews_ArticleRecord extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc BaseRecord::getTableName()
     *
     * @return string
     */
    public function getTableName()
    {
        return 'applenews_articles';
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


}
