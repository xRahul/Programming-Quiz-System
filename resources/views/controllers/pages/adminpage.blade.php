@extends('layouts.default')

@section('title', 'Admin')

@section('extrafooters')
	<script src="{{ elixir('js/admin.js') }}"></script>
	{{-- <script>
		$( document ).ready(function(){
			$('.nav-tabs li a').click(function (e) {$(this).tab('show')})
		});
	</script> --}}
@endsection

@section('content')
    <div class="container" id="root"></div>
@endsection