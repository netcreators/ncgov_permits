jQuery.noConflict();

// Put all your code in your document ready area
jQuery(document).ready(function($){
	show = 0;

	// slide menu right column news //////////////////////
	$(".week h3").click(function () {
		$(this).next(".permit-result").slideToggle();
	});
        
        $(".permit-result:not(:first)").hide(); 

});