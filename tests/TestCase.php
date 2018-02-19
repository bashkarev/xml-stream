<?php
/**
 * @copyright Copyright (c) 2018 Dmitry Bashkarev
 * @license https://github.com/bashkarev/xml-stream/blob/master/LICENSE
 * @link https://github.com/bashkarev/xml-stream#readme
 */

namespace Bashkarev\XmlStream\Tests;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{

    /**
     * @param string $file
     * @return Stream
     */
    public function getStream($file)
    {
        return new Stream(__DIR__ . '/data' . $file);
    }

}