(function () {
	'use strict';

	$('.FLOW3-Error-Debugger-VarDump-Floating').draggable();
	$('.FLOW3-Error-Debugger-VarDump-Floating').live('dblclick', function () {
		$(this).remove();
	});
}());
