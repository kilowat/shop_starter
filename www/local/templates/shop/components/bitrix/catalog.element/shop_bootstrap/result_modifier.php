<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$component = $this->getComponent();
$arParams = $component->applyTemplateModifications();