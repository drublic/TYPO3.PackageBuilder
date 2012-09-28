(function () {
	"use strict";

	// Generic view for messages
	TYPO3.PackageBuilder.Modeller.MessageView = Ember.View.extend({

		// Class names and their bindings
		classNames: ["message"],
		classNameBindings: ["type"],

		templateName: "Modeller-Message",

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
