function form_formListeners(windowNum) {
	// Form submission
	$(windowNum + '_form').on('submit', formSubmission);
}

function form_formContentListeners(windowNum) {
	// Set item listeners on checkboxes
	$(windowNum).find('.form-delete-checkbox').on('click', highlightCheckBoxRow);
	// add buttons on tables
	$(windowNum).find('.data-table-button-add').on('click', addRowToFormDataTable);
	// Added checkboxes
	$(windowNum).find('.form-delete-checkbox-add').on('click', deleteAddedRow);
	// Chooser listener
	//$(windowNum).find('select.chooser-search').on('focus', form_chooserSearch_focus);
	//$(windowNum).find('.chooser-search').on('keyup', form_chooserSearch_keyup);
	//$(windowNum).find('.chooser-search').on('keydown', form_chooserSearch_keydown);
}

function form_dismissAlerts() {
	$('.alert-success').delay(3200).fadeOut('fast');
}

function form_submitButton() {

	// Get current window number
	var windowNumber = $('#window_bar > ul > .active').data('window');
	// Get current active tab 
	var activeTabID = $('#window_' + windowNumber + '_tab_bar > .nav-tabs > .active > a').prop('id');

	if (activeTabID == undefined) {
		// not in tab
		activeTabID = 'window_' + windowNumber + '_tab_';
	}

	if ($('#' + activeTabID + '_content').find('.record_bar').length > 0) {
		var recordBarTabData = $('#' + activeTabID + '_content > .record_bar_tab_menu_data');
		
		if (recordBarTabData.data('mode') == 'search') {
			$('#window_' + windowNumber + '_form_submit').prop('value', 'Search');
			$('.data-table-button-add').prop('disabled', 'disabled');
			$('#window_' + windowNumber + '_form_mode').prop('value', 'search');
		}
		else {
		$('#window_' + windowNumber + '_form_submit').prop('value', 'Submit');
			$('#window_' + windowNumber + '_form_mode').prop('value', 'edit');
		$('.data-table-button-add').prop('disabled', '');
		}
	} else {
		$('#window_' + windowNumber + '_form_submit').prop('value', 'Submit');
		$('#window_' + windowNumber + '_form_mode').prop('value', 'edit');
		$('.data-table-button-add').prop('disabled', '');
	}
	
}

function formSubmission(event, form_obj) {
	if (form_obj == null) {
    form_obj = $(this);
	}
	if (form_obj.data('navigation-type') != 'report' && form_obj.data('navigation-type') != 'menu_report') {
	event.preventDefault();
	thisurl = form_obj.prop('action');
	
	var idOfForm = form_obj.prop('id');
	idOfForm = navigation_replaceAllWindowIDPlaceholders(idOfForm, 1);
	idOfDiv = idOfForm.replace(/\_form/g, '');

	navigation_createFirstWindow();
	
	// disable submit and loading buttons
	$('#' + idOfForm + '_submit').prop('disabled', 'disabled');
	
	var options = new Array;
	
	$('#nav_bar_status > span').hide();
	$('#nav_bar_status_loading').show();
	
	processForm(form_obj, thisurl, 'window', idOfDiv, options, function(msg, options) {
		msg = navigation_replaceAllWindowIDPlaceholders(msg, $('#' + idOfDiv).data('window'));
		navigation_updateWindow($('#' + idOfDiv).data('window'), msg, '', thisurl);
		// disable submit and loading buttons
		
    $('#' + idOfForm + '_submit').removeProp('disabled');
	
		$('#nav_bar_status > span').hide();
		$('#nav_bar_status_success').show();
		setTimeout(function() {
			$('#nav_bar_status > span').hide();
			$('#nav_bar_status_ready').show();
		}, 3000);
		
		
	}, function(msg, options) {
		$('#' + idOfForm + '_submit').removeProp('disabled');
		
		$('#nav_bar_status > span').hide();
		$('#nav_bar_status_error').show();
		setTimeout(function() {
			$('#nav_bar_status > span').hide();
			$('#nav_bar_status_ready').show();
		}, 3000);
	});
  
	} // end if on data
}

function addRowToFormDataTable(event) {
	event.preventDefault();
	// get table to add to
	var tableToAdd = $(this).data('table');
	// get template row
	var templateRow = $('#' + tableToAdd + ' > tbody > tr.data-table-row-new');
	// get count of rows
	var tableRowCount = $('#' + tableToAdd + ' > tbody > tr').length;
	// add template row to table
	var clonedRow = templateRow.clone();

	clonedRow.show();
	clonedRow.removeClass('data-table-row-new');
	clonedRow.addClass('data-table-row-added');
	clonedRow.children('.data-table-cell-row-num').html(tableRowCount);
	
	var clonedRowHtml = clonedRow.html();
	clonedRowHtml = clonedRowHtml.replace(/\[new_num\]/g, '[' + tableRowCount + ']');
	
	clonedRowHtml = '<tr>' + clonedRowHtml + '</tr>';
	
	$('#' + tableToAdd + ' > tbody > tr:last').after(clonedRowHtml);

	$('.form-delete-checkbox-add').on('click', deleteAddedRow);
	
	// set focus to first field
	$('#' + tableToAdd + ' > tbody > tr:last').find('select:visible:enabled:first,input:not(.form-delete-checkbox-add):visible:enabled:first').focus();
	
	// Chooser listener
	//$('#' + tableToAdd + ' > tbody > tr:last > td').find('.chooser-search').on('focus', form_chooserSearch_focus);
	//$('#' + tableToAdd + ' > tbody > tr:last > td').find('.chooser-search').on('keyup', form_chooserSearch_keyup);
	//$('#' + tableToAdd + ' > tbody > tr:last > td').find('.chooser-search').on('keydown', form_chooserSearch_keydown);
  
  $('#' + tableToAdd + ' > tbody > tr:last > td').find('select:not(.chooser-search)').select2();
	$('#' + tableToAdd + ' > tbody > tr:last > td').find('.chooser-search').select2({
  	
  	ajax: {
      processResults: function (data) {
            return {
                results: $.map(data, function(obj) {
                    return { id: obj.id, text: obj.text };
                })
            };
        }
    }
  	
	});
	
}

function deleteAddedRow(event) {
	$(this).parent().parent().remove();
}

function highlightCheckBoxRow(event) {
	
	var selectedRow = $(this).parent().parent();

	if ($(this).is(':checked') && selectedRow.hasClass('data-table-deleted-row') == false) {
		// find any children with .data-table-detail-link
		selectedRow.find('.data-table-detail-link').each(function(index) {
			if ($(this).data('href') == undefined) {
				$(this).data('href', $(this).prop('href'));
			}
			$(this).prop('href', '#');
		});
		
		selectedRow.addClass('data-table-deleted-row');
		selectedRow.children().children().prop('disabled', true );
		$(this).prop('disabled', false);
	} else {
		// find any children with .data-table-detail-link
		selectedRow.find('.data-table-detail-link').each(function(index) {
			$(this).prop('href', $(this).data('href'));
		});
		 
		selectedRow.removeClass('data-table-deleted-row');
		selectedRow.children().children().prop('disabled', false);
	}
}

function processForm(form, url, partial, divToLoad, options, onsuccess, onerrorfunc) {

  // determine if no query string on URL or if something on query string
  if (partial) {
    var urlToAdd = "partial=" + partial;
  }

  if (url.indexOf('?') != -1) {
    var urlToRequest = url + '&' + urlToAdd;
  } else {
    var urlToRequest = url + '?' + urlToAdd;
  }
	
	// Get current window number
	var windowNumber = $('#window_bar > ul > .active').data('window');
	// Get current active tab 
	var activeTabID = $('#window_' + windowNumber + '_tab_bar > .nav-tabs > .active > a').prop('id');
	
	if (activeTabID == undefined) {
		// check to see if existing tab exists
		var existing_tab = $('#window_' + windowNumber + '_content > div:first-child').prop('id');
		
		if (existing_tab) {
			if (existing_tab.indexOf('content') != -1) {
				activeTabID = existing_tab.substring(0, existing_tab.indexOf('content')-1);
			} else {
				activeTabID = existing_tab;
			}
			
		} else {
			// not in tab
			activeTabID = 'window_' + windowNumber + '_tab_';
		}
	}
	
	// clear all field errors
	$('#' + activeTabID + '_content').find('.field_error').removeClass('field_error');
	var record_id = $('.record_bar_tab_menu_data').data('record-id'); // #' + activeTabID + '_content > 
	var record_type = $('.record_bar_tab_menu_data').data('record-type'); // #' + activeTabID + '_content > 
	
	if (window.FormData) {
    	var form_data = new FormData(form[0]);
    	form_data.append('focus_org', encodeURIComponent($('#window_bar > ul > .active').data('focus-org')));
    	form_data.append('focus_term', encodeURIComponent($('#window_bar > ul > .active').data('focus-term')));
    	form_data.append('record_type', encodeURIComponent(record_type));
    	form_data.append('record_id', encodeURIComponent(record_id));
    } else {
    	var form_data = form.serialize();
		form_data += "&focus_org=" + encodeURIComponent($('#window_bar > ul > .active').data('focus-org'));
  		form_data += "&focus_term=" + encodeURIComponent($('#window_bar > ul > .active').data('focus-term'));
		form_data += "&record_type=" + encodeURIComponent(record_type);
		form_data += "&record_id=" + encodeURIComponent(record_id);
    }
		   
  $.ajax({
		type: 'POST',
		url: urlToRequest,
		data: form_data,
   		processData: false,
    	contentType: false,
		error: function(msg) {
			
			  // update url & history
				options['url'] = url;
				
			window.history.pushState({"html":msg.responseText,"pageTitle":null},"", url);
			$('#modal_iframe').contents().find('html').html(msg.responseText);
			$('.modal-footer').on('click', 'button', function(event) {
				$('#myModal').hide();
			});
			$('#myModal').show();
			// disable submit and loading buttons
			if (onerrorfunc) onerrorfunc(msg, options);		
		},
		success: function(msg) {
			
			//var json_response = jQuery.parseJSON(msg);
			if (msg.type == 'form_error') {
				// disable submit and loading buttons
				if (onerrorfunc) onerrorfunc(msg, options);	
				
				// set error message
				var alert_element = $('#' + activeTabID + '_content > div.window_content').prepend("<div class=\"alert alert-error\">" + msg.message +"</div>");
        
				//alert_element.html(msg.message);
				//alert_element.addClass('alert alert-error');
				
				if (msg.fields) {
				$.each(msg.fields, function(i, item) {
					$('#' + activeTabID + '_content').find('input[name=\'' + item +'\'],select[name=\'' + item +'\']').addClass('field_error');
				});
				}
			} else {
				
			// update url & history
			options['url'] = url;
			if (onsuccess) onsuccess(msg, options);
			}
		},
  });
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