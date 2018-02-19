<?php
/**
 * @copyright Copyright (c) 2018 Dmitry Bashkarev
 * @license https://github.com/bashkarev/xml-stream/blob/master/LICENSE
 * @link https://github.com/bashkarev/xml-stream#readme
 */

namespace Bashkarev\XmlStream\Tests;

use Psr\Http\Message\StreamInterface;

class Stream implements StreamInterface
{
    /**
     * @var resource
     */
    private $handle;

    /**
     * @param $path
     */
    public function __construct($path)
    {
        $this->handle = fopen($path, 'rb');
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        throw new \RuntimeException('Not supported');

    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        fclose($this->handle);
    }

    /**
     * {@inheritdoc}
     */
    public function detach()
    {
        throw new \RuntimeException('Not supported');
    }

    /**
     * {@inheritdoc}
     */
    public function getSize()
    {
        throw new \RuntimeException('Not supported');
    }

    /**
     * {@inheritdoc}
     */
    public function tell()
    {
        throw new \RuntimeException('Not supported');
    }

    /**
     * {@inheritdoc}
     */
    public function eof()
    {
        return feof($this->handle);
    }

    /**
     * {@inheritdoc}
     */
    public function isSeekable()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        throw new \RuntimeException('Not supported');
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        throw new \RuntimeException('Not supported');
    }

    /**
     * {@inheritdoc}
     */
    public function isWritable()
    {
        throw new \RuntimeException('Not supported');
    }

    /**
     * {@inheritdoc}
     */
    public function write($string)
    {
        throw new \RuntimeException('Not supported');
    }

    /**
     * {@inheritdoc}
     */
    public function isReadable()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function read($length)
    {
        return fread($this->handle, $length);
    }

    /**
     * {@inheritdoc}
     */
    public function getContents()
    {
        throw new \RuntimeException('Not supported');
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata($key = null)
    {
        throw new \RuntimeException('Not supported');
    }

}