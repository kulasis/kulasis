// Executed by document.ready
function navigation_documentReady() {
	// start navigation listeners
	navigation_startNavigationListeners();

	// if no drawer set to show, show first drawer
	if (!$('.navigation-forms.drawer-contents').css('display') == 'block')
		$('.navigation-forms.drawer-contents:first').show({ duration: 200, queue: false });
	
	if (!$('.navigation-reports.drawer-contents').css('display') == 'block')
		$('.navigation-reports.drawer-contents:first').show({ duration: 200, queue: false });
	
	navigation_windowListeners();
  
  $(".navigation-link").contextMenu({
      menuSelector: "#contextMenu",
      menuSelected: function (invokedOn, selectedMenu) {
        if (selectedMenu.data('action') == 'newtab') {
          navigation_drawerItemListenerNewWindow(invokedOn);
        }
      }
  });
}

/* Navigation Listeners */
function navigation_startNavigationListeners() {
	// Make drawer headers clickable
	$('.drawer-header').on('click', navigation_drawerListener);
	// Making nav bar items clickable
	$('#nav_bar_navigation ul li').on('click', navgiation_navigationBarListener);
	// Make items in navigation drawer clickable
	$("#nav_pane").on("click", ".navigation-link", navigation_drawerItemListenerExistingWindow);
	//$("#nav_pane").on("click", ".navigation-link-new-window", navigation_drawerItemListenerNewWindow);
}

// show or hide drawer listener
function navigation_drawerListener(event) {
	// get category set on drawer header that was clicked; concatenate in to get jquery obj
	var divToShowOrHide = $('#drawer-contents-' + $(this).data('category'));
	// get category
	var category = $(this).data('category').split('-', 1);
  
	// if drawer currently hidden
	if (divToShowOrHide.is(':hidden')) {
		
		if (category == 'forms') {
	  	// hide all other drawers
			$('.navigation-forms.drawer-contents').hide({ duration: 200, queue: false });
	  } else if (category == 'reports') {
	  	// hide all other drawers
			$('.navigation-reports.drawer-contents').hide({ duration: 200, queue: false });	
	  }
		// show drawer that was clicked
		divToShowOrHide.show({ duration: 200, queue: false });
	}
}

// show or hide navigation bar areas
function navgiation_navigationBarListener(event) {

	var divToShow = '#' + $(this).data('nav-panel');
	var classElement = $('.nav-bar-navigation-open');
	var divToHide = '#' + classElement.data('nav-panel');

	// if nav pane open && this item is selected
	if ($(this).is($('.nav-bar-navigation-open')) && !$('#nav_pane').is(':hidden')) {
		$(divToHide).hide();
		$("#nav_pane").hide({ duration: 200, queue: false });
		$("#main_container").css('margin-left', '0');
		$(this).removeClass('nav-bar-navigation-open');
	}
	
	// if nav pane hidden, show selected
	if ($('#nav_pane').is(':hidden')) {
		$(divToShow).show();
		$("#nav_pane").show({ duration: 200, queue: false });
		$("#main_container").css('margin-left', '15em');
		$(this).addClass('nav-bar-navigation-open');
	}
	
	// if nav pane selected is different from the one open
	if (!classElement.is($(this))) {
		$(divToHide).hide();
		$(divToShow).show();
		$(this).addClass('nav-bar-navigation-open');
		classElement.removeClass('nav-bar-navigation-open');
	}
	
}

function navigation_drawerItemListenerExistingWindow(event) {
  event.preventDefault();
  
  url = $(this).attr('href');
	
	var options = new Array();
	options['windowTitle'] = $(this).text();
	options['url'] = url;
	navigation_createFirstWindow();
	
	// Get current window number
	var windowNumber = $('#window_bar .selected-window-element').data('window');
  
  if (windowNumber == undefined) {
    navigation_drawerItemListenerNewWindow($(this));
    return;
  }
  
	// Get current active tab 
	var activeTabID = $('#window_' + windowNumber + '_tab_bar > .tabs > .active > a').prop('id');
	
	if (activeTabID == undefined) {
		// not in tab
		activeTabID = 'window_' + windowNumber + '_tab_';
	}
	
	var record_id = $('#' + activeTabID + '_content > .record_bar_tab_menu_data').data('record-id');
	var record_type = $('#' + activeTabID + '_content > .record_bar_tab_menu_data').data('record-type');

	url += "?record_type=" + encodeURIComponent(record_type);
	url += "&record_id=" + encodeURIComponent(record_id);

  getLink(url, 'window', 'windows_container', options, function(msg, options) {
		
		// get currently selected panel
		var currentWindow = $('.selected-window-element').data('window');
		// replace all window_num with new window number
		msg = navigation_replaceAllWindowIDPlaceholders(msg, currentWindow);

		navigation_updateWindow(currentWindow, msg, options['windowTitle'], options['url']);
		
	}); 
}

function navigation_drawerItemListenerNewWindow(invokedOn) {
  //event.preventDefault();
  url = invokedOn.attr('href');
	
	navigation_createFirstWindow();
	
	var options = new Array();
	options['windowTitle'] = invokedOn.text();
	
  getLink(url, 'window', 'windows_container', options, function(msg, options) {
		// create new window
		var newWindowNum = navigation_createWindow(options['url']);
		// replace all {panel_num} with new window number
		msg = navigation_replaceAllWindowIDPlaceholders(msg, newWindowNum);
		navigation_updateWindow(newWindowNum, msg, options['windowTitle'], url);
		// Set listeners on Window Bar
		navigation_windowBarListeners(newWindowNum);
	}); 
}

function navigation_updateWindow(num, html, title, url) {
	
	// Update html
  $('#window_' + num).html(html);
	// Update window name
	if (title != '')
		$('#windowTitle_' + num).html('<div><span class="close-window">x</span> ' + title + '</div>');
		
	if (url) {
	 if (url.indexOf('?') != -1) {
	   var quest_pos = url.indexOf('?');
		 url = url.substring(0, quest_pos);
	 } 
	
  // Update window url
	$('#windowTitle_' + num).data('window-url', url);
	$('#window_' + num).data('window-url', url);
	}
	
	navigation_windowListeners(num);
	navigation_windowBarListeners(num);
	
}


/* Window Bar */
function navigation_windowBarListeners(windowNum) {
	// Make windowTitles clickable
	$('#window_bar > ul > li').on('click', navigation_changeFocusedWindow);
	// Make X clickable
	$('#windowTitle_' + windowNum + ' > div > .close-window').on('click', navigation_closePanelWindow);
}

function navigation_changeFocusedWindow(event) {
	event.preventDefault();
	windowNum = $(this).data('window');
	
	$('#window_bar > ul > li').removeClass('selected-window-element');
	$(this).addClass('selected-window-element');
	
	$('#windows_container > div').hide();
	$('#window_' + windowNum).show();
	focus_organizationterm_update();
	
	window.history.pushState({"html":null,"pageTitle":null},"", $(this).data('window-url'));
}

function navigation_closePanelWindow(event) {
	event.preventDefault();
	windowNum = $(this).parent().parent().data('window');

	$('#window_' + windowNum).remove();
	$('#windowTitle_' + windowNum).remove();
	
	// show last one in panels container
	$('#windows_container > div').hide();
	$('#windows_container > div').last().show();
	var panelToShow = $('#windows_container > div').last().data('window');
	$('#window_bar > ul > li').removeClass('selected-window-element');
	$('#windowTitle_' + panelToShow).addClass('selected-window-element');
	
	window.history.pushState({"html":null,"pageTitle":null},"", $('#windowTitle_' + panelToShow).data('window-url'));	
}

function navigation_windowListeners(windowNum) {
	windowNum = windowNum || '';

	if (windowNum) {
	  var elementToFind = '#window_' + windowNum ;
	} else {
		var elementToFind = '#window_window_num';
	}
	
	// Set listeners on record movers
	$(elementToFind + '_menu_bar').find('.window_menu_bar_record').children('span').on('click', navigation_menuBarRecord);
	// set listeners on menus
	$(elementToFind + '_menu_actions > ul > li').on('click', 'a', navigation_menuListener);
	// set listeners on menus
	$(elementToFind + '_menu_reports > ul > li').on('click', 'a', navigation_menuListener);
  // set tab listeners on any tabs
  $(elementToFind + '_tab_bar').on('click', '.window-tab-link', navigation_tabListener);   
	// set listener on form submission
	form_formListeners(elementToFind);
	// reload button listener
	if ($(elementToFind + '_button_reload').data('listener_set') == undefined) {
		$(elementToFind + '_button_reload').on('click', navigation_reloadListener);
		$(elementToFind + '_button_reload').data('listener_set', 'Y');
	}
	
	navigation_tabListeners(windowNum);
}

function navigation_reloadListener(event) {
	event.preventDefault();
	
	navigation_createFirstWindow();
	
	// modify URL to match current tab
	var windowNumber = $('#window_bar .selected-window-element').data('window');
	var currentURL = $('#window_' + windowNumber).data('window-url');

	// modify URL to match current tab
	var windowNumber = $('#window_bar .selected-window-element').data('window');
	var currentURL = $('#window_' + windowNumber).data('window-url');
	urlToUse = currentURL;
	
	// Get current window number
	var windowNumber = $('#window_bar .selected-window-element').data('window');
	// Get current active tab 
	var activeTabID = $('#window_' + windowNumber + '_tab_bar > .tabs > .active > a').prop('id');

	if (activeTabID == undefined) {
		// not in tab
		activeTabID = 'window_' + windowNumber + '_tab_';
	}
	
	var record_id = $('#' + activeTabID + '_content > .record_bar_tab_menu_data').data('record-id');
	var record_type = $('#' + activeTabID + '_content > .record_bar_tab_menu_data').data('record-type');
	
	var urlToAdd = "focus_org=" + encodeURIComponent($('.selected-window-element').data('focus-org'));
  urlToAdd += "&focus_term=" + encodeURIComponent($('.selected-window-element').data('focus-term'));
	urlToAdd += "&record_type=" + encodeURIComponent(record_type);
	urlToAdd += "&record_id=" + encodeURIComponent(record_id);
	
  if (urlToUse.indexOf('?') != -1) {
    var urlToUse = urlToUse + '&' + urlToAdd;
  } else {
    var urlToUse = urlToUse + '?' + urlToAdd;
  }
	
	var options = new Array();
  
	getLink(urlToUse, 'window', 'windows_container', options, function(msg, options) {
		// get currently selected panel
		var currentWindow = $('.selected-window-element').data('window');
		// replace all {panel_num} with new window number
		msg = navigation_replaceAllWindowIDPlaceholders(msg, currentWindow);
		navigation_updateWindow(currentWindow, msg, '', currentURL);
	});
	
}

function navigation_tabListeners(windowNum) {

	if (/^\d+$/.test(windowNum)) {
		var elementToFind = '#window_' + windowNum;
	} else if (windowNum) {
	  var elementToFind = windowNum;	
		var elementForDataTableDetail = '#window_' + windowNum;
	} else {
		var elementToFind = '#window_window_num_content';
	}
	
	if (elementForDataTableDetail) {
		$(elementForDataTableDetail).on('click', '.data-table-detail-link', navigation_detailLinkListener);	
	} else {
		$(elementToFind).on('click', '.data-table-detail-link', navigation_detailLinkListener);
	}
	
	$('#window_' + windowNum + '_content').on('click', 'a.normal-link', navigation_link);

	form_formContentListeners(elementToFind);
}

function navigation_link(event) {
	event.preventDefault();
	var url = $(this).attr('href');
	
	// Get current window number
	var windowNumber = $('#window_bar .selected-window-element').data('window');

	var urlToAdd = 'scrub=' + $(this).data('scrubber');
	if ($(this).data('record-id')) urlToAdd += '&record_id=' + $(this).data('record-id');
	if ($(this).data('record-type')) urlToAdd += '&record_type=' + $(this).data('record-type');

  if (url.indexOf('?') != -1) {
    var url = url + '&' + urlToAdd;
  } else {
    var url = url + '?' + urlToAdd;
  }
	
	getLink(url, 'window', 'window_' + windowNumber, null, function(msg, options) {
		msg = navigation_replaceAllWindowIDPlaceholders(msg, windowNumber);
		$('#window_' + windowNumber).html(msg);
		navigation_windowListeners(windowNumber);
	});
}

function navigation_createFirstWindow() {
	var numberOfWindows = $('#windows_container > .window').last().data('window');
	var panelsContainer = $('#windows_container');
	
	if (!numberOfWindows || numberOfWindows <= 0) {
		// detach all elements
		var elementsToReattach = panelsContainer.contents();
		panelsContainer.empty();
		panelsContainer.append('<div id="window_1" class="window" data-window="1" data-window-url="' + $('#windowTitle_1').data('window-url') + '"></div>');
		$('#window_1').html(elementsToReattach);
		$('#window_1').html(navigation_replaceAllWindowIDPlaceholders($('#window_1').html(), 1));
		navigation_windowListeners(1);
		navigation_windowBarListeners(1);
	}
}


function navigation_createWindow(url) {	
	var panelsContainer = $('#windows_container');
	var numberOfWindows = $('#windows_container > .window').last().data('window');

	var newWindowNum = numberOfWindows + 1;
	
	// insert child div
	panelsContainer.append('<div id="window_' + newWindowNum + '" class="window" data-window="' + newWindowNum + '" data-window-url="' + url + '" data-focus-org="' + $('#focus_organization').val() + '" data-focus-term="' + $('#focus_term').val() + '"></div>');
	
	// hide all showing
	var panelContainers = $('#windows_container > div');
	if (panelContainers)
		$('#windows_container > div').hide();

	// show the new container
	$('#window_' + newWindowNum).show();
	
	// create window elements
	$('#window_bar > ul > li').removeClass('selected-window-element');
	$('#window_bar > ul').append('<li id="windowTitle_' + newWindowNum + '" class="selected-window-element" data-window="' + newWindowNum + '" data-window-url="' + url + '" data-focus-org="' + $('#focus_organization').val() + '" data-focus-term="' + $('#focus_term').val() + '"><span class="close-window">x</span> Window ' + newWindowNum + '</li>');
	
	return newWindowNum;
}

function navigation_menuBarRecord(event) {
  event.preventDefault();

	var options = new Array();
	options['updateurl'] = 'N';
	
	navigation_createFirstWindow();
	
	// modify URL to match current tab
	var windowNumber = $('#window_bar .selected-window-element').data('window');
	var currentURL = $('#window_' + windowNumber).data('window-url');
	urlToUse = currentURL;
	
	var urlToAdd = 'scrub=' + $(this).data('scrubber');
	if ($(this).data('record-id')) urlToAdd += '&record_id=' + $(this).data('record-id');
	if ($(this).data('record-type')) urlToAdd += '&record_type=' + $(this).data('record-type');
	
  if (urlToUse.indexOf('?') != -1) {
    var urlToUse = urlToUse + '&' + urlToAdd;
  } else {
    var urlToUse = urlToUse + '?' + urlToAdd;
  }
	
  getLink(urlToUse, 'window', 'windows_container', options, function(msg, options) {
		// get currently selected panel
		var currentWindow = $('.selected-window-element').data('window');
		// replace all {panel_num} with new window number
		msg = navigation_replaceAllWindowIDPlaceholders(msg, currentWindow);
		navigation_updateWindow(currentWindow, msg, '', urlToUse);
	});
}

function navigation_menuListener(event) {
	event.preventDefault();
  urlToUse = $(this).attr('href');
		
	var options = new Array();
	options['windowTitle'] = $(this).text();
  options['updateurl'] = 'N';
	
	navigation_createFirstWindow();
	
	method = $(this).data('http-method');
	confirmText = $(this).data('confirm');
	
	// Get current active tab 
	var windowNumber = $('#window_bar .selected-window-element').data('window');
	var activeTabID = $('#window_' + windowNumber + '_tab_bar > .tabs > .active > a').prop('id');

	if (activeTabID == undefined) {
		// not in tab
		activeTabID = 'window_' + windowNumber + '_tab_';
	}

	var record_id = $('#' + activeTabID + '_content > .record_bar_tab_menu_data').data('record-id');
	var record_type = $('#' + activeTabID + '_content > .record_bar_tab_menu_data').data('record-type');
	
	var urlToAdd = 'scrub=';
	if (record_id) urlToAdd += '&record_id=' + record_id;
	if (record_type) urlToAdd += '&record_type=' + record_type;
	
  if (urlToUse.indexOf('?') != -1) {
    var urlToUse = urlToUse + '&' + urlToAdd;
  } else {
    var urlToUse = urlToUse + '?' + urlToAdd;
  }
	
   getLink(urlToUse, 'window', 'windows_container', options, function(msg, options) {
			// get currently selected panel
			var currentWindow = $('.selected-window-element').data('window');
			// replace all {panel_num} with new window number
			msg = navigation_replaceAllWindowIDPlaceholders(msg, currentWindow);
			navigation_updateWindow(currentWindow, msg, options['windowTitle'], null);
		}, method, confirmText); 
}

function navigation_tabListener(event) {

  event.preventDefault();
  thisurl = $(this).attr('href');
		
	navigation_createFirstWindow();
		
	var clickedID = $(this).attr('id');
		
	if (clickedID.indexOf('window_num') >= 0) {
		clickedID = navigation_replaceAllWindowIDPlaceholders(clickedID, 1)
	}
	
		options = new Array();
		options['tabID'] = clickedID;
		var ifExists = $('#' + options['tabID'] + '_content').length;
		var currentWindowNum = $('#' + clickedID).parent().parent().parent().parent().parent().data('window');
		//console.log(options['tabID']);
		var currentURL = $('#' + options['tabID'] + '_content').data('window-url');
		//alert(currentURL);
		if (ifExists > 0 && currentURL.indexOf(thisurl) > -1) {
			//var strlen = currentURL.indexOf('?');
			var strlen = thisurl.length;
		} else if (ifExists > 0) {
			var strlen = currentURL.length;
		} else {
			var currentURL = $('#' + clickedID).parent().parent().parent().parent().parent().data('window-url');
		}

		//currentURL = currentURL.substring(0, strlen);
		
		// Get current window number
		var windowNumber = $('#window_bar .selected-window-element').data('window');
		// Get current active tab 
		var activeTabID = $('#window_' + windowNumber + '_tab_bar > .tabs > .active > a').prop('id');

		if (activeTabID == undefined) {
			// not in tab
			activeTabID = 'window_' + windowNumber + '_tab_';
		}

		var dataMode = $('#' + activeTabID + '_content > .record_bar_tab_menu_data').data('mode');
	
		if (dataMode == 'search')
			var replaceArea = 'tab';
		else
			var replaceArea = 'window';

		if (currentURL != thisurl || replaceArea == 'window') {
				
			// Get current window number
			var windowNumber = $('#window_bar .selected-window-element').data('window');
			// Get current active tab 
			var activeTabID = $('#window_' + windowNumber + '_tab_bar > .tabs > .active > a').prop('id');
	
			if (activeTabID == undefined) {
				// not in tab
				activeTabID = 'window_' + windowNumber + '_tab_';
			}
			
			var record_id = $('#' + activeTabID + '_content > .record_bar_tab_menu_data').data('record-id');
			var record_type = $('#' + activeTabID + '_content > .record_bar_tab_menu_data').data('record-type');
			
		  thisurl = thisurl.split("?");
			var urltouse = thisurl[0] + "?focus_org=" + encodeURIComponent($('.selected-window-element').data('focus-org'));
		  urltouse += "&focus_term=" + encodeURIComponent($('.selected-window-element').data('focus-term'));
			urltouse += "&record_type=" + encodeURIComponent(record_type);
			urltouse += "&record_id=" + encodeURIComponent(record_id);

	    getLink(urltouse, replaceArea, 'window_content', options, function(msg, options) {
				
				if (replaceArea == 'tab') {
					if ($('#' + options['tabID'] + '_content').length == 0)
						navigation_createTabContent(options['tabID'], thisurl);
					msg = msg.replace(/window_window_num/g, options['tabID']);
					$('#' + options['tabID'] + '_content').html(msg);
					navigation_tabListeners('#' + options['tabID'] + '_content');
				} else {
					// get currently selected panel
					var currentWindow = $('.selected-window-element').data('window');
					// replace all {panel_num} with new window number
					msg = navigation_replaceAllWindowIDPlaceholders(msg, currentWindow);
					navigation_updateWindow(currentWindow, msg, '', null);
				}
				
			}); 
		}
		
		// hide all showing
		var panelsContainerID = $('#' + options['tabID']).data('window');
		$('#window_' + panelsContainerID + '_content > div').hide();

		// show the new container
		$('#' + options['tabID'] + '_content').show();
		$('#' + options['tabID'] + '_content').data('window-url', thisurl);
	
		// change active tab
		$('#' + options['tabID']).parent().parent().children().removeClass('active');
		$('#' + options['tabID']).parent().addClass('active');
		
		//var tabTitle = $('#' + options['tabID']).html();
		
		//if (tabTitle)
			//$('#windowTitle_' + currentWindowNum).html('<div><span class="close-window">x</span> ' + tabTitle + '</div>');
	  // Update window url
		$('#windowTitle_' + currentWindowNum).data('window-url', thisurl);
		$('#window_' + currentWindowNum).data('window-url', thisurl);
		// reset listeners
		navigation_windowBarListeners(currentWindowNum);
		$('#window_' + currentWindowNum + '_form').prop('action', thisurl);
		//$('#window_' + panelsContainerID + '_content').prop('action', thisurl);
		window.history.pushState({"html":null,"pageTitle":null},"", thisurl);
		
		 navigation_displayRecordBar();
	 	form_submitButton();
}


function navigation_createTabContent(tabID, url) {
	
	// container
	var panelsContainerID = $('#' + tabID).data('window');
	var panelsContainer = $('#window_' + panelsContainerID + '_content');
	// insert child div
	panelsContainer.append('<div id="' + tabID + '_content" class="tab_content" data-window-url="' + url + '"></div>');

}

function navigation_displayRecordBar() {
	
	// Get current window number
	var windowNumber = $('#window_bar .selected-window-element').data('window');
	// Get current active tab 
	var activeTabID = $('#window_' + windowNumber + '_tab_bar > .tabs > .active > a').prop('id');
	
	if (activeTabID == undefined) {
		// not in tab
		activeTabID = 'window_' + windowNumber + '_tab_';
	}

	if ($('#' + activeTabID + '_content').find('.record_bar').length > 0) {
		$('#window_' + windowNumber + '_menu_bar').find('.window_menu_bar_record').show();
	} else {
		$('#window_' + windowNumber + '_menu_bar').find('.window_menu_bar_record').hide();	
	}
	
}

function navigation_syncWindowURL() {
	// Get current window number
	var windowNumber = $('#window_bar .selected-window-element').data('window');
	// Get current active tab 
	var activeTabID = $('#window_' + windowNumber + '_tab_bar > .tabs > .active > a').prop('id');
	
	var currentURL = $('#' + activeTabID + '_content').data('window-url');
	// Get current URL
	if (currentURL) {
		$('#windowTitle_' + windowNumber).data('window-url', currentURL);
		$('#window_' + windowNumber).data('window-url', currentURL);
		window.history.pushState({"html":null,"pageTitle":null},"", currentURL);
	}
}


function getLink(url, divWindow, divToLoad, options, onsuccess, method, confirmText) {
	
	if (!options) var options = new Array;
	
  // determine if no query string on URL or if something on query string
  if (divWindow) {
    var urlToAdd = "partial=" + divWindow;
  }

  if (url.indexOf('?') != -1) {
    var urlToRequest = url + '&' + urlToAdd;
  } else {
    var urlToRequest = url + '?' + urlToAdd;
  }

	if (confirmText && confirmText.length > 0) {
		var confirmResponse = confirm(confirmText);
	}
	
	if ((confirmText && confirmText.length > 0 && confirmResponse) || confirmText == undefined || confirmText.length == 0) {
	
	
	if (method == null)
		method = 'GET';
		
		var form_data = urlToRequest;
		form_data += "&focus_org=" + encodeURIComponent($('.selected-window-element').data('focus-org'));
	  form_data += "&focus_term=" + encodeURIComponent($('.selected-window-element').data('focus-term'));
		
		options['divWindow'] = divWindow;

  $.ajax({
		type: method,
		url: form_data,
		beforeSend: function(msg) {
			$('#nav_bar_status > span').hide();
			$('#nav_bar_status_loading').show();
		},
		complete: function(msg, status) {
			$('#nav_bar_status > span').hide();
			$('#nav_bar_status_success').show();
			setTimeout(function() {
				$('#nav_bar_status > span').hide();
				$('#nav_bar_status_ready').show();
			}, 2000);
		},
		error: function(msg) {
			$('#nav_bar_status > span').hide();
			$('#nav_bar_status_error').show();
		  // update url & history
			options['url'] = url;
			window.history.pushState({"html":msg.responseText,"pageTitle":null},"", url);
			$('#modal_iframe').contents().find('html').html(msg.responseText);
			$('.modal-footer').on('click', 'button', function(event) {
				$('#myModal').hide();
			});
			$('#myModal').show();
		},
		success: function(msg) {
		  // update url & history
			if (msg.type == 'form_error') {
				// Get current window number
				var windowNumber = $('#window_bar .selected-window-element').data('window');
				// Get current active tab 
				var activeTabID = $('#window_' + windowNumber + '_tab_bar > .tabs > .active > a').prop('id');
	
				if (activeTabID == undefined) {
					// not in tab
					activeTabID = 'window_' + windowNumber + '_tab_';
				}
				
				// set error message		
				var alert_element = $('#' + activeTabID + '_alert');
				alert_element.html(msg.message);
				alert_element.addClass('alert alert-error');
			} else {
			if (options['divWindow'] != 'detail' && options['updateurl'] != 'N') {
				window.history.pushState({"html":msg,"pageTitle":null},"", options['url']);
		  }
			if (onsuccess) onsuccess(msg, options);
		  }
		},
  });
	
  } // end confirm text/confirm response check
}

function navigation_replaceAllWindowIDPlaceholders(stringToReplace, windowNumber) {
	return stringToReplace.replace(/window_num/g, windowNumber);
}

function navigation_detailLinkListener(event) {
	event.preventDefault();
	// Get URL to call
	var url = $(this).prop('href');
	var rowid = $(this).data('row-id');
	var rowidprefix = $(this).data('row-id-prefix');
	var selectedRow = $(this).parent().parent().prop('id');
	
	navigation_createFirstWindow();
	
	// Get current window number
	var windowNumber = $('#window_bar .selected-window-element').data('window');
	// Get current active tab 
	var activeTabID = $('#window_' + windowNumber + '_tab_bar > .tabs > .active > a').prop('id');
	
	if (activeTabID == undefined) {
		// not in tab
		activeTabID = 'window_' + windowNumber + '_tab_';
	}
	
	// Get window detail
	var windowDetailDiv = $('#' + activeTabID + '_detail');

	var detailID = activeTabID + '_detail_' + rowidprefix + '_' + rowid;
	
	var record_id = $('#' + activeTabID + '_content > .record_bar_tab_menu_data').data('record-id');
	var record_type = $('#' + activeTabID + '_content > .record_bar_tab_menu_data').data('record-type');
	
	url = url.split("?");
	var urltouse = url[0] + "?focus_org=" + encodeURIComponent($('.selected-window-element').data('focus-org'));
  urltouse += "&focus_term=" + encodeURIComponent($('.selected-window-element').data('focus-term'));
	urltouse += "&record_type=" + encodeURIComponent(record_type);
	urltouse += "&record_id=" + encodeURIComponent(record_id);
	
	// hide any children
	$(windowDetailDiv).children().hide();
	// highlight selected row data-table-highlighted-row
	selectedRow = navigation_replaceAllWindowIDPlaceholders(selectedRow, 1);
	
	$('#' + selectedRow).parent().children().removeClass('data-table-highlighted-row');
	$('#' + selectedRow).addClass('data-table-highlighted-row');
	
	if ($('#' + detailID).length == 0) {

		windowDetailDiv.append('<div id="' + detailID + '" class="window-detail" data-window-url="' + url + '"><div class="window-detail-close">x Close Window</div><div id="' + detailID + '_content" class="window-detail-content"></div></div>');
	
		options = new Array();
		 options['updateurl'] = 'N';
		getLink(urltouse, 'detail', detailID, options, function(msg, options) {
			$('#' + detailID + '_content').html(msg);
			form_formContentListeners('#' + detailID + '_content');
		});

		$('#' + detailID).on('click', '.window-detail-close', navigation_detailCloseDiv);
		
	} else {
		// show existing div
		$('#' + detailID).show();
	}
	
}

function navigation_detailCloseDiv(event) {
	$(this).parent().hide();
	
	var windowNumber = $('#window_bar .selected-window-element').data('window');
	var activeTabID = $('#window_' + windowNumber + '_tab_bar > .tabs > .active > a').prop('id');
	if (activeTabID == undefined) {
		// not in tab
		activeTabID = 'window_' + windowNumber + '_tab_';
	}
	
	$('#' + activeTabID + '_content').find('.data-table-highlighted-row').removeClass('data-table-highlighted-row');
}
