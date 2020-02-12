$.subscribe('plugin/swStorageField/setFieldValueFromStorage.cohaOrdPosCom', function() {
	var me = arguments[1];

	var value = me.storage.getItem(me.storageKey);

	if(value === null) {
		$(me.$el.attr('data-selector')).val(me.$el.val());
	}
});