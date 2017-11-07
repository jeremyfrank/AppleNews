<?php
namespace craft\applenews\elementactions;
use Craft;
use craft\elements\Entry;
use craft\applenews\services\AppleNewsService;
/**
 * Class AppleNews_PostArticlesElementAction
 *
 * @license https://github.com/pixelandtonic/AppleNews/blob/master/LICENSE
 */
class AppleNews_PostArticlesElementAction extends BaseElementAction
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc IComponentType::getName()
     *
     * @return string
     */
    public function getName()
    {
        return Craft::t('apple-news','Publish to Apple News');
    }

    /**
     * @inheritDoc IElementAction::performAction()
     *
     * @param ElementCriteriaModel $criteria
     *
     * @return bool
     */
    public function performAction(ElementCriteriaModel $criteria)
    {
        /** @var AppleNewsService $service */
        $service = craft()->appleNews;

        // Queue them up
        foreach ($criteria->all() as $entry) {
            /** @var Entry $entry */
            $service->queueArticle($entry);
        }

        return true;
    }
}
