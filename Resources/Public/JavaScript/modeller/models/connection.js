/*jshint curly: true, eqeqeq: true, immed: true, latedef: true, newcap: true, noarg: true, sub: true, undef: true, boss: true, eqnull: true, browser: true */
/*globals console, Query, $, TYPO3, Ember */
(function() {

	// Connections
	TYPO3.PackageBuilder.Modeller.Connection = Ember.Object.extend({
		source : null,
		target : null,
		label : {
			title : null
		},

		// If anything changes in this model, the Storage is updated
		modellChanged: function () {
			TYPO3.PackageBuilder.Modeller.Storage.update(this);
		}.observes('source', 'target', 'label'),

		render: function () {
			var item = this;

			TYPO3.PackageBuilder.Modeller.jsPlumb.connect({
				source: item.source,
				target: item.target,
				cssClass: "connector",
				connector: "StateMachine",
				endpoint: "Blank",
				anchor: "AutoDefault",
				paintStyle: {
					lineWidth: 2,
					strokeStyle: TYPO3.PackageBuilder.Modeller._plumb.colors.connector_stroke
				},
				overlays : [
					["Label", {
						cssClass: "connector--label",
						label : item.label.title
					}],
					["PlainArrow", {
						location: 1,
						width: 20,
						length: 12
					}]
				]
			});
		}

	});


}());
