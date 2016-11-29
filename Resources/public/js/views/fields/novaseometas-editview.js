YUI.add('novaseometas-editview', function (Y) {
    "use strict";
    Y.namespace('Novactive');

    var L = Y.Lang,
        FIELDTYPE_IDENTIFIER = 'novaseometas';

    Y.Novactive.NovaSEOMetasEditView = Y.Base.create('novaseometasEditView', Y.eZ.FieldEditView, [], {
        initializer: function () {
            this._setMetasEditViews();
        },

        _setMetasEditViews: function () {
            var views;
            this._metaEditViews = views;
        },

        _renderFieldEditViews: function () {
            var container = this.get('container');

            Y.Array.each(this._metaEditViews, function (view) {
                var fieldset;

                fieldset = container.one('.novaseometas-input-ui');
                fieldset.append(view.render().get('container'));
            });
        },

        render: function () {
            this._renderFieldEditViews();
        }
    });

    Y.eZ.FieldEditView.registerFieldEditView(
        FIELDTYPE_IDENTIFIER, Y.Novactive.NovaSEOMetasEditView
    );
});
