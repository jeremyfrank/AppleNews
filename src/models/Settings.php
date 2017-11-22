<?php

namespace craft\applenews\models;

use applenewschannels\MyNewsChannel;
use craft\base\Model;

class Settings extends Model
{
    public $channels = [
        [],

    ];
    public $autoPublishOnSave = true;
    public $limit;
}