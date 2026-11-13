<?php
/**
 * Хлебные крошки на Bootstrap 5.
 *
 * Компонент bitrix:breadcrumb вызывает этот файл через CMain::GetNavChain()
 * с $bIncludeOnce = true, поэтому шаблон обязан ВЕРНУТЬ строку, а не вывести её.
 * $arResult — готовая цепочка «раздел → подраздел → страница».
 *
 * @var array $arResult
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

if (empty($arResult)) {
    return '';
}

$items = array_values($arResult);
$lastIndex = count($items) - 1;

ob_start();
?>
<nav aria-label="Хлебные крошки" class="mb-3">
    <ol class="breadcrumb mb-0" itemscope itemtype="https://schema.org/BreadcrumbList">
        <?php foreach ($items as $index => $item):
            $title = htmlspecialcharsex($item['TITLE'] ?? '');
            $link = (string) ($item['LINK'] ?? '');
            $isLast = $index === $lastIndex;
            ?>
            <li class="breadcrumb-item<?= $isLast ? ' active' : '' ?>"
                itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem"
                <?php if ($isLast): ?>aria-current="page"<?php endif; ?>>
                <?php if (!$isLast && $link !== ''): ?>
                    <a href="<?= $link ?>" itemprop="item"><span itemprop="name"><?= $title ?></span></a>
                <?php else: ?>
                    <span itemprop="name"><?= $title ?></span>
                <?php endif; ?>
                <meta itemprop="position" content="<?= $index + 1 ?>">
            </li>
        <?php endforeach; ?>
    </ol>
</nav>
<?php
return ob_get_clean();
