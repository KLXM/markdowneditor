/**
 * markdowneditor.js – REDAXO MarkdownEditor integration
 *
 * Features:
 *  - EasyMDE initialisation with REDAXO profile support
 *  - Mediapool integration (toolbar button)
 *  - Structure linkmap integration (toolbar button)
 *  - YForm dataset linking (toolbar button)
 *  - Image drag & drop upload to mediapool
 *  - MBlock / MForm Repeater compatibility
 *  - REDAXO dark / light theme awareness
 */
;(function ($) {
    'use strict';

    /* ================================================================
     *  Registry  – keeps track of all active EasyMDE instances
     * ================================================================ */
    var instances = new Map();
    var activeEditor = null; // Editor that triggered a popup

    /* ================================================================
     *  Profile helpers
     * ================================================================ */
    function getProfiles() {
        return rex.markdowneditor_profiles || {};
    }

    function getConfig() {
        return rex.markdowneditor_config || {};
    }

    function getProfile(name) {
        var profiles = getProfiles();
        return profiles[name] || profiles['default'] || {};
    }

    /* ================================================================
     *  Custom toolbar actions – REDAXO-specific
     * ================================================================ */

    /**
     * Open REDAXO mediapool and insert selected media as Markdown image.
     *
     * Uses REDAXO's newPoolWindow() and attaches the rex:selectMedia handler
     * to the popup window (not the main window), matching how REDAXO triggers
     * the event: opener.jQuery(window).trigger() in the popup context.
     */
    function rexMediaAction(editor) {
        activeEditor = editor;
        var profile = getProfile(editor.element.getAttribute('data-markdowneditor-profile') || 'default');
        var categoryId = profile.mediaCategory || 0;
        var poolUrl = 'index.php?page=mediapool/media&opener_input_field=markdowneditor_media&rex_file_category=' + categoryId;
        var poolWindow = newPoolWindow(poolUrl);
        if (poolWindow) {
            $(poolWindow).on('rex:selectMedia', function (event, filename) {
                event.preventDefault();
                if (!activeEditor) return;
                var cm = activeEditor.codemirror;
                var isImage = /\.(jpg|jpeg|png|gif|webp|svg)$/i.test(filename);
                if (isImage) {
                    cm.replaceSelection('![' + filename + '](index.php?rex_media_type=markitupImage&rex_media_file=' + filename + ')');
                } else {
                    var selection = cm.getSelection() || filename;
                    cm.replaceSelection('[' + selection + '](/media/' + filename + ')');
                }
                activeEditor = null;
                cm.focus();
                poolWindow.close();
            });
        }
    }

    /**
     * Open REDAXO structure linkmap and insert selected link as Markdown link.
     *
     * Uses REDAXO's newLinkMapWindow() with opener_input_field so the linkmap
     * generates the insertLink() function and shows "select" buttons.
     */
    function rexLinkAction(editor) {
        activeEditor = editor;
        var clang = $('body').data('clang') || 1;
        var linkUrl = 'index.php?page=linkmap&opener_input_field=markdowneditor_link&clang=' + clang + '&category_id=0';
        var linkWindow = newLinkMapWindow(linkUrl);
        if (linkWindow) {
            $(linkWindow).on('rex:selectLink', function (event, link, name) {
                event.preventDefault();
                if (!activeEditor) return;
                var cm = activeEditor.codemirror;
                var selection = cm.getSelection() || name || 'Link';
                cm.replaceSelection('[' + selection + '](' + link + ')');
                activeEditor = null;
                cm.focus();
                linkWindow.close();
            });
        }
    }

    /**
     * Open YForm dataset selector popup.
     *
     * Uses YForm's built-in data management page with the rex_yform_manager_opener
     * pattern (same approach as TinyMCE/markitup). The popup renders YForm's own
     * UI and triggers 'rex:YForm_selectData' when a dataset is selected.
     */
    function rexYFormLinkAction(editor) {
        activeEditor = editor;
        var profile = getProfile(editor.element.getAttribute('data-markdowneditor-profile') || 'default');
        var yformTables = profile.yformTables || [];

        if (!yformTables.length) {
            alert('Keine YForm-Tabellen im Profil konfiguriert.\nBitte in den Profil-Einstellungen YForm-Tabellen hinzufügen.');
            return;
        }

        function openYFormTable(tableConfig) {
            var table = tableConfig.table;
            var field = tableConfig.label || 'name';
            var yformUrl = 'index.php?page=yform/manager/data_edit&table_name=' + encodeURIComponent(table)
                + '&rex_yform_manager_opener[id]=1'
                + '&rex_yform_manager_opener[field]=' + encodeURIComponent(field)
                + '&rex_yform_manager_opener[multiple]=0';

            var yformWindow = newWindow('mdeyform', yformUrl, 1200, Math.max(screen.height * 0.75, 800), ',status=yes,resizable=yes');
            if (yformWindow) {
                $(yformWindow).on('rex:YForm_selectData', function (event, id, value) {
                    event.preventDefault();
                    yformWindow.close();
                    if (!activeEditor) return;
                    var cm = activeEditor.codemirror;
                    // Clean value: remove [id=X] suffix added by YForm
                    var label = value.replace(/\s*\[id=\d+\]\s*$/, '');
                    var selection = cm.getSelection() || label || (table + ' #' + id);
                    cm.replaceSelection('[' + selection + '](yform:' + table + '/' + id + ')');
                    activeEditor = null;
                    cm.focus();
                });
            }
        }

        if (yformTables.length === 1) {
            openYFormTable(yformTables[0]);
        } else {
            // Multiple tables: show a simple selection dropdown
            showYFormTableSelector(editor, yformTables, openYFormTable);
        }
    }

    /**
     * Show a dropdown to select which YForm table to browse.
     */
    function showYFormTableSelector(editor, tables, callback) {
        // Remove existing dropdown
        $('.mde-yform-table-selector').remove();

        var toolbarButton = editor.toolbarElements['rex-yform-link'];
        if (!toolbarButton) return;

        var dropdown = $('<div class="mde-yform-table-selector"></div>');
        dropdown.css({
            position: 'absolute',
            top: '100%',
            left: 0,
            zIndex: 10000,
            background: '#fff',
            border: '1px solid #ccc',
            borderRadius: '4px',
            boxShadow: '0 4px 12px rgba(0,0,0,0.15)',
            padding: '4px 0',
            minWidth: '180px'
        });

        for (var i = 0; i < tables.length; i++) {
            (function(tableConfig) {
                var label = tableConfig.display || tableConfig.table;
                var item = $('<a href="#" style="display:block;padding:6px 12px;color:#333;text-decoration:none;"></a>');
                item.text(label);
                item.on('mouseenter', function() { $(this).css('background', '#f0f0f0'); });
                item.on('mouseleave', function() { $(this).css('background', 'transparent'); });
                item.on('click', function(e) {
                    e.preventDefault();
                    dropdown.remove();
                    callback(tableConfig);
                });
                dropdown.append(item);
            })(tables[i]);
        }

        $(toolbarButton).css('position', 'relative').append(dropdown);

        // Close on outside click
        setTimeout(function() {
            $(document).one('click', function(e) {
                if (!$(e.target).closest('.mde-yform-table-selector').length) {
                    dropdown.remove();
                }
            });
        }, 0);
    }

    /* ================================================================
     *  Plugin button registry
     * ================================================================ */
    function getPluginButtons() {
        return rex.markdowneditor_plugin_buttons || [];
    }

    /* ================================================================
     *  Build toolbar definition from profile
     * ================================================================ */
    function buildToolbar(profile) {
        var toolbarConfig = profile.toolbar || [];
        var toolbar = [];
        var pluginButtons = getPluginButtons();

        // Index plugin buttons by name for fast lookup
        var pluginButtonMap = {};
        for (var p = 0; p < pluginButtons.length; p++) {
            pluginButtonMap[pluginButtons[p].name] = pluginButtons[p];
        }

        for (var i = 0; i < toolbarConfig.length; i++) {
            var item = toolbarConfig[i];

            if (item === '|') {
                toolbar.push('|');
                continue;
            }

            switch (item) {
                case 'rex-media':
                    toolbar.push({
                        name: 'rex-media',
                        action: rexMediaAction,
                        className: 'fa fa-image',
                        title: 'Medienpool'
                    });
                    break;

                case 'rex-link':
                    toolbar.push({
                        name: 'rex-link',
                        action: rexLinkAction,
                        className: 'fa fa-sitemap',
                        title: 'Link aus Struktur'
                    });
                    break;

                case 'rex-yform-link':
                    toolbar.push({
                        name: 'rex-yform-link',
                        action: rexYFormLinkAction,
                        className: 'fa fa-database',
                        title: 'YForm Datensatz'
                    });
                    break;

                case 'checklist':
                    // Map to EasyMDE's checklist name
                    toolbar.push('unordered-list');
                    break;

                default:
                    // Check if this is a plugin-provided button
                    if (pluginButtonMap[item]) {
                        var pb = pluginButtonMap[item];
                        var buttonDef = {
                            name: pb.name,
                            className: pb.icon || 'fa fa-puzzle-piece',
                            title: pb.title || pb.name
                        };
                        if (pb.action && typeof window[pb.action] === 'function') {
                            buttonDef.action = window[pb.action];
                        } else {
                            // Plugins register their own handler via MarkdownEditor.on()
                            buttonDef.action = function (editor) {
                                $(document).trigger('markdowneditor:plugin-action', [pb.name, editor]);
                            };
                        }
                        toolbar.push(buttonDef);
                    } else {
                        toolbar.push(item);
                    }
                    break;
            }
        }

        return toolbar;
    }

    /* ================================================================
     *  Image upload via drag & drop
     * ================================================================ */
    function createImageUploadFunction(profile) {
        if (!profile.uploadEnabled) {
            return undefined;
        }

        return function (file, onSuccess, onError) {
            var config = getConfig();
            var formData = new FormData();
            formData.append('file', file);
            formData.append('rex-api-call', 'markdowneditor_image_upload');
            formData.append('_csrf_token', config.csrf_token || '');
            formData.append('media_category', profile.mediaCategory || 0);
            formData.append('media_type', profile.mediaType || '');

            $.ajax({
                url: 'index.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function (response) {
                    if (response && response.success) {
                        onSuccess('index.php?rex_media_type=markitupImage&rex_media_file=' + response.filename);
                    } else {
                        onError(response.error || 'Upload fehlgeschlagen');
                    }
                },
                error: function (xhr) {
                    var msg = 'Upload fehlgeschlagen';
                    try {
                        var resp = JSON.parse(xhr.responseText);
                        if (resp.error) msg = resp.error;
                    } catch (e) { /* ignore */ }
                    onError(msg);
                }
            });
        };
    }

    /* ================================================================
     *  Custom preview renderer – resolves REDAXO-specific URLs
     * ================================================================ */
    function createPreviewRenderer() {
        return function (plainText, preview) {
            // Resolve media:// URLs for preview
            var html = plainText;
            html = html.replace(/media:\/\/([^\s\)\"\']+)/g, function (match, filename) {
                var config = getConfig();
                return (config.media_url || '/media/') + filename;
            });

            // Use EasyMDE's built-in markdown parsing
            // (or override with a custom parser if needed)
            return null; // null = use default parser, but on the modified text
        };
    }

    /* ================================================================
     *  EasyMDE instance creation
     * ================================================================ */
    function createEditor(textarea) {
        // Skip if already initialised
        if (instances.has(textarea)) {
            return instances.get(textarea);
        }

        var profileName = textarea.getAttribute('data-markdowneditor-profile') || 'default';
        var profile = getProfile(profileName);

        var options = {
            element: textarea,
            toolbar: buildToolbar(profile),
            minHeight: profile.minHeight + 'px',
            maxHeight: profile.maxHeight + 'px',
            status: profile.statusBar ? ['lines', 'words', 'cursor'] : false,
            spellChecker: profile.spellChecker || false,
            forceSync: true, // Always sync content to textarea
            renderingConfig: {
                singleLineBreaks: false,
                codeSyntaxHighlighting: true
            },
            insertTexts: {
                image: ['![', '](index.php?rex_media_type=markitupImage&rex_media_file=)'],
                link: ['[', '](redaxo://)'],
                table: ['', '\n\n| Spalte 1 | Spalte 2 | Spalte 3 |\n| -------- | -------- | -------- |\n| Zelle    | Zelle    | Zelle    |\n']
            },
            promptURLs: false,
            styleSelectedText: true,
            sideBySideFullscreen: false,
            tabSize: 4
        };

        // Image upload via drag & drop
        if (profile.uploadEnabled) {
            options.uploadImage = true;
            options.imageUploadFunction = createImageUploadFunction(profile);
            options.imageAccept = 'image/png, image/jpeg, image/gif, image/webp, image/svg+xml';
            options.imageMaxSize = 10 * 1024 * 1024; // 10 MB
        }

        // Autosave
        if (profile.autosave) {
            var uniqueId = textarea.getAttribute('name') || textarea.getAttribute('id') || 'mde-' + Date.now();
            options.autosave = {
                enabled: true,
                uniqueId: 'markdowneditor-' + uniqueId,
                delay: 5000,
                submit_delay: 10000
            };
        }

        // Merge additional options from profile
        if (profile.options && typeof profile.options === 'object') {
            for (var key in profile.options) {
                if (profile.options.hasOwnProperty(key)) {
                    options[key] = profile.options[key];
                }
            }
        }

        // Custom preview render: resolve media:// and redaxo:// URLs
        options.previewRender = function (plainText) {
            // Resolve markitup-compatible media URLs for preview
            var config = getConfig();
            var mediaBase = config.media_url || '/media/';
            var resolved = plainText;

            // Legacy media:// support
            resolved = resolved.replace(/media:\/\/([^\s\)\"\']+)/g, function (match, filename) {
                return mediaBase + filename;
            });

            // markitup-style image URLs: index.php?rex_media_type=...&rex_media_file=FILE
            resolved = resolved.replace(/index\.php\?rex_media_type=[^&]+&rex_media_file=([^\s\)\"\']+)/g, function (match, filename) {
                return mediaBase + filename;
            });

            // Use EasyMDE's built-in markdown-it parser
            return this.parent.markdown(resolved);
        };

        // Create instance
        var mde = new EasyMDE(options);

        // Register REDAXO-specific keyboard shortcuts via CodeMirror
        mde.codemirror.addKeyMap({
            'Shift-Cmd-M': function () { rexMediaAction(mde); },
            'Shift-Ctrl-M': function () { rexMediaAction(mde); },
            'Shift-Cmd-L': function () { rexLinkAction(mde); },
            'Shift-Ctrl-L': function () { rexLinkAction(mde); }
        });

        // Store instance
        instances.set(textarea, mde);

        // Sync value on change (important for MBlock/MForm)
        mde.codemirror.on('change', function () {
            textarea.value = mde.value();
            // Trigger native input event for Alpine.js / MForm Repeater
            textarea.dispatchEvent(new Event('input', { bubbles: true }));
            textarea.dispatchEvent(new Event('change', { bubbles: true }));
        });

        return mde;
    }

    /* ================================================================
     *  Destroy an EasyMDE instance
     * ================================================================ */
    function destroyEditor(textarea) {
        var mde = instances.get(textarea);
        if (mde) {
            mde.toTextArea();
            instances.delete(textarea);
        }
    }

    /* ================================================================
     *  Init / Re-init all editors in a container
     *
     *  MBlock triggers rex:ready on cloned items. The cloned DOM contains
     *  the full .EasyMDEContainer (toolbar + CodeMirror + hidden textarea).
     *  We must clean up stale containers before creating fresh editors.
     *
     *  IMPORTANT: EasyMDE places the textarea as a SIBLING before the
     *  .EasyMDEContainer, not inside it.  After MBlock clones a block
     *  the DOM looks like:
     *      <textarea style="display:none">…</textarea>   ← cloned
     *      <div class="EasyMDEContainer">…</div>         ← cloned (stale)
     *  We must remove the stale container and restore the textarea.
     * ================================================================ */
    function initEditors(container) {
        var $container = $(container || document);

        // Initialise editors – clean up orphans per textarea
        $container.find('textarea.markdowneditor-editor').each(function () {
            // Skip elements inside MBlock templates
            if ($(this).closest('.mblock-template-holder').length > 0) {
                return;
            }

            // Already managed – skip
            if (instances.has(this)) {
                return;
            }

            // Orphaned clone: remove stale EasyMDE sibling containers
            // that were copied by MBlock but have no live instance.
            $(this).siblings('.EasyMDEContainer').remove();
            $(this).show().css('display', '');

            createEditor(this);
        });
    }

    /* ================================================================
     *  MBlock / MForm Repeater compatibility
     * ================================================================ */

    // Prepare editors for DOM move operations (destroy before clone/drag)
    function prepareForMove(container) {
        $(container).find('textarea.markdowneditor-editor').each(function () {
            destroyEditor(this);
        });
    }

    /* ================================================================
     *  REDAXO rex:ready – primary initialisation point
     * ================================================================ */
    $(document).on('rex:ready', function (e, container) {
        initEditors(container);
    });

    /* ================================================================
     *  Public API (for advanced use / external addons)
     * ================================================================ */
    window.MarkdownEditor = {
        /** Get EasyMDE instance for a textarea */
        getInstance: function (textarea) {
            return instances.get(textarea) || null;
        },
        /** Create editor on a textarea */
        create: createEditor,
        /** Destroy editor on a textarea */
        destroy: destroyEditor,
        /** Re-init all editors in a container */
        init: initEditors,
        /** Prepare editors for move (destroy before DOM operation) */
        prepareForMove: prepareForMove,
        /** Listen for plugin action events */
        on: function (eventName, callback) {
            $(document).on('markdowneditor:' + eventName, callback);
        },
        /** Trigger a plugin action event */
        trigger: function (eventName, data) {
            $(document).trigger('markdowneditor:' + eventName, data);
        }
    };

})(jQuery);
