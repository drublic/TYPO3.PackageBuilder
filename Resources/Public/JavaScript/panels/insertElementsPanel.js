
/*jshint curly: true, eqeqeq: true, immed: true, latedef: true, newcap: true, noarg: true, sub: true, undef: true, boss: true, eqnull: true, browser: true */
/*globals console, jQuery, $, TYPO3 */
TYPO3.Ice.View.InsertElementsPanelClass.Element = TYPO3.Ice.View.InsertElementsPanelClass.Element.extend({
	enabled: function () {
		if (this.getPath('projectElementType.options._isTopLevel') ||
			this.getPath('projectElementType.enableTypes').indexOf('all') > -1) {
			return true;
		}
		var currentlySelectedElement = this.get('currentlySelectedElement');
		if (currentlySelectedElement && this.getPath('projectElementType.enableTypes').indexOf(currentlySelectedElement.get('type').split(':')[1]) > -1) {
			return true;
		}
		return false;

	}.property('projectElementType', 'currentlySelectedElement').cacheable(),
	click: function () {
		var currentlySelectedElement, defaultValues, identifier, indexInParent,
			newElement, parentElementsArray, referenceElement, topLevelContainer,
			_this = this;

		currentlySelectedElement = this.get('currentlySelectedElement');
		if (!currentlySelectedElement) {
			return;
		}
		if (!this.get('enabled')) {
			return;
		}
		defaultValues = this.getPath('projectElementType.options.predefinedDefaults') || {};
		identifier = this.getNextFreeIdentifier();
		newElement = TYPO3.Ice.Model.Element.create($.extend({
			type:this.getPath('projectElementType.key'),
			identifier:identifier,
			label:identifier
		}, defaultValues));
		if (this.getPath('projectElementType.group') === 'packageElements') {
			topLevelContainer = this.addTopLevelContainer(this.getPath('projectElementType.label'));
			topLevelContainer.get('children').pushObject(newElement);
		} else {
			if (currentlySelectedElement.getPath('typeDefinition.options._isCompositeElement')) {
				currentlySelectedElement.get('children').pushObject(newElement);
			} else {
				referenceElement = currentlySelectedElement;
				if (referenceElement.findEnclosingCompositeElementWhichIsNotOnTopLevel()) {
					referenceElement = referenceElement.findEnclosingCompositeElementWhichIsNotOnTopLevel();
					referenceElement.get('children').pushObject(newElement);
				}
			}
		}
		return window.setTimeout(function () {
			return _this.set('currentlySelectedElement', newElement);
		}, 10);
	},
	addTopLevelContainer: function (containerIdentifier) {
		var newContainer,
			topLevelContainers = TYPO3.Ice.Model.Project.get('projectDefinition').get('children');

		for (var i = 0; i < topLevelContainers.length; i++) {
			if (topLevelContainers[i].get('identifier') === containerIdentifier) {
				return topLevelContainers[i];
			}
		}
		newContainer = TYPO3.Ice.Model.Element.create($.extend({
			type:'TYPO3.PackageBuilder:Container',
			identifier:containerIdentifier,
			label:containerIdentifier + 's'
		}, {}));

		TYPO3.Ice.Model.Project.get('projectDefinition').get('children').pushObject(newContainer);
		return newContainer;
	}
});

window.setTimeout( function () {
	var _default = {
		identifier:'package-init',
		label:'My Package',
		type:'TYPO3.PackageBuilder:Package'
	};

	if (!TYPO3.Ice.Model.Project.get('projectDefinition')) {
		TYPO3.Ice.Model.Project.set('projectDefinition', TYPO3.Ice.Model.Element.create(_default));
	}
}, 200);
