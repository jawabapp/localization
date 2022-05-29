<?php
/**
 * Created by PhpStorm.
 * User: qanah
 * Date: 11/29/17
 * Time: 9:39 AM
 */

namespace Jawabapp\Localization\Libraries;

use Illuminate\Translation\Translator as LaravelTranslator;

class Translator extends LaravelTranslator
{

    /**
     * Get the translation for the given key.
     *
     * @param  string  $key
     * @param  array   $replace
     * @param  string  $locale
     * @return string
     */
    public function get($key, array $replace = array(), $locale = null, $fallback = true)
    {
        // Get without fallback
        $result = parent::get($key, $replace, $locale, false);

        if($result === $key){
            $this->notifyMissingKey($key);

            // Reget with fallback
            $result = parent::get($key, $replace, $locale, $fallback);
        }

        return $result;
    }

    protected function notifyMissingKey($key)
    {
        $allowMissingKey = true;

        if (0 === strpos($key, 'validation')) {
            $allowMissingKey = false;
        }

        if($allowMissingKey) {
            Localization::addKeyToTranslation($key);
        }
    }

}