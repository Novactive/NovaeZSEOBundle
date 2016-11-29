YUI.add('novaseometas-view', function (Y) {
    "use strict";
    Y.namespace('Novactive');

    Y.Novactive.NovaSEOMetasView = Y.Base.create('novaseometasView', Y.eZ.FieldView, [], {
        _getName: function () {
            return Y.Novactive.NovaSEOMetasView.NAME;
        },
    });

    Y.eZ.FieldView.registerFieldView('novaseometas', Y.Novactive.NovaSEOMetasView);
});