OW_Components_DragAndDropAjaxHandler = function(urlResponder, placeName) {
	this.ajax = new OW_Component_Ajax(urlResponder);
	this.placeName = placeName;
};

OW_Components_DragAndDropAjaxHandler.prototype = {

	changeState: function( sectionName, itemStack) {
		this.ajax.addRequest('saveComponentPlacePositions', {
			stack: itemStack,
			place: this.placeName,
			section: sectionName
		});
	},

        clone: function(section, stack, id, success) {
            this.ajax.send('cloneComponent', {
                place: this.placeName,
                section: section,
        	componentId: id,
                stack: stack
            }, success);
        },

        remove: function(id) {
             this.ajax.send('deleteComponent', {
                place: this.placeName,
        	componentId: id
            });
        },

        loadSettings: function(id, successFunction) {
             this.ajax.send('getSettingsMarkup', {
                place: this.placeName,
        	componentId: id
            }, successFunction);
        },

        saveSettings: function(id, settings , successFunction) {
             this.ajax.send('saveSettings', {
                place: this.placeName,
        	componentId: id,
                settings: settings
            }, successFunction);
        },

        saveScheme: function(scheme , successFunction) {
             this.ajax.send('savePlaceScheme', {
                place: this.placeName,
        	scheme: scheme
            }, successFunction);
        },

        moveToPanel: function(id) {
            this.ajax.addRequest('moveComponentToPanel', {
                componentId: id,
                place: this.placeName
            });
        },

	complete: function(success) {
            this.ajax.sendQueue(success);
	}
};