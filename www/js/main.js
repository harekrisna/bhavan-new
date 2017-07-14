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

	function CollectionBreadcrumbNavigationFit() {
		var breadcrumb_nav = $("#breadcrumb_nav");
		var collection_title = $(".collection-title");

		if(breadcrumb_nav.length && collection_title.length) {
			if($(collection_title).position().top < $(breadcrumb_nav).position().top) {
				$(collection_title).addClass('mobile');
				$(breadcrumb_nav).addClass('mobile');
			}
			else {
				$(collection_title).removeClass('mobile');
				$(breadcrumb_nav).removeClass('mobile');	
			}
		}
	}


	function CollectionNextPrevNavigationFit() {
		var breadcrumb_nav = $("#breadcrumb_nav");
		var next_prev_nav = $("#next_prev_nav");

		if(breadcrumb_nav.length && next_prev_nav.length) {
			if($(next_prev_nav).position().top > $(breadcrumb_nav).position().top) {
				$(next_prev_nav).addClass('mobile');
			}
			else {
				$(next_prev_nav).removeClass('mobile');	
			}
		}
	}

	function CollectionNextPrevHoverLabelFit() {
		var next_prev_nav = $("#next_prev_nav");
		var label_prev = $("#label_prev");
		var label_next = $("#label_next");

		$(label_next).css("left", $(next_prev_nav).outerWidth() - $(label_next).outerWidth());
		$(label_prev).css("left", $(next_prev_nav).outerWidth() - $(label_prev).outerWidth());

	}

	ScaleListTitle();
    CollectionBreadcrumbNavigationFit();
    CollectionNextPrevNavigationFit();
    //CollectionNextPrevHoverLabelFit();
    
    $(window).bind("load", ScaleListTitle);
    $(window).bind("load", CollectionNextPrevNavigationFit);
    $(window).bind("load", CollectionBreadcrumbNavigationFit);
    //$(window).bind("load", CollectionNextPrevHoverLabelFit);
    $(window).bind("resize", ScaleListTitle);
    $(window).bind("resize", CollectionNextPrevNavigationFit);
    $(window).bind("resize", CollectionBreadcrumbNavigationFit);
    $(window).bind("orientationchange", ScaleListTitle);
    $(window).bind("orientationchange", CollectionNextPrevNavigationFit);
    $(window).bind("orientationchange", CollectionBreadcrumbNavigationFit);

});
