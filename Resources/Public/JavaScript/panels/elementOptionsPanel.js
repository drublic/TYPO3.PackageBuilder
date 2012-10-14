(function() {

	"use strict";

	TYPO3.Ice.View.ElementOptionsPanelClass = Ember.ContainerView.extend({
		childViews: ['settingsView'],

		projectElementBinding: "TYPO3.Ice.Model.Project.currentlySelectedElement",

		settingsView: TYPO3.Ice.View.Settings,

		orderedElementEditors: function() {
			var k, v,
				elementEditors = $.extend({}, this.getPath('projectElement.typeDefinition.options.editors')),
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

		// If the project definition changes
		onProjectElementChange: function () {
			var elementEditor, subView, subViewClass, subViewOptions, _i, _len, _ref, _results;
			this.removeAllChildren();
			this.rerender();
			this.get('childViews').pushObject(this.get('settingsView'));

			if (!this.projectElement) {
				return;
			}
			_ref = this.get('orderedElementEditors');
			_results = [];

			for (_i = 0, _len = _ref.length; _i < _len; _i++) {
				elementEditor = _ref[_i];
				subViewClass = Ember.getPath(elementEditor.viewName);
				if (!subViewClass) {
					throw "Editor class '" + elementEditor.viewName + "' not found";
				}
				subViewOptions = $.extend({}, elementEditor, {
					projectElement: this.projectElement
				});
				subView = subViewClass.create(subViewOptions);
				_results.push(this.get('childViews').pushObject(subView));
			}
			return _results;
		}.observes('projectElement')

	});

}.call(this));
