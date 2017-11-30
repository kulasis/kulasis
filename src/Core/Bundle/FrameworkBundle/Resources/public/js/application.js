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

function submitFormWithKeys(event) {
  // Get current window number
  var windowNum = $('#window_bar > ul > .active').data('window');
  windowNum = windowNum || '';

  if (windowNum) {
    var elementToFind = '#window_' + windowNum ;
  } else {
    var elementToFind = '#window_window_num';
  }

  formSubmission(event, $(elementToFind + '_form'));
}

// Save Form
jQuery(document).keypress(function(event) {
  if (event.which == 115 && (event.ctrlKey||event.metaKey)|| (event.which == 19)) {
    event.preventDefault();

    submitFormWithKeys(event);

    return false;
  }
  return true;
});

$(document).bind('keydown', function(e) {
  if(e.ctrlKey && (e.which == 83)) {
    e.preventDefault();
    submitFormWithKeys(e);
    return false;
  }
});
