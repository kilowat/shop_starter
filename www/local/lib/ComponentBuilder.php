<?php

namespace Lib;

use Bitrix\Main\Application;
use Bitrix\Main\Text\StringHelper;
use Bitrix\Main\Engine\Response\AjaxJson;
use Bitrix\Main\Engine\Response\ContentArea\Component as ContentComponent;

abstract class ComponentBuilder
{
    protected $name;
    protected $params = [];
    protected $defaultParams = [];
    protected $template = '.default';

    /** @var array Разрешённые методы для переопределения через Request */
    protected $allowRequestParams = [];

    /** @var array Ключи данных, которые нужно вернуть из setResultCacheKeys*/
    protected $dataKeys = [];

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

    public function setTemplate($template): static
    {
        $this->template = $template;
        return $this;
    }

    public function setDataKeys(array $keys): static
    {
        $this->dataKeys = $keys;
        return $this;
    }

    public function getData(): array
    {
        $component = new ContentComponent(
            $this->name,
            $this->template,
            $this->getMergedParams(),
            $this->dataKeys,
        );

        $component->getHtml();

        return $component->getSectionData();

    }

    public function sendResponse($showHtml = true)
    {
        $component = new ContentComponent(
            $this->name,
            $this->template,
            $this->getMergedParams(),
            $this->dataKeys,
        );

        $htmlResult = $component->getHtml();

        $response = AjaxJson::createSuccess([
            'html' => $showHtml ? $htmlResult : null,
            'result' => $component->getSectionData(),
        ]);

        $response->send();
        \Bitrix\Main\Application::getInstance()->end();
    }

    public function setParamsFromRequest(): static
    {
        $request = Application::getInstance()->getContext()->getRequest();

        foreach ($this->allowRequestParams as $allowed) {
            $method = $allowed;
            $key = $allowed;

            if (method_exists($this, $method)) {
                $value = $request->get($key) ?? $request->get(StringHelper::camel2snake($key));
                if ($value !== null) {
                    $this->{$method}($value);
                }
            }
        }

        return $this;
    }

    private function getMergedParams(): array
    {
        return array_merge($this->defaultParams, $this->params);
    }

}
