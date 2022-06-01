define(function(require, exports, module) {

	require('jquery.cycle2');

	var Swiper = require('swiper');

	var Lazyload = require('echo.js');

	exports.run = function() {
        $('.homepage-feature').cycle({
	        fx:"scrollHorz",
	        slides: "> a, > img",
	        log: "false",
	        pauseOnHover: "true"
    	});

		if ($(".es-poster .swiper-slide").length > 1) {
			var swiper = new Swiper('.es-poster.swiper-container', {
				pagination: '.swiper-pager',
				paginationClickable: true,
				autoplay: 5000,
				autoplayDisableOnInteraction: false,
				loop: true,
				calculateHeight: true,
				roundLengths: true,
				onInit: function(swiper) {
					$(".swiper-slide").removeClass('swiper-hidden');
				}
			});
		}

		Lazyload.init();


        $('.live-rating-course').find('.media-body').hover(function() {
        	$( this ).find( ".rating" ).show();
        }, function() {
        	$( this ).find( ".rating" ).hide();
        });

	};

});