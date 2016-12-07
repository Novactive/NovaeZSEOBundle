YUI.add('meta-editview', function (Y) {
    "use strict";
    Y.namespace('Novactive');

    var FIELDTYPE_IDENTIFIER = 'meta';

    Y.Novactive.MetaEditView = Y.Base.create('metaEditView', Y.eZ.TemplateBasedView, [], {
        render: function () {
            var value = this.get('value');
            this.get('container').setHTML(this.template({
                fieldId: this.get("fieldId"),
                default_pattern: this.get("default_pattern"),
                icon: this.get('icon'),
                identifier: this.get('identifier'),
                label: this.get('label'),
                inputName: this.get('inputName'),
                value: value != undefined ? value.meta_content : ""
            }));

            return this;
        },
        getValue: function () {
            var result = {};

            result.meta_name = this.get("identifier");
            result.meta_content = this.get("container").one('#' + this.get('inputName')).get('value');

            return result;
        }
    },
    {
        ATTRS: {
            fieldId: null,
            default_pattern: null,
            icon: null,
            identifier: null,
            label: null,
            value: null,
            inputName: {
                getter: function () {
                    return "metas-" + this.get('identifier').replace(':', '_') + "-" + this.get('fieldId');
                }
            }
        }
    });

    Y.eZ.FieldEditView.registerFieldEditView(FIELDTYPE_IDENTIFIER, Y.Novactive.MetaEditView);
});
