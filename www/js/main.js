$(function(){
    $('.list .list-box').click(function() {
      	var href = $(this).children("a").first().attr("href");
      	window.location.href = href;
    });

	$('.list-simple .list-simple-item').click(function() {
      	var item_text = $(this).next();
      	if($(this).hasClass('item-expanded')) {
      		$(this).removeClass('item-expanded');	
      		$(item_text).slideUp();
      	}
      	else {
      		$(this).addClass('item-expanded');
      		$(item_text).slideDown();
      	}
    });


	$('.lectures-group .group-header-title').click(function() {
      	var item_content = $(this).parent().next();
      	if($(this).hasClass('item-expanded')) {
      		$(this).removeClass('item-expanded');	
      		$(item_content).slideUp();
      	}
      	else {
      		$(this).addClass('item-expanded');
      		$(item_content).slideDown();
      	}
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

	function ScaleAudioGroupHeaderTitle() {
		var container = $('.group-header');
		
		container.each(function(index) {
			var text = $(this).find('.group-header-title');
			var height = $(text).height();
			
			if(height <= 25) {
				$(text).removeClass("adjust-mob-small");
				$(text).removeClass("adjust-mob-large");
			}
			else if(height > 25 && height <= 50) {
				$(text).removeClass("adjust-mob-small");
				$(text).addClass("adjust-mob-large");
			}
			else if(height > 50) {
				$(text).removeClass("adjust-mob-large");
				$(text).addClass("adjust-mob-small");
			}
		});
			
	}

	ScaleListTitle();
    CollectionBreadcrumbNavigationFit();
    CollectionNextPrevNavigationFit();
    ScaleAudioGroupHeaderTitle();
    
    $(window).bind("load", ScaleListTitle);
    $(window).bind("load", ScaleAudioGroupHeaderTitle);
    $(window).bind("load", CollectionNextPrevNavigationFit);
    $(window).bind("load", CollectionBreadcrumbNavigationFit);
    $(window).bind("resize", ScaleListTitle);
    $(window).bind("resize", ScaleAudioGroupHeaderTitle);    
    $(window).bind("resize", CollectionNextPrevNavigationFit);
    $(window).bind("resize", CollectionBreadcrumbNavigationFit);
    $(window).bind("orientationchange", ScaleListTitle);
    $(window).bind("orientationchange", ScaleAudioGroupHeaderTitle);    
    $(window).bind("orientationchange", CollectionNextPrevNavigationFit);
    $(window).bind("orientationchange", CollectionBreadcrumbNavigationFit);
	
	// hover label pro horní next/prev navigaci
    $('#top_link_prev').hover(function() {
		$('#top_label_prev').addClass('hover');
	}).mouseout(function() {
		$('#top_label_prev').removeClass('hover');
	});

	$('#top_link_next').hover(function() {
		$('#top_label_next').addClass('hover');
	}).mouseout(function() {
		$('#top_label_next').removeClass('hover');
	});


	// hover label pro spodní next/prev navigaci
    $('#bottom_link_back').mouseover(function() {
		$('#bottom_label_back').addClass('hover');
	}).mouseout(function() {
		$('#bottom_label_back').removeClass('hover');
	});

	$('#bottom_link_prev').hover(function() {
		$('#bottom_label_prev').addClass('hover');
	}).mouseout(function() {
		$('#bottom_label_prev').removeClass('hover');
	});
	
    $('#bottom_link_next').hover(function() {
		$('#bottom_label_next').addClass('hover');
	}).mouseout(function() {
		$('#bottom_label_next').removeClass('hover');
	});
});

function myPaginator(pages_container, prev_button, next_button) {
    var visible_page = $(pages_container).find('.link-current').parent().parent();
    $(visible_page).show();

    if($(visible_page).is(':first-child')) {
        $(prev_button).addClass('link-disabled');
    }

    if($(visible_page).is(':last-child')) {
        $(next_button).addClass('link-disabled');
    }

    $(prev_button).on('click', function(event) {
        var visible_page = $(pages_container).find(' > *:visible');
        if($(visible_page).prev().length) {
            $(visible_page).hide();
            $(visible_page).prev().fadeIn();

            if($(visible_page).prev().prev().length == 0) {
                $(this).addClass('link-disabled');
            }

            $(next_button).removeClass('link-disabled');
        }
        event.preventDefault();
        return false;
    });

    $(next_button).on('click', function(event) {
        var visible_page = $(pages_container).find(' > *:visible');
        if($(visible_page).next().length) {
            $(visible_page).hide();
            $(visible_page).next().fadeIn();

            if($(visible_page).next().next().length == 0) {
                $(this).addClass('link-disabled');
            }

            $(prev_button).removeClass('link-disabled');
        }

        event.preventDefault();
        return false;
    });
}