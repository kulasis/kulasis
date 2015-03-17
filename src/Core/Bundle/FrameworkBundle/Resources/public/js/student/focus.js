function focus_documentReady() {
  $('#focus_usergroup').on('change', focus_usergroup_change);
  $('#focus_school').on('change', focus_organizationterm_change);
  $('#focus_student').on('change', focus_student_change);
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

function focus_student_change(event) {

  var url = window.location.href, urlToAdd = "";
  
  data = "focus_student=" + encodeURIComponent($('#focus_student').val());
  
  $.ajax({
    type: "POST",
    url: url,
    data: data,
    success: function() {
      window.location.href = url;
    },
  });
}