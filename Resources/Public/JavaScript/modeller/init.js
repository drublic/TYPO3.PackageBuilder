/*jshint curly: true, eqeqeq: true, immed: true, latedef: true, newcap: true, noarg: true, sub: true, undef: true, boss: true, eqnull: true, browser: true */
/*globals console, Query, $, TYPO3, Ember */
(function () {

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
				$.each(models, function (el) {
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
	});

}());
