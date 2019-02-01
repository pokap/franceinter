<?php

/**
 * Returns absolute url.
 *
 * @param string $path
 *
 * @return string
 */
function generate_url($path)
{
    if ($path[0] === '/') {
        $path = substr($path, 1);
    }

    return "http://www.franceinter.fr/".$path;
}

/**
 * Returns list of diffusions path.
 *
 * @param int $id Identifier of diffusions
 *
 * @return string[]
 */
function get_diffusions($id)
{
    $html_content = file_get_contents(generate_url(sprintf("/reecouter-diffusions/%d", $id)));

    if (preg_match_all("#\/player\/reecouter\?play=[0-9]*#i", $html_content, $urls)) {
        return $urls[0];
    }

    return array();
}

/**
 * Returns list of mp3 url.
 *
 * @param string $diffusion Path of the diffusion
 *
 * @return string[]
 */
function get_mp3s($diffusion)
{
    $content_page = file_get_contents(generate_url($diffusion));

    if (preg_match_all("#\/sites\/default\/files\/sons\/.*\.mp3#i", $content_page, $mp3url)) {
        return $mp3url[0];
    }

    return array();
}

/**
 * Format name given by path.
 *
 * @param string $path
 *
 * @return string
 */
function get_name_mp3($path)
{
    return basename($path);
}

/**
 * Download the file.
 *
 * @param string $path
 *
 * @return bool
 */
function download($source, $target)
{
    $target_buffer = $target.'.temp'.uniqid('', true);

    $source_handle = fopen($source, 'rb');
    if ($source_handle === false) {
        return false;
    }

    $target_handler = fopen($target_buffer, 'wb');
    if ($target_handler === false) {
        fclose($source_handle);

        return false;
    }

    while (!feof($source_handle)) {
        fwrite($target_handler, fread($source_handle, 1024*1024));
    }

    fclose($source_handle);
    fclose($target_handler);

    rename($target_buffer, $target);

    return true;
}

/**
 * Display log message.
 *
 * @param string $filename
 * @param bool   $success
 *
 * @return void
 */
function log_state($filename, $success)
{
    $memory_usage = memory_get_usage(true) / 1024 / 1024;

    echo sprintf('[Mem:%.1fMiB] - %s - %s', $memory_usage, $success? 'SUCCESS' : 'FAIL!  ', $filename) . PHP_EOL;
}

// ------------------

foreach (get_diffusions(434607) as $diffusion) {
    foreach (get_mp3s($diffusion) as $mp3) {
        $filename = get_name_mp3($mp3);

        $success = download(generate_url($mp3), $filename);

        log_state($filename, $success);
    }
}

