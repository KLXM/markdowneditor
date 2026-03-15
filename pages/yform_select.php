<?php

/**
 * yform_select.php – Popup page for selecting YForm datasets.
 *
 * Opened from the markdowneditor toolbar button. Shows configured YForm tables,
 * lets the user pick a table and then select a dataset.
 *
 * Query params:
 *   tables – JSON-encoded array of table configs from the profile
 *   table  – currently selected table name (for dataset list)
 *
 * @var rex_addon $this
 */

$addon = rex_addon::get('markdowneditor');

// Check YForm availability
if (!rex_addon::get('yform')->isAvailable() || !rex_addon::get('yform')->getPlugin('manager')->isAvailable()) {
    echo rex_view::error($addon->i18n('markdowneditor_yform_not_available'));
    return;
}

$tablesJson = rex_request('tables', 'string', '[]');
$tables = json_decode($tablesJson, true);

if (!is_array($tables) || count($tables) === 0) {
    echo rex_view::warning($addon->i18n('markdowneditor_yform_no_tables'));
    return;
}

$selectedTable = rex_request('table', 'string', '');
$searchTerm = rex_request('search', 'string', '');

// Validate selected table is in allowed list
$tableConfig = null;
foreach ($tables as $t) {
    if (isset($t['table']) && $t['table'] === $selectedTable) {
        $tableConfig = $t;
        break;
    }
}

// Nonce for inline script
$nonce = rex_response::getNonce();

?>
<div class="container-fluid" style="padding: 20px;">
    <h3><i class="rex-icon fa-database"></i> <?= $addon->i18n('markdowneditor_yform_select_title') ?></h3>

    <?php if (count($tables) > 1 || $selectedTable === ''): ?>
    <div class="panel panel-default" style="margin-bottom: 20px;">
        <div class="panel-heading"><?= $addon->i18n('markdowneditor_yform_select_table') ?></div>
        <div class="panel-body">
            <div class="btn-group" role="group">
                <?php foreach ($tables as $t):
                    $tName = $t['table'] ?? '';
                    $tLabel = $t['display'] ?? $tName;
                    $yformTable = rex_yform_manager_table::get($tName);
                    $displayName = $yformTable !== null ? $yformTable->getName() : $tLabel;
                    $isActive = ($tName === $selectedTable) ? ' active' : '';
                    $url = 'index.php?page=markdowneditor/yform_select&table=' . urlencode($tName) . '&tables=' . urlencode($tablesJson);
                ?>
                <a href="<?= $url ?>" class="btn btn-default<?= $isActive ?>"><?= rex_escape($displayName) ?></a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php
    // Auto-select first table if only one
    if ($selectedTable === '' && count($tables) === 1) {
        $selectedTable = $tables[0]['table'] ?? '';
        foreach ($tables as $t) {
            if (isset($t['table']) && $t['table'] === $selectedTable) {
                $tableConfig = $t;
                break;
            }
        }
    }

    if ($tableConfig !== null && $selectedTable !== ''):
        $yformTable = rex_yform_manager_table::get($selectedTable);
        if ($yformTable === null) {
            echo rex_view::error(rex_i18n::msg('markdowneditor_yform_table_not_found', $selectedTable));
            return;
        }

        $labelField = $tableConfig['label'] ?? 'name';
        $displayField = $tableConfig['display'] ?? $labelField;

        // Check if fields exist
        $availableFields = [];
        foreach ($yformTable->getFields() as $field) {
            $availableFields[] = $field->getName();
        }

        // Fallback if configured fields don't exist
        if (!in_array($labelField, $availableFields, true)) {
            $labelField = $availableFields[0] ?? 'id';
        }
        if (!in_array($displayField, $availableFields, true)) {
            $displayField = $labelField;
        }

        // Search
        $searchHtml = '
        <form method="get" class="form-inline" style="margin-bottom: 15px;">
            <input type="hidden" name="page" value="markdowneditor/yform_select">
            <input type="hidden" name="table" value="' . rex_escape($selectedTable) . '">
            <input type="hidden" name="tables" value="' . rex_escape($tablesJson) . '">
            <div class="input-group" style="width: 100%; max-width: 400px;">
                <input type="text" name="search" class="form-control" placeholder="' . $addon->i18n('markdowneditor_yform_search') . '" value="' . rex_escape($searchTerm) . '">
                <span class="input-group-btn">
                    <button class="btn btn-default" type="submit"><i class="rex-icon fa-search"></i></button>
                </span>
            </div>
        </form>';
        echo $searchHtml;

        // Build query
        $tableName = $yformTable->getTableName();
        $query = 'SELECT id, `' . rex_sql::factory()->escape($labelField) . '`';
        if ($displayField !== $labelField) {
            $query .= ', `' . rex_sql::factory()->escape($displayField) . '`';
        }
        $query .= ' FROM ' . $tableName;

        $params = [];
        if ($searchTerm !== '') {
            $query .= ' WHERE `' . rex_sql::factory()->escape($labelField) . '` LIKE :search';
            if ($displayField !== $labelField) {
                $query .= ' OR `' . rex_sql::factory()->escape($displayField) . '` LIKE :search';
            }
            $params['search'] = '%' . $searchTerm . '%';
        }
        $query .= ' ORDER BY `' . rex_sql::factory()->escape($labelField) . '` ASC LIMIT 100';

        $sql = rex_sql::factory();
        $sql->setQuery($query, $params);

        $displayTableName = $yformTable->getName();
        ?>
        <div class="panel panel-default">
            <div class="panel-heading"><?= rex_escape($displayTableName) ?> <small class="text-muted">(<?= $sql->getRows() ?> <?= $addon->i18n('markdowneditor_yform_entries') ?>)</small></div>
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th><?= rex_escape($labelField) ?></th>
                        <?php if ($displayField !== $labelField): ?>
                        <th><?= rex_escape($displayField) ?></th>
                        <?php endif; ?>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php for ($i = 0; $i < $sql->getRows(); ++$i): ?>
                    <?php
                        $dataId = (int) $sql->getValue('id');
                        $dataLabel = (string) $sql->getValue($labelField);
                        $dataDisplay = ($displayField !== $labelField) ? (string) $sql->getValue($displayField) : '';
                    ?>
                    <tr>
                        <td><?= $dataId ?></td>
                        <td><?= rex_escape($dataLabel) ?></td>
                        <?php if ($displayField !== $labelField): ?>
                        <td><?= rex_escape($dataDisplay) ?></td>
                        <?php endif; ?>
                        <td class="text-right">
                            <a href="#" class="btn btn-xs btn-select"
                               onclick="selectYFormDataset('<?= rex_escape($selectedTable, 'js') ?>', <?= $dataId ?>, '<?= rex_escape($dataLabel, 'js') ?>'); return false;">
                                <i class="rex-icon fa-check"></i> <?= $addon->i18n('markdowneditor_yform_select') ?>
                            </a>
                        </td>
                    </tr>
                <?php $sql->next(); endfor; ?>
                <?php if ($sql->getRows() === 0): ?>
                    <tr><td colspan="<?= ($displayField !== $labelField) ? 4 : 3 ?>" class="text-muted text-center"><?= $addon->i18n('markdowneditor_yform_no_results') ?></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script type="text/javascript" nonce="<?= $nonce ?>">
function selectYFormDataset(table, id, label) {
    if (window.opener && window.opener.jQuery) {
        var event = window.opener.jQuery.Event('markdowneditor:selectYFormLink');
        window.opener.jQuery(window).trigger(event, [table, id, label]);
    }
    self.close();
}
</script>
