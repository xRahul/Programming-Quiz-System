@extends('layouts.default')

@section('title', 'Admin')

@section('extrafooters')
	<script src="{{ elixir('js/admin.js') }}"></script>
@endsection

@section('content')
    Yo, this is adminpage
    <div id="root"></div>
@endsection