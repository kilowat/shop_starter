<?php
namespace Components;

use Lib\Common\Component\AbstractComponent;

class CatalogSectionList extends AbstractComponent
{
    protected string $name = 'bitrix:catalog.section.list';

    private function __construct()
    {
        $this->params = [
            'IBLOCK_TYPE' => 'catalog',
            'IBLOCK_ID' => 2,
            'TOP_DEPTH' => 2,
            'COUNT_ELEMENTS' => 'Y',
            'CACHE_TYPE' => 'A',
            'CACHE_TIME' => 3600,
        ];
    }

    /**
     * Presets
     */
    public static function sidebar(): static
    {
        $self = new static();
        $self->params['TOP_DEPTH'] = 1;
        return $self;
    }

    public static function full(): static
    {
        $self = new static();
        $self->params['TOP_DEPTH'] = 3;
        return $self;
    }

    public static function footer(): static
    {
        $self = new static();
        $self->params['COUNT_ELEMENTS'] = 'N';
        return $self;
    }

    /**
     * Fluent setters (IDE автокомплит)
     */
    public function iblock(int $id): static
    {
        $this->params['IBLOCK_ID'] = $id;
        return $this;
    }

    public function depth(int $depth): static
    {
        $this->params['TOP_DEPTH'] = max(1, min(5, $depth));
        return $this;
    }

    public function countElements(bool $value = true): static
    {
        $this->params['COUNT_ELEMENTS'] = $value ? 'Y' : 'N';
        return $this;
    }

    public function cache(int $seconds): static
    {
        $this->params['CACHE_TIME'] = $seconds;
        return $this;
    }
}