(function () {

	"use strict";

	// Create a view which will be the accordion in the right sidebar
	TYPO3.Ice.View.Accordion = Ember.View.extend({

		relationLabelsBinding: "TYPO3.PackageBuilder.modellerBuild.settings.showRelationLabels",

		// The template
		templateName: "Modeller-Accordion",

		// Binding for the current element
		projectDefinitionBinding: "TYPO3.Ice.Model.Project.projectDefinition",

		// If the project definition changes
		onProjectElementChange: function () {
		}.observes('projectDefinition'),

		relationLabelsChange: function () {
			this.set('relationLabels', !this.get('relationLabels'));
			TYPO3.PackageBuilder.modellerBuild.set('settings.showRelationLabels', this.get('relationLabels'));
		}
	});
}());
