$(function() {
	focus_documentReady();  
});

function focus_documentReady() {
  //$('#focus_usergroup').on('change', focus_usergroup_change);
  
  $('#focus_school').on('change', function() { $('#record_form').submit(); });
  $('#focus_term').on('change', function() { $('#record_form').submit(); });
  
  $('#admin_focus_school').on('change', function() { $('#admin_focus_form').submit(); });
  $('#admin_focus_term').on('change', function() { $('#admin_focus_form').submit(); });
  $('#admin_focus_student').on('change', function() { $('#admin_focus_form').submit(); });
}

