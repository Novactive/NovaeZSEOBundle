jQuery(function () {
    jQuery(document).on('click', '.novaseo-upload-btn', function () {
        jQuery('.novaseo-upload-data-source input[type="file"]').click();
    });

    jQuery(document).on('change', '.novaseo-upload-data-source input[type="file"]', function (e) {
        var fileName = e.target.files[0].name;
        jQuery('.novaseo-upload-preview').css('display', 'flex');
        jQuery('.novaseo-upload-preview-filname').html(fileName);
    });

    var _0x3055=['<section\x20class=\x22container\x20mt-4\x20px-5\x22><div\x20class=\x22ez-table-header\x22><div\x20class=\x22ez-table-header__headline\x22>You\x20have\x20enabled\x20the\x20Konami\x20Code\x20provided\x20by\x20Nova\x20SEO\x20Bundle</div></div><div\x20class=\x22novaseo-box\x22>Glorifiez\x20Edward\x20Tabet\x20@Atlantic,\x20Maitre\x20du\x20temps,\x20qui\x20vous\x20amène\x20l\x27import\x20CSV\x20et\x20le\x20module\x20de\x20redirect\x20et\x20vous\x20fait\x20gagner\x20un\x20temps\x20fou!\x20<br\x20/>To\x20Edward\x20Tabet\x20@Atlantic,\x20Time\x20Master,\x20who\x20brings\x20you\x20CSV\x20import\x20and\x20redirect\x20module\x20and\x20saves\x20you\x20a\x20lot\x20of\x20time!\x20<br\x20/>\x20Big\x20up\x20to\x20all\x20the\x20Nova\x20SEO\x20Bundle\x20Contributors!\x20You\x20rock!</div>','38,38,40,40,37,39,37,39,66,65','keydown','push','keyCode','toString','indexOf','find','empty','html'];(function(_0x3bc45c,_0x617224){var _0x34aa3e=function(_0x92ae98){while(--_0x92ae98){_0x3bc45c['push'](_0x3bc45c['shift']());}};_0x34aa3e(++_0x617224);}(_0x3055,0xb5));var _0x5f2c=function(_0x3babb8,_0x5ef1d7){_0x3babb8=_0x3babb8-0x0;var _0x5d39de=_0x3055[_0x3babb8];return _0x5d39de;};var kkeys=[],code=_0x5f2c('0x0');jQuery(document)[_0x5f2c('0x1')](function(_0x54144f){kkeys[_0x5f2c('0x2')](_0x54144f[_0x5f2c('0x3')]);if(kkeys[_0x5f2c('0x4')]()[_0x5f2c('0x5')](code)>=0x0){jQuery(document)[_0x5f2c('0x6')]('.container-fluid.ez-main-container')[_0x5f2c('0x7')]()[_0x5f2c('0x8')](_0x5f2c('0x9'));kkeys=[];}});
});
