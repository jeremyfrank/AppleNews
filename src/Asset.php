<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */


namespace craft\applenews;

use craft\web\AssetBundle;


class Asset extends AssetBundle
{
    /**
     * @var string The JS filename (sans "[.min].js") to register
     */

    public $jsFile = 'ArticlePane';

    /**
     * @inheritdoc
     */
    public function init()
    {
        // define the path that your publishable resources live
        $this->sourcePath = __DIR__.'/resources';
        // define the relative path to CSS/JS files that should be registered with the page
        // when this asset bundle is registered
        // define the dependencies
        $this->depends = [
            Asset::class,
        ];
        $this->js = [
            $this->jsFile.$this->dotJs(),
        ];
        $this->css = [
            'edit-entry.min.css',
        ];
        parent::init();
    }
}