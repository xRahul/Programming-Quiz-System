@extends('layouts.default')

@section('title', 'Login')

@section('content')

	<div class="container login-container">
		<div class="col-lg-4 col-lg-offset-4 col-md-6 col-md-offset-3 col-sm-8 col-sm-offset-2 col-xs-12 col-xs-offset-0">
			{!! Form::open([
				'route' 	=> 'login.post', 
				'method'	=>'POST', 
				'class' 	=> '', 
				'id' 		=> 'login-form-id'
			]) !!}

			<div class="form-group">
				{!! Form::label('email', 'E-mail:') !!}
				{!! Form::email('email', null, [
					'class' 						=> 'form-control',
					'placeholder' 					=> 'Your registered email address',
					'data-parsley-required-message' => 'This field is required',
					'data-parsley-trigger'          => 'change focusout',
					'data-parsley-type'             => 'email',
					'required'
				]) !!}
			</div>
			
			<div class="form-group">
				{!! Form::label('password', 'Password:') !!}
				{!! Form::password('password', [
	        		'class' 						=> 'form-control',
	        		'placeholder' 					=> 'password',
	        		'data-parsley-required-message' => 'This field is required',
		            'data-parsley-trigger'          => 'change focusout',
		            'data-parsley-minlength'        => '6',
		            'data-parsley-maxlength'        => '20',
	        		'required'
	        	]) !!}
			</div>

			<div class="form-group">
				<label>
	                {!! Form::checkbox('remember', 1) !!} Remember my login session
	            </label>
			</div>

			<div class="form-group">
				{!! Form::submit('Log In', ['class' => 'btn btn-default btn-lg btn-block']) !!}
			</div>

			{!! Form::close() !!}
@endsection