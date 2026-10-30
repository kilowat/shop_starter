<?php

namespace Helper;

class Debug
{
    public static function dump(mixed $data, bool $die = false)
    {
        global $USER;

        // 1. Basic security check
        if (!is_object($USER) || !$USER->IsAdmin()) {
            return;
        }

        $myIp = $_SERVER['REMOTE_ADDR'];
        $tokenName = 'BITRIX_ADM_FDB';
        $salt = 'BX_DEBUG';
        $hash = md5($myIp . $salt);

        // 2. Activation / Deactivation via URL
        if (isset($_GET['debug_on'])) {
            setcookie($tokenName, $hash, time() + 31536000, '/');
            $_COOKIE[$tokenName] = $hash;
        }
        if (isset($_GET['debug_off'])) {
            setcookie($tokenName, '', time() - 3600, '/');
            return;
        }

        // 3. Access check (Token must match current IP)
        if (($_COOKIE[$tokenName] ?? '') !== $hash) {
            return;
        }

        // 4. Backtrace to find where dump was called
        $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $caller = [];
        foreach ($bt as $step) {
            if (isset($step['file']) && !str_contains($step['file'], 'Debug.php') && !in_array($step['function'], ['dump', 'dd'])) {
                $caller = $step;
                break;
            }
        }
        $file = $caller['file'] ?? 'unknown';
        $line = $caller['line'] ?? '0';

        // 5. AJAX Handling (Clean JSON output)
        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) || isset($_REQUEST['ajax']) || str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json');

        if ($isAjax) {
            if (ob_get_level()) ob_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'DEBUG' => [
                    'LOCATION' => $file . ' : ' . $line,
                    'DATA' => $data
                ]
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            die();
        }

        // 6. Standard HTML Output
        if ($die) {
            if (ob_get_level()) ob_clean();
            $wrapperStyles = "position: fixed !important; top: 0 !important; left: 0 !important; width: 100vw !important; height: 100vh !important; z-index: 2147483647 !important; background: #000 !important; overflow: auto !important; padding: 20px !important; box-sizing: border-box !important;";
            $innerStyles = "margin: 0 auto !important; max-width: 1400px !important; border: 2px solid #ff4444 !important;";
        } else {
            $wrapperStyles = "position: relative !important; z-index: 999999 !important; clear: both !important; width: 100% !important; padding: 10px !important; box-sizing: border-box !important;";
            $innerStyles = "max-height: 600px !important; overflow: auto !important; border-left: 5px solid #f39c12 !important;";
        }

        echo "<!-- DEBUG START -->
        <div style='{$wrapperStyles} font-family: sans-serif !important; text-align: left !important; direction: ltr !important;'>
            <div style='{$innerStyles} background: #1c1c1c !important; color: #4af626 !important; padding: 20px !important; border-radius: 8px !important; font-family: \"Courier New\", Courier, monospace !important; font-size: 14px !important; line-height: 1.5 !important; box-shadow: 0 0 50px rgba(0,0,0,1) !important;'>
                <div style='margin-bottom: 15px !important; padding-bottom: 10px !important; border-bottom: 1px solid #333 !important; position: sticky; top: 0; background: #1c1c1c; z-index: 2;'>
                    <b style='color: #f39c12 !important; font-size: 16px;'>" . ($die ? '🛑 TERMINATED' : '🔍 DEBUG INFO') . "</b><br>
                    <span style='color: #fff !important; font-size: 12px; word-break: break-all;'>$file</span> : <b style='color: #007bff !important;'>$line</b>
                </div>
                <pre style='margin: 0 !important; white-space: pre-wrap !important; background: transparent !important; border: none !important; color: #4af626 !important; font-family: inherit !important;'>";

        if (is_array($data) || is_object($data)) {
            echo htmlspecialchars(print_r($data, true));
        } elseif (is_bool($data)) {
            echo '<b style="color:#007bff">bool</b>(' . ($data ? 'true' : 'false') . ')';
        } else {
            var_dump($data);
        }

        echo "</pre></div></div>";

        if ($die) {
            echo '<style>body { overflow: hidden !important; }</style>';
            die();
        }
    }
}
