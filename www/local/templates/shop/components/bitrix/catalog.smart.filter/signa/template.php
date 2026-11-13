<?php
/**
 * Умный фильтр каталога.
 *
 * Цены и числовые свойства — диапазоны (два поля min/max),
 * все остальные значения — чекбоксы. Кнопка «Применить» отправляет
 * обычную GET-форму на текущую страницу.
 *
 * @var array $arResult
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$h = static fn($value): string => htmlspecialcharsbx((string)$value);

$count = static fn(array $value): string => (string)(int)($value['ELEMENT_COUNT'] ?? 0);

// Для сброса убираем ещё и номер страницы: показываем первую.
$resetUrl = (string)preg_replace('~[?&]PAGEN_\d+=[^&]*~', '', (string)$arResult['FORM_ACTION']);
?>
<smart-filter class="d-block">
    <div class="smart-filter card border-0 shadow-sm">
        <form method="get" action="<?= $h($arResult['FORM_ACTION']) ?>" class="smart-filter__form">
            <input type="hidden" name="set_filter" value="y">

            <?php foreach ($arResult['HIDDEN'] as $field): ?>
                <?php // номер страницы не переносим: после фильтра список начинается с первой ?>
                <?php if (empty($field['CONTROL_NAME']) || str_starts_with($field['CONTROL_NAME'], 'PAGEN_')): ?>
                    <?php continue; ?>
                <?php endif; ?>
                <input type="hidden"
                       name="<?= $h($field['CONTROL_NAME']) ?>"
                       value="<?= $h($field['HTML_VALUE'] ?? 'Y') ?>">
            <?php endforeach; ?>

            <div class="card-body">
                <?php foreach ($arResult['ITEMS'] as $item): ?>
                    <?php $values = $item['VALUES'] ?? []; ?>

                    <?php if (isset($values['MIN'], $values['MAX'])): ?>
                        <?php
                        $min = $values['MIN'];
                        $max = $values['MAX'];
                        $floor = (float)($min['VALUE'] ?? 0);
                        $ceil = (float)($max['VALUE'] ?? 0);
                        $htmlMin = (string)($min['HTML_VALUE'] ?? '');
                        $htmlMax = (string)($max['HTML_VALUE'] ?? '');
                        ?>
                        <div class="smart-filter__group mb-4">
                            <div class="smart-filter__group-title fw-semibold mb-2"><?= $h($item['NAME']) ?></div>

                            <div class="px-2 pt-1">
                                <div class="smart-filter__slider"
                                     data-slider
                                     data-min="<?= $h($floor) ?>"
                                     data-max="<?= $h($ceil) ?>"
                                     data-lo="<?= $h($htmlMin !== '' ? $htmlMin : $floor) ?>"
                                     data-hi="<?= $h($htmlMax !== '' ? $htmlMax : $ceil) ?>"
                                     data-decimals="<?= $h($item['DECIMALS'] ?? 0) ?>"></div>
                            </div>

                            <div class="d-flex align-items-center gap-2 mt-2">
                                <input type="number"
                                       class="form-control form-control-sm"
                                       name="<?= $h($min['CONTROL_NAME']) ?>"
                                       value="<?= $h($htmlMin) ?>"
                                       placeholder="<?= $h($floor) ?>"
                                       aria-label="<?= $h($item['NAME']) ?>: от"
                                       data-side="min">
                                <span class="text-muted">—</span>
                                <input type="number"
                                       class="form-control form-control-sm"
                                       name="<?= $h($max['CONTROL_NAME']) ?>"
                                       value="<?= $h($htmlMax) ?>"
                                       placeholder="<?= $h($ceil) ?>"
                                       aria-label="<?= $h($item['NAME']) ?>: до"
                                       data-side="max">
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="smart-filter__group mb-4">
                            <div class="smart-filter__group-title fw-semibold mb-2"><?= $h($item['NAME']) ?></div>

                                <?php foreach ($values as $value): ?>
                                    <div class="form-check mb-1 smart-filter__item"
                                        data-control="<?= $h($value['CONTROL_NAME']) ?>">
                                        <input class="form-check-input"
                                            type="checkbox"
                                            id="sf-<?= $h($value['CONTROL_ID']) ?>"
                                            name="<?= $h($value['CONTROL_NAME']) ?>"
                                            value="Y"
                                            <?= !empty($value['CHECKED']) ? 'checked' : '' ?>
                                            <?= !empty($value['DISABLED']) && empty($value['CHECKED']) ? 'disabled' : '' ?>>

                                        <label class="form-check-label d-flex align-items-center gap-1"
                                            for="sf-<?= $h($value['CONTROL_ID']) ?>">
                                            <?php if (!empty($value['PICT'])): ?>
                                                <img class="smart-filter__pict rounded"
                                                    src="<?= $h($value['PICT']) ?>"
                                                    alt=""
                                                    loading="lazy">
                                            <?php endif; ?>
                                            <span><?= $h($value['VALUE']) ?></span>
                                            <span class="smart-filter__count"
                                                data-count="<?= $count($value) ?>"></span>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <div class="card-footer border-0 bg-white pt-0 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">Применить</button>

                <?php // Сброс — обычный переход на страницу без параметров фильтра ?>
                <a class="btn btn-outline-secondary" href="<?= $h($resetUrl) ?>">Сбросить</a>
            </div>
        </form>
    </div>
</smart-filter>
