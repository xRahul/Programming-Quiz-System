@if(session()->has('flash_message'))
	<script>
		$(document).ready(function(){
		    swal({   
		      title: "{{ session('flash_message.title') }}",
		      text: "{{ session('flash_message.message') }}",
		      type: "{{ session('flash_message.level') }}",  
		      timer: 1700,
		      showConfirmButton: false
		    });
		});
	</script>
@endif

@if(session()->has('flash_message_overlay'))
	<script>
		$(document).ready(function(){
		    swal({   
		      title: "{{ session('flash_message_overlay.title') }}",
		      text: "{{ session('flash_message_overlay.message') }}",
		      type: "{{ session('flash_message_overlay.level') }}",  
		      confirmButtonText: 'Okay'
		    });
		});
	</script>
@endif