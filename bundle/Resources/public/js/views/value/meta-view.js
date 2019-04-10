YUI.add('meta-view', function (Y) {
    "use strict";
    Y.namespace('Novactive');

    Y.Novactive.MetaView = Y.Base.create('metaView', Y.eZ.TemplateBasedView, [], {
        render: function () {
            var value = this.get('value');
            this.get('container').setHTML(this.template({
                label: this.get('label'),
                value: value != undefined && value.meta_content != "" ? value.meta_content : this.get("default_pattern")
            }));

            return this;
        }
    },
    {
        ATTRS: {
            fieldId: null,
            default_pattern: null,
            icon: null,
            identifier: null,
            label: null,
            value: null
        }
    });
});
