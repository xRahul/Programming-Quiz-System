var elixir = require('laravel-elixir');

/*
 |--------------------------------------------------------------------------
 | Elixir Asset Management
 |--------------------------------------------------------------------------
 |
 | Elixir provides a clean, fluent API for defining some basic Gulp tasks
 | for your Laravel application. By default, we are compiling the Sass
 | file for our application, as well as publishing vendor resources.
 |
 */

elixir(function(mix) {
    mix.sass('app.scss')
    	
    	.scripts(
		    [
		    	'node_modules/jquery/dist/jquery.js',
		    	'bootstrap-sass/assets/javascripts/bootstrap.js', 
		    	'sweetalert/dist/sweetalert-dev.js'
		    ],
		    'public/js/app.js',
		    'node_modules'
		)
		.webpack('./resources/assets/jsx/index.js')
    	.version(['css/app.css', 'js/app.js', 'js/admin.js']);
});