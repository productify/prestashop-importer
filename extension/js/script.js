$(document).ready(function() {
	$("#productify_right .block_content").height("64px");

	setInterval(function() {
		$("#productify_right .block_content p").fadeOut(1000)
		$("#productify_right .block_content p").fadeIn(1000);
	}, 3000);
});
