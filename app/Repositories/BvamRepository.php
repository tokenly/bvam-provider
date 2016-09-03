<?php

namespace App\Repositories;

use App\Models\Bvam;
use App\Providers\DateProvider\Facade\DateProvider;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tokenly\LaravelApiProvider\Repositories\APIRepository;

/*
* BvamRepository
*/
class BvamRepository extends APIRepository
{

    protected $model_type = 'App\Models\Bvam';

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

    public function markActiveBvamsForAssetAsReplaced($asset) {
        return $this->prototype_model
            ->where('asset', '=', $asset)
            ->whereIn('status', [Bvam::STATUS_UNCONFIRMED, Bvam::STATUS_CONFIRMED,])
            ->update([
                'status'     => Bvam::STATUS_REPLACED,
                'updated_at' => DateProvider::now(),
            ]);
    }

    public function findByActiveBvamByAsset($asset) {
        $query = $this->prototype_model->newQuery();
        $query->where(['asset' => $asset, 'status' => Bvam::STATUS_CONFIRMED]);
        $query->orderBy('first_validated_at', 'DESC');
        return $query->first();
    }

    public function findByHash($hash) {
        $query = $this->prototype_model->newQuery();
        $query->where(['hash' => $hash]);
        return $query->first();
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
