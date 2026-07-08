/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 */
humhub.module('systemEmailCustomizer', function (module, require, $) {
    var Widget = require('ui.widget').Widget;
    var status = require('ui.status');

    function syncRichTextEditors($form) {
        $form.find('textarea[id$="_input"]').each(function () {
            var inputEl = this;
            var editorId = inputEl.id.replace(/_input$/, '');
            var editorEl = document.getElementById(editorId);
            if (!editorEl) {
                return;
            }

            $(editorEl).trigger('focusout');

            var editorWidget = Widget.instance(editorEl);
            if (editorWidget && editorWidget.editor && typeof editorWidget.editor.serialize === 'function') {
                inputEl.value = editorWidget.editor.serialize();
                $(inputEl).trigger('change');
            }
        });
    }

    function clearRichTextBackup($form) {
        var backupKey = 'RichTextEditor.backup';
        var backupRaw = sessionStorage.getItem(backupKey);
        if (!backupRaw) {
            return;
        }

        var backup;
        try {
            backup = JSON.parse(backupRaw);
        } catch (e) {
            sessionStorage.removeItem(backupKey);
            return;
        }

        $form.find('textarea[id$="_input"]').each(function () {
            delete backup[this.id];
        });

        if (Object.keys(backup).length) {
            sessionStorage.setItem(backupKey, JSON.stringify(backup));
        } else {
            sessionStorage.removeItem(backupKey);
        }
    }

    function getEditorWidget(safeKey, section) {
        var editorId = 'sec_' + section + '_' + safeKey;
        var editorEl = document.getElementById(editorId);
        if (!editorEl) {
            return null;
        }
        return Widget.instance(editorEl);
    }

    function insertIntoEditor(safeKey, section, shortcode) {
        var editorWidget = getEditorWidget(safeKey, section);
        if (!editorWidget || !editorWidget.editor || typeof editorWidget.editor.init !== 'function') {
            return false;
        }

        var current = '';
        if (typeof editorWidget.editor.serialize === 'function') {
            current = editorWidget.editor.serialize();
        }

        var separator = current.trim() ? '\n\n' : '';
        var newContent = current + separator + shortcode;
        editorWidget.editor.init(newContent);
        editorWidget.getInput().val(newContent).trigger('change');
        return true;
    }

    function initButtonBuilder() {
        var $builder = $('.sec-button-builder');
        if (!$builder.length) {
            return;
        }

        var safeKey = $builder.data('safe-key');

        $('#sec-button-url').off('change.secEmailCustomizer').on('change.secEmailCustomizer', function () {
            var isCustom = $(this).val() === '__custom__';
            $('#sec-button-custom-url').toggle(isCustom);
        });

        $('.sec-insert-button').off('click.secEmailCustomizer').on('click.secEmailCustomizer', function () {
            var label = $.trim($('#sec-button-label').val());
            var url = $('#sec-button-url').val();
            if (url === '__custom__') {
                url = $.trim($('#sec-button-custom-url').val());
            }
            var section = $('#sec-button-target').val();

            if (!label || !url) {
                status.error(module.text('error.missingButtonFields'));
                return;
            }

            var shortcode = '{button:' + label + '|' + url + '}';
            if (!insertIntoEditor(safeKey, section, shortcode)) {
                status.error(module.text('error.insertButtonFailed'));
                return;
            }

            var sectionLabel = $('#sec-button-target option:selected').text();
            status.success(module.text('success.buttonInserted', {section: sectionLabel}));
        });
    }

    function initTemplateForm() {
        var $form = $('#sec-email-template-form');
        if (!$form.length) {
            return;
        }

        clearRichTextBackup($form);

        $form.off('.secEmailCustomizer');

        $form.on('submit.secEmailCustomizer', function () {
            syncRichTextEditors($form);
        });

        $form.on('beforeSubmit.secEmailCustomizer', function () {
            syncRichTextEditors($form);
            return true;
        });

        $form.on('click.secEmailCustomizer', '[type="submit"]', function () {
            syncRichTextEditors($form);
        });
    }

    module.init = function () {
        initTemplateForm();
        initButtonBuilder();

        $(document).on('click', '.sec-copy-variable', function () {
            var variable = $(this).data('variable');
            if (!variable) {
                return;
            }

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(variable);
            } else {
                var $temp = $('<textarea>');
                $('body').append($temp);
                $temp.val(variable).select();
                document.execCommand('copy');
                $temp.remove();
            }

            module.log('Copied ' + variable);
        });
    };

    module.export({
        init: module.init,
        initOnPjaxLoad: true,
        text: {
            'error.missingButtonFields': 'Please enter a button label and link.',
            'error.insertButtonFailed': 'Could not insert button into the selected field.',
            'success.buttonInserted': 'Button inserted into the {section} field.'
        }
    });
});
