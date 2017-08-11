@extends('brackets/admin::admin.layout.index')

@section('body')

    This is for testing purposes
    <ul>
    @foreach($data as $d)
        <li>{{ print_r($d->toArray(), true) }}</li>
    @endforeach
    </ul>

@endsection