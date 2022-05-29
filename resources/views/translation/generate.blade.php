@extends('localization::layouts.app')

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="alert alert-success">
                language files are generated successfully.
                <br>
                <br>
                check {{ App::langPath() }}
            </div>
        </div>
    </div>
@endsection