/*jshint curly: true, eqeqeq: true, immed: true, latedef: true, newcap: true, noarg: true, sub: true, undef: true, boss: true, eqnull: true, browser: true */
/*globals console, Query, $, TYPO3, Ember */
(function() {

	// Dialogues
	TYPO3.PackageBuilder.Modeller.DialogueView = Ember.View.extend({
		templateName: "Modeller-ConnectorDialogue",
		tagName: "form",

		// Generate a new relation after saving
		save: function (val) {
			var relation = TYPO3.PackageBuilder.Modeller.Connection.create({
				source: TYPO3.PackageBuilder.Modeller.connect.start,
				target: TYPO3.PackageBuilder.Modeller.connect.end,
				label: {
					title: val.filterProperty("name", "connector--title")[0].value + ', ' + val.filterProperty("name", "connector--relationtype")[0].value
				}
			}).render();

			this.reset();
		},

		// Reset connection
		reset: function () {
			TYPO3.PackageBuilder.Modeller.connect.start = null;
			TYPO3.PackageBuilder.Modeller.connect.end = null;
		},

		// Render a connection
		didInsertElement: function () {
			var that = this;

			$(this.get('element')).dialog({
				dialogClass: 'connector--dialogue typo3-ice-dialog',
				title: 'Create Relation',
				modal: true,
				buttons: {
					"Create Relation": function () {
						that.save($(this).serializeArray());
						$(this).dialog('close');
					},
					"Cancel": function () {
						that.reset();
						$(this).dialog('close');
					}
				}
			});

			return this;
		}

	});


}());
