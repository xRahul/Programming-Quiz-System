//hiding the overlay
    function close_overlay(){
        document.getElementById('video').style.display='none';
        document.getElementById('fade').style.display='none';
        document.getElementById('video_player').load();
    }

//showing the overlay
    function open_overlay(){
        document.getElementById('video').style.display='block';
        document.getElementById('fade').style.display='block';
    }