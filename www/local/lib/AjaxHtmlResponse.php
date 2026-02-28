<?php
namespace Lib;

use Bitrix\Main\Engine\Response\AjaxJson;
use Bitrix\Main\Engine\Response\Converter;

final class AjaxHtmlResponse extends AjaxJson
{
    private string $viewPath;
    private array $viewParams;
    private array $additionalParams;
    private bool $withSiteTemplate;

    public function __construct(
        string $viewPath,
        array $viewParams = [],
        array $additionalParams = [],
        bool $withSiteTemplate = false
    ) {
        $this->viewPath = $viewPath;
        $this->viewParams = $viewParams;
        $this->additionalParams = $additionalParams;
        $this->withSiteTemplate = $withSiteTemplate;

        parent::__construct($this->prepareData(), self::STATUS_SUCCESS, null);
    }

    public static function sendResponse(
        string $viewPath,
        array $viewParams = [],
        array $additionalParams = [],
        bool $withSiteTemplate = false
    ): never {
        $response = new self(
            $viewPath,
            $viewParams,
            $additionalParams,
            $withSiteTemplate
        );

        $response->send();
        exit;
    }

    private function prepareData(): array
    {
        $html = $this->renderView();
        $converter = Converter::toJson();
        $params = $converter->process($this->additionalParams);

        return [
            'html' => $html,
            'params' => $params,
        ];
    }

    private function renderView(): string
    {
        ob_start();

        try {
            global $APPLICATION;

            if ($this->withSiteTemplate) {
                $APPLICATION->IncludeFile(
                    $this->viewPath,
                    $this->viewParams,
                    ['SHOW_BORDER' => false, 'MODE' => 'html']
                );
            } else {
                extract($this->viewParams);
                require $_SERVER['DOCUMENT_ROOT'] . $this->viewPath;
            }

            return ob_get_clean() ?: '';
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
    }
}