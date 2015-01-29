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
	$('#focus_form').submit();
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