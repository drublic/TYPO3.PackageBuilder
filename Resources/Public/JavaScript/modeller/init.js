(function () {

	"use strict";

	// Init PackageBuilder and PackageBuilder.Modeller
	TYPO3.PackageBuilder = {};
	TYPO3.PackageBuilder.Modeller = {

		// Some settings
		settings: {
			'localStorage': 't3_modeller'
		},

		// LocalStorage
		generateLocalStore: function () {
			var models = window.localStorage[TYPO3.PackageBuilder.Modeller.settings.localStorage];
			if (models !== null) {
				models = $.parseJSON(models);
				$.each(models, function () {
					// @TODO Generate models
				});
			}
		},

		connect: {
			start: null,
			end: null
		}
	};

	$(document).ready( function () {
		TYPO3.PackageBuilder.Modeller.generateLocalStore();

		$('.component').zoom();
	});

}());
