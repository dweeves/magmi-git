$j(document).ready(function($) {
	$j('[data-toggle="popover"]').popover();
});

$j(document).ready(function($) {
	$j('[data-toggle="tooltip"]').tooltip();
});

// Scrollspy
$j(document).ready(function($) {
	$j('body').scrollspy({
		target: '#navbarTop',
		offset: 70
	});
});

// Smooth Scroll
$j(document).ready(function($) {
	$j('a[href*="#"]')
	.not('[href="#"]')
	.not('[href="#0"]')
	.click(function(event) {
		if (
			location.pathname.replace(/^\//, '') == this.pathname.replace(/^\//, '') 
			&& 
			location.hostname == this.hostname
		) {
			var target = $(this.hash);
			target = target.length ? target : $('[name=' + this.hash.slice(1) + ']');
			if (target.length) {
				event.preventDefault();
				$('html, body').animate({
					scrollTop: target.offset().top -70
				}, 400, function() {
					var $target = $(target);
					$target.focus();
					if ($target.is(":focus")) {
						return false;
					} else {
						$target.attr('tabindex','-1');
						$target.focus();
					};
				});
			}
		}
	});
});