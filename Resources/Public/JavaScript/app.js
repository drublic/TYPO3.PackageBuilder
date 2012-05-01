

TYPO3.Ice.View.InsertElementsPanel.Element = TYPO3.Ice.View.InsertElementsPanel.Element.extend({
	click:function () {
		var currentlySelectedRenderable, defaultValues, identifier, indexInParent, newRenderable, parentRenderablesArray, referenceRenderable,
				_this = this;
		currentlySelectedRenderable = this.get('currentlySelectedElement');
		if (!currentlySelectedRenderable) {
			return
		}
		if (!this.get('enabled')) {
			return;
		}
		defaultValues = this.getPath('projectElementType.options.predefinedDefaults') || {};
		identifier = this.getNextFreeIdentifier();
		newRenderable = TYPO3.Ice.Model.Renderable.create($.extend({
		   type:this.getPath('projectElementType.key'),
		   identifier:identifier,
		   label:identifier
		}, defaultValues));
		if (this.getPath('projectElementType.group') == 'packageElements') {
			topLevelContainer = this.addTopLevelContainer(this.getPath('projectElementType.label'));
			topLevelContainer.get('renderables').pushObject(newRenderable);
		} else if (!this.getPath('projectElementType.options._isTopLevel') && currentlySelectedRenderable.getPath('typeDefinition.options._isCompositeRenderable')) {
			currentlySelectedRenderable.get('renderables').pushObject(newRenderable);
		} else {
			referenceRenderable = currentlySelectedRenderable;
			if (referenceRenderable === TYPO3.Ice.Model.Project.get('projectDefinition')) {
				referenceRenderable = referenceRenderable.getPath('renderables.0');
			} else if (this.getPath('projectElementType.options._isTopLevel') && !currentlySelectedRenderable.getPath('typeDefinition.options._isTopLevel')) {
				referenceRenderable = referenceRenderable.findEnclosingPage();
			} else if (this.getPath('projectElementType.options._isCompositeRenderable')) {
				if (referenceRenderable.findEnclosingCompositeRenderableWhichIsNotOnTopLevel()) {
					referenceRenderable = referenceRenderable.findEnclosingCompositeRenderableWhichIsNotOnTopLevel();
				}
			}
			parentRenderablesArray = referenceRenderable.getPath('parentRenderable.renderables');
			indexInParent = parentRenderablesArray.indexOf(referenceRenderable);
			parentRenderablesArray.replace(indexInParent + 1, 0, [newRenderable]);
		}
		return window.setTimeout(function () {
			return _this.set('currentlySelectedElement', newRenderable);
		}, 10);
	},
	addTopLevelContainer:function (containerIdentifier) {
		var topLevelContainers = TYPO3.Ice.Model.Project.get('projectDefinition').get('renderables');
		for (var i = 0; i < topLevelContainers.length; i++) {
		   if (topLevelContainers[i].get('identifier') == containerIdentifier) {
			   return topLevelContainers[i];
		   }
		}
		newContainer = TYPO3.Ice.Model.Renderable.create($.extend({
			 type:'TYPO3.PackageBuilder:Container',
			 identifier:containerIdentifier,
			 label:containerIdentifier + 's'
		 }, {}));
		TYPO3.Ice.Model.Project.get('projectDefinition').get('renderables').pushObject(newContainer);
		return newContainer;
	}
	});



