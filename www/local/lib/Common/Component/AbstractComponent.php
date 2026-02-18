<?php
namespace Lib\Common\Component;

use Bitrix\Main\Application;

abstract class AbstractComponent
{
    protected string $name;
    protected array $params = [];

    /**
     * Render component
     */
    public function render(string $template = ''): void
    {
        global $APPLICATION;

        $APPLICATION->IncludeComponent(
            $this->name,
            $template,
            $this->params,
            false,
            ['HIDE_ICONS' => 'Y']
        );
    }

    /**
     * Return HTML as string
     */
    public function html(string $template = ''): string
    {
        global $APPLICATION;

        ob_start();

        $APPLICATION->IncludeComponent(
            $this->name,
            $template,
            $this->params,
            false,
            ['HIDE_ICONS' => 'Y']
        );

        return ob_get_clean();
    }

    /**
     * Automatically override parameters from request
     * находит все методы fluent setters без аргументов и вызывает их если есть ключ в request
     */
    public function fromRequest(): static
    {
        $request = Application::getInstance()->getContext()->getRequest();

        // получаем список методов класса
        $methods = get_class_methods($this);

        foreach ($methods as $method) {
            // фильтруем только fluent setters по соглашению: методы с 1 аргументом
            $reflection = new \ReflectionMethod($this, $method);
            if ($reflection->getNumberOfParameters() !== 1) {
                continue;
            }

            // ключ в request соответствует имени метода
            $key = $method;
            if ($request->get($key) !== null) {
                $this->{$method}($request->get($key));
            }
        }

        return $this;
    }
}
