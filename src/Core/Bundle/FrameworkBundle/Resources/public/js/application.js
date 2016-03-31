jQuery(document).ready(function($) {

	navigation_documentReady();
	form_submitButton();
	focus_documentReady();
});

jQuery(document).ajaxComplete(function($) {
	
	if (jQuery('#login_form').length) {
  	window.top.location.href='/';
  }
	
	navigation_displayRecordBar();
	//navigation_syncWindowURL();
	form_dismissAlerts();
	form_submitButton();
});