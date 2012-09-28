(function () {
	"use strict";

	TYPO3.PackageBuilder.Modeller.ErrorView = Ember.View.extend({
		classNames: ["message"],
		classNameBindings: ["type"],
		title: "Error:",
		message: "",

		// Type: One of ["message-error", "message-warning", "message-success"]
		type: "",

		init: function () {
			var _this = this;
			setTimeout( function () {
				_this.remove();
			}, 5000);
		}

	});

}());
