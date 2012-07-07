/*jshint curly: true, eqeqeq: true, immed: true, latedef: true, newcap: true, noarg: true, sub: true, undef: true, boss: true, eqnull: true, browser: true */
/*globals jQuery, $, TYPO3 */
(function() {

	TYPO3.Ice.View.StageClass = TYPO3.Ice.View.StageClass.extend({
	didInsertElement: function() {
		var object2table;
			object2table = function(o) {
				var key, t;
				t = "<div class=\"component\">";
				for (key in o) {
					t += "<tr><td class=\"key\">" + key + "</td><td class=\"value\">";
					if (typeof o[key] === "object") {
						t += object2table(o[key]);
					} else {
						t += o[key];
					}
					t += "</tr>";
				}
				t += "</div>";
				return t;
			};
			//window.console.log(TYPO3.Ice.Utility.convertToSimpleObject(this.get("projectDefinition")));
			if (this.getPath("projectDefinition")) {
				return this.$().html("<div class='stage'><h3>Model Definition</h3>" + object2table(TYPO3.Ice.Utility.convertToSimpleObject(this.get("projectDefinition"))) + '</div>');
			}
		}.observes('projectDefinition.__nestedPropertyChange')
	});

}.call(this));
