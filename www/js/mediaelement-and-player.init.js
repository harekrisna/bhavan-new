$(function() {
  	var me = $('audio.desktop').mediaelementplayer({
	    alwaysShowHours: true,
	    alwaysShowControls: true,
	    iPadUseNativeControls: false,
	    iPhoneUseNativeControls: false,
	    AndroidUseNativeControls: false,

	    success: function (mediaElement, domObject) {

	        // Event listeners

	        mediaElement.addEventListener('seeking', function (e) {
	            var audio_player = $(mediaElement).closest('.audio-player');
	            var time_float = $(audio_player).find('.mejs__controls .mejs__time-float');
	            $(time_float).show();

	        }, false);
        }
	});
	
	$('div.audio-section audio').each(function(index) {
		redraw_playcount_title(this, $(this).data('playcount'));
		console.log("lol");
	});
	
	// přičtení počtu stažení
	$('.audio-player .download-button').on('click', function(event) {
		var download_link = $(this).closest('.audio-player').find('.download-link');	
		var download_button = $(this).closest('.audio-player').find('.download-button');
		var url = $(download_button).data('increase_url');
						
		$.get(url, 
            function(payload) {
	            var downloadcount = payload.downloadcount;
	            if(downloadcount == "") downloadcount = 0;
				$(download_link).attr('title', 'Staženo: ' + downloadcount + 'x');
				$(download_button).attr('title', 'Staženo: ' + downloadcount + 'x');
            }
        );
	});

	$('div.mejs__controls').append('<div class="mejs__button"><!-- padding --></div>');
	
	$('.mejs__playpause-button').click(function() {
		var button = $(this).find('button').attr('aria-label');
		var audio = $(this).closest('.audio-player').find('audio');
		
		if(button == 'Play') {
			var audio_player = $(audio).closest('.audio-player');
			var hidder = audio_player.find('.hidder');
			var url = $(audio).data('increase_url');
			
			$(audio_player).find('.mejs__controls').addClass('on-play');
			$(audio_player).find('.download-part-background').addClass('on-play');
		
			$('.audio-player .hidder').addClass("hidder-hide");
		
			hidder.removeClass('hidder-hide');
			
			$.get(url, 
		        function(payload) {
		            redraw_playcount_title(audio, payload.playcount)
		        }
		    );
		}
		
		if(button == 'Pause') {
			var audio_player = $(audio).closest('.audio-player');
			var hidder = audio_player.find('.hidder');
		
			$(audio_player).find('.mejs__controls').removeClass('on-play');
			$(audio_player).find('.download-part-background').removeClass('on-play');
		
			hidder.addClass("hidder-hide");
		}
	});

	$('.player-bar-container').show();
});

function redraw_playcount_title(me, playcount) {
	var player_bar = $(me).closest('div.player-bar-container');
	var playpause_button = $(player_bar).find('div.mejs__playpause-button button');
	$(playpause_button).attr('title', 'Přehráno: ' + playcount + 'x');	
}


