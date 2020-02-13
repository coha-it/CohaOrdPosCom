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
			var _that = this,
				$el = _that.$el,
				opts = _that.opts;

			_that.applyDataAttributes();
			_that.registerListeners();
		},

		/**
		 * Registers all necessary events for the plugin.
		 *
		 * @public
		 * @method registerListeners
		 */
		registerListeners: function () {
			var _that = this;

			_that._on(_that.$el, 'keyup', $.proxy(_that.onKeyUp, _that));
		},

		onKeyUp: function(event) {
			var _that = this;

			event.preventDefault();
			_that.sendForm();
		},

		sendForm: function() {
			var _that = this;

			$.ajax({
				type: 'post',
				url: _that.opts.url,
				data: 'basketId=' + _that.opts.basketId + '&comment=' + _that.$el.val(),
				success: function(result) {
					console.log('Send Ajax & Update the s_order_basket_attributes with key:' + _that.opts.basketId +  ' => value:' + _that.$el.val());
				},
			});
		},

		destroy: function () {
			var _that = this;

			_that._destroy();
		}
	});
})(jQuery);

(function($, window) {
	window.StateManager.addPlugin('*[data-coha-ord-pos-com="true"]', 'cohaOrdPosCom');
})(jQuery, window);