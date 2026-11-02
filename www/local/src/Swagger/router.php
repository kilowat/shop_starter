<?php

use Bitrix\Main\Application;
use Bitrix\Main\Config\Configuration;
use Bitrix\Main\HttpResponse;
use Bitrix\Main\Routing\Controllers\PublicPageController;
use Bitrix\Main\Routing\RoutingConfigurator;
use OpenApi\Builder;
use Psr\Log\AbstractLogger;
use Symfony\Component\Finder\Finder;

/*
 * ============================ НАСТРОЙКИ ============================
 * Всё, что обычно приходится менять, собрано здесь.
 */

$config = [
    // URL страницы со Swagger UI
    'ui_url' => '/swagger',

    // Файл со Swagger UI (путь от корня сайта).
    // Адрес спецификации внутри него должен совпадать с json_url.
    'ui_page' => '/local/src/Swagger/ui/index.php',

    // URL, по которому отдаётся OpenAPI JSON
    'json_url' => '/swagger/openapi.json',

    // Что сканировать (пути от корня сайта)
    'source_dirs' => [
        '/local/controllers',
        '/local/src/Swagger',
    ],

    // Маска файлов для сканирования
    'file_pattern' => '*.php',

    // Что исключить из сканирования
    'exclude_paths' => [],               // по части пути
    'exclude_names' => [],               // по имени файла (можно добавить 'const.php', 'func.php' и т.п.)

    // Версия OpenAPI ('3.0.0', '3.1.0', '3.2.0') или null — решает swagger-php
    'openapi_version' => null,

    // Уровни сообщений swagger-php, которые не попадают в ответ
    'ignored_log_levels' => ['debug', 'info', 'notice'],

    // Заголовок Cache-Control для JSON
    'cache_control' => 'no-store',
];
/* =================================================================== */

return static function (RoutingConfigurator $routes) use ($config) {

    // Swagger UI
    $routes->get($config['ui_url'], new PublicPageController($config['ui_page']));

    // OpenAPI JSON
    $routes->get($config['json_url'], static function () use ($config) {
        $root = Application::getDocumentRoot();

        // Подробности ошибок показываем только если в Bitrix включён debug
        // (/bitrix/.settings.php → exception_handling → debug)
        $debug = (bool) (Configuration::getValue('exception_handling')['debug'] ?? false);

        $logger = new class ($config['ignored_log_levels']) extends AbstractLogger {
            public array $messages = [];

            public function __construct(private array $ignoredLevels)
            {}

            public function log($level, string|\Stringable $message, array $context = []): void
            {
                if (in_array($level, $this->ignoredLevels, true)) {
                    return;
                }

                $replace = [];
                foreach ($context as $key => $value) {
                    if (is_scalar($value) || $value instanceof \Stringable) {
                        $replace['{' . $key . '}'] = (string) $value;
                    }
                }

                $this->messages[] = [
                'level' => (string) $level,
                'message' => strtr((string) $message, $replace),
                ];
            }
        };

        $finder = Finder::create()
            ->files()
            ->name($config['file_pattern'])
            ->in(array_map(static fn(string $dir) => $root . $dir, $config['source_dirs']));

        foreach ($config['exclude_paths'] as $path) {
            $finder->notPath($path);
        }
        if ($config['exclude_names']) {
            $finder->notName($config['exclude_names']);
        }

        $json = null;

        try {
            $builder = (new Builder())
                ->setLogger($logger)
                ->addSource($finder);

            if ($config['openapi_version']) {
                $builder->setVersion($config['openapi_version']);
            }

            $result = $builder->build();

            if ($result->isValid()) {
                $json = $result->toJson();
            }
        } catch (\Throwable $e) {
            $logger->log('error', sprintf('%s: %s', get_class($e), $e->getMessage()));
        }

        $response = new HttpResponse();
        $response->addHeader('Content-Type', 'application/json; charset=UTF-8');
        $response->addHeader('Cache-Control', $config['cache_control']);

        if ($json === null) {
            $payload = ['error' => 'OpenAPI generation failed'];

            if ($debug) {
                $payload['messages'] = $logger->messages;
            }

            $response->setStatus('500 Internal Server Error');
            $response->setContent(json_encode(
                $payload,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
            ));

            return $response;
        }

        $response->setStatus('200 OK');
        $response->setContent($json);

        return $response;
    });
};
