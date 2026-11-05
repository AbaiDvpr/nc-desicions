import {Local, ru, Uppload, URL, xhrUploader} from "uppload";

$(document).ready(function() {
    if ($("a[data-toggle=modal]").length && $("#modal-remove-photo").length) {
        $("a[data-toggle=modal]").click(function () {
            let form = $("#modal-remove-photo").find('form');

            form.attr('action', $(this).attr('href'));
        });

        let modalRemoveRole = document.getElementById('modal-remove-photo');
        modalRemoveRole.addEventListener('hidden.coreui.modal', function (event) {
            let form = modalRemoveRole.querySelector('form');

            form.setAttribute('action', '');
        });
    }
});

if (typeof uploadConfig !== 'undefined') {
    const uploader = new Uppload({
        lang: ru,
        uploader: xhrUploader({
            endpoint: uploadConfig.endpoint,
        }),
        bind: document.querySelector(uploadConfig.bind),
        call: document.querySelector(uploadConfig.call),
    });

    uploader.use([new Local(), new URL()]);
}
