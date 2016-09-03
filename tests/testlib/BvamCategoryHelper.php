<?php

use App\Models\Bvam;
use App\Models\BvamCategory;
use Illuminate\Support\Facades\Log;
use App\Handlers\Bvam\Facade\BvamUtil;

/*
* BvamCategoryHelper
*/
class BvamCategoryHelper
{
    public function __construct() {
    }


    public function newBvamCategory($category_data_override_vars=[], $model_override_vars=[]) {
        $bvam_category_json_string = $this->defaultBvamCategoryJson($category_data_override_vars);
        $bvam_category_vars = array_merge($this->defaultBvamCategoryVars($bvam_category_json_string), $model_override_vars);
        $bvam_category_vars = $this->applyHash($bvam_category_json_string, $bvam_category_vars);
        $bvam_category = app('App\Repositories\BvamCategoryRepository')->create($bvam_category_vars);
        return $bvam_category;
    }



    public function defaultBvamCategoryVars($bvam_category_json_string) {
        $bvam_category_data = json_decode($bvam_category_json_string, true);

        return [
            'category_json'      => $bvam_category_json_string,
            'hash'               => '',
            'category_id'        => $bvam_category_data['category_id'],
            'title'              => $bvam_category_data['title'],
            'version'            => $bvam_category_data['version'],
            'txid'               => null,
            'owner'              => null,
            'status'             => BvamCategory::STATUS_DRAFT,
            'first_validated_at' => null,
            'last_validated_at'  => null,
            'confirmations'      => null,
        ];
    }

    public function applyHash($json_string, $bvam_vars) {
        $bvam_vars['hash'] = BvamUtil::createHash($json_string, "S");
        return $bvam_vars;
    }

    public function defaultBvamCategoryJson($override_vars=[]) {
        return json_encode($this->defaultBvamCategoryData($override_vars), 192);
    }
        
    public function defaultBvamCategoryData($override_vars=[]) {
        return array_merge([
            'category_id' => 'BVAM Test Category One 201609a',
            'title'       => 'Test Category One',
            'version'     => '1.0.0',
            'properties'  => [
                'program_name' => [
                    'label'     => 'Program Name',
                    'type'      => 'string',
                    'maxLength' => 127,
                ],
            ],
        ], $override_vars);
    }
        
}
