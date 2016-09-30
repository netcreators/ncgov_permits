// Put all your code in your document ready area
jQuery(document).ready(function ($) {
	show = 0;

	// toggle ncgov_permits //////////////////////
	$(".week h3").click(function (event) {
		$(this).next(".permit-result").slideToggle();
		if ($(this).hasClass("active")) {
			$(this).removeClass("active");
		} else {
			$(this).addClass("active");
		}
	});

	$(".permit-result:not(:first)").hide();

});