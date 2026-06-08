/**
 * Bisani Brothers — Blog CKEditor (local install in assets/ckeditor/)
 */
(function () {
    'use strict';

    function initBlogEditor() {
        if (typeof window.CKEDITOR === 'undefined') {
            return false;
        }

        if (CKEDITOR.instances['blog-content-editor']) {
            return true;
        }

        var cssUrl = window.BB_CKEDITOR_CSS || '../assets/css/ckeditor-blog-content.css';

        CKEDITOR.replace('blog-content-editor', {
            customConfig: '',
            versionCheck: false,
            height: 480,
            width: '100%',
            format_tags: 'p;h2;h3;h4',
            removePlugins: 'elementspath',
            extraPlugins: 'tableresize,tabletools,tableselection,colorbutton,font,justify',
            resize_enabled: true,
            toolbarCanCollapse: false,
            allowedContent: true,
            extraAllowedContent: 'table thead tbody tfoot tr th td caption[*]{*}(*)',
            contentsCss: [cssUrl],
            bodyClass: 'blog-ckeditor-body',
            toolbar: [
                { name: 'document', items: ['Source', '-', 'Preview', 'Maximize'] },
                { name: 'clipboard', items: ['Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord', '-', 'Undo', 'Redo'] },
                { name: 'editing', items: ['Find', 'Replace', '-', 'SelectAll'] },
                '/',
                { name: 'basicstyles', items: ['Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript', '-', 'RemoveFormat'] },
                { name: 'paragraph', items: ['NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock'] },
                { name: 'links', items: ['Link', 'Unlink', 'Anchor'] },
                { name: 'insert', items: ['Image', 'Table', 'HorizontalRule', 'SpecialChar'] },
                '/',
                { name: 'styles', items: ['Format', 'Font', 'FontSize'] },
                { name: 'colors', items: ['TextColor', 'BGColor'] },
                { name: 'tools', items: ['ShowBlocks'] }
            ],
            font_names: 'Outfit/Outfit, sans-serif;Arial/Arial, Helvetica, sans-serif;Georgia/Georgia, serif;Times New Roman/Times New Roman, Times, serif',
            font_defaultLabel: 'Outfit',
            fontSize_sizes: '12/12px;14/14px;16/16px;18/18px;20/20px;24/24px;28/28px;32/32px'
        });

        return true;
    }

    function showEditorError() {
        var field = document.getElementById('blog-content-editor');
        if (field) {
            field.style.visibility = 'visible';
            field.style.minHeight = '420px';
        }
        if (document.getElementById('bb-editor-error')) {
            return;
        }
        var note = document.createElement('p');
        note.id = 'bb-editor-error';
        note.className = 'text-red-600 text-sm font-bold mt-2';
        note.textContent = 'Editor failed to load. Check internet connection, hard refresh (Ctrl+F5), or contact admin.';
        if (field && field.parentNode) {
            field.parentNode.insertBefore(note, field.nextSibling);
        }
    }

    var bootAttempts = 0;

    function boot() {
        bootAttempts += 1;
        if (initBlogEditor()) {
            return;
        }
        if (bootAttempts < 80) {
            setTimeout(boot, 50);
            return;
        }
        showEditorError();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }

    window.bbSyncBlogEditor = function () {
        if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances['blog-content-editor']) {
            CKEDITOR.instances['blog-content-editor'].updateElement();
        }
    };
})();
