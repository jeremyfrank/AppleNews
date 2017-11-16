<?php

namespace craft\applenews\tasks;

use Craft;
use craft\applenews\Plugin;
use craft\applenews\services\AppleNewsService;
use craft\db\Query;
use craft\queue\BaseJob;

/**
 * Class AppleNews_PostQueuedArticlesTask
 *
 * @license https://github.com/pixelandtonic/AppleNews/blob/master/LICENSE
 */
class AppleNews_PostQueuedArticlesJob extends BaseJob
{
    // Properties
    // =========================================================================

    /**
     * @var array[] Info needed for each step
     */
    private $_stepInfo;

    // Public Methods
    // =========================================================================

    /**
     * @return void
     */
    public function execute($queue): void
    {
        //get total step info
        $limit = Plugin::getInstance()->getSettings()->limit;

        // Get the rows
        $rows = (new Query())
            ->select('id, entryId, locale, channelId')
            ->from('applenews_articlequeue')
            ->limit($limit)
            ->all();

        // If there are any more, create a follow-up task.
        if ($limit) {
            $total = (new Query())
                ->from('applenews_articlequeue')
                ->count('id');
            if ($total > $limit) {
                $this->getService()->createPostQueuedArticlesJob();
            }
        }
        $this->_stepInfo = [];

        foreach ($rows as $row) {
            $entryId = $row['entryId'];
            if (!isset($this->_stepInfo[$entryId])) {
                $this->_stepInfo[$entryId] = [
                    'entryId' => $row['entryId'],
                    'locale' => $row['locale'],
                    'channelIds' => [],
                ];
            }
            $this->_stepInfo[$entryId]['channelIds'][] = $row['channelId'];
        }

        $totalSteps = count($this->_stepInfo);

        // execute posting article
        for ($step = 0; $step < $totalSteps; $step++) {
            $this->setProgress($queue, $step / $totalSteps);
            $info = array_shift($this->_stepInfo);
            $entry = Craft::$app->getEntries()->getEntryById($info['entryId'], $info['locale']);

            if ($entry) {
                $this->getService()->postArticle($entry, $info['channelIds']);
            }
        }
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritDoc ITask::getDescription()
     *
     * @return string
     */
    protected function defaultDescription(): string
    {
        return Craft::t('apple-news', 'Publishing articles to Apple News');
    }

    /**
     * @return AppleNewsService
     */
    protected function getService(): AppleNewsService
    {
        return Plugin::getInstance()->appleNewsService;
    }

}
