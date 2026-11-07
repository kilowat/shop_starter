<?php
/**
 * Универсальный AJAX-обработчик.
 *
 * Создаётся в _ajax.php страницы: (new CatalogAjax([...]))->handle().
 */

namespace App\Helper;

use Bitrix\Main\Web\Json;
use RuntimeException;
use Throwable;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

final class CatalogAjax
{
    public const RESPONSE_HTML = 'html';
    public const RESPONSE_RESULT = 'result';

    /**
     * @param array<string, CatalogAjaxAction> $actions
     */
    public function __construct(
        private readonly array $actions,
        private readonly string $actionField = 'ajax_action',
        private readonly string $sessidField = 'sessid',
        private readonly string $method = 'POST',
    ) {
    }

    public function handle(): void
    {
        try {
            $this->validateConfig();
            $this->validateMethod();
            $this->validateSessid();

            $actionName = $this->actionName();
            $action = $this->actions[$actionName] ?? null;

            if (!$action instanceof CatalogAjaxAction) {
                $this->respond([
                    'status' => 'error',
                    'errors' => [['code' => 'invalid_action', 'message' => 'Unknown action']],
                ], 400);
            }

            if ($action->init !== null) {
                ($action->init)();
            }

            $this->execute($action);
        } catch (Throwable $e) {
            AddMessage2Log(
                $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine(),
                'ajax'
            );

            $this->respond([
                'status' => 'error',
                'errors' => [['code' => 'render_failed', 'message' => 'Unable to render response']],
            ], 500);
        }
    }

    private function validateConfig(): void
    {
        if ($this->actions === []) {
            $this->respond([
                'status' => 'error',
                'errors' => [['code' => 'invalid_config', 'message' => 'AJAX actions are not configured']],
            ], 500);
        }

        foreach ($this->actions as $action) {
            if (!$action instanceof CatalogAjaxAction) {
                $this->respond([
                    'status' => 'error',
                    'errors' => [['code' => 'invalid_config', 'message' => 'Invalid AJAX action config']],
                ], 500);
            }
        }
    }

    private function validateMethod(): void
    {
        $method = strtoupper(trim($this->method));

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== $method) {
            $this->respond([
                'status' => 'error',
                'errors' => [['code' => 'invalid_method', 'message' => 'Method not allowed']],
            ], 405);
        }
    }

    private function validateSessid(): void
    {
        $field = trim($this->sessidField);
        if ($field === '') {
            $field = 'sessid';
        }

        if (!check_bitrix_sessid($field)) {
            $this->respond([
                'status' => 'error',
                'errors' => [['code' => 'invalid_sessid', 'message' => 'Session expired']],
            ], 403);
        }
    }

    private function actionName(): string
    {
        $field = trim($this->actionField);
        if ($field === '') {
            $field = 'action';
        }

        $value = $_POST[$field] ?? '';

        return is_string($value) ? $value : '';
    }

    private function execute(CatalogAjaxAction $action): never
    {
        global $APPLICATION;

        $hideIcons = 'Y';
        $returnResult = false;

        foreach ($action->prepare as $path) {
            if (!is_string($path) || trim($path) === '') {
                throw new RuntimeException('Invalid AJAX prepare path');
            }

            $file = $this->includeFile($path);

            ob_start();
            require $file;
            ob_end_clean();
        }

        $renderFile = $this->includeFile($action->render);
        $returnResult = $action->returnResult;

        ob_start();
        $result = require $renderFile;
        $html = ob_get_clean();
        $html = is_string($html) ? $html : '';

        if ($action->response === self::RESPONSE_HTML) {
            $this->respond([
                'status' => 'success',
                'data' => ['html' => $html],
            ]);
        }

        if ($action->response === self::RESPONSE_RESULT) {
            $data = [];

            if (is_array($result)) {
                if ($action->resultKeys === []) {
                    $data = $result;
                } else {
                    foreach ($action->resultKeys as $key) {
                        if (is_string($key) && array_key_exists($key, $result)) {
                            $data[$key] = $result[$key];
                        }
                    }
                }
            }

            $this->respond([
                'status' => 'success',
                'data' => ['result' => $data],
            ]);
        }

        throw new RuntimeException('Unknown AJAX response type: ' . $action->response);
    }

    private function includeFile(string $path): string
    {
        $path = trim($path);

        if ($path === '') {
            throw new RuntimeException('Empty AJAX include path');
        }

        $file = str_starts_with($path, '/')
            ? rtrim((string)$_SERVER['DOCUMENT_ROOT'], '/') . $path
            : $path;

        if (!is_file($file)) {
            throw new RuntimeException('AJAX include not found: ' . $path);
        }

        return $file;
    }

    private function respond(array $payload, int $status = 200): never
    {
        global $APPLICATION;

        $APPLICATION->RestartBuffer();

        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('X-Content-Type-Options: nosniff');

        echo Json::encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
        );

        die();
    }
}
