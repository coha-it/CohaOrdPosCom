;(function ($) {
	'use strict';

	$.plugin('cohaOrdPosCom', {

		defaults: {
			url: null,
			basketId: null,
		},

		/**
		 * Initializes the plugin
		 *
		 * @public
		 * @method init
		 */
		init: function () {
			var me = this,
				$el = me.$el,
				opts = me.opts;

			me.applyDataAttributes();
			me.registerListeners();
		},

		/**
		 * Registers all necessary events for the plugin.
		 *
		 * @public
		 * @method registerListeners
		 */
		registerListeners: function () {
			var me = this;

			me._on(me.$el, 'keyup', $.proxy(me.onKeyUp, me));
		},

		onKeyUp: function(event) {
			var me = this;

			event.preventDefault();
			me.sendForm();
		},

		sendForm: function() {
			var me = this;

			$.ajax({
				type: 'post',
				url: me.opts.url,
				data: 'basketId=' + me.opts.basketId + '&comment=' + me.$el.val(),
				success: function(result) {
					// console.log('Updated s_order_basket_attributes #' + me.opts.basketId +  ': ' + me.$el.val());
				},
			});
		},

		destroy: function () {
			var me = this;

			me._destroy();
		}
	});
})(jQuery);

(function($, window) {
	window.StateManager.addPlugin('*[data-coha-ord-pos-com="true"]', 'cohaOrdPosCom');
})(jQuery, window);