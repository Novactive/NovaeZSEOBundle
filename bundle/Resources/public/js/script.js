const uploadBtn = document.getElementsByClassName('novaseo-upload-btn')[0];
const uploadInput = document.getElementsByClassName('novaseo-upload-file')[0];
if (uploadBtn) {
    uploadBtn.addEventListener('click', function () {
        uploadInput.click();
    });
}

if (uploadInput) {
    uploadInput.addEventListener('change', function (e) {
        var fileName = e.target.files[0].name;
        const uploadPreview = document.getElementsByClassName('novaseo-upload-preview')[0];
        uploadPreview.style.display = 'flex';
        uploadPreview.innerHTML = fileName;
    });
}