<?php

namespace App\Catalog;

final class ProductCardData
{
    private const OFFER_LABEL_SKIP = [
        'ARTNUMBER',
        'CML2_LINK',
        'MORE_PHOTO',
    ];

    public static function fromSectionItem(array $item): array
    {
        $offers = [];

        foreach (($item['OFFERS'] ?? []) as $offer) {
            if (!is_array($offer)) {
                continue;
            }

            $offers[] = self::offer($offer, $item);
        }

        $price = self::formattedPrice($item);

        if ($offers) {
            $minValue = null;

            foreach (($item['OFFERS'] ?? []) as $offer) {
                $value = self::priceValue($offer);

                if ($value === null) {
                    continue;
                }

                if ($minValue === null || $value < $minValue) {
                    $minValue = $value;
                    $price = self::formattedPrice($offer);
                }
            }
        }

        return [
            'id' => (int) ($item['ID'] ?? 0),
            'name' => self::text($item['NAME'] ?? ''),
            'price' => $price,
            'image' => self::image($item),
            'available' => $offers
                ? (bool) array_filter($offers, static fn(array $offer): bool => $offer['available'])
                : self::available($item),
            'offers' => $offers,
        ];
    }

    private static function offer(array $offer, array $item): array
    {
        return [
            'id' => (int) ($offer['ID'] ?? 0),
            'label' => self::offerLabel($offer, $item),
            'price' => self::formattedPrice($offer),
            'image' => self::image($offer),
            'available' => self::available($offer),
        ];
    }

    private static function offerLabel(array $offer, array $item): string
    {
        $parts = [];
        $tree = is_array($offer['TREE'] ?? null) ? $offer['TREE'] : [];
        $skuProps = [];

        foreach (($item['SKU_PROPS'] ?? []) as $property) {
            if (is_array($property) && isset($property['ID'])) {
                $skuProps[(int) $property['ID']] = $property;
            }
        }

        foreach ($tree as $key => $valueId) {
            if (!preg_match('/^PROP_(\d+)$/', (string) $key, $match)) {
                continue;
            }

            $property = $skuProps[(int) $match[1]] ?? null;
            $value = $property['VALUES'][$valueId]['NAME'] ?? '';

            if ($value !== '') {
                $parts[] = self::text($value);
            }
        }

        if ($parts) {
            return implode(' / ', $parts);
        }

        foreach (($offer['DISPLAY_PROPERTIES'] ?? []) as $code => $property) {
            if (in_array($code, self::OFFER_LABEL_SKIP, true)) {
                continue;
            }

            $value = $property['DISPLAY_VALUE'] ?? $property['VALUE'] ?? '';

            if (is_array($value)) {
                $value = implode(', ', array_map([self::class, 'text'], $value));
            } else {
                $value = self::text($value);
            }

            if ($value !== '') {
                $parts[] = $value;
            }
        }

        if ($parts) {
            return implode(' / ', $parts);
        }

        $name = self::text($offer['NAME'] ?? '');

        return $name !== '' ? $name : 'Вариант ' . (int) ($offer['ID'] ?? 0);
    }

    private static function formattedPrice(array $entity): string
    {
        $price = $entity['ITEM_PRICES'][$entity['ITEM_PRICE_SELECTED'] ?? 0] ?? null;

        return is_array($price) ? (string) ($price['PRINT_PRICE'] ?? '') : '';
    }

    private static function priceValue(array $entity): ?float
    {
        $price = $entity['ITEM_PRICES'][$entity['ITEM_PRICE_SELECTED'] ?? 0] ?? null;

        if (!is_array($price)) {
            return null;
        }

        $value = $price['PRICE'] ?? $price['VALUE'] ?? null;

        return $value === null ? null : (float) $value;
    }

    private static function image(array $entity): string
    {
        $file = $entity['DETAIL_PICTURE'] ?? null;

        if (!is_array($file) || empty($file['SRC'])) {
            $file = $entity['PREVIEW_PICTURE'] ?? null;
        }

        return is_array($file) && !empty($file['SRC']) ? (string) $file['SRC'] : '';
    }

    private static function available(array $entity): bool
    {
        if (isset($entity['CAN_BUY'])) {
            return (bool) $entity['CAN_BUY'];
        }

        return (float) ($entity['CATALOG_QUANTITY'] ?? 0) > 0;
    }

    private static function text(mixed $value): string
    {
        if (is_array($value)) {
            return implode(', ', array_map([self::class, 'text'], $value));
        }

        return htmlspecialcharsback(trim(strip_tags((string) $value)));
    }
}