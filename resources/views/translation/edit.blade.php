@extends('localization::layouts.app')

@section('content')
    <div class="card">
        <div class="card-header">
            Edit / Translation
        </div>

        <div class="card-body">
            <form method="POST" action="{{ route('jawab.translation.update', $item->id) }}" class="form-horizontal" >
                @csrf
                <input type="hidden" name="_method" value="patch">
                <input type="hidden" name="id" value="{{ $item->id }}">
                @include('localization::translation.fields')
            </form>
        </div>
    </div>
@endsection
