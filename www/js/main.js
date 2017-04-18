$(function(){
    $('.list .list-box').click(function() {
      	var href = $(this).children("a").first().attr("href");
      	window.location.href = href;
    });

    function ScaleListTitle() {
		var container = $('.scale-text');
		var text = container.find('span');
		var font_size = parseInt(text.css('font-size'));

		//pokud má kontejner menší šířku než text, sníží se velikost fontu dokud se nevejde
		while(container.width() < text.width()) {
			font_size -= 1;
			text.css('font-size', font_size);
		}
		
		//pokud je kontejner širší než text zvýší se velikost fontu dokud není větší nebo není fontsize 30
		if(container.width() > text.width()) {
			while(font_size < 30) {
				font_size += 1;
				text.css('font-size', font_size);
				if(container.width() < text.width()) {
					text.css('font-size', font_size - 1);
					break;
				}
			}
		}

		// nakonec korekce font-size
		font_size = parseInt(text.css('font-size'));
		if(font_size < 30)
			text.css('font-size', font_size + 1);
		if(font_size > 30)
			text.css('font-size', 30);
	}

	ScaleListTitle();
        
    $(window).bind("load", ScaleListTitle);
    $(window).bind("resize", ScaleListTitle);
    $(window).bind("orientationchange", ScaleListTitle);
});
