(function () {
    'use strict';

    function initJobEditor() {
        if (typeof window.CKEDITOR === 'undefined') {
            return false;
        }
        if (CKEDITOR.instances['job-content-editor']) {
            return true;
        }

        CKEDITOR.replace('job-content-editor', {
            customConfig: '',
            versionCheck: false,
            height: 420,
            width: '100%',
            format_tags: 'p;h2;h3;h4',
            removePlugins: 'elementspath',
            extraPlugins: 'colorbutton,font,justify,tableresize,tabletools,tableselection',
            allowedContent: true,
            toolbar: [
                { name: 'document', items: ['Source', '-', 'Preview', 'Maximize'] },
                { name: 'clipboard', items: ['Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord', '-', 'Undo', 'Redo'] },
                '/',
                { name: 'basicstyles', items: ['Bold', 'Italic', 'Underline', 'Strike', '-', 'RemoveFormat'] },
                { name: 'paragraph', items: ['NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight'] },
                { name: 'links', items: ['Link', 'Unlink'] },
                { name: 'insert', items: ['Image', 'Table', 'HorizontalRule', 'SpecialChar'] },
                '/',
                { name: 'styles', items: ['Format', 'Font', 'FontSize'] },
                { name: 'colors', items: ['TextColor', 'BGColor'] }
            ]
        });
        return true;
    }

    document.addEventListener('DOMContentLoaded', function () {
        var tries = 0;
        var iv = setInterval(function () {
            if (initJobEditor() || ++tries > 40) {
                clearInterval(iv);
            }
        }, 100);

        var form = document.getElementById('job-form');
        if (form) {
            form.addEventListener('submit', function () {
                if (CKEDITOR.instances['job-content-editor']) {
                    document.getElementById('job-description-hidden').value =
                        CKEDITOR.instances['job-content-editor'].getData();
                }
            });
        }
    });
})();
