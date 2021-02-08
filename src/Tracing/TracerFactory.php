<?php

namespace Tracing;

use Tracing\JaegerTracer;
use Exception;
use InvalidArgumentException;

class TracerFactory {

    private static $instances = [];

    /**
     * @param string $strName
     * @param string $arrConfig
     *
     * @return TracerInterface
     *
     * @throws Exception
     */
    public static function create(string $strName, array $arrConfig = []) {
        $strName = trim($strName);

        if ('' === $strName) {
            throw new InvalidArgumentException('Invalid name');
        }

        if (isset(self::$instances[$strName])) {
            return self::$instances[$strName];
        }

        // Default is jaeger
        $strType = 'jaeger';

        if (!empty($arrConfig['type'])) {
            $strType = trim($arrConfig['type']);
        }

        $arrConfig['name'] = $strName;

        // Currently only support jaeger
        $tracer = null;
        switch ($strType) {
            case 'jaeger':
                $tracer = new JaegerTracer($arrConfig);
                break;

            default:
                throw new \Exception('Invalid type');
        }

        self::$instances[$strName] = $tracer;

        return $tracer;
    }

    /**
     * @param string $strName
     *
     * @return TracerInterface|null
     *
     * @throws Exception
     */
    public static function getByName(string $strName) {
        if (trim($strName) === '') {
            throw new \Exception('Invalid name');
        }

        $strName = trim($strName);

        if (isset(self::$instances[$strName])) {
            return self::$instances[$strName];
        }

        return null;
    }

}
