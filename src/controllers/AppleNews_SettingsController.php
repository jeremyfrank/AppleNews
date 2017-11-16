<?php

namespace craft\applenews\controllers;

use craft\applenews\Plugin;
use craft\applenews\services\AppleNews_ApiService;
use craft\applenews\services\AppleNewsService;
use craft\elements\Entry;
use craft\web\Controller;
use Craft;
use yii\base\Exception;
use yii\web\HttpException;


/**
 * Class AppleNews_SettingsController
 *
 * @license https://github.com/pixelandtonic/AppleNews/blob/master/LICENSE
 */
class AppleNews_SettingsController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * Initializes the controller
     */
    public function init()
    {
        // All settings actions requrie an admin account
        $this->requireAdmin();
    }

    /**
     * Settnigs index
     */
    public function actionIndex()
    {
        // Load the channel info
        $channels = $this->getService()->getChannels();
        $channelNames = [];
        $sections = [];
        $api = $this->getApiService();

        foreach ($channels as $channelId => $channel) {
            $channelNames[$channelId] = $this->getService()->getChannelName($channelId);
            $response = $api->listSections($channelId);
            $sections[$channelId] = $response->data;
        }

        $this->renderTemplate('src/_index', [
            'channels' => $channels,
            'channelNames' => $channelNames,
            'sections' => $sections,
        ]);
    }

    /**
     * Returns the latest info about an entry's articles
     */
    public function actionGetArticleInfo()
    {
        $entry = $this->getEntry();
        $channelId = Craft::$app->getRequest()->getParam('channelId');

        return $this->asJson([
            'infos' => $this->getArticleInfo($entry, $channelId, true),
        ]);
    }

    /**
     * Posts an article to Apple News.
     */
    public function actionPostArticle()
    {
        $entry = $this->getEntry();
        $channelId = Craft::$app->getRequest()->getRequiredParam('channelId');
        $service = $this->getService();

        $service->queueArticle($entry, $channelId);

        return $this->asJson([
            'success' => true,
            'infos' => $this->getArticleInfo($entry, $channelId),
        ]);
    }

    // Protected Methods
    // =========================================================================

    /**
     * @param bool $acceptRevision
     *
     * @return Entry
     * @throws HttpException
     */
    protected function getEntry($acceptRevision = false): Entry
    {
        $entryId = Craft::$app->getRequest()->getRequiredParam('entryId');
        $localeId = Craft::$app->getRequest()->getRequiredParam('locale');

        if ($acceptRevision) {
            $versionId = Craft::$app->getRequest()->getRequiredParam('versionId');
            $draftId = Craft::$app->getRequest()->getRequiredParam('draftId');
        } else {
            $versionId = $draftId = null;
        }

        if ($versionId) {
            $entry = Craft::$app->getEntryRevisions()->getVersionById($versionId);
        } elseif ($draftId) {
            $entry = Craft::$app->getEntryRevisions()->getDraftById($draftId);
        } else {
            $entry = Craft::$app->getEntries()->getEntryById($entryId, $localeId);
        }

        if (!$entry) {
            throw new HttpException(404);
        }

        // Make sure the user is allowed to edit entries in this section
        Craft::$app->getUser()->can('editEntries:'.$entry->sectionId);

        return $entry;
    }

    /**
     * @param Entry  $entry
     * @param string $channelId
     * @param bool   $refresh
     *
     * @return \array[]
     * @throws Exception
     */
    protected function getArticleInfo(Entry $entry, $channelId, $refresh = false): array
    {
        $infos = $this->getService()->getArticleInfo($entry, $channelId, true);

        // Add canPublish keys
        foreach ($infos as $channelId => $channelInfo) {
            $channel = $this->getService()->getChannelById($channelId);
            $infos[$channelId]['canPublish'] = $channel->canPublish($entry);
        }

        return $infos;
    }

    /**
     * @return AppleNewsService
     */
    protected function getService(): AppleNewsService
    {
        return Plugin::getInstance()->appleNewsService;
    }

    /**
     * @return AppleNews_ApiService
     */
    protected function getApiService(): AppleNews_ApiService
    {
        return Plugin::getInstance()->appleNewsApiService;
    }
}
