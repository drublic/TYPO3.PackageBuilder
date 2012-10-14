(function() {

	"use strict";

	// Connections
	TYPO3.PackageBuilder.Modeller.Connection = Ember.Object.extend({
		source: null,
		target: null,
		label: {
			title : null
		},

		showLabelsBinding: 'TYPO3.PackageBuilder.modellerBuild.settings.showRelationLabels',

		// Render a connection
		render: function () {
			var item = this;

			TYPO3.PackageBuilder.Modeller.jsPlumb.connect({
				source: item.get('source'),
				target: item.get('target'),
				cssClass: "connector",
				connector:[
					"Bezier", {
						curviness: 80
					}, {}
				],
				endpoint: "Blank",
				anchor: "AutoDefault",
				paintStyle: {
					lineWidth: 2,
					strokeStyle: TYPO3.PackageBuilder.Modeller._plumb.colors.connector_stroke
				},
				overlays : [
					["Label", {
						cssClass: "connector--label",
						label: item.get('label').title
					}],
					["PlainArrow", {
						location: 1,
						width: 20,
						length: 12
					}]
				]
			});

			return this;
		},

		showLabelsChange: function () {
			if (!this.showLabels) {
				$('.connector--label').addClass('hidden');
			} else {
				$('.connector--label').removeClass('hidden');
			}
		}.observes('showLabels')

	});


}());
