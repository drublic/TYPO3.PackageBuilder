(function() {

	'use strict';

	TYPO3.Ice.Model.Project.reopen({
		projectElementBinding: 'TYPO3.Ice.Model.Project.currentlySelectedElement',

		isActive: function () {
			return false;
		}.property()
	});


	TYPO3.PackageBuilder.Modeller.Collection = Ember.ArrayProxy.create({
		content: [],

		// Find model by its id
		findById: function (id) {
			var model;

			this.get('content').forEach( function (el) {
				if (el.get('identifier') === id) {
					model = el;
				}
			});

			return model;
		},

		// Deactivate model
		deactivate: function() {
			this.filterProperty('isActive', true).forEach( function (el) {
				el.set('isActive', false);
			});

			return this;
		},

		relationable: function (curId) {
			this.get('content').forEach( function (el) {
				if (el.get('identifier') !== curId) {
					el.set('isRelationable', true);
				}
			});
		},

		derelationable: function () {
			this.filterProperty('isRelationable', true).forEach( function (el) {
				el.set('isRelationable', false);
			});
		},

		// Push a new model to the collection
		createModel: function (model) {

			// If not yet part of collection
			this.deactivate();

			// create new object
			this.addObject(model);
			return model;
		}
	});


	TYPO3.Ice.View.StageClass = TYPO3.Ice.View.StageClass.extend({

		// Name of Template
		templateName: 'Modeller-Stage',

		// Classes and bindings for classes
		classNames: ["modeller-stage"],
		classNameBindings: ["zoom:is-zoomable:"],

		// Binding for properties
		zoomBinding: "TYPO3.PackageBuilder.modellerBuild.settings.zoom",

		// When an element was inserted...
		didInsertElement: function () {
		}

	});


}.call(this));
