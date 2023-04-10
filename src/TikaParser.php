<?php

namespace Songspace\TikaParser;

use Exception;

class TikaParser
{
    const METHOD_JAR = 'jar';
    const METHOD_API = 'api';

    const OUTPUT_TEXT = 'text';
    const OUTPUT_HTML = 'html';
    const OUTPUT_XML = 'xml';
    const OUTPUT_JSON = 'json';
    const OUTPUT_XMP = 'xmp';

    /** @var string */
    protected $path;

    /** @var string */
    protected $type;

    /** @var string */
    protected $version;

    /**
     * TikaParser constructor.
     * @param null $path
     * @param string $type
     */
    public function __construct($path = null, $type = self::METHOD_JAR)
    {
        // Default to local jar
        if ($path === null && $type === self::METHOD_JAR) {
            $path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'tika-app-2.7.0.jar';
        }

        $this->path = $path;
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return trim($this->run('version', null, null));
    }

    /**
     * @param string $filename
     * @param string $output
     * @return string
     */
    public function getContent($filename, $output = self::OUTPUT_TEXT)
    {
        return $this->run('content', $filename, $output);
    }

    /**
     * @param string $filename
     * @param string $output
     * @return array|string
     */
    public function getMeta($filename, $output = self::OUTPUT_JSON)
    {
        return $this->run('meta', $filename, $output);
    }

    /**
     * @param string $filename
     * @return string
     */
    public function getLanguage($filename)
    {
        return $this->run('language', $filename, null);
    }

    /**
     * @param string $cmd
     * @param string|null $filename
     * @param string|null $output
     * @return array|string
     * @throws Exception
     */
    protected function run($cmd, $filename, $output)
    {
        if ($filename && !($realpath = realpath($filename))) {
            throw new Exception('File not found: ' . $filename);
        }

        switch ($this->type) {
            case self::METHOD_JAR:
                return $this->runJar($cmd, $realpath, $output);
            case self::METHOD_API:
                return $this->runApi($cmd, $realpath, $output);
        }

        throw new Exception('Invalid Tika Command');
    }

    /**
     * @param string $cmd
     * @param string|null $filename
     * @param string|null $output
     * @return array|string
     * @throws Exception
     */
    protected function runJar($cmd, $filename, $output)
    {
        $option = '';
        $json = false;

        if ($cmd === 'version') {
            $option = '--version';
        } elseif ($cmd === 'content') {
            if ($output === self::OUTPUT_TEXT) {
                $option = '--text';
            } elseif ($output === self::OUTPUT_HTML) {
                $option = '--html';
            } elseif ($output === self::OUTPUT_XML) {
                $option = '--xml';
            }
        } elseif ($cmd === 'meta') {
            if ($output === self::OUTPUT_TEXT) {
                $option = '--metadata';
            } elseif ($output === self::OUTPUT_JSON) {
                $option = '--json';
                $json = true;
            } elseif ($output === self::OUTPUT_XMP) {
                $option = '--xmp';
            }
        } elseif ($cmd === 'language') {
            $option = '--language';
        }

        // Make sure JAR exists
        if (!($jar = realpath($this->path))) {
            throw new Exception('Tika Jar not found');
        }

        // Run Command
        $process = sprintf('java -jar %s %s', escapeshellarg($jar), $option);
        if ($filename) {
            $process .= ' ' . escapeshellarg($filename);
        }
        $output = shell_exec($process);

        return $json ? json_decode($output, true) : $output;
    }

    /**
     * @param string $cmd
     * @param null|string $filename
     * @param null|string $output
     * @return array|string
     */
    protected function runApi($cmd, $filename, $output)
    {
        $uri = '';
        $headers = array();
        $json = false;

        if ($cmd === 'version') {
            $uri = '/version';
        } elseif ($cmd === 'content') {
            $uri = '/tika';
            if ($output === self::OUTPUT_TEXT) {
                $headers['Accept'] = 'text/plain';
            } elseif ($output === self::OUTPUT_HTML) {
                $headers['Accept'] = 'text/html';
            } elseif ($output === self::OUTPUT_XML) {
                $headers['Accept'] = 'text/plain';
            }
        } elseif ($cmd === 'meta') {
            $uri = '/version';
            if ($output === self::OUTPUT_TEXT) {
                $headers['Accept'] = 'text/plain';
            } elseif ($output === self::OUTPUT_JSON) {
                $headers['Accept'] = 'application/json';
                $json = true;
            } elseif ($output === self::OUTPUT_XMP) {
                $headers['Accept'] = 'application/rdf+xml';
            }
        } elseif ($cmd === 'language') {
            $uri = '/language';
        }

        $url = rtrim($this->path, '/') . $uri;
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_PUT, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        if ($filename) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, array('file' => '@' . $filename));
        }
        $output = curl_exec($curl);

        return $json ? json_decode($output, true) : $output;
    }
}
