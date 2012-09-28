(function () {

	"use strict";

	// Init PackageBuilder and PackageBuilder.Modeller
	TYPO3.PackageBuilder = {};
	TYPO3.PackageBuilder.Modeller = {};
	TYPO3.PackageBuilder.Modeller.Build = Ember.Object.extend({

		// Some settings
		settings: {
			'localStorage': 't3_modeller',
			'showRelationLabels': true,
			'zoom': false
		},

		// Connection Cache
		connect: {
			start: null,
			end: null
		},

		// LocalStorage
		generateLocalStore: function () {
			var models = window.localStorage[this.get('settings.localStorage')];
			if (models !== null) {
				models = $.parseJSON(models);
				$.each(models, function () {
					// @TODO Generate models
				});
			}
		}
	});

	TYPO3.PackageBuilder.modellerBuild = TYPO3.PackageBuilder.Modeller.Build.create();

	$(document).ready( function () {
		TYPO3.PackageBuilder.modellerBuild.generateLocalStore();

		$('.component').zoom();
	});

}());
