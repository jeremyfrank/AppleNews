<?php

namespace craft\applenews;

use craft\elements\Entry;
use Craft;
use craft\IAppleNewsChannel;
use craft\applenews\services\AppleNewsService;
use craft\applenews\services\AppleNews_ApiService;
use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlManager;
use yii\base\Event;
use craft\applenews\models\Settings;

/**
 * Class AppleNewsPlugin
 *
 * @license https://github.com/pixelandtonic/AppleNews/blob/master/LICENSE
 */

Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {
    $event->rules['apple-news'] = 'appleNews/settings/index';
});

class Plugin extends craft\base\Plugin
{


    /**
     * @return void
     */
    public function init()
    {
        parent::init();


        if ($this->getSettings()->autoPublishOnSave){
            Craft::$app->on('entries.saveEntry', [$this, 'handleEntrySave']);
        }

        Craft::$app->on('entries.beforeDeleteEntry', [$this, 'handleEntryDelete']);
        Craft::$app->getView()->hook('cp.entries.edit.right-pane', [
            $this,
            'addEditEntryPagePane'
        ]);

        $this->setComponents([
            'appleNewsService' => \craft\applenews\services\AppleNewsService::class,
            'appleNewsApiService' => \craft\applenews\services\AppleNews_ApiService::class,
        ]);
    }

    /**
     * @param Event $event
     *
     * @return void
     */
    public function handleEntrySave(Event $event)
    {
        /** @var Entry $entry */
        $entry = $event->params['entry'];

        // Make sure it's not a revision
        if ($entry instanceof EntryVersionModel || $entry instanceof EntryDraftModel) {
            return;
        }

        // Queue it up to be posted to Apple News
        $this->getService()->queueArticle($entry);

    }

    /**
     * @param Event $event
     *
     * @return void
     */
    public function handleEntryDelete(Event $event)
    {
        /** @var Entry $entry */
        $entry = $event->params['entry'];

        $this->getService()->deleteArticle($entry);
    }

    /**
     * @param array &$context
     *
     * @return string
     */
    public function addEditEntryPagePane(&$context)
    {
        /** @var Entry $entry */
        $entry = $context['entry'];

        if (!$entry->id) {
            return '';
        }

        // Find any channels that match this entry
        /** @var IAppleNewsChannel[] $channels */
        $channels = [];
        foreach ($this->getService()->getChannels() as $channel) {
            if ($channel->matchEntry($entry)) {
                $channels[$channel->getChannelId()] = $channel;
            }
        }

        if (!$channels) {
            return '';
        }

        $isVersion = ($entry instanceof EntryVersionModel);
        $isDraft = ($entry instanceof EntryDraftModel);

        // Get any existing records for these channels.
        $infos = $this->getService()->getArticleInfo($entry, array_keys($channels));

        $html = '<div class="pane lightpane meta" id="apple-news-pane">'.
            '<h4 class="heading">'.Craft::t('apple-news','Apple News Channels').'</h4>'.
            '<div class="spinner hidden"></div>';

        foreach ($channels as $channelId => $channel) {
            $state = isset($infos[$channelId]) ? $infos[$channelId]['state'] : null;
            switch ($state) {
                case 'QUEUED':
                    $statusColor = 'grey';
                    $statusMessage = Craft::t('apple-news','The article is in the queue to be published.');
                    break;
                case 'QUEUED_UPDATE':
                    $statusColor = 'grey';
                    $statusMessage = Craft::t('apple-news','A previous version of the article has been published, and an update is currently in the queue to be published.');
                    break;
                case 'PROCESSING':
                    $statusColor = 'orange';
                    $statusMessage = Craft::t('apple-news','The article has been published and is going through processing.');
                    break;
                case 'PROCESSING_UPDATE':
                    $statusColor = 'orange';
                    $statusMessage = Craft::t('apple-news','A previous version of the article is visible in the News app, and an update is currently in processing.');
                    break;
                case 'LIVE':
                    $statusColor = 'green';
                    $statusMessage = Craft::t('apple-news','The article has been published, finished processing, and is visible in the News app.');
                    break;
                case 'FAILED_PROCESSING':
                    $statusColor = 'red';
                    $statusMessage = Craft::t('apple-news','The article failed during processing and is not visible in the News app.');
                    break;
                case 'FAILED_PROCESSING_UPDATE':
                    $statusColor = 'red';
                    $statusMessage = Craft::t('apple-news','A previous version of the article is visible in the News app, but an update failed during processing.');
                    break;
                case 'TAKEN_DOWN':
                    $statusColor = null;
                    $statusMessage = Craft::t('apple-news','The article was previously visible in the News app, but was taken down.');
                    break;
                default:
                    $statusColor = null;
                    $statusMessage = Craft::t('apple-news','The article has not been published yet.');
            }

            $html .= '<div class="data" data-channel-id="'.$channelId.'">'.
                '<h5 class="heading">'.
                "<div class=\"status {$statusColor}\" title=\"{$statusMessage}\"></div>".
                $this->getService()->getChannelName($channelId).
                '</h5>'.
                '<div class="value"><a class="btn menubtn" data-icon="settings" title="'.Craft::t('apple-news','Actions').'"></a>'.
                '<div class="menu">'.
                '<ul>';

            if (in_array($state, [
                'QUEUED_UPDATE',
                'PROCESSING',
                'PROCESSING_UPDATE',
                'LIVE'
            ])) {
                $shareUrl = $infos[$channelId]['shareUrl'];
                $html .= '<li><a data-action="copy-share-url" data-url="'.$shareUrl.'">'.Craft::t('apple-news','Copy share URL').'</a></li>';
            }

            if (!in_array($state, [
                    'QUEUED',
                    'QUEUED_UPDATE'
                ]) && !$isVersion && !$isDraft && $channel->canPublish($entry)
            ) {
                $html .= '<li><a data-action="post-article">'.Craft::t('apple-news','Publish to Apple News').'</a></li>';
            } else {
                // TODO: preview support that ignores canPublish()
                //$html .= '<li><a data-action="post-preview">'.Craft::t('apple-news','Post preview to Apple News').'</a></li>';
            }

            $downloadUrlParams = [
                'entryId' => $entry->id,
                'locale' => Craft::$app->getLocale(),
                'channelId' => $channelId,
            ];

            if ($isVersion) {
                $downloadUrlParams['versionId'] = $entry->versionId;
            } else if ($isDraft) {
                $downloadUrlParams['draftId'] = $entry->draftId;
            }

            $downloadUrl = UrlHelper::getActionUrl('appleNews/downloadArticle', $downloadUrlParams);

            $html .= '<li><a href="'.$downloadUrl.'" target="_blank">'.Craft::t('apple-news','Download for News Preview').'</a></li>'.
                '</ul>'.
                '</div>'.
                '</div>'.
                '</div>';
        };

        $html .= '</div>';

        Craft::$app->templates->includeCssResource('src/css/edit-entry.css');
        Craft::$app->templates->includeJsResource('src/js/ArticlePane.js');

        $infosJs = JsonHelper::encode($infos);
        $versionIdJs = $isVersion ? $entry->versionId : 'null';
        $draftIdJs = $isDraft ? $entry->draftId : 'null';

        $js = <<<EOT
Garnish.\$doc.ready(function() {
	new Craft.AppleNews.ArticlePane(
		{$entry->id},
		'{$entry->locale}',
		{$versionIdJs},
		{$draftIdJs},
		{$infosJs});
});
EOT;
        Craft::$app->templates->includeJs($js);

        return $html;
    }

    /**
     * Adds new bulk actions to the Entries index page.
     *
     * @param string $source The currently selected source
     *
     * @return array The bulk actions
     */
    public function addEntryActions($source)
    {
        $actions = [];

        // Post Articles action
        $canPostArticles = false;
        $userSessionService = Craft::$app->userSession;

        if ($userSessionService->isAdmin()) {
            $canPostArticles = true;
        } else if (preg_match('/^section:(\d+)$/', $source, $matches)) {
            if ($userSessionService->checkPermission('publishEntries:'.$matches[1])) {
                $canPostArticles = true;
            }
        }

        if ($canPostArticles) {
            $actions[] = 'AppleNews_PostArticles';
        }

        return $actions;
    }

    // Protected Methods
    // =========================================================================

    /**
     * @return AppleNewsService
     */
    protected function getService()
    {
        return $this->appleNewsService;
    }


    protected function createSettingsModel()
    {
        return new Settings();
    }

}
