@extends('localization::layouts.app')

@section('content')
    <div class="card">
        <div class="card-body pb-0">
            <form>
                <div class="jumbotron m-0 p-0">
                    <div class="row">
                        <div class="col-md-6">
                            <input type="text" class="form-control mb-2" name="fltr[key]" value="{{ request('fltr')['key'] ?? '' }}" placeholder="Translation Key">
                        </div>
                        <div class="col-md-6">
                            <input type="text" class="form-control mb-2" name="fltr[value]" value="{{ request('fltr')['value'] ?? '' }}" placeholder="Translation Value">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <select name="fltr[language_code]" class="form-control mb-2">
                                <option value="">All Languages</option>
                                @foreach(config('localization.locales') as $code => $locale)
                                    <option value="{{ $code }}" {{ !empty(request('fltr')['language_code']) && request('fltr')['language_code'] == $code ? 'selected' : '' }}>{{ $locale }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <select name="fltr[group]" class="form-control mb-2">
                                <option value="">All Groups</option>
                                @foreach(config('localization.groups') as $group)
                                    <option value="{{ $group }}" {{ !empty(request('fltr')['group']) && request('fltr')['group'] == $group ? 'selected' : '' }}>{{ $group }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <select name="fltr[miss_code]" class="form-control mb-2">
                        <option value="">Miss Translation</option>
                        @foreach(config('localization.locales') as $code => $locale)
                            <option value="{{ $code }}" {{ !empty(request('fltr')['miss_code']) && request('fltr')['miss_code'] == $code ? 'selected' : '' }}>{{ $locale }}</option>
                        @endforeach
                    </select>

                    <div class="row mt-2">
                        <div class="col-md-6 text-start">
                            <div class="py-2"><b>Count</b> : {{ $data->total() }}</div>
                        </div>
                        <div class="col-md-6 text-end">
                            <button class="btn btn-info"><i class="fa fa-search"></i> Search</button>
                            <a href="{{route('jawab.translation.index')}}" class="btn btn-info"><i class="fa fa-refresh"></i> Reset</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <hr>

        <div class="card-body">
            @if($data->items())
                <table class="table table-striped table-bordered">
                    <tbody>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col" style="width: 60%">Translation Value</th>
                        <th scope="col">Language</th>
                        <th scope="col" class="text-center">Actions</th>
                    </tr>
                    @foreach($data->items() as $item) 
                    <tr>
                        <th scope="row">{{$item->id}}</th>
                        <td>
                            <p {{ $item->language_code == 'ar' ? 'dir=rtl' : '' }}>{{ Str::words($item->value, 50) }}</p>
                            <small>{{ Str::limit($item->key, 100) }}</small>
                        </td>
                        <td>{{config('localization.locales')[$item->language_code] ?? ''}}</td>
                        <td class="text-center">
                            <form method="POST" action="{{ route('jawab.translation.destroy', $item->id) }}" >
                                @csrf
                                <input type="hidden" name="_method" value="delete">
                                <a href="{!! route('jawab.translation.edit', [$item->id]) !!}" class="btn btn-primary btn-sm">Edit</a>
                                <button type="submit" onclick="return confirm('Are you sure?')" class="btn btn-danger btn-sm text-white">Delete</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            @else
                <div class="alert alert-danger" role="alert">There are no Data</div>
            @endif
        </div>

        <div class="card-footer">
            {{ $data->appends(request()->query())->links() }}
        </div>
    </div>
@endsection