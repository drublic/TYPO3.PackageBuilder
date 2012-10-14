/*jshint curly: true, eqeqeq: true, immed: true, latedef: true, newcap: true, noarg: true, sub: true, undef: true, boss: true, eqnull: true, browser: true */
/*globals jQuery, $, TYPO3 */

// Init PackageBuilder and PackageBuilder.Modeller
TYPO3.PackageBuilder = {};
TYPO3.PackageBuilder.Modeller = {};

// Set some initial values for jsPlumb
TYPO3.PackageBuilder.Modeller._plumb = {
	colors : {
		connector_stroke : "rgba(100, 100, 100, 1)",
		connector_stroke_highlight : "rgba(200, 200, 200, 1)",
		outline : "rgba(50, 50, 50, 1)",
		hover_paint : {
			strokeStyle: "#7ec3d9"
		},
		endpoint : {
			fillStyle: "#a7b04b"
		}
	}
};

// Set jsPlumb to be part of Modeller package rather than global varibale
TYPO3.PackageBuilder.Modeller.jsPlumb = window.jsPlumb;

// Init function for Modeller
TYPO3.PackageBuilder.Modeller.init = function () {

	// Drag-events for components
	var timestamp = 0;

	$(document).on('drag', '.modeller-stage .component', function (e) {

		// Check if timestemps differ more then 100ms
		if ((e.timeStamp - timestamp) > 100) {
			var offset = $(this).offset();

			// @TODO Map position to model's properties

			timestamp = e.timeStamp;
		}
	});

};