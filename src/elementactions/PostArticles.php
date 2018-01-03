<?php
namespace craft\applenews\elementactions;
use Craft;
use craft\applenews\Plugin;
use craft\base\ElementAction;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\elements\Entry;
use craft\applenews\services\DefaultService;

/**
 * Class AppleNews_PostArticlesElementAction
 *
 * @license https://github.com/pixelandtonic/AppleNews/blob/master/LICENSE
 *
 * @property string $triggerLabel
 */
class PostArticles extends ElementAction
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc IComponentType::getName()
     *
     * @return string
     */
    public function getTriggerLabel(): string
    {
        return Craft::t('apple-news','Publish to Apple News');
    }

    /**
     * @inheritDoc IElementAction::performAction()
     *
     * @param ElementQuery $criteria
     *
     * @return bool
     */
    public function performAction(ElementQueryInterface $criteria): bool
    {
        /** @var DefaultService $service */
        $service = Plugin::getInstance()->appleNewsService;

        // Queue them up
        foreach ($criteria->all() as $entry) {
            /** @var Entry $entry */
            $service->queueArticle($entry);
        }

        return true;
    }
}
