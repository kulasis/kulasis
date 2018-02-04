function focus_documentReady() {
  $('#focus_organization').on('change', focus_organization_change);
  $('#focus_term').on('change', focus_term_change);
}

function focus_organization_change(event) {
  $('#focus_form').submit();
}

function focus_term_change(event) {
  
  $('#window_bar > ul > .active').data('focus-term', $('#focus_term').val());

  var options = new Array();
  options['updateurl'] = 'N';
  
  navigation_createFirstWindow();
  
  // modify URL to match current tab
  var windowNumber = $('#window_bar > ul > .active').data('window');
  var currentURL = $('#window_' + windowNumber).data('window-url');
  urlToUse = currentURL;

	// Get current active tab 
	var activeTabID = $('#window_' + windowNumber + '_tab_bar > .nav-tabs > .active > a').prop('id');
	
	if (activeTabID == undefined) {
		// not in tab
		activeTabID = 'window_' + windowNumber + '_tab_';
	}
  
	var record_id = $('#' + activeTabID + '_content > .record_bar_tab_menu_data').data('record-id');
	var record_type = $('#' + activeTabID + '_content > .record_bar_tab_menu_data').data('record-type');
  
  if (urlToUse.indexOf('?') != -1) {
    var urlToUse = urlToUse + '&';
  } else {
    var urlToUse = urlToUse + '?';
  }
  
	urlToUse += "record_type=" + encodeURIComponent(record_type);
  urlToUse += "&record_id=" + encodeURIComponent(record_id);

  getLink(urlToUse, 'window', 'windows_container', options, function(msg, options) {

    // get currently selected panel
    var currentWindow = $('#window_bar > ul > .active').data('window');

    // replace all {panel_num} with new window number
    msg = navigation_replaceAllWindowIDPlaceholders(msg, currentWindow);
    navigation_updateWindow(currentWindow, msg, '', urlToUse);
  });
}