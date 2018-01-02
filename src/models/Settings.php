<?php

namespace craft\applenews\models;

use applenewschannels\MyNewsChannel;
use craft\base\Model;

class Settings extends Model
{
    public $channels = [
        [
            'class' => MyNewsChannel::class,
            'channelId' => '22b3609e-f0a6-4c2a-8002-c44cff67868c',
            'apiKeyId' => '680865e9-e33a-487f-b699-06d2d59cb4de',
            'apiSecret' => 'xddOF2onwHc56rrh9MUHKWuvRNnd1oqWPHHwmk3JXl8=',
        ],

    ];
    public $autoPublishOnSave = false;
    public $limit = 10;
}