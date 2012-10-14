/*jshint curly: true, eqeqeq: true, immed: true, latedef: true, newcap: true, noarg: true, sub: true, undef: true, boss: true, eqnull: true, browser: true */
/*globals console, Query, $, TYPO3, Ember */
(function() {
	var timestamp = 0;

	TYPO3.PackageBuilder.Modeller.Model = Ember.Object.extend({
		id : null,
		identifier: null,
		title: null,
		position: {
			top : 0,
			left : 0
		},

		// If anything changes in this model, this Storage is updated
		modellChanged: function () {
			var model = TYPO3.PackageBuilder.Modeller.Storage.update(this);
		}.observes('identifier', 'title', 'position')
	});


	$(document)

		// When Modeller changes, this event fires
		.on('modeller:change', function (e, view) {
			var model = TYPO3.PackageBuilder.Modeller.Model.create({
				id: view.get('elementId')
			});

			TYPO3.PackageBuilder.Modeller.Storage.create(model);
		})

		// Drag-events for components
		.on('drag', '.component', function (e) {

			// Check if timestemps differ more then 100ms
			if ((e.timeStamp - timestamp) > 100) {
				var offset = $(this).offset();

				// Update Classes
				var draggedModell = TYPO3.PackageBuilder.Modeller.Storage.find(this.id);
				draggedModell.set('position', {
					top : offset.top,
					left : offset.left
				});

				timestamp = e.timeStamp;
			}
		});

}());
