<?php

namespace craft\applenews\models;

use craft\base\Model;

class Settings extends Model
{
    public $channels = [];
    public $autoPublishOnSave = true;

    public function rules()
    {


    }
}