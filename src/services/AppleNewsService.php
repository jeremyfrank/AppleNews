<?php

namespace craft\applenews\services;

use ChapterThree\AppleNewsAPI\PublisherAPI;
use Craft;
use craft\applenews\AppleNewsChannelInterface;
use craft\applenews\Plugin;
use craft\applenews\records\AppleNews_ArticleRecord;
use craft\applenews\tasks\AppleNews_PostQueuedArticlesJob;

use craft\elements\Entry;
use yii\base\Component;
use yii\base\Exception;

use yii\helpers\Json;


/**
 * Class AppleNewsService
 *
 * @license https://github.com/pixelandtonic/AppleNews/blob/master/LICENSE
 */
class AppleNewsService extends Component
{
    // Properties
    // =========================================================================

    /**
     * @var AppleNewsChannelInterface[] The channels
     */
    private $_channels;

    /**
     * @var array Generator metadata properties
     */
    private $_generatorMetadata;

    // Public Methods
    // =========================================================================

    /**
     * Instantiates the application component
     */
    public function init()
    {


        // Set the applenewschannels alias
        defined('APPLE_NEWS_CHANELS_PATH') || define('APPLE_NEWS_CHANELS_PATH', CRAFT_BASE_PATH.'applenewschannels/');
        Craft::setAlias('applenewschannels', APPLE_NEWS_CHANELS_PATH);
    }

    /**
     * Returns all the channels.
     *
     * @return AppleNewsChannelInterface[]
     * @throws Exception if any of the channels don't implement AppleNewsChannelInterface.
     */
    public function getChannels(): array
    {
        if (!isset($this->_channels)) {
            $this->_channels = [];
            $channelConfigs = Plugin::getInstance()->getSettings()->channels;

            foreach ($channelConfigs as $config) {
                $channel = Craft::$app->getComponents($config);

                if (!($channel instanceof AppleNewsChannelInterface)) {
                    throw new Exception('All Apple News channels must implement the AppleNewsChannelInterface interface');
                }

                $this->_channels[$channel->getChannelId()] = $channel;
            }
        }

        return $this->_channels;
    }

    /**
     * Returns a channel by its ID.
     *
     * @param string $channelId The channel ID
     *
     * @return AppleNewsChannelInterface
     * @throws Exception if no channel exists with that ID
     */
    public function getChannelById($channelId): AppleNewsChannelInterface
    {
        $channels = $this->getChannels();

        if (isset($channels[$channelId])) {
            return $channels[$channelId];
        }

        throw new Exception('No channel exists with the ID '.$channelId);
    }

    /**
     * Returns a channel’s name by its ID.
     *
     * @param string $channelId The channel ID
     *
     * @return string The channel name
     * @throws Exception if no channel exists with that ID
     */
    public function getChannelName($channelId): string
    {
        $cacheKey = 'appleNews:channelName:'.$channelId;
        $name = Craft::$app->getCache()->get($cacheKey);

        if ($name === false) {
            $info = $this->getApiService()->readChannel($channelId);
            $name = $info->data->name;
            Craft::$app->getCache()->set($cacheKey, $name, 0);
        }

        return $name;
    }

    /**
     * Returns all known info about an entry's articles on Apple News.
     *
     * @param Entry                $entry     The entry
     * @param string|string[]|null $channelId The channel ID(s) to limit the query to
     * @param bool                 $refresh   Whether the info should be refreshed for articles that are processing
     *
     * @return array[] The info, indexed by channel ID
     */
    public function getArticleInfo(Entry $entry, $channelId = null, $refresh = false): array
    {
        $attributes = ['entryId' => $entry->id];
        if ($channelId !== null) {
            $attributes['channelId'] = $channelId;
        }
        $records = AppleNews_ArticleRecord::model()->findAllByAttributes($attributes);

        $infos = [];

        foreach ($records as $record) {
            // Refresh first?
            if ($refresh && in_array($record->state, [
                    'PROCESSING',
                    'PROCESSING_UPDATE'
                ])
            ) {
                $response = $this->getApiService()->readArticle($record->channelId, $record->articleId);
                if (isset($response->data)) {
                    $this->updateArticleRecord($record, $response);
                }
            }

            $infos[$record->channelId] = [
                'articleId' => $record->articleId,
                'revisionId' => $record->revisionId,
                'isSponsored' => (bool)$record->isSponsored,
                'isPreview' => (bool)$record->isPreview,
                'state' => $record->state,
                'shareUrl' => $record->shareUrl,
            ];
        }

        // Merge in any queue info
        $queuedChannels = $this->getQueuedChannelIdsForEntry($entry, $channelId);

        foreach ($queuedChannels as $channelId) {
            // Does an article already exist for this channel?
            if (isset($infos[$channelId])) {
                $infos[$channelId]['state'] = 'QUEUED_UPDATE';
            } else {
                $infos[$channelId]['state'] = 'QUEUED';
            }
        }

        return $infos;
    }

    /**
     * Returns whether any channels can publish the given entry.
     *
     * @param Entry                $entry
     * @param string|string[]|null $channelId The channel ID(s) to post the article to, if not all
     *
     * @return bool Whether the entry can be published to any channels
     */
    public function canPostArticle(Entry $entry)
    {
        // See if any channels will have it
        foreach ($this->getChannels() as $channel) {
            if ($channel->matchEntry($entry) && $channel->canPublish($entry)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Queues an article up to be posted.
     *
     * @param Entry                $entry
     * @param string|string[]|null $channelIds
     *
     * @return bool Whether the channel was queued up to be posted in any channels
     */
    public function queueArticle(Entry $entry, $channelIds = null): bool
    {
        if ($channelIds === null) {
            // Queue all of them
            $channelIds = [];
            foreach ($this->getChannels() as $channelId => $channel) {
                if ($channel->matchEntry($entry) && $channel->canPublish($entry)) {
                    $channelIds[] = $channelId;
                }
            }
        } else if (!is_array($channelIds)) {
            $channelIds = [$channelIds];
        }

        if ($channelIds) {
            foreach ($channelIds as $channelId) {
                $result = \Craft::$app->db->createCommand()
                    ->upsert('{{%applenews_articlequeue}}', 'entryId')
                    ->upsert('{{%applenews_articlequeue}}', 'locale')
                    ->upsert('{{%applenews_articlequeue}}', 'channelId')
                    ->execute();

                // Create a PostQueuedArticles task
                $this->createPostQueuedArticlesJob();

                return true;
            }
        } else {
            return false;
        }
    }


    /**
     * Creates a new PostQueuedArticles job if there isn't already one pending
     *
     * @return void
     */
    public function createPostQueuedArticlesJob(): void
    {
        Craft::$app->queue->push(new AppleNews_PostQueuedArticlesJob([
            'description' => 'Custom description',
            'mySetting' => 'value',
        ]));
    }

    /**
     * Returns the channel IDs that a given entry is queued to be posted in
     *
     * @param Entry                $entry
     * @param string|string[]|null $channelId The channel ID(s) the query should be limited to
     *
     * @return string[]
     */

    public function getQueuedChannelIdsForEntry(Entry $entry, $channelId = null): string
    {
        $queuedChannelQuery = Craft::$app->db->createCommand()
            ->select('channelId')
            ->from('applenews_articlequeue')
            ->where('entryId = :entryId', [':entryId' => $entry->id]);

        if ($channelId !== null) {
            if (is_array($channelId)) {
                $queuedChannelQuery->andWhere(['in', 'channelId', $channelId]);
            } else {
                $queuedChannelQuery->andWhere('channelId = :channelId', [':channelId' => $channelId]);
            }
        }

        return $queuedChannelQuery->queryColumn();
    }

    /**
     * Posts an article to Apple News.
     *
     * @param Entry                $entry
     * @param string|string[]|null $channelId The channel ID(s) to post the article to, if not all
     *
     * @return bool Whether the entry was posted to Apple News successfully
     */
    public function postArticle(Entry $entry, $channelId = null)
    {
        if (is_string($channelId)) {
            $channelId = [$channelId];
        }

        /** @var AppleNewsChannelInterface[] $channels */
        $channels = [];

        foreach ($this->getChannels() as $channel) {
            if ($channelId !== null && !in_array($channel->getChannelId(), $channelId)) {
                continue;
            }

            if ($channel->matchEntry($entry) && $channel->canPublish($entry)) {
                $channels[] = $channel;
            }
        }

        if (!$channels) {
            return false;
        }

        $articleRecords = $this->getArticleRecordsForEntry($entry);

        foreach ($channels as $channel) {
            $channelId = $channel->getChannelId();
            $articleExists = isset($articleRecords[$channelId]);

            $article = $channel->createArticle($entry);
            $content = $article->getContent();
            $metadata = $article->getMetadata() ?: [];

            // Include the generator metadata in the content
            $content['metadata'] = array_merge(
                isset($content['metadata']) ? $content['metadata'] : [],
                $this->getGeneratorMetadata());

            // Include the latest revision ID if we have one
            if ($articleExists) {
                $revisionId = $articleRecords[$channelId]->revisionId;
                $metadata['revision'] = $revisionId;
            }

            // Prepare the data and send the request
            $data = [
                'files' => $article->getFiles(),
                'metadata' => $metadata ? Json::encode(['data' => $metadata]) : null,
                'json' => Json::encode($content)
            ];

            // Publish the article
            if ($articleExists) {
                $articleId = $articleRecords[$channelId]->articleId;
                $response = $this->getApiService()->updateArticle($channelId, $articleId, $data);
            } else {
                $response = $this->getApiService()->createArticle($channelId, $data);
            }

            if (isset($response->data)) {
                // Save a record of the article
                if ($articleExists) {
                    $record = $articleRecords[$channelId];
                } else {
                    $record = new AppleNews_ArticleRecord();
                    $record->entryId = $entry->id;
                    $record->channelId = $channelId;
                    $record->articleId = $response->data->id;
                }
                $this->updateArticleRecord($record, $response);

                // Delete this entry+channel from the queue, if it's in there
                Craft::$app->getDb()->createCommand()->delete('applenews_articlequeue',
                    ['entryId' => $entry->id, 'channelId' => $channelId]);
            }

            if ($articleExists) {
                // Forget about this record since we've dealt with it, so it doesn't get deleted
                unset($articleRecords[$channelId]);
            }
        }

        // If there are any records left over, delete them
        $this->deleteArticlesFromRecords($articleRecords);

        return true;
    }

    /**
     * Deletes an article in a channel.
     *
     * @param Entry $entry
     *
     * @return void
     */
    public function deleteArticle(Entry $entry)
    {
        $articleRecords = $this->getArticleRecordsForEntry($entry);
        $this->deleteArticlesFromRecords($articleRecords);
    }

    // Protected Methods
    // =========================================================================

    /**
     * Returns the API service.
     *
     * @return AppleNews_ApiService
     */
    protected function getApiService()
    {
        return Plugin::getInstance()->appleNewsApiService;
    }


    /**
     * Returns article records for a given entry ID, indexed by the channel ID.
     *
     * @param Entry $entry
     *
     * @return AppleNews_ArticleRecord[]
     */
    protected function getArticleRecordsForEntry(Entry $entry)
    {
        $records = AppleNews_ArticleRecord::model()->findAllByAttributes([
            'entryId' => $entry->id
        ]);

        // Index by channel ID
        $recordsByChannelId = [];

        foreach ($records as $record) {
            $recordsByChannelId[$record->channelId] = $record;
        }

        return $recordsByChannelId;
    }

    /**
     * Deletes articles on Apple News based on the given records.
     *
     * @param AppleNews_ArticleRecord[] $records The article records
     *
     * @return void
     */
    protected function deleteArticlesFromRecords($records)
    {
        $apiService = $this->getApiService();
        foreach ($records as $channelId => $record) {
            $apiService->deleteArticle($channelId, $record->articleId);
            $record->delete();
        }
    }

    /**
     * Updates a given Article record with the data in an Apple News API response.
     *
     * @param AppleNews_ArticleRecord $record
     * @param \stdClass               $response
     */
    protected function updateArticleRecord(AppleNews_ArticleRecord $record, $response)
    {
        $record->revisionId = $response->data->revision;
        $record->isSponsored = $response->data->isSponsored;
        $record->isPreview = $response->data->isPreview;
        $record->state = $response->data->state;
        $record->shareUrl = $response->data->shareUrl;
        $record->response = Json::encode($response);

        $record->save();
    }

    /**
     * @return array Generator metadata properties
     */
    protected function getGeneratorMetadata(): array
    {
        if (!isset($this->_generatorMetadata)) {
            $this->_generatorMetadata = [
                'generatorIdentifier' => 'CraftCMS',
                'generatorName' => 'Craft CMS',
                'generatorVersion' => Craft::$app->getPlugins()->getPlugin('src')->getVersion(),
            ];
        }

        return $this->_generatorMetadata;
    }
}
