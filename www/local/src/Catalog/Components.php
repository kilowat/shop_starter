<?php

namespace App\Catalog;

/**
 * Компоненты каталога.
 *
 * Только имена: параметры подписываются целиком — ровно те, что переданы
 * при вызове компонента на странице, вести списки ключей не нужно.
 */
final class Components
{
    /** Список товаров. */
    public const LIST = 'bitrix:catalog.section';

    /** Умный фильтр. */
    public const FILTER = 'bitrix:catalog.smart.filter';
}
