(function() {

	"use strict";

	TYPO3.Ice.View.ElementOptionsPanelClass = Ember.ContainerView.extend({
		childViews: ['accordionView'],

		projectElementBinding: 'TYPO3.Ice.Model.Project.currentlySelectedElement',

		accordionView: TYPO3.Ice.View.Accordion,

		orderedElementEditors: function() {
			var elementEditors, k, orderedElementEditors, v;
			elementEditors = $.extend({}, this.getPath('projectElement.typeDefinition.options.editors'));
			orderedElementEditors = [];
			for (k in elementEditors) {
				v = elementEditors[k];
				if (!v) {
					continue;
				}
				v.key = k;
				orderedElementEditors.push(v);
			}
			orderedElementEditors.sort(function(a, b) {
				return a.sorting - b.sorting;
			});
			return orderedElementEditors;
		}.property('projectElement.typeDefinition').cacheable(),

		onProjectElementChange: function() {
			this.rerender();
		}.observes('projectElement')

	});

}.call(this));
