<?php

namespace Jawabapp\Localization\Http\Controllers;

use Illuminate\Http\Request;
use Jawabapp\Localization\Libraries\Localization;
use Jawabapp\Localization\Models\Translation;

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
            $query->where('language_code', $filters['language_code']);
        }

        if(!empty($filters['miss_code'])) {
            $translationTable = app(Translation::class)->getTable();
            $query->whereNotIn('key', function ($q) use ($translationTable, $filters) {
                $q->select('t.key')->from("{$translationTable} AS t")
                    ->where('t.language_code', $filters['miss_code']);
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

        if (!$item->id) {
            return redirect(route('jawab.translation.index', session()->get('query')));
        }

        return view('localization::translation.edit')->with('item', $item);
    }

    public function update(Request $request, $id)
    {
        $values = $request->get('value');

        $item = Translation::find($id);

        if($item->id && $values && is_array($values)) {
            foreach ($values as $langCode => $value) {
                Localization::addKeyToTranslation($item->key, $value, $langCode);
            }

            return redirect(route('jawab.translation.index', session()->get('query')));
        }

        return redirect(route('jawab.translation.index', session()->get('query')));
    }

    public function destroy(Request $request, $id)
    {
        $item = Translation::find($id);

        if (!$item->id) {
            return redirect(route('jawab.translation.index', session()->get('query')));
        }

        $item->delete();

        return redirect(route('jawab.translation.index', session()->get('query')));
    }

    public function generate(Request $request)
    {
        Localization::exportTranslations();

        return view('localization::translation.generate');
    }
}