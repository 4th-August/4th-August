define(function(require, exports, module) {

	require('jquery.cycle2');
    require('boot');

	var Swiper = require('swiper');

	var Lazyload = require('echo.js');

	exports.run = function() {

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

	};

});