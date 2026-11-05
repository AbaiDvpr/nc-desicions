window._ = require('lodash');

/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

window.axios = require('axios');

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

require('@coreui/coreui/js/index.umd');
require('jquery/src/jquery');
window.toastr = require('toastr');

toastr.options.closeButton = true;

require('./media');

if ($('textarea.tinymce').length) {
    tinymce.init({
        selector: 'textarea.tinymce',
        theme: "modern",
        language: "ru",
        width: '100%',
        height: 350,
        document_base_url: '/',
        relative_urls: false,
        convert_urls: true,
        fontsize_formats: '8px 10px 12px 14px 18px 24px 36px',
        plugins: [
            "addsign advlist autolink link image lists charmap print preview hr anchor pagebreak spellchecker",
            "searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime media nonbreaking",
            "save table contextmenu directionality emoticons template paste textcolor filemanager",
        ],
        image_advtab: true,
        menubar: false,
        extended_valid_elements: 'iframe[*],div[*],p[*],span[*],a[*],img[*],b[*],i[*],strong[*],svg[*],use[*]',
        toolbar: [
            "undo redo | styleselect formatselect fontsizeselect | cut copy paste | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify pagebreak | bullist numlist outdent indent blockquote ",
            "link unlink anchor insertfile image | preview media fullscreen | removeformat | subscript superscript | forecolor backcolor charmap emoticons filemanager code"
        ],
        external_filemanager_path: "/admin/js/tinymce/plugins/filemanager/",
        filemanager_title: "Responsive Filemanager",
        external_plugins: {"filemanager": "/admin/js/tinymce/plugins/filemanager/plugin.min.js"}
    });
}
