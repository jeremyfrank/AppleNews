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
 * Class SettingsController
 *
 * @license https://github.com/pixelandtonic/AppleNews/blob/master/LICENSE
 */
class SettingsController extends Controller
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
     * Settings index
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
