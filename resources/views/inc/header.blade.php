<nav class="navbar navbar-inverse navbar-fixed-top">
  <div class="container-fluid">
	<!-- Brand and toggle get grouped for better mobile display -->
    <div class="navbar-header">
      <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#header-navbar-collapse" aria-expanded="false">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
      <a class="navbar-brand" href="/">QuizSystem</a>
    </div>
	  <!-- Collect the nav links, forms, and other content for toggling -->
	  <div class="collapse navbar-collapse" id="header-navbar-collapse">
	    <ul class="nav navbar-nav navbar-right">
	      @if(!Auth::check())
	        <li class="header-li">
	          <a class="header-a" href="{{ route('login.get') }}">
	            Sign In
	          </a>
	      	</li>
	      @else
	        <li class="dropdown header-li">
	          <a class="dropdown-toggle header-a" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
	            Hi {{Auth::user()->first_name}} <span class="caret"></span>
	          </a>
	          <ul class="dropdown-menu header-dropdown-menu">
	          	@if(Auth::user()->hasRole('admin'))
								<li>
				          <a class="header-a" href="{{ route('register.get') }}">
				            Register Admin
				          </a>
				      	</li>
				      	<li role="separator" class="divider"></li>
				      @endif
	            <li>
	              <a class="header-a" href="{{ route('logout.get') }}" id="header-log-out-link">
	                Log Out
	              </a>
	            </li>
	          </ul>
	        </li>
	      @endif
	    </ul>
	  </div><!-- /.navbar-collapse -->
	</div>
</nav>

@include('inc.alert')