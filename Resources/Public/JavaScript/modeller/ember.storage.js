/*jshint curly: true, eqeqeq: true, immed: true, latedef: true, newcap: true, noarg: true, sub: true, undef: true, boss: true, eqnull: true, browser: true */
/*globals console, Query, $, TYPO3, Ember */
TYPO3.PackageBuilder.Modeller.Storage = (function () {

	// Generate four random hex digits.
	var S4 = function () {
		return (((1+Math.random())*0x10000)|0).toString(16).substring(1);
	};

	// Generate a pseudo-GUID by concatenating random hexadecimal.
	var guid = function () {
		return (S4()+S4()+"-"+S4()+"-"+S4()+"-"+S4()+"-"+S4()+S4()+S4());
	};

	// Our Store is represented by a single JS object in *localStorage*. Create it
	// with a meaningful name, like the name you'd give a table.
	var Store = function(name) {
		this.name = name;
		var store = localStorage.getItem(this.name);
		this.data = (store && JSON.parse(store)) || {};

		// Save the current state of the **Store** to *localStorage*.
		this.save = function() {
			localStorage.setItem(this.name, JSON.stringify(this.data));
		};

		// Add a model, giving it a (hopefully)-unique GUID, if it doesn't already
		// have an id of it's own.
		this.create = function (model) {
			if (!model.get('id')) {
				model.set('id', guid());
			}
			return this.update(model);
		};

		// Update a model by replacing its copy in `this.data`.
		this.update = function(model) {
			this.data[model.get('id')] = model.getProperties('id', 'identifier', 'title', 'position');
			this.save();
			return model;
		};

		// Retrieve a model from `this.data` by id.
		this.find = function(model) {
			return TYPO3.PackageBuilder.Modeller.Model.create(this.data[model]);
		};

		// Return the array of all models currently in storage.
		this.findAll = function() {
			var result = [];
			for (var key in this.data) {
				var todo = TYPO3.PackageBuilder.Modeller.Model.create(this.data[key]);
				result.push(todo);
			}

			return result;
		};

		// Delete a model from `this.data`, returning it.
		this.remove = function(model) {
			delete this.data[model.get('id')];
			this.save();
			return model;
		};
	};

	return new Store(TYPO3.PackageBuilder.Modeller.settings.localStorage);
}());







