<?php

use Illuminate\Support\Facades\Log;
use App\Models\Bvam;
use App\Handlers\Bvam\Facade\BvamUtil;

/*
* BvamHelper
*/
class BvamHelper
{
    public function __construct() {
    }


    public function newBvam($bvam_data_override_vars=[], $bvam_model_override_vars=[]) {
        $bvam_json_string = $this->defaultBvamJson($bvam_data_override_vars);
        $bvam_vars = array_merge($this->defaultBvamVars($bvam_json_string), $bvam_model_override_vars);
        $bvam_vars = $this->applyHash($bvam_json_string, $bvam_vars);
        $bvam = app('App\Repositories\BvamRepository')->create($bvam_vars);
        return $bvam;
    }



    public function defaultBvamVars($bvam_json_string) {
        $bvam_data = json_decode($bvam_json_string, true);

        return [
            'bvam_json'          => $bvam_json_string,
            'hash'               => '',
            'asset'              => $bvam_data['asset'],
            'txid'               => '',
            'status'             => Bvam::STATUS_DRAFT,
            'first_validated_at' => null,
            'last_validated_at'  => null,
            'confirmations'      => null,
        ];
    }

    public function applyHash($json_string, $bvam_vars) {
        $bvam_vars['hash'] = BvamUtil::createHash($json_string, "T");
        return $bvam_vars;
    }

    public function defaultBvamJson($override_vars=[]) {
        return json_encode($this->defaultBvamData($override_vars), 192);
    }
        
    public function defaultBvamData($override_vars=[]) {
        return array_merge([
            'asset' => 'MYTOKEN',
            'name'  => 'My Token',
            'meta'  => [
                'bvam_version' => '1.0.0',
            ],
        ], $override_vars);
    }
        
}
