/*
    Tagger Widget v1.1
    Copyright (C) 2011 Chris Iufer (chris@iufer.com)

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>

	This is a modified version of Tagger, for use with Minerva CMS.
	The edits shouldn't change how Tagger works, but it enhances it with some
	sanity checking and appends the inputs directly to the form element instead
	of to the .data('list') where the tags are added visually (with removal option).
	This was the only way the form data seemed to see the fields in my experience.
	I'm not sure how it worked before. It could be the positioning of the form
	within the layout or something weird. This makes it all work though.
*/

(function($){
	
	$.fn.addTag = function(v){
		var r = v.split(',');
		for(var i in r){
			n = r[i].replace(/([^a-zA-Z0-9\s\-\_\.])|^\s|\s$/g, '');			
			if(n == '') break;
			var fn = $(this).data('name');
			var i = $('<input type="hidden" />').attr('name',fn).val(n);
			var t = $('<li />').text(n).addClass('tagName')
				.click(function(){
					var hidden = $(this).data('hidden');
					$(hidden).remove();
					$(this).remove();
				})
				.data('hidden',i);
			var l = $(this).data('list');
			// Originally, it appended both label and input field
			//$(l).append(t).append(i);
			//
			// Append the tag (and remove option) to the list of tags visually, but not the hidden input
			$(l).append(t);
			
			// Must append the input to the form! It's not being seen otherwise.
			var elem = $(this);
			var form = elem.length > 0 ? $(elem[0].form) : $();
			$(form).append(i);
		}
		return this;
	};
		
})(jQuery);

jQuery(function(){	
	// This ensures that the tagger text input field is disabled upon submit.
	// Otherwise, we're saving empty values in a tag array in the database.
	var input = $('.tagger');
	var form = input.length > 0 ? $(input[0].form) : $();
	$(form).bind('submit', function() { 
		$(input).attr('disabled', true);
	});
	
	$('.tagger').each(function(i){
		// Make the input a multiple (array) input if it isn't already
		var n = $(this).attr('name');
		if(n.substr(-2) != '[]') {
			$(this).attr('name', n + '[]');
		}
		
		$(this).data('name', $(this).attr('name'));		
		var b = $('<button type="button" />').html('Add').addClass('tagAdd')
			.click(function(){
				var tagger = $(this).data('tagger');
				$(tagger).addTag( $(tagger).val() ).val('');
			})
			.data('tagger', this);
		var l = $('<ul />').addClass('tagList');
		$(this).data('list', l).after(l).after(b);
	})
	.bind('keypress', function(e){
		if( 13 == e.keyCode){
			e.stopPropagation();
			e.preventDefault();
			$(this).addTag( $(this).val() ).val('');
		}
	});
});