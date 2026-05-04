<?php

/**
 * profiles.php – Profile management for MarkdownEditor.
 *
 * @var rex_addon $this
 */

$addon = rex_addon::get('markdowneditor');
$func = rex_request('func', 'string', 'list');
$id = rex_request('id', 'int', 0);
$message = '';

// ---------- Import (AJAX) ----------
if ($func === 'import') {
    // Delegated to API endpoint – should not reach here directly
    $func = 'list';
}

// ---------- Delete ----------
if ($func === 'delete' && $id > 0) {
    $csrfToken = rex_csrf_token::factory('markdowneditor_profiles');
    if (!$csrfToken->isValid()) {
        echo rex_view::error(rex_i18n::msg('csrf_token_invalid'));
    } else {
        $sql = rex_sql::factory();
        $sql->setTable(rex::getTable('markdowneditor_profiles'));
        $sql->setWhere(['id' => $id]);
        $sql->delete();
        $message = rex_view::success($addon->i18n('markdowneditor_profile_deleted'));
        $func = 'list';
    }
}

// ---------- Clone ----------
if ($func === 'clone' && $id > 0) {
    $csrfToken = rex_csrf_token::factory('markdowneditor_profiles');
    if (!$csrfToken->isValid()) {
        echo rex_view::error(rex_i18n::msg('csrf_token_invalid'));
    } else {
        $sql = rex_sql::factory();
        $sql->setQuery('SELECT * FROM ' . rex::getTable('markdowneditor_profiles') . ' WHERE id = :id', ['id' => $id]);
        if ($sql->getRows() > 0) {
            $now = date('Y-m-d H:i:s');
            $cloneSql = rex_sql::factory();
            $cloneSql->setTable(rex::getTable('markdowneditor_profiles'));
            $cloneSql->setValue('name', $sql->getValue('name') . '_copy');
            $cloneSql->setValue('description', $sql->getValue('description') . ' (Kopie)');
            $cloneSql->setValue('toolbar', $sql->getValue('toolbar'));
            $cloneSql->setValue('min_height', $sql->getValue('min_height'));
            $cloneSql->setValue('max_height', $sql->getValue('max_height'));
            $cloneSql->setValue('status_bar', $sql->getValue('status_bar'));
            $cloneSql->setValue('spell_checker', $sql->getValue('spell_checker'));
            $cloneSql->setValue('autosave', $sql->getValue('autosave'));
            $cloneSql->setValue('upload_enabled', $sql->getValue('upload_enabled'));
            $cloneSql->setValue('media_category', $sql->getValue('media_category'));
            $cloneSql->setValue('media_type', $sql->getValue('media_type'));
            $cloneSql->setValue('options', $sql->getValue('options'));
            $cloneSql->setValue('createdate', $now);
            $cloneSql->setValue('createuser', rex::requireUser()->getLogin());
            $cloneSql->insert();
            $message = rex_view::success($addon->i18n('markdowneditor_profile_cloned'));
        }
        $func = 'list';
    }
}

// ---------- Edit / Add form ----------
if ($func === 'edit' || $func === 'add') {
    $form = rex_form::factory(rex::getTable('markdowneditor_profiles'), $addon->i18n('markdowneditor_profile'), 'id=' . $id, 'post');
    $form->addParam('func', $func);
    $form->addParam('id', $id);

    // Name
    $field = $form->addTextField('name');
    $field->setLabel($addon->i18n('markdowneditor_profile_name'));
    $field->setNotice($addon->i18n('markdowneditor_profile_name_notice'));
    $field->getValidator()->add('notEmpty', $addon->i18n('markdowneditor_profile_name_required'));
    $field->setAttribute('maxlength', '40');

    // Description
    $field = $form->addTextField('description');
    $field->setLabel($addon->i18n('markdowneditor_profile_description'));

    // Toolbar (JSON)
    $field = $form->addTextAreaField('toolbar');
    $field->setLabel($addon->i18n('markdowneditor_profile_toolbar'));
    $field->setNotice($addon->i18n('markdowneditor_profile_toolbar_notice'));
    $field->setAttribute('rows', '5');
    $field->setAttribute('class', 'form-control rex-code');

    // Min height
    $field = $form->addTextField('min_height');
    $field->setLabel($addon->i18n('markdowneditor_min_height'));
    $field->setAttribute('type', 'number');
    $field->setNotice('Pixel');

    // Max height
    $field = $form->addTextField('max_height');
    $field->setLabel($addon->i18n('markdowneditor_max_height'));
    $field->setAttribute('type', 'number');
    $field->setNotice('Pixel');

    // Status bar
    $field = $form->addCheckboxField('status_bar');
    $field->setLabel($addon->i18n('markdowneditor_status_bar'));
    $field->addOption($addon->i18n('markdowneditor_active'), 1);

    // Spell checker
    $field = $form->addCheckboxField('spell_checker');
    $field->setLabel($addon->i18n('markdowneditor_spell_checker'));
    $field->addOption($addon->i18n('markdowneditor_active'), 1);

    // Autosave
    $field = $form->addCheckboxField('autosave');
    $field->setLabel($addon->i18n('markdowneditor_autosave'));
    $field->addOption($addon->i18n('markdowneditor_active'), 1);

    // Upload enabled
    $field = $form->addCheckboxField('upload_enabled');
    $field->setLabel($addon->i18n('markdowneditor_upload'));
    $field->addOption($addon->i18n('markdowneditor_active'), 1);

    // Media category
    $field = $form->addTextField('media_category');
    $field->setLabel($addon->i18n('markdowneditor_media_category'));
    $field->setAttribute('type', 'number');
    $field->setNotice($addon->i18n('markdowneditor_media_category_notice'));

    // Media type
    $field = $form->addTextField('media_type');
    $field->setLabel($addon->i18n('markdowneditor_media_type'));
    $field->setNotice($addon->i18n('markdowneditor_media_type_notice'));

    // YForm tables (JSON)
    $field = $form->addTextAreaField('yform_tables');
    $field->setLabel($addon->i18n('markdowneditor_yform_tables'));
    $field->setAttribute('rows', '5');
    $field->setAttribute('class', 'form-control rex-code');
    $field->setNotice($addon->i18n('markdowneditor_yform_tables_notice'));

    // Additional options (JSON)
    $field = $form->addTextAreaField('options');
    $field->setLabel($addon->i18n('markdowneditor_extra_options'));
    $field->setAttribute('rows', '6');
    $field->setAttribute('class', 'form-control rex-code');
    $field->setNotice($addon->i18n('markdowneditor_extra_options_notice'));

    $content = $form->get();

    // Add live preview below form
    $preview = '
    <div class="markdowneditor-preview-container">
        <h4>' . $addon->i18n('markdowneditor_preview') . '</h4>
        <textarea class="form-control markdowneditor-editor" data-markdowneditor-profile="default" rows="6">' .
        rex_escape("# Vorschau\n\nDies ist eine **Vorschau** des Markdown-Editors.\n\n- Punkt 1\n- Punkt 2\n- Punkt 3\n\n> Ein Zitat als Beispiel\n\n`Code` und [Links](https://example.com) funktionieren auch.") .
        '</textarea>
    </div>';

    $fragment = new rex_fragment();
    $fragment->setVar('class', 'edit', false);
    $fragment->setVar('title', $func === 'add' ? $addon->i18n('markdowneditor_profile_add') : $addon->i18n('markdowneditor_profile_edit'), false);
    $fragment->setVar('body', $content . $preview, false);
    echo $message;
    echo $fragment->parse('core/page/section.php');
} else {
    // ---------- List ----------
    $list = rex_list::factory('SELECT id, name, description, min_height, max_height, status_bar, upload_enabled, updatedate FROM ' . rex::getTable('markdowneditor_profiles') . ' ORDER BY name ASC');
    $list->addTableAttribute('class', 'table-striped table-hover');

    // Add button
    $list->addTableColumnGroup([40, '*', '*', 100, 100, 80, 80, 150, 160]);

    // ID
    $thIcon = '<a href="' . $list->getUrl(['func' => 'add']) . '"><i class="rex-icon rex-icon-add-action"></i></a>';
    $tdIcon = '<i class="rex-icon fa-file-text-o"></i>';
    $list->addColumn($thIcon, $tdIcon, 0, ['<th class="rex-table-icon">###VALUE###</th>', '<td class="rex-table-icon">###VALUE###</td>']);
    $list->setColumnParams($thIcon, ['func' => 'edit', 'id' => '###id###']);

    // Name
    $list->setColumnLabel('name', $addon->i18n('markdowneditor_profile_name'));
    $list->setColumnParams('name', ['func' => 'edit', 'id' => '###id###']);

    // Description
    $list->setColumnLabel('description', $addon->i18n('markdowneditor_profile_description'));

    // Min height
    $list->setColumnLabel('min_height', $addon->i18n('markdowneditor_min_height'));
    $list->setColumnFormat('min_height', 'custom', static function ($params) {
        return $params['list']->getValue('min_height') . ' px';
    });

    // Max height
    $list->setColumnLabel('max_height', $addon->i18n('markdowneditor_max_height'));
    $list->setColumnFormat('max_height', 'custom', static function ($params) {
        return $params['list']->getValue('max_height') . ' px';
    });

    // Status bar
    $list->setColumnLabel('status_bar', $addon->i18n('markdowneditor_status_bar'));
    $list->setColumnFormat('status_bar', 'custom', static function ($params) {
        return (int) $params['list']->getValue('status_bar') === 1 ? '<i class="rex-icon fa-check text-success"></i>' : '<i class="rex-icon fa-times text-muted"></i>';
    });

    // Upload
    $list->setColumnLabel('upload_enabled', $addon->i18n('markdowneditor_upload'));
    $list->setColumnFormat('upload_enabled', 'custom', static function ($params) {
        return (int) $params['list']->getValue('upload_enabled') === 1 ? '<i class="rex-icon fa-check text-success"></i>' : '<i class="rex-icon fa-times text-muted"></i>';
    });

    // Updated
    $list->setColumnLabel('updatedate', $addon->i18n('markdowneditor_updated'));
    $list->setColumnFormat('updatedate', 'date', 'd.m.Y H:i');

    // Actions
    $list->addColumn('actions', '', -1, ['<th>' . rex_i18n::msg('header_function') . '</th>', '<td class="rex-table-action">###VALUE###</td>']);

    $csrfParams = rex_csrf_token::factory('markdowneditor_profiles')->getUrlParams();

    // Edit
    $list->setColumnFormat('actions', 'custom', static function ($params) use ($addon, $csrfParams) {
        $id = $params['list']->getValue('id');
        $actions = [];
        $actions[] = '<a href="' . $params['list']->getUrl(['func' => 'edit', 'id' => $id]) . '"><i class="rex-icon fa-pencil"></i> ' . $addon->i18n('markdowneditor_edit') . '</a>';
        $actions[] = '<a href="' . $params['list']->getUrl(array_merge(['func' => 'clone', 'id' => $id], $csrfParams)) . '"><i class="rex-icon fa-copy"></i> ' . $addon->i18n('markdowneditor_clone') . '</a>';
        $actions[] = '<a href="' . $params['list']->getUrl(array_merge(['func' => 'delete', 'id' => $id], $csrfParams)) . '" data-confirm="' . $addon->i18n('markdowneditor_delete_confirm') . '"><i class="rex-icon fa-trash-o"></i> ' . $addon->i18n('markdowneditor_delete') . '</a>';
        return implode(' | ', $actions);
    });

    $content = $list->get();

    $fragment = new rex_fragment();
    $fragment->setVar('title', $addon->i18n('markdowneditor_profiles'), false);
    $fragment->setVar('content', $content, false);
    echo $message;
    echo $fragment->parse('core/page/section.php');
}
