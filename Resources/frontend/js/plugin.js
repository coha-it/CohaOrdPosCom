$.subscribe('plugin/swStorageField/setFieldValueFromStorage.cohaOrdPosCom', function() {
	var _that = arguments[1];
	var value = _that.storage.getItem(_that.storageKey);
	if(value === null) {
		$(_that.$el.attr('data-selector')).val(_that.$el.val());
	}
});