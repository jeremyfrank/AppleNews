<?php

namespace craft\applenews;

use craft\elements\Entry;
use yii\base\Exception;

/**
 * Class BaseAppleNewsChannel
 *
 * @license https://github.com/pixelandtonic/AppleNews/blob/master/LICENSE
 */
abstract class BaseAppleNewsChannel implements AppleNewsChannelInterface
{
    // Properties
    // =========================================================================

    /**
     * @var string The channel ID
     */
    public $channelId;

    /**
     * @var string The channel API key ID
     */
    public $apiKeyId;

    /**
     * @var string The channel API shared secret
     */
    public $apiSecret;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getChannelId(): string
    {
        return $this->channelId;
    }

    /**
     * @inheritdoc
     */
    public function getApiKeyId(): string
    {
        return $this->apiKeyId;
    }

    /**
     * @inheritdoc
     */
    public function getApiSecret(): string
    {
        return $this->apiSecret;
    }

    /**
     * @inheritdoc
     */
    public function matchEntry(Entry $entry): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function canPublish(Entry $entry): bool
    {
        if ($entry->getStatus() != Entry::LIVE) {
            return false;
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function createArticle(Entry $entry)
    {
        throw new Exception('createArticle not implemented');
    }
}
