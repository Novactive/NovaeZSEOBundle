YUI.add('novaseometas-view', function (Y) {
    "use strict";
    Y.namespace('Novactive');

    Y.Novactive.NovaSEOMetasView = Y.Base.create('novaseometasView', Y.eZ.FieldView, [], {
        _getName: function () {
            return Y.Novactive.NovaSEOMetasView.NAME;
        },

        /**
         * Returns the value to display
         *
         * @method _getFieldValue
         * @protected
         * @return {String}
         */
        _getFieldValue: function () {
            var views = [],
                view,
                field = this.get('field'),
                fieldValue = field.fieldValue,
                fieldId = field.id,
                fieldMetasConfig = this.get('fieldDefinition').fieldSettings.configuration,
                globalMetasConfig = this.get('config').seoMetas;

            Y.Array.each(globalMetasConfig, function (meta) {
                view = new Y.Novactive.MetaView({
                    fieldId: fieldId,
                    default_pattern: fieldMetasConfig[meta.identifier] != undefined && fieldMetasConfig[meta.identifier] != null ? fieldMetasConfig[meta.identifier] : meta.default_pattern,
                    icon: meta.icon,
                    identifier: meta.identifier,
                    label: meta.label,
                    value: fieldValue[meta.identifier]
                });
                views.push(view.render().get('container').getContent());
            });

            return views;
        },
    });

    Y.eZ.FieldView.registerFieldView('novaseometas', Y.Novactive.NovaSEOMetasView);
});