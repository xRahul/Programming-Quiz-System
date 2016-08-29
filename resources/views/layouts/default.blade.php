<!DOCTYPE html>

<html lang="en">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">

	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="csrf-token" content="{{ csrf_token() }}" />

	<title>@yield('title')</title>

	<link rel="stylesheet" href="{{ elixir('css/app.css') }}">
	<script src="{{ elixir('js/app.js') }}"></script>
</head>

<body class="content">
	@include('inc.header')
    @yield('content')
	@include('inc.footer')
</body>