$(function() {
	$('#role_select').on('change', role_change);
  
  // start navigation listeners
	$('.drawer-header').on('click', navigation_openDrawer);
  
	// if no drawer set to show, show first drawer
	if (!$('.navigation-forms.drawer-contents').css('display') == 'block')
		$('.navigation-forms.drawer-contents:first').show({ duration: 200, queue: false });
	
	if (!$('.navigation-reports.drawer-contents').css('display') == 'block')
		$('.navigation-reports.drawer-contents:first').show({ duration: 200, queue: false });
});

// show or hide drawer listener
function navigation_openDrawer(event) {
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

function role_change(event) {
	$('#role_form').submit();
}