<?php
namespace craft\applenews;

use craft\base\Element;
use\craft\elements\Asset;
use craft\elements\Entry;

/**
 * Class AppleNewsArticle
 *
 * @license https://github.com/pixelandtonic/AppleNews/blob/master/LICENSE
 */
class AppleNewsArticle implements AppleNewsArticleInterface
{
    // Properties
    // =========================================================================

    /**
     * @var Element The associated entry
     */
    protected $entry;

    /**
     * @var array The files that should be included in the article (uri => path)
     */
    protected $files;

    /**
     * @var array The metadata that should be included with the request
     */
    protected $metadata;

    /**
     * @var array The article content, described in Apple News Format
     */
    protected $content;

    // Public Methods
    // =========================================================================

    /**
     * Constructor
     *
     * @param Element $entry
     */
    public function __construct(Entry $entry = null)
    {
        $this->entry = $entry;
    }

    /**
     * Initializes the article
     */
    public function init()
    {
        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * @param array $files The files that should be included in the article (uri => path)
     */
    public function setFiles($files)
    {
        $this->files = $files;
    }

    /**
     * Adds a new file to the article and returns its URL.
     *
     * @param Asset|string $file An {@link Asset} or a path to a file
     *
     * @return string The URL that the article should use to reference the file
     */
    public function addFile($file): string
    {
        if ($file instanceof Asset) {
            // Get the local path to the file (and copy it from its remote source if need be)
            $file = $file->getTransformSource();
        }

        // Get a unique filename for the article
        $filename = basename($file);
        $filename = str_replace('@', '', $filename);
        if (isset($this->files[$filename])) {
            $basename = basename($file, false);
            $ext = pathinfo($file);
            $i = 0;
            do {
                $filename = $basename.'_'.++$i.'.'.$ext;
            } while (isset($this->files[$filename]));
        }

        $this->files[$filename] = $file;

        return 'bundle://'.$filename;
    }

    /**
     * @inheritdoc
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * @param array $metadata The metadata that should be included with the request
     */
    public function setMetadata($metadata)
    {
        $this->metadata = $metadata;
    }

    /**
     * Adds a new metadata property to the article.
     *
     * @param string $name  The metadata property name. Can be a dot-delimited path for defining nested array properties (e.g. `links.sections`)
     * @param mixed  $value The metadata property value
     */
    public function addMetadata($name, $value)
    {
        if ($this->metadata === null) {
            $this->metadata = [];
        }

        $arr = &$this->metadata;

        if (strpos($name, '.') !== false) {
            $path = explode('.', $name);
            $name = array_pop($path);

            foreach ($path as $step) {
                if (!isset($arr[$step])) {
                    $arr[$step] = [];
                }
                $arr = &$arr[$step];
            }
        }

        $arr[$name] = $value;
    }

    /**
     * @inheritdoc
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param array $content The article content, described in Apple News Format
     */
    public function setContent($content)
    {
        $this->content = $content;
    }
}
