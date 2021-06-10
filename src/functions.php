<?php declare(strict_types =1);
namespace Medusa\DevTools;

function dd($v, int $exitCode = 0) {
    d($v);
    die($exitCode);
}

function d($v) {
    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

    if (
        $trace[0]['file'] === __FILE__
        && $trace[0]['function'] === __FUNCTION__
    ) {
        $trace = $trace[1];
    } else {
        $trace = $trace[0];
    }

    if (null === $v) {
        $v = 'NULL';
    } elseif ($v === true) {
        $v = 'true';
    } elseif ($v === false) {
        $v = 'false';
    } elseif ($v === '') {
        $v = '"-EMPTY STRING-"';
    }

    $cli = php_sapi_name() === 'cli';
    if (!$cli) {
        header_remove('content-length');
        $c = headers_list();
        header_remove('content-type');

        if (count($c) !== count(headers_list())) {
            $cli = true;
        }
    }

    if (!$cli) {
        echo '<pre style="white-space: pre-wrap">';
    }
    print_r($trace['file'] . ':' . $trace['line']);
    echo "\n";
    print_r($v);
    if (!$cli) {
        echo '</pre>';
    } else {
        echo "\n";
    }
}

/**
 * Debug log
 * @param             $data
 * @param string|null $logfileName
 *
 * #######################
 *
 * Format:
 *
 * REQUEST IDENT        RUNTIME SINCE FIRST     RUNTIME BETWEEN LAST            DEBUGMESSAGE
 *                                              AND CURRENT debugLog call
 *                      debugLog call
 * 2e1df6eb         -> [0,000000ms              +0,000000ms: ]                  Application init
 *
 * BASH_RC
 * function debuglog() {
 *      local _file=~/app_debug_logs/$(date +"log_%Y-%m-%d.log")
 *      [ -f "$_file" ] || touch $_file
 *      tail -f $_file
 * }
 * #######################
 *
 * Examples:
 * integrated counter:  debugLog('get config call: >>$i++<<');
 * result:
 * 3bdf1d7a -> [0,000000ms +0,000000ms: ] Get config 1
 * 3bdf1d7a -> [0,595947ms +0,595947ms: ] Get config 2
 * ....
 * 3bdf1d7a -> [78,260010ms +0,089844ms: ] Get config 69
 * 3bdf1d7a -> [78,322998ms +0,062988ms: ] Get config 70
 * 3bdf1d7a -> [78,387939ms +0,064941ms: ] Get config 71
 *
 */
function debugLog($data, string $logfileName = null) {

    static $runtime = 0;
    static $last;
    static $requestId;
    static $counts=[];
    static $home;

    if (!$home) {
        $home = $_SERVER['HOME'] ?? exec('echo $HOME');
        if ($home === '') {

            $server = $_SERVER['SERVER_SOFTWARE'] ?? '';
            if (preg_match('/^PHP ?.* Development Server$/i', $server, $matches)) {
                $root = $_SERVER['DOCUMENT_ROOT'];
                $user = get_current_user();

                if ($user === '') {
                    $user = exec('whoami');
                }
                if ($user === '') {
                    $home = $root;
                } else {
                    $home = preg_replace('|(/' . $user . ')/.*|', '$1', $root);
                }
            }
        }
        if ($home === '') {
            $home = __DIR__;
        }
    }

    $colorCodes = [
        "\033[31m%s\e[0m",
        "\033[32m%s\e[0m",
        "\033[33m%s\e[0m",
        "\033[34m%s\e[0m",
        "\033[35m%s\e[0m",
        "\033[36m%s\e[0m",
        "\033[91m%s\e[0m",
        "\033[92m%s\e[0m",
        "\033[93m%s\e[0m",
        "\033[94m%s\e[0m",
        "\033[95m%s\e[0m",
        "\033[96m%s\e[0m",
    ];

    $requestId ??= sprintf($colorCodes[array_rand($colorCodes)], substr(md5(microtime(true). ($_SERVER['REQUEST_URI'] ?? '')), 0, 8));

    $start = 1000 * microtime(true);

    if ($last === null) {
        $last = $start;
    }

    $logfileName = $logfileName ?? 'log_' . date('Y-m-d') . '.log';
    $logFile = $home . DIRECTORY_SEPARATOR . 'app_debug_logs' . DIRECTORY_SEPARATOR . $logfileName;
    $dir = dirname($logFile);

    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    $runtime += $start - $last;
    $data = print_r($data, true);

    if (strpos($data, '>>$i++<<') !== false) {
        if (!isset($counts[$data])) {
            $counts[$data] = 0;
        }
        $counts[$data]++;
        $data = str_replace('>>$i++<<', (string)$counts[$data], $data);
    }

    $color = "\033[31m%s\e[0m";
    $data = sprintf($color, $requestId) . ' -> [' . number_format($runtime, 6, ',', '.') . 'ms +' . number_format($start - $last, 6, ',', '.') . 'ms: ] ' . $data;
    $last = $start;
    file_put_contents($logFile, $data . PHP_EOL, FILE_APPEND);
}
