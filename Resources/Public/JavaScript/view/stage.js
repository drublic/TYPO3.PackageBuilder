/*jshint curly: true, eqeqeq: true, immed: true, latedef: true, newcap: true, noarg: true, sub: true, undef: true, boss: true, eqnull: true, browser: true */
/*globals jQuery, $, TYPO3, Ember */
(function() {
	TYPO3.PackageBuilder.Modeller.Controller = Ember.ArrayProxy.create({
		content: [],
		createModel: function (model) {
			var _model = TYPO3.Ice.Model.Element.create(model);
			this.pushObject(_model);
			return _model;
		}
	});

	TYPO3.Ice.View.StageClass = TYPO3.Ice.View.StageClass.extend({
		templateName: 'Modeller-Component',

		componentObj: function(o) {
			var key,
				component = {
					label: o['label'],
					identifier: o['identifier'],
					props: []
				};

			for (key in o) {
				if (key === "label" || key === "identifier") {
					continue;
				}

				if (typeof o[key] === "object") {
					// @TODO define how objects in objects look
					TYPO3.PackageBuilder.Modeller.Controller.createModel(this.componentObj(o[key]));
				} else {
					component.props.push({
						key: key,
						value: o[key]
					});
				}
			}

			return component;
		},

		didInsertElement: function() {
			if (this.getPath("projectDefinition")) {
				var _model = this.componentObj(TYPO3.Ice.Utility.convertToSimpleObject(this.get('projectDefinition')));
				TYPO3.PackageBuilder.Modeller.Controller.createModel(_model);
			}
		}.observes('projectDefinition.__nestedPropertyChange')
	});

}.call(this));
