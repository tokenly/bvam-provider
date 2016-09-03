<?php

namespace App\Handlers\Bvam;

use App\Models\Bvam;
use App\Models\BvamCategory;
use App\Providers\DateProvider\Facade\DateProvider;
use Exception;
use Illuminate\Support\Facades\DB;
use JsonSchema\Validator;
use StephenHill\Base58;
use Tokenly\LaravelEventLog\Facade\EventLog;


class BvamUtil
{

    const BVAM_SCHEMA          = 'BVAM-v1.0.0-draft-schema.json';
    const BVAM_CATEGORY_SCHEMA = 'BVAM-Category-v1.0.0-draft-schema.json';

    public function createBvamFromString($bvam_string, $valid_bvam_object=null) {
        if (!is_string($bvam_string)) {
            throw new Exception("BVAM data was not a string", 1);
        }

        // validate against the bvam schema
        if ($valid_bvam_object === null) {
            $validation_results = $this->validateBvamString($bvam_string);
            if (!$validation_results['valid']) {
                throw new Exception("Invalid BVAM.  ".implode(", ", $validation_results['errors']), 1);
            }
            $valid_bvam_object = $validation_results['data'];
        }


        // compute the hash
        $hash = $this->createHash($bvam_string, "T");

        // build create vars
        $create_vars = [
            'bvam_json' => $bvam_string,
            'hash'      => $hash,
            'asset'     => isset($valid_bvam_object->asset) ? $valid_bvam_object->asset : null,
            'status'    => Bvam::STATUS_DRAFT,
        ];

        $repository = app('App\Repositories\BvamRepository');
        return $repository->createOrUpdateByHash($create_vars);
    }


    public function validateBvamString($bvam_string) {
        $results = $this->decodeJSONString($bvam_string);
        if (!$results['valid']) { return $results; }

        return $this->validateAgainstSchema($results['data'], self::BVAM_SCHEMA);
    }


    // ------------------------------------------------------------------------

    public function validateBvamCategoryString($bvam_category_string) {
        $results = $this->decodeJSONString($bvam_category_string);
        if (!$results['valid']) { return $results; }

        return $this->validateAgainstSchema($results['data'], self::BVAM_CATEGORY_SCHEMA);
    }

    public function createBvamCategoryFromString($bvam_category_string, $valid_json_object=null) {
        if (!is_string($bvam_category_string)) {
            throw new Exception("BVAM category data was not a string", 1);
        }

        // validate against the bvam schema
        if ($valid_json_object === null) {
            $validation_results = $this->validateBvamCategoryString($bvam_category_string);
            if (!$validation_results['valid']) {
                throw new Exception("Invalid BVAM Category.  ".implode(", ", $validation_results['errors']), 1);
            }
            $valid_json_object = $validation_results['data'];
        }


        // compute the hash
        $hash = $this->createHash($bvam_category_string, "S");

        // build create vars
        $create_vars = [
            'category_json' => $bvam_category_string,
            'hash'          => $hash,

            'category_id'   => isset($valid_json_object->category_id) ? $valid_json_object->category_id : null,
            'title'         => isset($valid_json_object->title)       ? $valid_json_object->title       : null,
            'version'       => isset($valid_json_object->version)     ? $valid_json_object->version     : null,

            'status'        => Bvam::STATUS_DRAFT,
        ];

        $repository = app('App\Repositories\BvamCategoryRepository');
        return $repository->createOrUpdateByHash($create_vars);
    }

    // ------------------------------------------------------------------------

    public function createHash($string, $prefix="T") {
        $sha256_binary = hash('sha256', $string, true);
        $hash160_binary = hash('ripemd160', $sha256_binary, true);

        $base58Encoder = new Base58();
        $base58 = $base58Encoder->encode($hash160_binary);

        return $prefix.$base58;
    }

    // ------------------------------------------------------------------------
    
    public function markBvamAsUnconfirmed($hash, $asset_from_transaction, $txid) {
        return $this->confirmBvam($hash, $asset_from_transaction, $txid, 0);
    }

    public function confirmBvam($hash, $asset_from_transaction, $txid, $confirmations) {
        $repository = app('App\Repositories\BvamRepository');
        
        $bvam = $repository->findByHash($hash);
        if (!$bvam) {
            // not found...
            return false;
        }

        // verify the asset matches
        if ($bvam['asset'] != $asset_from_transaction) {
            EventLog::logError('bvam.confirm.assetMismatch', "The asset $asset_from_transaction from the transaction did not match the expected asset {$bvam['asset']}");
            return false;
        }


        $update_vars = [
            'txid'              => $txid,
            'status'            => ($confirmations > 0 ? Bvam::STATUS_CONFIRMED : Bvam::STATUS_UNCONFIRMED),
            'confirmations'     => $confirmations,
            'last_validated_at' => DateProvider::now(),
        ];
        if ($bvam['first_validated_at'] == null) {
            $update_vars['first_validated_at'] = DateProvider::now();
        }

        DB::transaction(function() use ($repository, $bvam, $update_vars, $confirmations) {
            if ($confirmations > 0) {
                // make all previous active bvams for this asset replaced
                $repository->markActiveBvamsForAssetAsReplaced($bvam['asset']);
            }

            // apply the update
            $repository->update($bvam, $update_vars);
        });



        // return the updated bvam
        return $bvam;
    }


    public function markBvamCategoryAsUnconfirmed($hash, $category_id_from_transaction, $txid) {
        return $this->confirmBvamCategory($hash, $category_id_from_transaction, $txid, 0);
    }

    public function confirmBvamCategory($hash, $category_id_from_transaction, $txid, $confirmations) {
        $repository = app('App\Repositories\BvamCategoryRepository');
        
        $bvam_category = $repository->findByHash($hash);
        if (!$bvam_category) {
            // not found...
            return false;
        }

        // verify the category id matches
        if ($bvam_category['category_id'] != $category_id_from_transaction) {
            EventLog::logError('bvamCategory.confirm.categoryMismatch', "The category_id $category_id_from_transaction from the transaction did not match the expected category id {$bvam_category['category_id']}");
            return false;
        }


        $update_vars = [
            'txid'              => $txid,
            'status'            => ($confirmations > 0 ? BvamCategory::STATUS_CONFIRMED : BvamCategory::STATUS_UNCONFIRMED),
            'confirmations'     => $confirmations,
            'last_validated_at' => DateProvider::now(),
        ];
        if ($bvam_category['first_validated_at'] == null) {
            $update_vars['first_validated_at'] = DateProvider::now();
        }

        DB::transaction(function() use ($repository, $bvam_category, $update_vars, $confirmations) {
            if ($confirmations > 0) {
                // make all previous active bvam categories for this category ID as replaced
                $repository->markActiveForCategoryIdAsReplaced($bvam_category['category_id']);
            }

            // apply the update
            $repository->update($bvam_category, $update_vars);
        });



        // return the updated bvam
        return $bvam_category;
    }

    // ------------------------------------------------------------------------
    
    protected function decodeJSONString($json_string) {
        $json_object = @json_decode($json_string, false);
        if ($json_object === null) {
            return [
                'valid'  => false,
                'data'   => null,
                'errors' => ["Invalid JSON: ".json_last_error_msg()],
            ];
        }

        return [
            'valid'  => true,
            'data'   => $json_object,
            'errors' => [],
        ];
    }

    protected function validateAgainstSchema($json_object, $schema_filename) {
        $schema_filepath = realpath(base_path('resources/schema/'.$schema_filename));

        $validator = new Validator();
        $validator->check($json_object, (object)['$ref' => 'file://'.$schema_filepath]);

        if ($validator->isValid()) {
            return [
                'valid'  => true,
                'data'   => $json_object,
                'errors' => [],
            ];
        } else {
            $errors = [];
            foreach ($validator->getErrors() as $error) {
                $error_string = '';
                if ($error['constraint'] == 'required') {
                    $error_string = $error['message'];
                } else {
                    $error_string = "For property {$error['property']}, {$error['message']}";
                }
                $errors[] = $error_string;
            }
            return [
                'valid'  => false,
                'data'   => $json_object,
                'errors' => $errors,
            ];
        }
    }

}
