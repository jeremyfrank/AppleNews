<?php

namespace craft\applenews\controllers;
use Composer\Package\Archiver\ZipArchiver;
use Craft;
use craft\applenews\Plugin;
use craft\elements\Entry;
use craft\applenews\services\AppleNewsService;
use craft\helpers\FileHelper;
use craft\helpers\StringHelper;
use craft\web\Controller;
use yii\base\Exception;
use yii\helpers\Json;
use yii\web\HttpException;

/**
 * Class AppleNewsController
 *
 * @license https://github.com/pixelandtonic/AppleNews/blob/master/LICENSE
 */
class AppleNewsController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * Downloads a bundle for Apple News Preview.
     */
    public function actionDownloadArticle()
    {
        $entry = $this->getEntry(true);
        $channelId = Craft::$app->getRequest()->getRequiredParam('channelId');
        $channel = $this->getService()->getChannelById($channelId);


        if (!$channel->matchEntry($entry)) {
            throw new Exception('This channel does not want anything to do with this entry.');
        }

        $article = $channel->createArticle($entry);

        // Prep the zip staging folder
        $zipDir = Craft::$app->getPath()->getTempPath().StringHelper::UUID();

        $zipContentDir = $zipDir.'/'.$entry->slug;
        FileHelper::createDirectory($zipDir);
        FileHelper::createDirectory($zipContentDir);

        // Create article.json
        $json = Json::encode($article->getContent());
        FileHelper::writeToFile($zipContentDir.'/article.json', $json);

        // Copy the files
        $files = $article->getFiles();
        if ($files) {
            foreach ($files as $uri => $path) {
                FileHelper::copyDirectory($path, $zipContentDir.'/'.$uri);
            }
        }

        $zipFile = $zipDir.'.zip';
        FileHelper::createDirectory($zipFile);

        ZipArchiver::archive($zipFile, $zipDir, $zipDir);

        Craft::$app->getRequest()->sendFile($zipFile, FileHelper::getFileContents($zipFile), [
            'filename' => $entry->slug.'.zip',
            'forceDownload' => true
        ], false);

        FileHelper::clearDirectory($zipDir);
        FileHelper::removeFile($zipFile);
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
        $channelId = Craft::$app->getRequest()->getParam('channelId');
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
    protected function getEntry($acceptRevision = false)
    {
        $entryId = Craft::$app->getRequest()->getRequiredParam('entryId');
        $localeId = Craft::$app->getRequest()->getRequiredParam('locale');

        if ($acceptRevision) {
            $versionId = Craft::$app->getRequest()->getParam('versionId');
            $draftId = Craft::$app->getRequest()->getParam('draftId');
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
     * @param Entry $entry
     * @param string     $channelId
     * @param bool       $refresh
     *
     * @return \array[]
     * @throws Exception
     */
    protected function getArticleInfo(Entry $entry, $channelId, $refresh = false)
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
    protected function getService()
    {
        return Plugin::getInstance()->appleNewsService;
    }
}
