<?php

namespace craft\applenews\controllers;

use craft\applenews\Plugin;
use craft\applenews\services\ApiService;
use craft\applenews\services\DefaultService;
use craft\web\Controller;

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
     * @return DefaultService
     */
    protected function getService(): DefaultService
    {
        return Plugin::getInstance()->appleNewsService;
    }

    /**
     * @return ApiService
     */
    protected function getApiService(): ApiService
    {
        return Plugin::getInstance()->appleNewsApiService;
    }
}
