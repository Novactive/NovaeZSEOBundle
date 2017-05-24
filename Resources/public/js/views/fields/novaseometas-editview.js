YUI.add('novaseometas-editview', function (Y) {
    "use strict";
    Y.namespace('Novactive');

    var L = Y.Lang,
        IS_VISIBLE_CLASS = 'is-visible',
        IS_SHOWING_DESCRIPTION = 'is-showing-description',
        FIELD_INPUT = '.ez-editfield-input',
        TOOLTIP_DESCR = '.ez-field-description',
        STANDARD_DESCR = 'ez-standard-description',
        FIELDTYPE_IDENTIFIER = 'novaseometas';

    Y.Novactive.NovaSEOMetasEditView = Y.Base.create('novaseometasEditView', Y.eZ.FieldEditView, [], {
        /**
         * Custom initializer method, it sets the event handling on the
         * errorStatusChange event
         *
         * @method initializer
         */
        initializer: function () {
            this._setMetasEditViews();
        },

        _setMetasEditViews: function () {
            var views = [],
                viewsById = [],
                view,
                field = this.get('field'),
                fieldValue = field.fieldValue,
                fieldId = field.id,
                fieldMetasConfig = this.get('fieldDefinition').fieldSettings.configuration,
                globalMetasConfig = this.get('config').seoMetas;

            Y.Array.each(globalMetasConfig, function (meta) {
                view = new Y.Novactive.MetaEditView({
                    fieldId: fieldId,
                    default_pattern: fieldMetasConfig[meta.identifier] != undefined && fieldMetasConfig[meta.identifier] != null ? fieldMetasConfig[meta.identifier] : meta.default_pattern,
                    icon: meta.icon,
                    identifier: meta.identifier,
                    label: meta.label,
                    value: fieldValue[meta.identifier]
                });
                viewsById[meta.identifier] = view;
                views.push(view);
            });
            this._metaEditViews = views;
            this._fieldEditViewsByDefinitionId = viewsById;
        },

        _renderFieldEditViews: function () {
            var container = this.get('container');

            Y.Array.each(this._metaEditViews, function (view) {
                var fieldset;

                fieldset = container.one('.novaseometas-input-ui');
                fieldset.append(view.render().get('container'));
            });
        },

        /**
         * Default implementation of the field edit view render. By default, it
         * injects the field definition, the field, the content and the content
         * type. If the view is already active, it calls `_afterActiveReRender`
         * after the rendering process so that the field edit view
         * implementation has a way to handle *rerender* for instance when
         * switching language in the UI.
         *
         * @method render
         * @return {eZ.FieldEditView}
         */
        render: function () {
            var def = this.get('fieldDefinition'),
                defaultVariables = {
                    fieldDefinition: def,
                    field: this.get('field'),
                    content: this.get('content').toJSON(),
                    version: this.get('version').toJSON(),
                    contentType: this.get('contentType').toJSON(),
                },
                container = this.get('container');

            container.setAttribute('data-field-definition-identifier', def.identifier);

            if (this._useStandardFieldDefinitionDescription) {
                container.addClass(STANDARD_DESCR);
            }
            this.get('container').setHTML(
                this.template(Y.mix(this._variables(), defaultVariables, true))
            );

            if (this._isTouch()) {
                container.addClass('is-using-touch-device');
            }

            if (this.get('active')) {
                this._afterActiveReRender();
            }

            this._renderFieldEditViews();
            return this;
        },

        /**
         * Returns the value of the field from the current user input. This
         * method should be implemented in each field edit view.
         *
         * The default implementation returns undefined. Returning undefined
         * means that the field should be ignored when saving the content.
         *
         * @method _getFieldValue
         * @protected
         * @return mixed
         */
        _getFieldValue: function () {
            var res = [];
            Y.Array.each(this._metaEditViews, function (view) {
                res.push(view.getValue());
            });
            return res;
        },
    });

    Y.eZ.FieldEditView.registerFieldEditView(
        FIELDTYPE_IDENTIFIER, Y.Novactive.NovaSEOMetasEditView
    );
});
