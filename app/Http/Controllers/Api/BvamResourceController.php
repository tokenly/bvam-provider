<?php

namespace App\Http\Controllers\Api;

use App\Handlers\Bvam\Facade\BvamUtil;
use App\Repositories\BvamCategoryRepository;
use App\Repositories\BvamRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Tokenly\LaravelApiProvider\Helpers\APIControllerHelper;
use Exception;

class BvamResourceController extends ApiController
{

    public function getResource(Request $request, BvamRepository $bvam_repository, BvamCategoryRepository $bvam_category_repository, APIControllerHelper $helper, $bvam_hash_filename) {
        $filename_info = BvamUtil::parseBvamFilename($bvam_hash_filename);
        $bvam_hash = $filename_info['bvam_hash'];

        if ($filename_info['type'] == 'bvam') {

            $model = $bvam_repository->findByHash($bvam_hash);
            if (!$model) { return $helper->newJsonResponseWithErrors("BVAM Resource not found with this hash", 404); }
            if (!$model->isActive()) { return $helper->newJsonResponseWithErrors("BVAM Resource not found with this hash", 404); }
            $raw_json = $model['bvam_json'];

        } else if ($filename_info['type'] == 'category') {

            $model = $bvam_category_repository->findByHash($bvam_hash);
            if (!$model) { return $helper->newJsonResponseWithErrors("BVAM Category not found with this hash", 404); }
            if (!$model->isActive()) { return $helper->newJsonResponseWithErrors("BVAM Category not found with this hash", 404); }
            $raw_json = $model['category_json'];

        } else {
            return $helper->newJsonResponseWithErrors("Resource not found", 404);
        }

        return $this->rawJsonResponse($raw_json);
    }

}
