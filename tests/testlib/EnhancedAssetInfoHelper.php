<?php

use Illuminate\Support\Facades\Log;

/*
* EnhancedAssetInfoHelper
*/
class EnhancedAssetInfoHelper
{
    public function __construct() {
    }

    public function getEnhancedAssetInfo($override_vars=[]) {
        $out = array_merge([
            'asset'       => 'MYTOKEN',
            'description' => 'This is a long description of my token',
            'image'       => 'http://imagesite.foo/tokenimage.png',
            'website'     => 'http://tokensite.foo/',
        ], $override_vars);

        foreach (array_keys($override_vars) as $key) {
            if (!isset($override_vars[$key])) {
                unset($out[$key]);
            }
        }

        return $out;
    }

    public function getEnhancedAssetInfoAsJson($override_vars=[]) {
        return json_encode($this->getEnhancedAssetInfo($override_vars), 192);
    }
    

    public function samplePNGImageBinary() {
        // a tiny blue png
        return hex2bin('89504e470d0a1a0a0000000d49484452000000100000001008020000009091683600000019494441542891636431fecf400a602249f5a886510d434a0300e6360156cfa0933d0000000049454e44ae426082');
    }

    public function samplePNGImageBase64() {
        return 'data:image/png;base64,'.base64_encode($this->samplePNGImageBinary());
    }        
}
