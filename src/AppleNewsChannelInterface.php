<?php
namespace craft\applenews;


use craft\elements\Entry;

/**
 * Interface AppleNewsChannelInterface
 *
 * @license https://github.com/pixelandtonic/AppleNews/blob/master/LICENSE
 */
interface AppleNewsChannelInterface
{
    // Public Methods
    // =========================================================================

    /**
     * @return string The channel ID
     */
    public function getChannelId();

    /**
     * @return string The channel API key ID
     */
    public function getApiKeyId();

    /**
     * @return string The channel API shared secret
     */
    public function getApiSecret();

    /**
     * Determines whether a given entry should be included in the News channel.
     *
     * @param Entry $entry The entry
     *
     * @return bool Whether the entry should be included in the News channel
     */
    public function matchEntry(Entry $entry);

    /**
     * Determines whether a given entry should be published to Apple News in its current state.
     *
     * @param Entry $entry The entry
     *
     * @return bool Whether the entry should be published to Apple News
     */
    public function canPublish(Entry $entry);

    /**
     * Creates an {@link AppleNewsArticleInterface} for the given entry
     *
     * @param Entry $entry The entry
     *
     * @return AppleNewsArticleInterface The article that represents the entry
     */
    public function createArticle(Entry $entry);
}
