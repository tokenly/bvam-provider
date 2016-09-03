<?php

namespace App\Http\Controllers\Api;

use App\Handlers\Bvam\Facade\BvamUtil;
use App\Http\Requests;
use App\Models\Bvam as BvamModel;
use App\Repositories\BvamCategoryRepository;
use App\Repositories\BvamRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Tokenly\LaravelApiProvider\Filter\IndexRequestFilter;
use Tokenly\LaravelApiProvider\Helpers\APIControllerHelper;

class BvamController extends ApiController
{

    public function create(Request $request, APIControllerHelper $helper) {

        // if bvam is an array - convert it to a string before validation
        $bvam_input = $request->input('bvam');
        if ($bvam_input AND is_array($bvam_input)) {
            $bvam_string = json_encode($bvam_input, 192);
            $request->merge(['bvam' => $bvam_string]);
        }

        // require and validate the bvam parameter
        $request_attributes = $this->getValidatedAttributes($request, [
            'bvam' => 'required|max:20480',
        ]);


        // Make sure BVAM is valid BVAM
        $bvam_string = $request_attributes['bvam'];
        $validation_results = BvamUtil::validateBvamString($bvam_string);
        if (!$validation_results['valid']) {
            $validation_errors = $validation_results['errors'];
            $message = "The BVAM was invalid. ".(count($validation_errors) == 1 ? $validation_errors[0] : "");
            return $helper->newJsonResponseWithErrors($validation_errors, 400, $message);
        }

        // create the bvam model and return a JSON representation
        $bvam_model = BvamUtil::createBvamFromString($bvam_string, $validation_results['data']);
        return $helper->transformResourceForOutput($bvam_model);
    }

    public function listBvam(Request $request, BvamRepository $bvam_repository, APIControllerHelper $helper) {
        // limit to active only
        $filter = IndexRequestFilter::createFromRequest($request, $bvam_repository->buildFindAllFilterDefinition());
        $filter->setOverrideParameters(['status' => BvamModel::STATUS_CONFIRMED]);

        $all_resources = $bvam_repository->findAll($filter);

        $response = $helper->transformResourcesForOutput($all_resources, null, function($serialized_resources) {
            return [
                'count' => count($serialized_resources),
                'items' => $serialized_resources,
            ];
        });

        return $response;
    }

    public function createCategory(Request $request, APIControllerHelper $helper) {
        // if category is an array - convert it to a string before validation
        $category_input = $request->input('category');
        Log::debug("\$category_input=".json_encode($category_input, 192));
        if ($category_input AND is_array($category_input)) {
            $category_string = json_encode($category_input, 192);
            $request->merge(['category' => $category_string]);
        }

        // require and validate the category parameter
        $request_attributes = $this->getValidatedAttributes($request, [
            'category' => 'required|max:20480',
        ]);


        // Make sure BVAM is valid BVAM
        $category_string = $request_attributes['category'];
        $validation_results = BvamUtil::validateBvamCategoryString($category_string);
        if (!$validation_results['valid']) {
            $validation_errors = $validation_results['errors'];
            $message = "The BVAM Category Schema was invalid. ".(count($validation_errors) == 1 ? $validation_errors[0] : "");
            return $helper->newJsonResponseWithErrors($validation_errors, 400, $message);
        }

        // create the category model and return a JSON representation
        $category_model = BvamUtil::createBvamCategoryFromString($category_string, $validation_results['data']);
        return $helper->transformResourceForOutput($category_model);
    }

    public function listBvamCategories(Request $request, BvamCategoryRepository $bvam_category_repository, APIControllerHelper $helper) {
        // limit to active only
        $filter = IndexRequestFilter::createFromRequest($request, $bvam_category_repository->buildFindAllFilterDefinition());
        $filter->setOverrideParameters(['status' => BvamModel::STATUS_CONFIRMED]);

        $all_resources = $bvam_category_repository->findAll($filter);

        $response = $helper->transformResourcesForOutput($all_resources, null, function($serialized_resources) {
            return [
                'count' => count($serialized_resources),
                'items' => $serialized_resources,
            ];
        });

        return $response;
    }

}
