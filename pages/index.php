<?php

/** @var rex_addon $this */

$package = rex_addon::get('markdowneditor');
echo rex_view::title($package->i18n('markdowneditor_title'));
rex_be_controller::includeCurrentPageSubPath();
