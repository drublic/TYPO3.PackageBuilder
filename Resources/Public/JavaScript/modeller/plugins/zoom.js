(function ($) {

	"use strict";

	var methods = {
		init: function (options) {

			// Create some defaults, extending them with any options that were provided
			var settings = $.extend({
			}, options);

			methods.events(this.selector);

			return this.each( function () {
			});
		},

		events: function (element) {
			$(document).on('dblclick', element, function () {

				if ($(this).hasClass('zoom-in')) {
					$(this).removeClass('zoom-in');
				} else {
					$(this).addClass('zoom-in');
				}
			});
		}
	};

	$.fn.zoom = function (method) {

		// Method calling logic
		if ( methods[method] ) {
			return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
		} else if (typeof method === 'object' || !method) {
			return methods.init.apply(this, arguments);
		} else {
			$.error('Method ' +  method + ' does not exist on jQuery.tooltip');
		}
	};

}(jQuery));
