jQuery(function () {
    jQuery(document).on('click', '.novaseo-upload-btn', function () {
        jQuery('.novaseo-upload-data-source input[type="file"]').click();
    });

    jQuery(document).on('change','.novaseo-upload-data-source input[type="file"]', function(e){
        var fileName = e.target.files[0].name;
        jQuery('.novaseo-upload-preview').css('display','flex');
        jQuery('.novaseo-upload-preview-filname').html(fileName);
    });
});