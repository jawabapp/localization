<?php
/**
 * Created by PhpStorm.
 * User: qanah
 * Date: 10/31/17
 * Time: 8:59 PM
 */

namespace Jawabapp\Localization\Libraries;

use Jawabapp\Localization\Models\Translation;
use App;

class Localization
{
    public static function routePrefix($locale = null) {

        $currentLocale = null;

        if (empty($locale) || !is_string($locale)) {
            $locale = request()->segment(1);
        }

        if (array_key_exists($locale, config('localization.locales', []))) {
            $currentLocale = $locale;
        } else {
            $locale = null;
            $currentLocale = config('app.fallback_locale');
        }

        App::setLocale($currentLocale);

        return $locale;
    }

    public static function generate($class, &$attributes, $old = null)
    {
        $generate = false;

        $key = uniqid();

        foreach ($attributes as $filed => $value) {
            if (preg_match('/_key$/', $filed)) {
                if($value) {
                    $attributes[$filed] = empty($old[$filed]) ? self::getTranslationKey($class, $filed, $key) : $old[$filed];

                    self::addKeyToTranslation($attributes[$filed], $value);

                    $generate = true;
                } else {
                    $attributes[$filed] = '';

                    if(!empty($old[$filed])) {
                        try {
                            Translation::where('key', $old[$filed])->delete();
                        } catch (\Exception $e) {
                        }

                        $generate = true;
                    }
                }
            }
        }

        if ($generate && !App::runningInConsole())
        {
            self::exportTranslations();
        }
    }

    public static function delete($old) {

        $generate = false;

        foreach ($old as $filed => $value) {
            if (preg_match('/_key$/', $filed)) {
                try {
                    Translation::where('key', $old[$filed])->delete();
                } catch (\Exception $e) {
                }

                $generate = true;
            }
        }

        if ($generate && !App::runningInConsole())
        {
            self::exportTranslations();
        }
    }

    public static function addKeyToTranslation($key, $value = null, $languageCode = null)
    {
        $flag = false;

        if(is_null($languageCode)) {
            $languageCode = 'en';
        }

        list($namespace, $group, $item) = app('translator')->parseKey($key);

        if(in_array($group, config('localization.groups', []))) {
            $flag = true;
        }

        if($flag) {

            // check if translation not exists
            $existsTransKey = Translation::where('key', $key)->where('language_code', $languageCode)->first();
            if(!$existsTransKey) {
                Translation::create([
                    'key' => $key,
                    'language_code' => $languageCode,
                    'value' => $value ?? $item
                ]);
            } else {
                if(isset($value)) {
                    $existsTransKey->value = $value;
                    $existsTransKey->save();
                }
            }
        }

        return $flag;
    }

    public static function getTranslationFileName($class)
    {
        return 'db_' . strtolower(str_replace('App\\Models\\', '', $class));
    }

    private static function getTranslationKey($class, $filed, $key)
    {
        return self::getTranslationFileName($class) . '.' . $filed . '_' . $key;
    }

    public static function exportTranslations()
    {

        $languages = array();

        foreach (config('localization.locales') as $code => $locale) {

            $translations = Translation::where('language_code', $code)->get();

            foreach ($translations as $translation) {
                $languages[$code][$translation->key] = $translation->value;
            }
        }

        foreach ($languages as $code => $language) {
            self::exportPHPTranslation($code, $language);
            //self::exportJSTranslation($code, $language);
        }

    }

    private static function exportPHPTranslation($code, array $language) {

        $langPath = App::langPath() . DIRECTORY_SEPARATOR . $code . DIRECTORY_SEPARATOR;

        $db_trans = $language['db'] ?? [];

        if(isset($language['db'])) {
            unset($language['db']);
        }

        $language += $db_trans;

        /*
         * export trans
         */
        $translations = [];

        foreach ($language as $key => $value) {
            self::getFolder($translations, $key, $value);
        }

        foreach ($translations as $group => $trans) {
            if($group == 'validation') {
                self::prepareValidationArray($trans);
            }

            self::exportPHP($langPath . $group . '.php', $trans);
        }
    }

    private static function exportPHP($fullPath, array $trans) {

        if (!is_dir(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0777, true);
        }

        file_put_contents($fullPath, '<?php' . "\n\n" . 'return ' . var_export($trans, true) . ';');
    }

    private static function prepareValidationArray(array &$trans) {

        foreach ($trans as $key => $value) {

            $keys = explode('.', $key);

            switch (count($keys)) {
                case 2:
                    list($k1, $k2) = $keys;
                    $trans[$k1][$k2] = $value;
                    unset($trans[$key]);
                    break;
                case 3:
                    list($k1, $k2, $k3) = $keys;
                    $trans[$k1][$k2][$k3] = $value;
                    unset($trans[$key]);
                    break;
            }

        }

    }

    private static function exportJSTranslation($code, array $language) {

        $langPath = base_path('public/js/locales') . DIRECTORY_SEPARATOR;

        /*
         * export db trans
         */
        $db_trans = $language['db'] ?? [];
        self::exportJS($langPath . 'db_' . $code . '.js', $db_trans);

        if(isset($language['db'])) {
            unset($language['db']);
        }

        /*
         * export static trans
         */
        $static_trans = $language ?? [];
        self::exportJS($langPath . $code . '.js', $static_trans);
    }

    private static function exportJS($fullPath, array $trans) {

        if (!is_dir(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0777, true);
        }

        file_put_contents($fullPath, "Object.assign(window.translations, " . json_encode($trans) . ");");
    }

    private static function getFolder(&$array, $key, $value)
    {
        list($namespace, $group, $item) = app('translator')->parseKey($key);

        if($namespace == '*') {
            $array[$group][$item] = $value;
        } else {
            #TODO namespace folder
        }
    }

}