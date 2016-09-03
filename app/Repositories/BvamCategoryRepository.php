<?php

namespace App\Repositories;

use App\Models\BvamCategory;
use App\Providers\DateProvider\Facade\DateProvider;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tokenly\LaravelApiProvider\Repositories\APIRepository;

/*
* BvamCategoryRepository
*/
class BvamCategoryRepository extends APIRepository
{

    protected $model_type = 'App\Models\BvamCategory';

    public function createOrUpdateByHash($attributes) {
        try {
            return $this->create($attributes);
        } catch (QueryException $e) {
            if ($e->errorInfo[0] == 23000) {
                // Found a bvam with this hash - update the existing model instead
                return DB::transaction(function() use ($attributes) {
                    $existing_model = $this->prototype_model
                        ->where('hash', '=', $attributes['hash'])
                        ->lockForUpdate()
                        ->first();

                    // force updated date, even though nothing changed
                    $attributes['updated_at'] = DateProvider::now();

                    $this->update($existing_model, $attributes);

                    return $existing_model;
                });
            } else {
                throw $e;
            }
        }
    }

    public function findByHash($hash) {
        $query = $this->prototype_model->newQuery();
        $query->where(['hash' => $hash]);
        return $query->first();
    }

    public function markActiveForCategoryIdAsReplaced($category_id) {
        return $this->prototype_model
            ->where('category_id', '=', $category_id)
            ->whereIn('status', [BvamCategory::STATUS_UNCONFIRMED, BvamCategory::STATUS_CONFIRMED,])
            ->update([
                'status'     => BvamCategory::STATUS_REPLACED,
                'updated_at' => DateProvider::now(),
            ]);
    }

    // ------------------------------------------------------------------------
    
    public function buildFindAllFilterDefinition() {
        return [
            'fields' => [
                'status'   => ['field' => 'status',],
            ],

            'defaults' => ['sort' => ['first_validated_at DESC']],
            'limit' => ['max' => 100],
        ];

    }

}
