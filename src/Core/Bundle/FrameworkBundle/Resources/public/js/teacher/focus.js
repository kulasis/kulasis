function focus_documentReady() {
	$('#focus_usergroup').on('change', focus_usergroup_change);
	$('#focus_school').on('change', focus_organizationterm_change);
	$('#focus_teacher').on('change', focus_teacher_change);
	$('#focus_section').on('change', focus_section_change);
  
	$('#focus_advisor_student').on('change', focus_advisor_students_change);

	$('#focus_term').on('change', focus_organizationterm_change);
}

function focus_usergroup_change(event) {
	$('#focus_form').submit();
}

function focus_organizationterm_change(event) {

	var url = window.location.href, urlToAdd = "";
	
	var data = "focus_org=" + encodeURIComponent($('#focus_school').val());
  data += "&focus_term=" + encodeURIComponent($('#focus_term').val());
	
	$.ajax({
	  type: "POST",
	  url: url,
	  data: data,
		success: function() {
			window.location.href = url;
		},
	});
}

function focus_teacher_change(event) {

	var url = window.location.href, urlToAdd = "";
	
	data = "focus_teacher=" + encodeURIComponent($('#focus_teacher').val());
	
	$.ajax({
	  type: "POST",
	  url: url,
	  data: data,
		success: function() {
			window.location.href = url;
		},
	});
}

function focus_section_change(event) {

	var url = window.location.href, urlToAdd = "";
	
	data = "focus_section=" + encodeURIComponent($('#focus_section').val());

	$.ajax({
	  type: "POST",
	  url: url,
	  data: data,
		success: function(msg) {
			window.location.href = url;
		},
	});
}

function focus_advisor_students_change(event) {

	var url = window.location.href, urlToAdd = "";
	
	data = "focus_advisor_student=" + encodeURIComponent($('#focus_advisor_student').val());

	$.ajax({
	  type: "POST",
	  url: url,
	  data: data,
		success: function(msg) {
			window.location.href = url;
		},
	});
}