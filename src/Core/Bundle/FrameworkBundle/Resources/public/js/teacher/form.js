function form_formContentListeners() {
	// Chooser listener
	$(document).find('select.chooser-search').on('focus', form_chooserSearch_focus);
	$(document).find('.chooser-search').on('keyup', form_chooserSearch_keyup);
	$(document).find('.chooser-search').on('keydown', form_chooserSearch_keydown);
}

var typingTimer; //timer identifier
var chooserSearch_searchString;

function form_chooserSearch_keyup(event) {
  clearTimeout(typingTimer);
  typingTimer = setTimeout(function() { form_chooserSearch_perform(event) }, 500);
}

function form_chooserSearch_keydown(event) {
	clearTimeout(typingTimer);
}

function form_chooserSearch_perform(event) {
	var calledElement = $(event.target);
	var searchString = calledElement.val();
	
	if (searchString  != '' && searchString != chooserSearch_searchString) {
	
	var options = new Array;
  $.ajax({
		type: 'GET',
		url: calledElement.data('search-url'),
		data: 'q='+searchString,
		error: function(msg) {
			$('#modal_iframe').contents().find('html').html(msg.responseText);
			$('.modal-footer').on('click', 'button', function(event) {
				$('#myModal').hide();
			});
			$('#myModal').show();
		},
		success: function(data) {
			// update url & history
			var html = '';
			var len = data.length;
			for (var i = 0; i< len; i++) {
			   html += '<option value="' + data[i].ID + '">' + data[i].OPTION + '</option>';
			}
			calledElement.parent().find('select').html(html);
			 //$('select.month').append(html);
		},
  });
	
		chooserSearch_searchString = searchString;
	}
}

function form_chooserSearch_focus(event) {
	$(this).parent().find('input').show();
}