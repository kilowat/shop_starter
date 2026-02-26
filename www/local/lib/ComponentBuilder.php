<?php
namespace Lib;

use Bitrix\Main\Application;
use Bitrix\Main\Text\StringHelper;
use Bitrix\Main\Engine\Response\Component;
use Bitrix\Main\Engine\Response\ContentArea\Component as ContentCompnent;

abstract class ComponentBuilder
{
    protected string $name;
    protected array $params = [];
    protected array $defaultParams = [];
    protected string $template = '.default';

    /**
     * @var array Список параметров/методов, которые можно переопределять через Request
     * Могут быть названия функций
     */
    protected array $allowRequestParams = [];

    public function getMergedParams(): array
    {
        return array_merge($this->defaultParams, $this->params);
    }

    public function fromRequest(): static
    {
        $request = Application::getInstance()->getContext()->getRequest();

        foreach ($this->allowRequestParams as $allowed) {
            $method = $allowed;
            $key = $allowed;

            // если разрешено как функция
            if (method_exists($this, $method)) {
                $value = $request->get($key) ?? $request->get(StringHelper::camel2snake($key));
                if ($value !== null) {
                    $this->{$method}($value);
                }
            }
        }

        return $this;
    }

    public function setTempate($template = '')
    {
        $this->template = $template;
    }
    public function render($returnResult = false): mixed
    {
        global $APPLICATION;

        return $APPLICATION->IncludeComponent(
            $this->name,
            $this->template,
            $this->getMergedParams(),
            false,
            ['HIDE_ICONS' => 'Y'],
            $returnResult
        );
    }

    public function getDataKeys(array $dataKeys = [])
    {
        $component = new ContentCompnent(
            $this->name,
            $this->template,
            $this->getMergedParams(),
            $dataKeys
        );

        $component->getHtml();
        return $component->getSectionData();
    }

    public function getDataKeysResponse(array $dataKeys = [])
    {
        $data = $this->getDataKeys($dataKeys);
        $json = json_encode([
            'status' => 'success',
            'data' => $data
        ], JSON_UNESCAPED_UNICODE);

        $currentResponse = Application::getInstance()->getContext()->getResponse();
        $currentResponse->addHeader('Content-Type', 'application/json');
        $currentResponse->setContent($json);

        return $currentResponse;
    }

    public function getResponse(array $dataKeys = []): Component
    {
        return new Component(
            $this->name,
            $this->template,
            $this->getMergedParams(),
            [],
            $dataKeys
        );
    }
}
