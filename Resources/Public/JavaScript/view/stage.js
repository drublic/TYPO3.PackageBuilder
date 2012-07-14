/*jshint curly: true, eqeqeq: true, immed: true, latedef: true, newcap: true, noarg: true, sub: true, undef: true, boss: true, eqnull: true, browser: true */
/*globals console, Query, $, TYPO3, Ember */
(function() {
	TYPO3.PackageBuilder.Modeller.Controller = Ember.ArrayProxy.create({
		content: [],
		currentlySelectedElementBinding: 'TYPO3.Ice.Model.Project.currentlySelectedElement',
		projectElementTypeBinding: 'content',

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

		createModel: function (model) {

			// If not yet part of collection
			this.deactivate();

			// create new object
			this.addObject(model);
			return model;
		}
	});


	TYPO3.Ice.View.StageClass = TYPO3.Ice.View.StageClass.extend({
		templateName: 'Modeller-Component',

		didInsertElement: function() {
			var _self = this,
				projDef = TYPO3.Ice.Model.Project.get('projectDefinition');

			// Use model as component
			if (projDef && projDef.get('children').length > 0) {
				projDef.get('children').forEach( function (el) {

					// Do this only for DomainObjects
					if (el.get("identifier") === "DomainObject" && el.get('children').length > 0) {
						el.get('children').forEach( function (child) {
							TYPO3.PackageBuilder.Modeller.Controller.createModel(child);
						});
					}
				});
			}

			this.get('childViews').forEach( function (el) {
				el.get('childViews').forEach( function (child) {
					window.setTimeout( function () {

						var el = $('#' + child.get('elementId'));

						// Make componant draggable
						TYPO3.PackageBuilder.Modeller.jsPlumb.draggable(el);
						$('a[rel="popover"]', el).popover();

						// Modeller changed-event
						$(document).trigger('modeller:change', child);
					}, 0);
				});
			});

		}
	});


}.call(this));
