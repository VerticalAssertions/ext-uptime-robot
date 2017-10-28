 
require.config({
    baseUrl: '/',
    paths: {
        'jquery': "//ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min",
        'bootstrap': "//netdna.bootstrapcdn.com/bootstrap/3.3.2/js/bootstrap.min"
    },
    shim: {
        'bootstrap': ['jquery']
    },
    map: {
        '*': {
            'jquery': 'jQueryNoConflict'
        },
        'jQueryNoConflict': {
            'jquery': 'jquery'
        }
    }
});
define('jQueryNoConflict', ['jquery'], function ($) {
    return $.noConflict();
});
if (Prototype.BrowserFeatures.ElementExtensions) {
    require(['jquery', 'bootstrap'], function ($) {
        // Fix incompatibilities between BootStrap and Prototype
        var disablePrototypeJS = function (method, pluginsToDisable) {
                var handler = function (event) {  
                    event.target[method] = undefined;
                    setTimeout(function () {
                        delete event.target[method];
                    }, 0);
                };
                pluginsToDisable.each(function (plugin) { 
                    $(window).on(method + '.bs.' + plugin, handler); 
                });
            },
            pluginsToDisable = ['collapse', 'dropdown', 'modal', 'tooltip', 'popover', 'tab'];
        disablePrototypeJS('show', pluginsToDisable);
        disablePrototypeJS('hide', pluginsToDisable);
    });
}
require(['jquery', 'bootstrap'], function($) {
    $(document).ready(function () {
        $('.bs-example-tooltips').children().each(function () {
            $(this).tooltip();
        });
        $('.bs-example-popovers').children().each(function () {
            $(this).popover();
        });
    });
});

require(['jquery', 'bootstrap'], function($) {
	$(document).ready(function() {
	    $('.fa').tooltip();

	    // Popover on links or buttons opening a next DOM element
	    $(".popover-trigger").popover({
	        container: 'body',
	        html: true,
	        trigger: 'focus',
	        placement: 'left',
	        template: '<div class="popover popover-links" role="tooltip"><div class="arrow"></div><h3 class="popover-title"></h3><div class="popover-content"></div></div>',
	        content: function() {
	          return $(this).parent().find($(this).data("target")).html();
	        },
	    });
	    /*$(document).on("click", ".popover-trigger", function(e) {
	        e.preventDefault();
	    });*/
	});
});