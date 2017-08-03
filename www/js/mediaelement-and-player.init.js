$(function(){
  	var me = $('audio.desktop').mediaelementplayer({
	    alwaysShowHours: true,
	    alwaysShowControls: true,
	    iPadUseNativeControls: false,
	    iPhoneUseNativeControls: false,
	    AndroidUseNativeControls: false,
	});
	
	$('section.lectures-group audio').each(function(index) {
		redraw_playcount_title(this, $(this).data('playcount'));
	});
	
	// přičtení počtu stažení
	$('.audio-player .download-link, .audio-player .download-button').on('click tap', function(event) {
		var audio_player = $(this).closest('div.audio-player');
		var	audio = $(audio_player).find('audio');
		var audio_id = $(audio).data('id');
		var download_link = $(this).closest('.audio-player').find('.download-link');	
		var download_button = $(this).closest('.audio-player').find('.download-button');
		
		$.get('increase-mp3-download/' + audio_id, 
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
			on_play(audio, $(audio).data('presenter'));
		}
		
		if(button == 'Pause') {
			on_pause(audio);
		}
	});
});

function on_play(audio, presenter = null) {
	var audio_player = $(audio).closest('.audio-player');
	var hidder = audio_player.find('.hidder');
	var audio_id = $(audio).data('id');
	
	hidder.removeClass('hidder-hide');
	var url = 'increase-mp3-playcount/' + audio_id;
	
	if(presenter != null)
		url = presenter + "/" + url;

	$.get(url, 
        function(payload) {
            redraw_playcount_title(audio, payload.playcount)
        }
    );
}

function on_pause(audio) {
	var audio_player = $(audio).closest('.audio-player');
	var hidder = audio_player.find('.hidder');
	
	hidder.addClass("hidder-hide");
}

function redraw_playcount_title(me, playcount) {
	var player_bar = $(me).closest('div.player-bar-container');
	var playpause_button = $(player_bar).find('div.mejs__playpause-button button');
	$(playpause_button).attr('title', 'Přehráno: ' + playcount + 'x');	
}


