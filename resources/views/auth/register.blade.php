@extends('layouts.default')

@section('title', 'Register')

@section('content')

	<div class="container register-container">
		<div class="col-lg-4 col-lg-offset-4 col-md-6 col-md-offset-3 col-sm-8 col-sm-offset-2 col-xs-12 col-xs-offset-0">
			{!! Form::open([
				'route' 	=> 'register.post', 
				'method'	=>'POST',
				'class' 	=> '',
				'id' 		=> 'register-form-id'
			]) !!}

			<div class="form-group">
				{!! Form::label('first_name', 'First Name:') !!}
	        	{!! Form::text('first_name', null, [
	        		'class' 						=> 'form-control', 
	        		'placeholder' 					=> 'First name', 
	        		'data-parsley-required-message' => 'First Name is required',
		            'data-parsley-trigger'          => 'change focusout',
		            'data-parsley-pattern'          => '/^[a-zA-Z ]*$/',
		            'data-parsley-minlength'        => '2',
		            'data-parsley-maxlength'        => '32',
	        		'required' 
	        	]) !!}
			</div>
			<div class="form-group">
				{!! Form::label('last_name', 'Last Name:') !!}
	        	{!! Form::text('last_name', null, [
	        		'class' => 'form-control', 
	        		'placeholder' => 'Last name', 
	        		'data-parsley-required-message' => 'Last Name is required',
		            'data-parsley-trigger'          => 'change focusout',
		            'data-parsley-pattern'          => '/^[a-zA-Z ]*$/',
		            'data-parsley-minlength'        => '2',
		            'data-parsley-maxlength'        => '32'
	        	]) !!}
			</div>
			<br />
			<div class="form-group">
				{!! Form::label('mobile', 'Mobile No:') !!}
				<div class="input-group">
					<span class="input-group-addon">+91</span>
	        		{!! Form::text('mobile', null, [
	        			'class' => 'form-control', 
	        			'placeholder' => 'Enter 10 digit mobile number', 
	        			'data-parsley-required-message' => 'Mobile No is required',
			            'data-parsley-trigger'          => 'change focusout',
			            'data-parsley-type'				=> 'integer',
			            'data-parsley-pattern'          => '/^[0-9]*$/',
			            'data-parsley-minlength'        => '10',
			            'data-parsley-maxlength'        => '10'
	        		]) !!}
				</div>
			</div>

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
			<br />
			<div class="form-group">
				{!! Form::label('password', 'Password:') !!}
				{!! Form::password('password', [
	        		'class' 						=> 'form-control',
	        		'id'                            => 'inputPassword',
	        		'placeholder' 					=> 'Choose your password (min 6 characters)',
	        		'data-parsley-required-message' => 'This field is required',
		            'data-parsley-trigger'          => 'change focusout',
		            'data-parsley-minlength'        => '6',
		            'data-parsley-maxlength'        => '20',
	        		'required'
	        	]) !!}
			</div>
			<div class="form-group">
				{!! Form::label('password_confirmation', 'Re-enter Password:') !!}
	        	{!! Form::password('password_confirmation', [
	        		'class' 						=> 'form-control', 
	        		'placeholder' 					=> 'Confirm Password', 
	        		'data-parsley-required-message' => 'Password confirmation is required',
		            'data-parsley-trigger'          => 'change focusout',
		            'data-parsley-equalto'          => '#inputPassword',
		            'data-parsley-equalto-message'  => 'Not same as Password',
	        		'required'
	        	]) !!}
			</div>

			@if (Auth::check() && Auth::user()->hasRole('admin'))
				<br />
				<div class="form-group">
					<label>
		                {!! Form::checkbox('admin_user', 1) !!} Registering an admin user?
		            </label>
				</div>
			@endif

			<br />

			<div class="form-group">
				{!! Form::submit('Register', ['class' => 'btn btn-default btn-lg btn-block']) !!}
			</div>

			{!! Form::close() !!}
		</div>
	</div>
@endsection