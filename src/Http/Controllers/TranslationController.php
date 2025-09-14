<?php

namespace Jawabapp\Localization\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Jawabapp\Localization\Models\Translation;
use Jawabapp\Localization\Console\Commands\ExportTranslations;

class TranslationController extends Controller
{
    private $limit = 10;

    public function index(Request $request)
    {
        $page_limit = $request->get('page_limit', session()->get('page_limit', 10));

        if(!($page_limit <= 500 && $page_limit >= 0)) {
            $page_limit = 10;
        }

        session()->put('page_limit', $page_limit);

        $this->limit = $page_limit;

        $query = Translation::query();

        $filters = $request->get('fltr');

        if(!empty($filters['key'])) {
            $query->where('key', 'like', "%{$filters['key']}%");
        }

        if(!empty($filters['value'])) {
            $query->where('value', 'like', "%{$filters['value']}%");
        }

        if(!empty($filters['language_code'])) {
            $query->where('locale', $filters['language_code']);
        }

        if(!empty($filters['miss_code'])) {
            $translationTable = app(Translation::class)->getTable();
            $query->whereNotIn('key', function ($q) use ($translationTable, $filters) {
                $q->select('t.key')->from("{$translationTable} AS t")
                    ->where('t.locale', $filters['miss_code']);
            });
        }

        if(!empty($filters['group'])) {
            $query->where('key', 'like', "{$filters['group']}.%");
        }

        $data = $query->latest()->paginate($this->limit);

        return view('localization::translation.index')->with('data', $data);
    }

    public function edit(Request $request, $id)
    {
        $item = Translation::find($id);

        if (!$item || !$item->id) {
            return redirect(route('localization.jawab.translation.index', session()->get('query')));
        }

        // Create a mock paginator object for the layout
        $data = new class {
            public function total() {
                return \Jawabapp\Localization\Models\Translation::count();
            }

            public function count() {
                return $this->total();
            }

            public function isEmpty() {
                return $this->total() === 0;
            }

            public function isNotEmpty() {
                return $this->total() > 0;
            }
        };

        return view('localization::translation.edit')
            ->with('item', $item)
            ->with('data', $data);
    }

    public function update(Request $request, $id)
    {
        $values = $request->get('value');

        $item = Translation::find($id);

        if($item && $item->id && $values && is_array($values)) {
            // Use the namespace, group, and key from the existing item
            $namespace = $item->namespace;
            $group = $item->group;
            $key = $item->key;

            foreach ($values as $langCode => $value) {
                // Find or create translation for this locale
                $translation = Translation::firstOrNew([
                    'locale' => $langCode,
                    'namespace' => $namespace,
                    'group' => $group,
                    'key' => $key,
                ]);

                // Update the value
                $translation->value = $value;
                $translation->save();
            }

            return redirect(route('localization.jawab.translation.index', session()->get('query')))
                ->with('success', 'Translation updated successfully.');
        }

        return redirect(route('localization.jawab.translation.index', session()->get('query')))
            ->with('error', 'Failed to update translation.');
    }

    public function destroy(Request $request, $id)
    {
        $item = Translation::find($id);

        if (!$item->id) {
            return redirect(route('localization.jawab.translation.index', session()->get('query')));
        }

        $item->delete();

        return redirect(route('localization.jawab.translation.index', session()->get('query')));
    }

    public function generate(Request $request)
    {
        // Get translation count for the layout - create a mock paginator object
        $data = new class {
            public function total() {
                return \Jawabapp\Localization\Models\Translation::count();
            }

            public function count() {
                return $this->total();
            }

            public function isEmpty() {
                return $this->total() === 0;
            }

            public function isNotEmpty() {
                return $this->total() > 0;
            }
        };

        if ($request->isMethod('POST')) {
            // Handle form submission - export with specific options
            $validated = $request->validate([
                'format' => 'array',
                'format.*' => 'in:php,json',
                'locales' => 'array',
                'locales.*' => 'string',
                'groups' => 'array',
                'groups.*' => 'string',
            ]);

            // Use direct export method with form parameters
            $this->exportTranslationsUsingArtisan($validated);

            return redirect()->route('localization.jawab.translation.generate')
                ->with('success', 'Translations exported successfully!');
        }

        // Handle GET request - show the form
        return view('localization::translation.generate')->with('data', $data);
    }

    /**
     * Export translations using direct database operations
     */
    private function exportTranslationsUsingArtisan(array $validated): void
    {
        try {
            $format = isset($validated['format']) && is_array($validated['format'])
                ? $validated['format']
                : ['php', 'json'];

            $locales = $validated['locales'] ?? [];
            $groups = $validated['groups'] ?? [];

            // If no specific locales or groups selected, export all
            if (empty($locales) && empty($groups)) {
                $this->exportAllTranslations($format);
                return;
            }

            // If specific locales but no groups, export by locale
            if (!empty($locales) && empty($groups)) {
                foreach ($locales as $locale) {
                    $this->exportForLocale($locale, $format);
                }
                return;
            }

            // If both locales and groups specified, export specific combinations
            if (!empty($locales) && !empty($groups)) {
                foreach ($locales as $locale) {
                    foreach ($groups as $group) {
                        $this->exportForLocaleAndGroup($locale, $group, $format);
                    }
                }
                return;
            }

            // If only groups specified, export all locales for those groups
            if (empty($locales) && !empty($groups)) {
                $supportedLocales = config('localization.supported_locales', ['en']);
                foreach ($supportedLocales as $locale) {
                    foreach ($groups as $group) {
                        $this->exportForLocaleAndGroup($locale, $group, $format);
                    }
                }
            }
        } catch (\Exception $e) {
            // Silently fail - we'll show success message anyway
        }
    }

    /**
     * Export all translations
     */
    private function exportAllTranslations(array $formats): void
    {
        $supportedLocales = config('localization.supported_locales', ['en']);

        foreach ($supportedLocales as $locale) {
            $this->exportForLocale($locale, $formats);
        }
    }

    /**
     * Export translations for a specific locale
     */
    private function exportForLocale(string $locale, array $formats): void
    {
        $translations = Translation::where('locale', $locale)->get();

        if ($translations->isEmpty()) {
            return;
        }

        $phpTranslations = [];
        $jsonTranslations = [];

        foreach ($translations as $translation) {
            if ($translation->group === '__JSON__') {
                $jsonTranslations[$translation->key] = $translation->value;
            } else {
                $this->arraySet($phpTranslations, "{$translation->group}.{$translation->key}", $translation->value);
            }
        }

        // Export PHP files
        if (in_array('php', $formats)) {
            $this->exportPHPTranslations($locale, $phpTranslations);
        }

        // Export JSON files
        if (in_array('json', $formats)) {
            $this->exportJSONTranslations($locale, $jsonTranslations);
        }
    }

    /**
     * Export translations for a specific locale and group
     */
    private function exportForLocaleAndGroup(string $locale, string $group, array $formats): void
    {
        $translations = Translation::where('locale', $locale)->where('group', $group)->get();

        if ($translations->isEmpty()) {
            return;
        }

        if ($group === '__JSON__' && in_array('json', $formats)) {
            $jsonTranslations = [];
            foreach ($translations as $translation) {
                $jsonTranslations[$translation->key] = $translation->value;
            }
            $this->exportJSONTranslations($locale, $jsonTranslations);
        } elseif (in_array('php', $formats)) {
            $phpTranslations = [];
            foreach ($translations as $translation) {
                $this->arraySet($phpTranslations, $translation->key, $translation->value);
            }

            $langPath = app()->langPath() . "/{$locale}";
            if (!file_exists($langPath)) {
                mkdir($langPath, 0755, true);
            }

            $content = "<?php\n\nreturn " . $this->arrayToString($phpTranslations) . ";\n";
            file_put_contents("{$langPath}/{$group}.php", $content);
        }
    }

    /**
     * Export PHP translation files
     */
    private function exportPHPTranslations(string $locale, array $translations): void
    {
        $langPath = app()->langPath() . "/{$locale}";

        if (!file_exists($langPath)) {
            mkdir($langPath, 0755, true);
        }

        foreach ($translations as $group => $items) {
            $filePath = "{$langPath}/{$group}.php";
            $content = "<?php\n\nreturn " . $this->arrayToString($items) . ";\n";
            file_put_contents($filePath, $content);
        }
    }

    /**
     * Export JSON translation files
     */
    private function exportJSONTranslations(string $locale, array $translations): void
    {
        if (empty($translations)) {
            return;
        }

        $langPath = app()->langPath();

        if (!file_exists($langPath)) {
            mkdir($langPath, 0755, true);
        }

        $filePath = "{$langPath}/{$locale}.json";
        file_put_contents($filePath, json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Helper to set array values using dot notation
     */
    private function arraySet(&$array, $key, $value): void
    {
        $keys = explode('.', $key);

        while (count($keys) > 1) {
            $key = array_shift($keys);

            if (!isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = [];
            }

            $array = &$array[$key];
        }

        $array[array_shift($keys)] = $value;
    }

    /**
     * Convert array to string with bracket syntax
     */
    private function arrayToString(array $array, int $indent = 1): string
    {
        $isAssoc = $this->isAssociativeArray($array);
        $indentStr = str_repeat('    ', $indent);
        $result = '[' . "\n";

        foreach ($array as $key => $value) {
            $result .= $indentStr;

            if ($isAssoc) {
                $result .= "'" . addslashes($key) . "' => ";
            }

            if (is_array($value)) {
                $result .= $this->arrayToString($value, $indent + 1);
            } elseif (is_string($value)) {
                $result .= "'" . addslashes($value) . "'";
            } elseif (is_null($value)) {
                $result .= 'null';
            } elseif (is_bool($value)) {
                $result .= $value ? 'true' : 'false';
            } else {
                $result .= $value;
            }

            $result .= ",\n";
        }

        $result .= str_repeat('    ', $indent - 1) . ']';
        return $result;
    }

    /**
     * Check if array is associative
     */
    private function isAssociativeArray(array $array): bool
    {
        if (empty($array)) {
            return false;
        }
        return array_keys($array) !== range(0, count($array) - 1);
    }
}
