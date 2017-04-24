
	function strtr(str, from, to) {
	  var fr = '',
	    i = 0,
	    j = 0,
	    lenStr = 0,
	    lenFrom = 0,
	    tmpStrictForIn = false,
	    fromTypeStr = '',
	    toTypeStr = '',
	    istr = '';
	  var tmpFrom = [];
	  var tmpTo = [];
	  var ret = '';
	  var match = false;
	
	  // Walk through subject and replace chars when needed
	  lenStr = str.length;
	  lenFrom = from.length;
	  fromTypeStr = typeof from === 'string';
	  toTypeStr = typeof to === 'string';
	
	  for (i = 0; i < lenStr; i++) {
	    match = false;
	    if (fromTypeStr) {
	      istr = str.charAt(i);
	      for (j = 0; j < lenFrom; j++) {
	        if (istr == from.charAt(j)) {
	          match = true;
	          break;
	        }
	      }
	    } else {
	      for (j = 0; j < lenFrom; j++) {
	        if (str.substr(i, from[j].length) == from[j]) {
	          match = true;
	          // Fast forward
	          i = (i + from[j].length) - 1;
	          break;
	        }
	      }
	    }
	    if (match) {
	      ret += toTypeStr ? to.charAt(j) : to[j];
	    } else {
	      ret += str.charAt(i);
	    }
	  }
	  return ret;
	}

	var trim = (function () {
	    "use strict";
	
	    function escapeRegex(string) {
	        return string.replace(/[\[\](){}?*+\^$\\.|\-]/g, "\\$&");
	    }
	
	    return function trim(str, characters, flags) {
	        flags = flags || "g";
	        if (typeof str !== "string" || typeof characters !== "string" || typeof flags !== "string") {
	            throw new TypeError("argument must be string");
	        }
	
	        if (!/^[gi]*$/.test(flags)) {
	            throw new TypeError("Invalid flags supplied '" + flags.match(new RegExp("[^gi]*")) + "'");
	        }
	
	        characters = escapeRegex(characters);
	
	        return str.replace(new RegExp("^[" + characters + "]+|[" + characters + "]+$", flags), '');
	    };
	}());

	function stringToNiceURL(string) {
		string = string.toLowerCase();
    	string = strtr(string, "áäčçďéěëíňóöřšťúůüýž", "aaccdeeeinoorstuuuyz");
    	string = string.replace(/\W/g, '-');
    	string = trim(string, '-');
    	string = string.replace(/[-]+/g, '-');
	   	return string;
	}	
	
    this.imagePreview = function(){		
		xOffset = 10;
		yOffset = 30;
	
		$("a.preview").hover(function(e){
			$("body").append("<p id='preview'><img alt='preview' src='"+ this.href +"' alt='Image preview' /></p>");								 
			$("#preview")
				.css("top",(e.pageY - xOffset) + "px")
				.css("left",(e.pageX + yOffset) + "px")
				.fadeIn("fast");						
	    },
		function(){
			this.title = this.t;	
			$("#preview").remove();
	    });	
		$("a.preview").mousemove(function(e){
			var height = $("#preview").find("img").height();
			$("#preview")
				.css("top",(e.pageY - xOffset - height/2) + "px")
				.css("left",(e.pageX + yOffset) + "px");
		});			
	};
	
	$('td a.remove').click(function (event) {
	    event.preventDefault();	
		if (window.confirm("Opravdu smazat?") !== false) {
			$(this).addClass('remove-loading');    

			var row = $(this).parent().parent();
			
			$.get(this.href, function (payload) {
				$.nette.success(payload);
			 	if(payload['success']) {
					row.children('td, th').hide(120);
					row.animate({
							height: 0,
					}, function() {
						this.remove();
					});
				}
			});
		 }
	}); 

