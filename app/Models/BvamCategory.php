<?php

namespace App\Models;

use Tokenly\LaravelApiProvider\Model\APIModel;
use Exception;

class BvamCategory extends APIModel {

    const STATUS_DRAFT       = 1;
    const STATUS_UNCONFIRMED = 2;
    const STATUS_CONFIRMED   = 3;
    const STATUS_REPLACED    = 4;

    protected $api_attributes = ['filename','uri','category_id','title','version','txid','owner','last_updated',];

    // ------------------------------------------------------------------------

    public function isActive() {
        return ($this['status'] == self::STATUS_CONFIRMED);
    }

    // ------------------------------------------------------------------------

    public function getFilenameAttribute() {
        return $this['hash'].'.json';
    }

    public function getUriAttribute() {
        return env('SITE_HOST').'/'.$this->getFilenameAttribute();
    }

    public function getLastUpdatedAttribute() {
        return $this['updated_at'];
    }

}
