<?php

namespace App\Handlers\AssetInfo;

use App\Handlers\Bvam\Facade\BvamUtil;
use Exception;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Tokenly\LaravelEventLog\Facade\EventLog;

/*
* EnhancedAssetInfoResolver
*/
class EnhancedAssetInfoResolver
{

    const MAX_PNG_SIZE = 20480;

    public function __construct() {
    }

    public function resolveExtendedAssetInfoFromDescription($description) {
        $is_enhanced = false;
        $is_bvam = false;
        $had_error = false;
        $enhanced_data = [];

        $url_data = BvamUtil::resolveURLFromDescription($description);
        if ($url_data['uri']) {
            $filename = isset($url_data['url_pieces']['path']) ? basename($url_data['url_pieces']['path']) : null;
            $filename_data = BvamUtil::parseBvamFilename($filename);
            if ($filename_data['type'] == 'enhanced') {
                $is_enhanced = true;

                try {
                    $enhanced_data = $this->loadEnhancedData($url_data['uri']);
                } catch (Exception $e) {
                    EventLog::logError('enhancedAssetError.loadData', $e, ['description' => $description, 'url' => $url_data['uri']]);
                    $had_error = true;
                }

            } else if ($filename_data['type'] == 'bvam') {
                $is_bvam = true;
            }
        }

        return [
            'is_enhanced'   => $is_enhanced,
            'is_bvam'       => $is_bvam,
            'enhanced_data' => $enhanced_data,
            'had_error'     => $had_error,
        ];
    }


    public function loadEnhancedData($url, $timeout=20) {
        // load the data
        $client = $this->buildGuzzleClient();

        $response = $client->request('GET', $url, [
            'timeout' => $timeout,
        ]);

        // decode JSON
        $json_string = $response->getBody()->getContents();
        $json = @json_decode($json_string, true);
        if ($json === null) {
            throw new Exception("Failed to parse json with error \"".json_last_error_msg()."\".  JSON was \"".substr($json_string, 0, 80).(strlen($json_string) > 80 ? '...' : '')."\"", 1);
        }

        // resolve the image
        if (isset($json['image']) AND strlen($json['image'])) {
            try {
                $json['image_base64'] = $this->imageToBase64($json['image'], $timeout);
            } catch (Exception $e) {
                EventLog::logError('enhancedAssetError.loadImage', $e, ['url' => $json['image']]);
            }
        }

        return $json;
    }

    public function imageToBase64($url, $timeout=20) {
        $client = $this->buildGuzzleClient();

        $response = $client->request('GET', $url, [
            'timeout' => $timeout,
        ]);

        $png_raw_data = $response->getBody()->getContents();

        if ($this->isPNG($png_raw_data)) {
            $png_string = 'data:image/png;base64,'.base64_encode($png_raw_data);

            if (strlen($png_string) > self::MAX_PNG_SIZE) {
                throw new Exception("PNG image too large at ".strlen($png_string)." characters", 1);
            }

            return $png_string;
        } else {
            throw new Exception("Loaded image at $url was not a PNG", 1);
        }

    }

    // ------------------------------------------------------------------------

    protected function buildGuzzleClient() {
        return app('enhancedAssetInfo.guzzle');
    }
    
    protected function isPNG($pict) {
      return (bin2hex($pict[0]) == '89' && $pict[1] == 'P' && $pict[2] == 'N' && $pict[3] == 'G');
    }

}
