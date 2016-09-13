<?php

namespace App\Handlers\Bvam;

use App\Models\Bvam;
use App\Models\BvamCategory;
use App\Providers\DateProvider\Facade\DateProvider;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use JsonSchema\Validator;
use StephenHill\Base58;
use Tokenly\LaravelEventLog\Facade\EventLog;


class BvamUtil
{

    const BVAM_SCHEMA          = 'BVAM-v1.0.0-draft-schema.json';
    const BVAM_CATEGORY_SCHEMA = 'BVAM-Category-v1.0.0-draft-schema.json';

    public function processIssuance($asset, $description, $txid, $confirmations) {
        // Log::debug("processIssuance called for $asset");
        $description_info = $this->parseBvamURLReference($description);
        $type = $description_info['type'];

        $issuance_log_info = [
            'asset'         => $asset,
            'hash'          => $description_info['bvam_hash'],
            'description'   => $description,
            'confirmations' => $confirmations,
            'txid'          => $txid,
            'type'          => $type,
        ];
        // Log::debug("\$issuance_log_info=".json_encode($issuance_log_info, 192));


        switch ($type) {
            case 'bvam':
                // if the bvam provider is external
                //   attempt to scrape from another provider
                if (!$this->isBvamProviderDomain($description_info['host'])) {
                    $this->scrapeBvam($description_info['uri']);
                }

                $confirmed = $this->confirmBvam($description_info['bvam_hash'], $asset, $txid, $confirmations);
                if ($confirmed) {
                    EventLog::info('asset.confirmedBvam', $issuance_log_info);
                } else {
                    EventLog::warning('asset.confirmedBvamFailed', $issuance_log_info);
                }
                break;

            case 'enhanced':
                EventLog::debug('asset.enhanced', $issuance_log_info);
                break;

            default:
                EventLog::debug('asset.unenhanced', $issuance_log_info);
                return;
        }

        return $issuance_log_info;
    }

    // ------------------------------------------------------------------------

    public function processBroadcast($message, $txid, $confirmations) {
        $message_info = $this->parseBvamBroadcastMessage($message);
        // echo "\$message_info: ".json_encode($message_info, 192)."\n";

        $broadcast_log_info = [
            'is_valid'      => $message_info['is_valid'],
            'error'         => $message_info['error'],
            'category_id'   => $message_info['category_id'],
            'version'       => $message_info['version'],
            'hash'          => ($message_info['bvam_info'] ? $message_info['bvam_info']['bvam_hash'] : null),
            'message'       => $message,
            'confirmations' => $confirmations,
            'txid'          => $txid,
        ];

        if ($message_info['is_valid']) {
            $confirmed = $this->confirmBvamCategory($message_info['bvam_info']['bvam_hash'], $message_info['category_id'], $message_info['version'], $txid, $confirmations);
            if ($confirmed) {
                EventLog::info('broadcast.confirmedBvamCategory', $broadcast_log_info);
            } else {
                EventLog::warning('broadcast.confirmedBvamCategoryFailed', $broadcast_log_info);
            }
        } else {
            EventLog::info('broadcast.unparsed', $broadcast_log_info);
        }

    }

    // ------------------------------------------------------------------------

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
            EventLog::logError('bvam.confirm.hashNotFound', "The asset $asset_from_transaction was not confirmed because the hash {$hash} was not found");
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
                $repository->markOtherActiveBvamsForAssetAsReplaced($bvam['asset'], $bvam);
            }

            // apply the update
            $repository->update($bvam, $update_vars);
        });



        // return the updated bvam
        return $bvam;
    }


    public function markBvamCategoryAsUnconfirmed($hash, $category_id_from_transaction, $version, $txid) {
        return $this->confirmBvamCategory($hash, $category_id_from_transaction, $version, $txid, 0);
    }

    public function confirmBvamCategory($hash, $category_id_from_transaction, $version, $txid, $confirmations) {
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

        // if a verified category already exists with a higher version
        //   then don't update this one
        $confirmed_bvam_category = $repository->findConfirmedByCategoryId($category_id_from_transaction);
        if (version_compare($version, $confirmed_bvam_category['version'], '<')) {
            EventLog::logError('bvamCategory.confirm.versionTooLow', "The version $version from the broadcast was less than the confirmed version {$confirmed_bvam_category['version']}");
            return false;
        }




        $update_vars = [
            'txid'              => $txid,
            'version'           => $version,
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
                $repository->markOtherActiveBvamCategoriesForCategoryIdAsReplaced($bvam_category['category_id'], $bvam_category);
            }

            // apply the update
            $repository->update($bvam_category, $update_vars);
        });



        // return the updated bvam
        return $bvam_category;
    }

    // ------------------------------------------------------------------------

    public function parseBvamURLReference($description) {
        $bvam_hash               = null;
        $enhanced_asset_info_url = null;
        $type                    = null;
        $host                    = null;
        $uri                     = null;

        $uri_data = $this->resolveURLFromDescription($description);

        if (!$uri_data['uri']) {
            // try parsing this description as just a bvam filename with no URL
            $trial_filename_data = $this->parseBvamFilename($description);
            if ($trial_filename_data AND in_array($trial_filename_data['type'], ['bvam','category'])) {
                $domain = array_keys($this->myBvamProviderDomainsMap())[0];
                $uri_data = $this->resolveURLFromDescription($domain.'/'.$trial_filename_data['bvam_hash'].'.json');
            }
        }

        if ($uri_data['uri']) {
            $uri        = $uri_data['uri'];
            $host       = $uri_data['host'];
            $url_pieces = $uri_data['url_pieces'];

            $filename = isset($url_pieces['path']) ? basename($url_pieces['path']) : null;
            $filename_data = $this->parseBvamFilename($filename);
            if ($filename_data) {
                if ($filename_data['type'] == 'bvam') {
                    $type = 'bvam';
                    $bvam_hash = $filename_data['bvam_hash'];

                    // use the https version if we inferred a bvam link
                    if ($uri_data['inferred']) {
                        $uri = $uri_data['secure_uri'];
                    }
                } else if ($filename_data['type'] == 'category') {
                    $type = 'category';
                    $bvam_hash = $filename_data['bvam_hash'];

                    // use the https version if we inferred a bvam link
                    if ($uri_data['inferred']) {
                        $uri = $uri_data['secure_uri'];
                    }
                } else if ($filename_data['type'] == 'enhanced') {
                    if (in_array($url_pieces['scheme'], ['http','https'])) {
                        // assume enhanced asset info
                        $type = 'enhanced';
                    }
                }
            } else {
                // not a valid enhanced or bvam filename
                $uri = null;

            }
        }

        return [
            'type'      => $type,
            'bvam_hash' => $bvam_hash,
            'host'      => $host,
            'uri'       => $uri,
        ];

    }

    public function parseBvamFilename($filename) {
        if ($filename === null) { return null; }

        $filename_stub = null;
        if (strtolower(substr($filename, -5)) == '.json') {
            $filename_stub = substr($filename, 0, -5);
        } else if ($this->looksLikeBvamHash($filename)) {
            $filename_stub = $filename;
        }

        if ($filename_stub) {
            if ($this->looksLikeBvamHash($filename_stub)) {
                $first_letter = substr($filename_stub, 0, 1);
                if ($first_letter == 'T') {
                    return [
                        'type'      => 'bvam',
                        'bvam_hash' => $filename_stub,
                    ];
                }
                if ($first_letter == 'S') {
                    return [
                        'type'      => 'category',
                        'bvam_hash' => $filename_stub,
                    ];
                }
            }

            return [
                'type'      => 'enhanced',
                'bvam_hash' => null,
            ];
        }

        return null;
    }

    // ------------------------------------------------------------------------

    public function parseBvamBroadcastMessage($message) {
        $category_id = null;
        $version     = null;
        $bvam_info   = null;
        $error       = null;
        $is_valid    = false;

        // BVAMCS;ACME Car Title;1.0.0;https://bvam-provider.com/bvamcs/S4RGTgM6BJtuu2EUsvsG3GkTpGr2T.json
        $pieces = explode(';', $message, 4);
        if (count($pieces) == 4) {
            list($trial_marker, $trial_id, $trial_version, $trial_url) = $pieces;
            $is_valid = true;
            if ($is_valid AND $trial_marker !== 'BVAMCS') {
                $error = 'Invalid marker';
                $is_valid = false;
            }
            if ($is_valid AND !$this->isValidCategoryID($trial_id)) {
                $error = 'Invalid category ID';
                $is_valid = false;
            }
            if ($is_valid AND !$this->isValidVersion($trial_version)) {
                $error = 'Invalid version';
                $is_valid = false;
            }
            if ($is_valid) {
                $trial_bvam_info = $this->parseBvamURLReference($trial_url);
                if ($trial_bvam_info['type'] != 'category') {
                    $error = 'Invalid BVAM url';
                    $is_valid = false;
                }
            }

            if ($is_valid) {
                $category_id = $trial_id;
                $version = $trial_version;
                $bvam_info = $trial_bvam_info;
            }
        } else {
            $error = 'Not a category schema broadcast message';
        }

        return [
            'is_valid'    => $is_valid,
            'category_id' => $category_id,
            'version'     => $version,
            'bvam_info'   => $bvam_info,
            'error'       => $error,
        ];
    }

    // This should be 128 characters or less in length. This can contain letters, numbers, spaces, dashes and underscores. 
    public function isValidCategoryID($id) {
        if (strlen($id) > 128) { return false; }
        if (strlen($id) == 0) { return false; }
        if (preg_match('!^[a-z0-9_ -]+$!i', $id) == 0) { return false; }

        return true;
    }
    public function isValidVersion($version) {
        if (preg_match('!^([0-9]+)\.([0-9]+)\.([0-9]+)$!', $version) == 0) {
            return false;
        }

        return true;
    }

    // ------------------------------------------------------------------------

    public function isBvamProviderDomain($domain) {
        $provider_domains_map = $this->myBvamProviderDomainsMap();
        return isset($provider_domains_map[strtolower($domain)]);
    }

    public function scrapeBvam($external_uri) {
        throw new Exception("scrapeBvam is unimplemented", 1);
    }


    // ------------------------------------------------------------------------


    // converts token asset_info into a BVAM-like structure
    public function makeBvamFromAssetInfo($asset_info, $enhanced_asset_info=[]) {
        // plain version with no enhanced asset info
        $token_data = [
            'asset'       => $asset_info['asset'],
            'name'        => $asset_info['asset'],
            'description' => $asset_info['description'],
            'meta' => [
                'bvam_version' => '1.0.0',
            ],
        ];

        // check for enhanced data
        if ($enhanced_asset_info) {
            if (isset($enhanced_asset_info['description'])) { $token_data['description'] = $enhanced_asset_info['description']; }
            if (isset($enhanced_asset_info['website'])) { $token_data['website'] = $enhanced_asset_info['website']; }
            if (isset($enhanced_asset_info['image_base64'])) {
                $token_data['images'] = [
                    [
                        'data' => $enhanced_asset_info['image_base64'],
                        'size' => '48x48',
                    ]
                ];
            }
        }

        return $token_data;
    }

    public function resolveURLFromDescription($description) {
        $uri                 = null;
        $host                = null;
        $url_pieces          = [];
        $scheme_was_inferred = false;
        $secure_uri          = null;

        $trial_urls = [$description, 'http://'.$description];
        foreach($trial_urls as $trial_offset => $trial_url) {
            if ($trial_offset > 0) { $scheme_was_inferred = true; }

            $url_pieces = parse_url($trial_url);
            if (isset($url_pieces['scheme']) AND $url_pieces['scheme']) {
                $host = isset($url_pieces['host']) ? $url_pieces['host'] : null;

                $host_looks_valid = false;
                if (strlen($host) > 2) {
                    $dot_offset = strpos($host, '.');
                    if ($dot_offset !== false) {
                        $host_looks_valid = ($dot_offset > 0) AND ($dot_offset < strlen($host) - 1);
                    }
                }

                if ($host_looks_valid AND isset($url_pieces['path'])) {
                    $uri        = $this->assembleURL($url_pieces);
                    $secure_uri = $this->assembleURL(array_merge($url_pieces, ['scheme' => 'https']));
                }

                // if there was a scheme provided, don't try any more versions
                break;
            }
        }

        return [
            'uri'        => $uri,
            'host'       => $host,
            'url_pieces' => $url_pieces,
            'inferred'   => $scheme_was_inferred,
            'secure_uri' => $secure_uri,
        ];
    }

    // ------------------------------------------------------------------------

    protected function myBvamProviderDomainsMap() {
        if (!isset($this->provider_domains_map)) {
            $this->provider_domains_map = [];
            foreach (explode(',', env('MY_BVAM_PROVIDER_DOMAINS', '')) as $value) {
                $value = strtolower(trim($value));
                if (strlen($value) > 0) {
                    $this->provider_domains_map[$value] = true;
                }
            }
        }
        return $this->provider_domains_map;
    }

    protected function looksLikeBvamHash($string) {
        $first_letter = substr($string, 0, 1);
        if (
            ($first_letter == 'S' OR $first_letter == 'T')
            AND strlen($string) >= 28 AND strlen($string) <= 30
            AND preg_match('!^[a-zA-Z0-9]+$!', $string)
        ) {
            return true;
        }
        return false;
    }


    protected function assembleURL($url_pieces) {
        return 
            $url_pieces['scheme'].'://'
            .$url_pieces['host']
            .(isset($url_pieces['port']) ? ':'.$url_pieces['port'] : '')
            .$url_pieces['path']
            ;
    }

    
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
