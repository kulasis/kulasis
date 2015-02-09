// Executed by document.ready
function navigation_documentReady() {
	// start navigation listeners
	navigation_startNavigationListeners();
  
	// if no drawer set to show, show first drawer
	var drawerOpen = false;
  $('#nav_forms').find('.drawer-contents').each(function (index) {
    if ($(this).css('display') == 'block') {
      drawerOpen = true;
    }
  });
  
  if (!drawerOpen) {
    $('.navigation-forms.drawer-contents:first').show({ duration: 200, queue: false });
	}
  
}

/* Navigation Listeners */
function navigation_startNavigationListeners() {
	// Make drawer headers clickable
	$('.drawer-header').on('click', navigation_drawerListener);
}

// show or hide drawer listener
function navigation_drawerListener(event) {
	event.preventDefault();
	// get category set on drawer header that was clicked; concatenate in to get jquery obj
	var divToShowOrHide = $('#drawer-contents-' + $(this).data('category'));
	// get category
	var category = $(this).data('category').split('-', 1);
  
	// if drawer currently hidden
	if (divToShowOrHide.is(':hidden')) {
		
		if (category == 'forms') {
	  	// hide all other drawers
			$('.navigation-forms.drawer-contents').hide({ duration: 200, queue: false });
	  } 
		// show drawer that was clicked
		divToShowOrHide.show({ duration: 200, queue: false });
	}
}