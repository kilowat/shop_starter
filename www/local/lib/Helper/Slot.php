<?php

namespace Helper;

class Slot
{
    protected string $target;

    public function __construct(string $target)
    {
        $this->target = $target;
        ob_start();
    }

    public static function start(string $target): self
    {
        return new self($target);
    }

    public function end(): void
    {
        global $APPLICATION;
        $content = ob_get_clean();
        $APPLICATION->AddViewContent($this->target, $content);
    }
}
