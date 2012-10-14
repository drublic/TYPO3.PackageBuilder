(function () {
	"use strict";

	TYPO3.PackageBuilder.Modeller.ComponentView = Ember.View.extend({
		templateName: 'Modeller-Component',
		classNames: ["component"],
		classNameBindings: ['root:component-root:'],

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
			TYPO3.PackageBuilder.modellerBuild.set('connect.start', this.get('elementId'));
		},

		// End the relation and create it
		endRelation: function () {
			TYPO3.PackageBuilder.Modeller.Collection.derelationable();
			$('#' + TYPO3.PackageBuilder.modellerBuild.get('connect.start')).find('.components--property.is-active').removeClass('is-active');

			TYPO3.PackageBuilder.modellerBuild.set('connect.end', this.get('elementId'));

			TYPO3.PackageBuilder.Modeller.DialogueView.create().appendTo('body');
		},

		// Add a new property to the model
		addProperty: function () {
			// @TODO Select element as currentlySelectedElement before adding property
			var currentlySelectedElement = TYPO3.Ice.Model.Project.currentlySelectedElement;
			var identifier = 'someIdentifier123';
			var newElement = TYPO3.Ice.Model.Element.create({
				type: "TYPO3.PackageBuilder:Property",
				identifier: identifier,
				label: identifier
			});

			var domainObject = currentlySelectedElement;

			if (currentlySelectedElement.get('type') !== "TYPO3.PackageBuilder:DomainObject") {
				domainObject = currentlySelectedElement.get('parentElement');

				if (domainObject.get('type') !== "TYPO3.PackageBuilder:DomainObject") {
					new Error('Uups');
				}
			}

			domainObject.get('children').pushObject(newElement);

			// Show properties if not opened
			if (!$('#' + this.get('elementId')).find('.components--more').hasClass('is-active')) {
				this.showMore();
			}

			return window.setTimeout(function () {
				return TYPO3.Ice.Model.Project.set('currentlySelectedElement', newElement);
			}, 10);
		}
	});
}());
