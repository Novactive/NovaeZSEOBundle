YUI.add('meta-editview', function (Y) {
    "use strict";
    Y.namespace('Novactive');

    var FIELDTYPE_IDENTIFIER = 'meta';

    Y.Novactive.MetaEditView = Y.Base.create('metaEditView', Y.eZ.FieldEditView, [], {

        render: function () {
            this.get('container').setHTML(this.template({
                fieldId: this.get("fieldId"),
                default_pattern: this.get("default_pattern"),
                icon: this.get('icon'),
                identifier: this.get('identifier'),
                label: this.get('label')
            }));

            return this;
        },
        _getFieldValue: function() {
            var result = {};

            result.metaName = this.get("identifier");
            result.metaContent = this.get("container").one('.ez-view-metaeditview input').get('value');

            return result;
        }
    },
    {
        ATTRS: {
            fieldId: null,
            default_pattern: null,
            icon: null,
            identifier: null,
            label: null
        }

    });

    Y.eZ.FieldEditView.registerFieldEditView(FIELDTYPE_IDENTIFIER, Y.Novactive.MetaEditView);
});
