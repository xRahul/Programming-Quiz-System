@if(Session::has('message') || $errors->has())
	<div class="alert-div-class container">
		@if(Session::has('message'))
		    <div class="alert alert-{{ Session::get('status') }} fade in {{ Session::has('alert-important') ? 'alert-important' : '' }}">
		        <button type="button" class="close" data-dismiss="alert">
		        	<span aria-hidden="true">&times;</span>
		        	<span class="sr-only">Close</span>
		        </button>
		        {{ Session::get('message') }}
		    </div>
		@endif

		@if($errors->has())
		    <div class="alert alert-danger fade in">
		        <button type="button" class="close" data-dismiss="alert">
		        	<span aria-hidden="true">&times;</span>
		        	<span class="sr-only">Close</span>
		        </button>
		        <h4>Error(s) occurred</h4>
		        <ul>
		            @foreach($errors->all() as $error)
		                <li>{{ $error }}</li>
		            @endforeach
		        </ul>
		    </div>
		@endif
	</div>
@endif