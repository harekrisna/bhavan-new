function initDateIntervalPicker(from_el, to_el) {
	$.datepicker.regional['cs'] = { 
	    closeText: 'OK', 
	    prevText: 'Předchozí', 
	    nextText: 'Další', 
	    currentText: 'Nyní', 
	    monthNames: ['Leden','Únor','Březen','Duben','Květen','Červen', 'Červenec','Srpen','Září','Říjen','Listopad','Prosinec'],
	    monthNamesShort: ['Le','Ún','Bř','Du','Kv','Čn', 'Čc','Sr','Zá','Ří','Li','Pr'], 
	    dayNames: ['Neděle','Pondělí','Úterý','Středa','Čtvrtek','Pátek','Sobota'], 
	    dayNamesShort: ['Ne','Po','Út','St','Čt','Pá','So',], 
	    dayNamesMin: ['Ne','Po','Út','St','Čt','Pá','So'], 
		hourText: 'Hodin:',
		minuteText: 'Minut:',
		currentText: 'Nyní',
		closeText: 'OK',
	    weekHeader: 'Sm',
	    dateFormat: 'dd.mm.yy', 
	    firstDay: 1, 
	    isRTL: false, 
	    showMonthAfterYear: false, 
	    yearSuffix: '',
			yearRange: '2010:2020',
	}; 
	
	$.datepicker.setDefaults($.datepicker.regional['cs']);
	
	var from = $(from_el);
	var to = $(to_el); 
	
	from.datepicker({
	    onSelect: function(dateText){
	        var date = new Date($(this).datepicker('getDate').getDate());
	        to.datepicker('option', 'minDate', date);
	    }
	}).keyup(function(e) {
		if(e.keyCode == 8 || e.keyCode == 46) {
			$(this).datepicker('setDate', null);
	        to.datepicker('option', 'minDate', null);
		}
	});
	
	to.datepicker({
	    onSelect: function(dateText){
	        var date = new Date($(this).datepicker('getDate').getDate());
	        from.datepicker('option', 'maxDate',date);
	    }
	}).keyup(function(e) {
		if(e.keyCode == 8 || e.keyCode == 46) {
			$(this).datepicker('setDate', null);
	        from.datepicker('option', 'minDate', null);
		}
	});
	
	if(from.datepicker('getDate') != null) {
	    var date = new Date(from.datepicker('getDate').getDate());
	    to.datepicker('option', 'minDate', date);
	}
	
	if(to.datepicker('getDate') != null) {
	    var date = new Date(to.datepicker('getDate').getDate());
	    from.datepicker('option', 'maxDate',date);
	}
}


function initDateTimeIntervalPicker(from_el, to_el) {
	$.datepicker.regional['cs'] = { 
	    closeText: 'OK', 
	    prevText: 'Předchozí', 
	    nextText: 'Další', 
	    currentText: 'Nyní', 
	    monthNames: ['Leden','Únor','Březen','Duben','Květen','Červen', 'Červenec','Srpen','Září','Říjen','Listopad','Prosinec'],
	    monthNamesShort: ['Le','Ún','Bř','Du','Kv','Čn', 'Čc','Sr','Zá','Ří','Li','Pr'], 
	    dayNames: ['Neděle','Pondělí','Úterý','Středa','Čtvrtek','Pátek','Sobota'], 
	    dayNamesShort: ['Ne','Po','Út','St','Čt','Pá','So',], 
	    dayNamesMin: ['Ne','Po','Út','St','Čt','Pá','So'], 
		timeText: 'Čas:',
		hourText: 'Hodin:',
		minuteText: 'Minut:',
		currentText: 'Nyní',
		closeText: 'OK',
	    weekHeader: 'Sm',
	    dateFormat: 'dd.mm.yy', 
	    firstDay: 1, 
	    isRTL: false, 
	    showMonthAfterYear: false, 
	    yearSuffix: '',
			yearRange: '2010:2020',
	}; 
	
	$.datepicker.setDefaults($.datepicker.regional['cs']);
	$.timepicker.setDefaults($.datepicker.regional['cs']);
	
	var from = $(from_el);
	var to = $(to_el); 
	
	from.datetimepicker({
	    onSelect: function(dateText){
	        var time = new Date($(this).datetimepicker('getDate').getTime());
	        var value = to.val();
	        to.datetimepicker('option', 'minDateTime', time);
	        to.datetimepicker('option', 'minDate', time);
	        to.val(value);
	    }
	}).keyup(function(e) {
		if(e.keyCode == 8 || e.keyCode == 46) {
			$(this).datetimepicker('setDate', null);
			to.datetimepicker('option', 'minDateTime', null);
	        to.datetimepicker('option', 'minDate', null);
		}
	});
	
	to.datetimepicker({
	    onSelect: function(dateText){
	        var time = new Date($(this).datetimepicker('getDate').getTime());
	        var value = from.val();
	        from.datetimepicker('option', 'maxDateTime',time);
	        from.datetimepicker('option', 'maxDate',time);
	        from.val(value);
	    }
	}).keyup(function(e) {
		if(e.keyCode == 8 || e.keyCode == 46) {
			$(this).datetimepicker('setDate', null);
			from.datetimepicker('option', 'minDateTime', null);
			from.datetimepicker('option', 'minDate', null);
		}
	});
	
	if(from.datetimepicker('getDate') != null) {
	    var time = new Date(from.datetimepicker('getDate').getTime());
	    to.datetimepicker('option', 'minDateTime', time);
	    to.datetimepicker('option', 'minDate', time);
	}
	
	if(to.datetimepicker('getDate') != null) {
	    var time = new Date(to.datetimepicker('getDate').getTime());
	    from.datetimepicker('option', 'maxDateTime',time);
	    from.datetimepicker('option', 'maxDate',time);
	}
}