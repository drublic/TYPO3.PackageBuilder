/*jshint curly: true, eqeqeq: true, immed: true, latedef: true, newcap: true, noarg: true, sub: true, undef: true, boss: true, eqnull: true, browser: true */
/*globals console, Query, $, TYPO3, Ember */
(function() {
	TYPO3.PackageBuilder.Modeller.Controller = Ember.ArrayProxy.create({
		content: [],
		findById: function (id) {
			var _model;

			this.get('content').forEach( function (el) {
				if (el.get('identifier') === id) {
					_model = el;
				}
			});

			return _model;
		},

		// Deactivate model
		deactivate: function() {
			this.filterProperty('isActive', true).forEach( function (el) {
				el.set('isActive', false);
			});

			return this;
		},

		createModel: function (model) {
			if (this.findById(model.identifier) === undefined) {
				var _model = TYPO3.Ice.Model.Element.create(model);

				this.deactivate();

				// create new object
				this.pushObject(_model);
				return _model;
			}
		}
	});

	TYPO3.Ice.View.StageClass = TYPO3.Ice.View.StageClass.extend({
		templateName: 'Modeller-Component',

		componentObj: function(o) {
			var key,
				component = {
					label: o['label'],
					identifier: o['identifier'],
					isActive: true,
					props: []
				};

			for (key in o) {
				if (key === "label" || key === "identifier") {
					continue;
				}

				if (typeof o[key] === "object") {
					// @TODO define how objects in objects look
					// TYPO3.PackageBuilder.Modeller.Controller.createModel(this.componentObj(o[key]));
				} else {
					component.props.push({
						key: key,
						value: o[key]
					});

					// this.didInsertChild(component);
				}
			}

			return component;
		},

		didInsertChild: function (data) {
			console.log(data);
		},

		didInsertElement: function() {
			var _self = this,
				projDef = TYPO3.Ice.Model.Project.get('projectDefinition');


			if (this.getPath("projectDefinition")) {

				// Use model as component
				if (projDef) {
					projDef.get('children').forEach( function (el) {

						// Do this only for DomainObjects
						if (el.get("identifier") === "DomainObject" && el.get('children').length > 0) {
							el.get('children').forEach( function (child) {
								var data = _self.componentObj(TYPO3.Ice.Utility.convertToSimpleObject(child));
								TYPO3.PackageBuilder.Modeller.Controller.createModel(data);
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
						});
					});
				});
			}

		}.observes('projectDefinition.__nestedPropertyChange')
	});

}.call(this));
