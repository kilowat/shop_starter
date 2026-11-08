<?php
/**
 * Умный фильтр: вёрстка целиком на сервере.
 *
 * JS (js/catalog/filter.js) не рисует разметку, а только:
 *   - обновляет счётчики [data-count] и итог [data-filter-total] после AJAX;
 *   - прокидывает выбор ползунка в поля диапазона;
 *   - отправляет форму в URL (каталог перезагружает список сам).
 *
 * Без JS форма уходит обычным GET-ом — фильтр применяется штатно.
 *
 * @var array $arResult
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$h = static fn($value): string => htmlspecialcharsbx((string)$value);

$isRange = static fn(array $item): bool => isset($item['VALUES']['MIN'], $item['VALUES']['MAX']);

$items = is_array($arResult['ITEMS'] ?? null) ? $arResult['ITEMS'] : [];

$ranges = [];
$groups = [];

foreach ($items as $item) {
    $values = is_array($item['VALUES'] ?? null) ? $item['VALUES'] : [];

    if ($values === []) {
        continue;
    }

    if ($isRange($item)) {
        $ranges[] = $item;
        continue;
    }

    $groups[] = $item;
}

$isPrice = static fn(array $item): bool => !empty($item['PRICE']);

$priceRanges = array_values(array_filter($ranges, $isPrice));
$valueRanges = array_values(array_filter($ranges, static fn(array $item): bool => !$isPrice($item)));

/** Есть ли применённые значения — от этого зависит кнопка «Сбросить». */
$hasSelection = false;

foreach ($groups as $item) {
    foreach ($item['VALUES'] as $value) {
        if (!empty($value['CHECKED'])) {
            $hasSelection = true;
            break 2;
        }
    }
}

foreach ($ranges as $item) {
    foreach (['MIN', 'MAX'] as $side) {
        if (($item['VALUES'][$side]['HTML_VALUE'] ?? '') !== '') {
            $hasSelection = true;
            break 2;
        }
    }
}

$total = (int)($arResult['TOTAL'] ?? 0);

$formAction = (string)($arResult['FORM_ACTION'] ?? '');
$hidden = is_array($arResult['HIDDEN'] ?? null) ? $arResult['HIDDEN'] : [];

/** Значение счётчика: показываем и ноль — так видно, что значение пустое. */
$countOf = static fn(array $value): string => isset($value['ELEMENT_COUNT']) && $value['ELEMENT_COUNT'] !== ''
    ? (string)(int)$value['ELEMENT_COUNT']
    : '';

$isDisabled = static fn(array $value): bool => !empty($value['DISABLED']) && empty($value['CHECKED']);

/** Ползунок диапазона: границы, текущие значения и формат. */
$rangeData = static function (array $item): array {
    $min = $item['VALUES']['MIN'];
    $max = $item['VALUES']['MAX'];
    $boundMin = (float)($min['VALUE'] ?? 0);
    $boundMax = (float)($max['VALUE'] ?? 0);
    $htmlMin = (string)($min['HTML_VALUE'] ?? '');
    $htmlMax = (string)($max['HTML_VALUE'] ?? '');

    return [
        'code' => (string)($item['CODE'] ?? ''),
        'name' => (string)($item['NAME'] ?? ''),
        'minName' => (string)($min['CONTROL_NAME'] ?? ''),
        'maxName' => (string)($max['CONTROL_NAME'] ?? ''),
        'boundMin' => $boundMin,
        'boundMax' => $boundMax,
        'lo' => $htmlMin !== '' ? (float)$htmlMin : $boundMin,
        'hi' => $htmlMax !== '' ? (float)$htmlMax : $boundMax,
        'htmlMin' => $htmlMin,
        'htmlMax' => $htmlMax,
        'decimals' => (int)($item['DECIMALS'] ?? 0),
        'currency' => (string)($min['CURRENCY'] ?? ''),
    ];
};

/** Разметка диапазона (цена и прочие числовые свойства). */
$renderRange = static function (array $range) use ($h): string {
    ob_start();
    ?>
    <div class="smart-filter__group mb-4" data-range="<?= $h($range['code']) ?>">
        <div class="smart-filter__group-title fw-semibold mb-2"><?= $h($range['name']) ?></div>

        <div class="px-2 pt-1">
            <div class="smart-filter__slider"
                 data-slider="<?= $h($range['code']) ?>"
                 data-min="<?= $h($range['boundMin']) ?>"
                 data-max="<?= $h($range['boundMax']) ?>"
                 data-lo="<?= $h($range['lo']) ?>"
                 data-hi="<?= $h($range['hi']) ?>"
                 data-decimals="<?= $h($range['decimals']) ?>"
                 data-currency="<?= $h($range['currency']) ?>"></div>
        </div>

        <div class="d-flex align-items-center gap-2 mt-2">
            <input type="number"
                   class="form-control form-control-sm"
                   inputmode="numeric"
                   name="<?= $h($range['minName']) ?>"
                   value="<?= $h($range['htmlMin']) ?>"
                   placeholder="<?= $h($range['boundMin']) ?>"
                   data-range-input
                   data-side="min">
            <span class="text-muted">—</span>
            <input type="number"
                   class="form-control form-control-sm"
                   inputmode="numeric"
                   name="<?= $h($range['maxName']) ?>"
                   value="<?= $h($range['htmlMax']) ?>"
                   placeholder="<?= $h($range['boundMax']) ?>"
                   data-range-input
                   data-side="max">
        </div>
    </div>
    <?php

    return (string)ob_get_clean();
};
?>
<div class="smart-filter card border-0 shadow-sm" data-filter>
    <form method="get" action="<?= $h($formAction) ?>" class="smart-filter__form" data-filter-form>
        <input type="hidden" name="set_filter" value="y">

        <?php foreach ($hidden as $field): ?>
            <?php if (!empty($field['CONTROL_NAME'])): ?>
                <input type="hidden" name="<?= $h($field['CONTROL_NAME']) ?>" value="<?= $h($field['HTML_VALUE'] ?? 'Y') ?>">
            <?php endif; ?>
        <?php endforeach; ?>

        <div class="card-header bg-white d-flex align-items-center justify-content-between py-3">
            <span class="fw-semibold">Подбор параметров</span>

            <?php // кнопка всегда в разметке: видимость переключает filter.js по выбранным значениям ?>
            <button type="button"
                    class="btn btn-link btn-sm p-0 text-decoration-none<?= $hasSelection ? '' : ' d-none' ?>"
                    data-filter-reset>Сбросить</button>
        </div>

        <div class="card-body">
            <?php foreach ($priceRanges as $item): ?>
                <?= $renderRange($rangeData($item)) ?>
            <?php endforeach; ?>

            <?php foreach ($groups as $item): ?>
                <?php
                $type = (string)($item['DISPLAY_TYPE'] ?? '');
                $isDropdown = $type === 'P' || $type === 'R';
                $isRadio = $type === 'K';
                $values = $item['VALUES'];
                $code = (string)($item['CODE'] ?? '');
                ?>
                <div class="smart-filter__group mb-4" data-group="<?= $h($code) ?>">
                    <div class="smart-filter__group-title fw-semibold mb-2"><?= $h($item['NAME']) ?></div>

                    <?php if ($isDropdown): ?>
                        <select class="form-select form-select-sm" data-single-select="<?= $h($code) ?>">
                            <option value="">Все</option>
                            <?php foreach ($values as $value): ?>
                                <option value="<?= $h($value['CONTROL_NAME']) ?>"<?= !empty($value['CHECKED']) ? ' selected' : '' ?>>
                                    <?= $h($value['VALUE']) ?><?= $countOf($value) !== '' ? ' (' . $countOf($value) . ')' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                    <?php elseif ($isRadio): ?>
                        <div class="d-flex flex-column gap-2">
                            <?php foreach ($values as $value): ?>
                                <div class="form-check mb-0">
                                    <input class="form-check-input"
                                           type="radio"
                                           id="sf-<?= $h($value['CONTROL_ID']) ?>"
                                           name="sf-<?= $h($code) ?>"
                                           value="<?= $h($value['CONTROL_NAME']) ?>"
                                           data-radio="<?= $h($code) ?>"
                                           <?= !empty($value['CHECKED']) ? 'checked' : '' ?>>
                                    <label class="form-check-label d-flex align-items-center gap-1"
                                           for="sf-<?= $h($value['CONTROL_ID']) ?>">
                                        <?php if (!empty($value['PICT'])): ?>
                                            <img class="smart-filter__pict rounded" src="<?= $h($value['PICT']) ?>" alt="" loading="lazy">
                                        <?php endif; ?>
                                        <span><?= $h($value['VALUE']) ?></span>
                                        <span class="smart-filter__count" data-count="<?= $h($value['CONTROL_NAME']) ?>"><?= $countOf($value) ?></span>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>

                    <?php else: ?>
                        <div class="d-flex flex-column gap-2">
                            <?php foreach ($values as $value): ?>
                                <div class="form-check mb-0">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           id="sf-<?= $h($value['CONTROL_ID']) ?>"
                                           name="<?= $h($value['CONTROL_NAME']) ?>"
                                           value="Y"
                                           data-value="<?= $h($value['CONTROL_NAME']) ?>"
                                           <?= !empty($value['CHECKED']) ? 'checked' : '' ?>
                                           <?= $isDisabled($value) ? 'disabled' : '' ?>>
                                    <label class="form-check-label d-flex align-items-center gap-1"
                                           for="sf-<?= $h($value['CONTROL_ID']) ?>">
                                        <?php if (!empty($value['PICT'])): ?>
                                            <img class="smart-filter__pict rounded" src="<?= $h($value['PICT']) ?>" alt="" loading="lazy">
                                        <?php endif; ?>
                                        <span><?= $h($value['VALUE']) ?></span>
                                        <span class="smart-filter__count" data-count="<?= $h($value['CONTROL_NAME']) ?>"><?= $countOf($value) ?></span>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($isDropdown || $isRadio): ?>
                        <?php foreach ($values as $value): ?>
                            <input type="hidden"
                                   name="<?= $h($value['CONTROL_NAME']) ?>"
                                   value="Y"
                                   data-value="<?= $h($value['CONTROL_NAME']) ?>"
                                   data-group="<?= $h($code) ?>"
                                   <?= empty($value['CHECKED']) ? 'disabled' : '' ?>>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <?php foreach ($valueRanges as $item): ?>
                <?= $renderRange($rangeData($item)) ?>
            <?php endforeach; ?>

            <button type="submit" class="btn btn-primary w-100 mt-2" data-filter-submit>
                Показать<span data-filter-total><?= $total > 0 ? ' (' . $total . ')' : '' ?></span>
            </button>
        </div>
    </form>
</div>
