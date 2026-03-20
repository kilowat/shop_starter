<?php

namespace Lib;

use Bitrix\Main\Application;
use Bitrix\Main\Text\StringHelper;
use Bitrix\Main\Engine\Response\ContentArea\Component as ContentCompnent;
use Bitrix\Main\Engine\Response\Converter;

abstract class ComponentBuilder
{
    protected $name;
    protected $params = [];
    protected $defaultParams = [];
    protected $template = '.default';

    /** @var array Разрешённые параметры/методы для переопределения через Request */
    protected $allowRequestParams = [];

    /** @var int|null Формат конвертора для data */
    protected $converterFormat = Converter::KEYS
        | Converter::RECURSIVE
        | Converter::TO_LOWER;

    /** @var array Ключи данных, которые нужно вернуть */
    protected $dataKeys = [];


    public function getMergedParams(): array
    {
        return array_merge($this->defaultParams, $this->params);
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

    public function setTemplate($template): static
    {
        $this->template = $template;
        return $this;
    }

    public function setDataConverterFormat(?int $format): static
    {
        $this->converterFormat = $format;
        return $this;
    }

    public function setDataKeys(array $keys): static
    {
        $this->dataKeys = $keys;
        return $this;
    }

    protected function convertData(array $data): array
    {
        if ($this->converterFormat === null) {
            return $data;
        }

        $converter = new Converter($this->converterFormat);
        return $converter->process($data);
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

    public function getData(): array
    {
        $component = new ContentCompnent(
            $this->name,
            $this->template,
            $this->getMergedParams(),
            $this->dataKeys,
        );

        $component->getHtml();

        $data = $component->getSectionData();

        return $this->convertData($data);
    }

    public function sendHtmlResponse()
    {
        $this->getHtmlResponse()->send();
    }

    public function sendDataResponse()
    {
        $this->getDataResponse()->send();
    }

    private function getDataResponse()
    {
        $data = $this->getData();

        $json = json_encode([
            'status' => 'success',
            'data' => $data
        ], JSON_UNESCAPED_UNICODE);

        $currentResponse = Application::getInstance()->getContext()->getResponse();
        $currentResponse->addHeader('Content-Type', 'application/json');
        $currentResponse->setContent($json);

        return $currentResponse;
    }

    private function getHtmlResponse()
    {
        $component = new ContentCompnent(
            $this->name,
            $this->template,
            $this->getMergedParams(),
            $this->dataKeys,
        );

        $html = $component->getHtml();
        $json = json_encode([
            'status' => 'success',
            'data' => $html
        ], JSON_UNESCAPED_UNICODE);

        $currentResponse = Application::getInstance()->getContext()->getResponse();
        $currentResponse->addHeader('Content-Type', 'application/json');
        $currentResponse->setContent($json);

        return $currentResponse;
    }
}
