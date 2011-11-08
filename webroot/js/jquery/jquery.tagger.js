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
			$(l).append(t).append(i);
		}
		return this;
	};
	
})(jQuery);

jQuery(function(){
	$('.tagger').each(function(i){
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