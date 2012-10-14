/*jshint curly: true, eqeqeq: true, immed: true, latedef: true, newcap: true, noarg: true, sub: true, undef: true, boss: true, eqnull: true, browser: true */
/*globals console, Query, $, TYPO3, Ember */
(function() {

	TYPO3.Ice.Model.Project.reopen({
		projectElementBinding: 'TYPO3.Ice.Model.Project.currentlySelectedElement',

		isActive: function () {
			return false;
		}
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
		templateName: 'Modeller-Stage',
		didInsertElement: function () {}
	});



	TYPO3.PackageBuilder.Modeller.ComponentView = Ember.View.extend({
		templateName: 'Modeller-Component',
		classNames: ["component"],

		didInsertElement: function () {
			var el = $('#' + this.get('elementId'));

			// Make componant draggable
			TYPO3.PackageBuilder.Modeller.jsPlumb.draggable(el);
			$('a[rel="popover"]', el).popover();

			// Modeller changed-event
			$(document).trigger('modeller:change', this);
		},

		// Activate this component
		onActivate: function () {

			// Retrive current model
			var id = $('#' + this.get('elementId')).find('.components--data').data('identifier'),
				model = TYPO3.PackageBuilder.Modeller.Collection.findById(id);

			// Activate the current model
			TYPO3.PackageBuilder.Modeller.Collection.deactivate();
			model.set('isActive', true);

			// Make the change public
			TYPO3.Ice.Model.Project.set('currentlySelectedElement', model);
		},

		// Show properties
		showMore: function () {
			$('#' + this.get('elementId')).find('.components--more').toggleClass('is-active');
			$('#' + this.get('elementId')).find('.components--properties').toggleClass('is-shown');

			return this;
		},


		// This is the starting-point for a relation
		startRelation: function (e) {
			var id = $('#' + this.get('elementId')).find('.components--data').data('identifier'),
				target = $(e.target).data('target');

			TYPO3.PackageBuilder.Modeller.Collection.relationable(id);
			$('#' + target).addClass('is-active');

			// Set element
			// @ TODO use property-id instead of component
			TYPO3.PackageBuilder.Modeller.connect.start = this.get('elementId');
		},

		// End the relation and create it
		endRelation: function () {
			TYPO3.PackageBuilder.Modeller.Collection.derelationable();
			$('#' + TYPO3.PackageBuilder.Modeller.connect.start).find('.components--property.is-active').removeClass('is-active');

			TYPO3.PackageBuilder.Modeller.connect.end = this.get('elementId');

			TYPO3.PackageBuilder.Modeller.DialogueView.create().appendTo('body');
		}
	});


}.call(this));
