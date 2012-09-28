(function () {

	"use strict";

	// Create a view which will hold the settings in the right sidebar
	TYPO3.Ice.View.Settings = Ember.View.extend({

		relationLabelsBinding: "TYPO3.PackageBuilder.modellerBuild.settings.showRelationLabels",
		zoomBinding: "TYPO3.PackageBuilder.modellerBuild.settings.zoom",

		// The template
		templateName: "Modeller-Settings",

		// Binding for the current element
		projectDefinitionBinding: "TYPO3.Ice.Model.Project.projectDefinition",

		relationLabelsChange: function () {
			this.set('relationLabels', !this.get('relationLabels'));
			TYPO3.PackageBuilder.modellerBuild.set('settings.showRelationLabels', this.get('relationLabels'));
		},

		zoomChange: function () {
			this.set('zoom', !this.get('zoom'));
			TYPO3.PackageBuilder.modellerBuild.set('settings.zoom', this.get('zoom'));
		}
	});

}());
