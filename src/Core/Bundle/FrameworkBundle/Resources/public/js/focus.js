function focus_documentReady() {
  $('#focus_usergroup').on('change', focus_usergroup_change);
  $('#focus_organization').on('change', focus_organizationterm_change);
  /* $("#focus_term").quickselect({
autoSelectFirst: true,
inputClass: 'focus-term-input'
                });  */
  $('#focus_term').on('change', focus_organizationterm_change);
  
}

function focus_usergroup_change(event) {
  $('#role_form').submit();
}

function focus_organizationterm_change(event) {
  $('.selected-window-element').data('focus-org', $('#focus_organization').val());
  $('.selected-window-element').data('focus-term', $('#focus_term').val());
  
  var options = new Array();
  options['updateurl'] = 'N';
  
  navigation_createFirstWindow();
  
  // modify URL to match current tab
  var windowNumber = $('#window_bar .selected-window-element').data('window');
  var currentURL = $('#window_' + windowNumber).data('window-url');
  urlToUse = currentURL;

	// Get current active tab 
	var activeTabID = $('#window_' + windowNumber + '_tab_bar > .tabs > .active > a').prop('id');
	
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
    var currentWindow = $('.selected-window-element').data('window');
    // replace all {panel_num} with new window number
    msg = navigation_replaceAllWindowIDPlaceholders(msg, currentWindow);
    navigation_updateWindow(currentWindow, msg, '', urlToUse);
  });
}

function focus_organizationterm_update(event) {
  $('#focus_organization').val($('.selected-window-element').data('focus-org'));
  $('#focus_term').val($('.selected-window-element').data('focus-term'));
}