@if($item->id ?? false)
    <?php
    $locales = config('localization.locale_names');
    foreach ($locales as $code => &$locale) {
        $locale = [
            'title' => $locale,
            'translation' => \Jawabapp\Localization\Models\Translation::where('key', $item->key)->where('locale', $code)->first()
        ];
    }

    list($keyNamespace, $keyGroup, $keyItem) = app('translator')->parseKey($item->key);
    ?>
    <div class="alert alert-primary">Namespace : {{ $keyNamespace }}</div>
    <div class="alert alert-primary">Group : {{ $keyGroup }}</div>
    <div class="alert alert-primary">Key : {{ $keyItem }}</div>

    <hr>

    @foreach($locales as $code => $data)
        <div class="form-group row mt-2">
            <label for="value-{{$code}}" class="col-md-2 col-form-label">Translation ({{$data['title']}})</label>

            <div class="col-md-8">
                <div>
                    <textarea id="value-{{$code}}" name="value[{{$code}}]" {{ $code == 'ar' ? 'dir=rtl' : '' }} type="text" class="form-control {{ !empty($errors) && $errors->has("value.{$code}") ? 'is-invalid' : '' }}" rows="5">{{ old("value.{$code}", ($data['translation']->value ?? '')) }}</textarea>

                    @if (!empty($errors) && $errors->has("value.{$code}"))
                        <span class="invalid-feedback"><strong>{{ $errors->first("value.{$code}") }}</strong></span>
                    @endif
                </div>
            </div>
        </div>
    @endforeach

    <div class="form-group row mt-4">
        <div class="col-md-8 offset-md-2">
            <button type="submit" class="btn btn-primary">
                Save
            </button>
            <a href="{{route('localization.jawab.translation.index')}}" class="btn btn-default">Cancel</a>
        </div>
    </div>
@endif