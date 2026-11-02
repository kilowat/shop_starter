<?php

namespace App\Helper;

class Debug
{
    private const TOKEN_NAME = 'BITRIX_ADM_FDB';
    private const SALT = 'BX_DEBUG';

    /** Стили и скрипт выводим один раз за запрос */
    private static bool $assetsPrinted = false;

    public static function dump(mixed $data, bool $die = false): void
    {
        global $USER;

        // 1. Basic security check
        if (!is_object($USER) || !$USER->IsAdmin()) {
            return;
        }

        $hash = md5(($_SERVER['REMOTE_ADDR'] ?? '') . self::SALT);

        // 2. Activation / Deactivation via URL
        if (isset($_GET['debug_on'])) {
            setcookie(self::TOKEN_NAME, $hash, time() + 31536000, '/');
            $_COOKIE[self::TOKEN_NAME] = $hash;
        }
        if (isset($_GET['debug_off'])) {
            setcookie(self::TOKEN_NAME, '', time() - 3600, '/');
            return;
        }

        // 3. Access check (Token must match current IP)
        if (($_COOKIE[self::TOKEN_NAME] ?? '') !== $hash) {
            return;
        }

        // 4. Where dump was called
        [$file, $line] = self::detectCaller();

        // 5. AJAX handling (clean JSON output)
        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH'])
            || isset($_REQUEST['ajax'])
            || str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json');

        if ($isAjax) {
            while (ob_get_level()) {
                ob_end_clean();
            }
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(
                [
                    'DEBUG' => [
                        'LOCATION' => $file . ' : ' . $line,
                        'DATA'     => $data,
                    ],
                ],
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
                    | JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE
            );
            die();
        }

        // 6. Standard HTML output
        if ($die) {
            // чистим буфер: ранее выведенные стили тоже пропадают
            while (ob_get_level()) {
                ob_end_clean();
            }
            self::$assetsPrinted = false;
        }

        [$typeLabel, $body] = self::render($data);

        $docRoot  = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');
        $showFile = ($docRoot !== '' && str_starts_with($file, $docRoot))
            ? substr($file, strlen($docRoot))
            : $file;

        $ms  = (int)round((microtime(true) - ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true))) * 1000);
        $mem = round(memory_get_peak_usage(true) / 1048576, 1);

        echo self::assets();
        echo sprintf(
            '<div class="bxdbg%s">'
                . '<details class="bxdbg__box" open>'
                . '<summary class="bxdbg__head">'
                . '<span class="bxdbg__badge">%s</span>'
                . '<span class="bxdbg__type">%s</span>'
                . '<span class="bxdbg__loc" title="%s">%s<b>:%s</b></span>'
                . '</summary>'
                . '<div class="bxdbg__bar">'
                . '<span class="bxdbg__meta">%d ms &middot; %s MB</span>'
                . '<button type="button" class="bxdbg__btn" data-bxdbg-copy>Копировать</button>'
                . '</div>'
                . '<pre class="bxdbg__pre">%s</pre>'
                . '</details>'
                . '</div>',
            $die ? ' bxdbg--die' : '',
            $die ? 'TERMINATED' : 'DEBUG',
            htmlspecialchars($typeLabel),
            htmlspecialchars($file),
            htmlspecialchars($showFile),
            (int)$line,
            $ms,
            $mem,
            $body
        );

        if ($die) {
            die();
        }
    }

    /**
     * Определяет файл и строку реального вызова dump().
     * Если dump вызван через обёртку dd(), берём место вызова dd().
     *
     * @return array{0: string, 1: int}
     */
    private static function detectCaller(): array
    {
        $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        $caller = $bt[0] ?? [];
        for ($i = 1, $n = count($bt); $i < $n; $i++) {
            if (!in_array($bt[$i]['function'] ?? '', ['dump', 'dd'], true)) {
                break;
            }
            $caller = $bt[$i];
        }

        return [$caller['file'] ?? 'unknown', (int)($caller['line'] ?? 0)];
    }

    /**
     * Возвращает [подпись типа, готовый HTML значения].
     *
     * @return array{0: string, 1: string}
     */
    private static function render(mixed $data): array
    {
        if (is_array($data)) {
            $label = 'array(' . count($data) . ')';
            $html  = htmlspecialchars(print_r($data, true), ENT_SUBSTITUTE);
        } elseif (is_object($data)) {
            $label = get_class($data);
            $html  = htmlspecialchars(print_r($data, true), ENT_SUBSTITUTE);
        } elseif (is_bool($data)) {
            $label = 'bool';
            $html  = '<span class="bxdbg__bool">' . ($data ? 'true' : 'false') . '</span>';
        } elseif ($data === null) {
            $label = 'null';
            $html  = '<span class="bxdbg__bool">null</span>';
        } elseif (is_string($data)) {
            $label = 'string(' . strlen($data) . ')';
            $html  = '<span class="bxdbg__str">' . htmlspecialchars($data, ENT_SUBSTITUTE) . '</span>';
        } elseif (is_int($data) || is_float($data)) {
            $label = gettype($data);
            $html  = '<span class="bxdbg__num">' . htmlspecialchars(var_export($data, true)) . '</span>';
        } else {
            $label = gettype($data);
            $html  = htmlspecialchars(var_export($data, true), ENT_SUBSTITUTE);
        }

        // подсветка ключей и служебных слов print_r: [key] => ...
        if (is_array($data) || is_object($data)) {
            $html = preg_replace('/\[([^\]\r\n]+)\] =&gt;/', '[<span class="bxdbg__key">$1</span>] =&gt;', $html);
            $html = preg_replace('/^(\s*)(Array|[\w\\\\]+ Object)(\s*)$/m', '$1<span class="bxdbg__cls">$2</span>$3', $html);
        }

        return [$label, $html];
    }

    /** Стили и скрипт копирования, один раз за запрос. */
    private static function assets(): string
    {
        if (self::$assetsPrinted) {
            return '';
        }
        self::$assetsPrinted = true;

        return <<<'HTML'
<style>
.bxdbg{all:initial;display:block;box-sizing:border-box;width:100%;padding:10px;position:relative;z-index:999999;clear:both;font:13px/1.5 -apple-system,"Segoe UI",Roboto,sans-serif;color:#d4d8de;text-align:left;direction:ltr}
.bxdbg *{box-sizing:border-box}
.bxdbg__box{background:#14161a;border:1px solid #2a2e35;border-left:4px solid #f5a623;border-radius:8px;box-shadow:0 8px 30px rgba(0,0,0,.35);overflow:hidden}
.bxdbg__head{display:flex;flex-wrap:wrap;align-items:center;gap:10px;padding:10px 14px;cursor:pointer;background:#1b1e24;list-style:none;position:sticky;top:0;z-index:2}
.bxdbg__head::-webkit-details-marker{display:none}
.bxdbg__head::before{content:"\25BE";color:#7d8590;font-size:12px}
.bxdbg__box:not([open]) .bxdbg__head::before{content:"\25B8"}
.bxdbg__badge{padding:2px 8px;border-radius:4px;background:#f5a623;color:#14161a;font-size:11px;font-weight:700;letter-spacing:.04em}
.bxdbg--die .bxdbg__badge{background:#ff5c5c;color:#fff}
.bxdbg__type{padding:2px 8px;border-radius:4px;background:#252a33;color:#7cb7ff;font:12px "SFMono-Regular",Consolas,"Courier New",monospace}
.bxdbg__loc{margin-left:auto;color:#9aa4b2;font:12px "SFMono-Regular",Consolas,"Courier New",monospace;word-break:break-all}
.bxdbg__loc b{color:#f5a623;font-weight:600}
.bxdbg__bar{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:6px 14px;border-top:1px solid #2a2e35;border-bottom:1px solid #2a2e35;background:#181b20}
.bxdbg__meta{color:#7d8590;font-size:12px}
.bxdbg__btn{all:unset;cursor:pointer;padding:3px 10px;border:1px solid #3a404a;border-radius:4px;color:#d4d8de;font-size:12px}
.bxdbg__btn:hover{background:#252a33}
.bxdbg__pre{display:block;margin:0;padding:14px;max-height:600px;overflow:auto;background:transparent;border:0;color:#d4d8de;font:13px/1.55 "SFMono-Regular",Consolas,"Courier New",monospace;white-space:pre-wrap;word-break:break-word;tab-size:4}
.bxdbg__key{color:#7cb7ff}
.bxdbg__cls{color:#c792ea}
.bxdbg__str{color:#9fe870}
.bxdbg__num{color:#f78c6c}
.bxdbg__bool{color:#ffcb6b;font-weight:600}
.bxdbg--die{position:fixed;inset:0;width:100vw;height:100vh;overflow:auto;padding:20px;background:rgba(0,0,0,.92);z-index:2147483647}
.bxdbg--die .bxdbg__box{max-width:1400px;margin:0 auto;border-left-color:#ff5c5c}
.bxdbg--die .bxdbg__pre{max-height:none}
</style>
<script>
document.addEventListener('click',function(e){
  var b=e.target.closest('[data-bxdbg-copy]');
  if(!b||!navigator.clipboard)return;
  var pre=b.closest('.bxdbg').querySelector('.bxdbg__pre');
  navigator.clipboard.writeText(pre.innerText).then(function(){
    var t=b.textContent;b.textContent='Скопировано';
    setTimeout(function(){b.textContent=t},1200);
  });
});
</script>
HTML;
    }
}
