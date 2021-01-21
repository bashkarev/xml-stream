<?php
/**
 * @copyright Copyright (c) 2018 Dmitry Bashkarev
 * @license https://github.com/bashkarev/xml-stream/blob/master/LICENSE
 * @link https://github.com/bashkarev/xml-stream#readme
 */

namespace Bashkarev\XmlStream;

use Psr\Http\Message\StreamInterface;
use SimpleXMLElement;

/**
 * @author David North
 * @author Dmitry Bashkarev <dmitry@bashkarev.com>
 */
class Parser
{
    /**
     * @var \Closure[][] An array of registered callbacks
     */
    private $callbacks;
    /**
     * @var string The current node path being investigated
     */
    private $currentPath;
    /**
     * @var array An array path data for paths that require callbacks
     */
    private $pathData;
    /**
     * @var boolean Whether or not the object is currently parsing
     */
    private $parse;
    /**
     * @var array A list of namespaces in this XML
     */
    private $namespaces;
    /**
     * @var int
     */
    private $chunkSize = 1024;


    public function setChunkSize($chunkSize)
    {
        $this->chunkSize = $chunkSize;

        return $this;
    }

    public function getChunkSize()
    {
        return $this->chunkSize;
    }

    public function parse(StreamInterface $stream)
    {
        if (!$stream->isReadable()) {
            throw new \RuntimeException('Stream not readable');
        }

        $parser = $this->init();
        if ($stream->isSeekable()) {
            $stream->seek(0);
        }

        while ($this->parse) {
            $chunk = $stream->read($this->chunkSize);
            $isFinal = $stream->eof();
            if (!\xml_parse($parser, $chunk, $isFinal)) {
                throw new \RuntimeException(
                    \sprintf('Error on line %u column %u: %s',
                        \xml_get_current_line_number($parser),
                        \xml_get_current_column_number($parser),
                        \xml_error_string(\xml_get_error_code($parser))
                    )
                );
            }
            if ($isFinal) {
                break;
            }
        }
        \xml_parser_free($parser);

        return $this;
    }

    /**
     * Registers a single callback for a specified XML path
     *
     * @param string $path The path that the callback is for
     * @param \Closure $callback The callback mechanism to use
     *
     * @return Parser
     */
    public function on($path, \Closure $callback)
    {
        //All tags and paths are lower cased, for consistency
        if (\substr($path, -1, 1) !== '/') {
            $path .= '/';
        }

        //If this is the first callback for this path, initialise the variable
        if (!isset($this->callbacks[$path])) {
            $this->callbacks[$path] = array();
        }

        //Add the callback
        $this->callbacks[$path][] = $callback;

        return $this;
    }

    /**
     * @param string $path
     *
     * @return Parser
     */
    public function off($path)
    {
        $this->callbacks[$path] = [];

        return $this;
    }

    /**
     * Parses the start tag
     *
     * @param resource $parser The XML parser
     * @param string $tag The tag that's being started
     * @param array $attributes The attributes on this tag
     */
    protected function start($parser, $tag, array $attributes)
    {
        //Update the current path
        $this->currentPath .= $tag . '/';

        $this->fireCurrentAttributesCallbacks($attributes);

        //Go through each callback and ensure that path data has been
        //started for it
        foreach ($this->callbacks as $path => $callbacks) {
            if ($path === $this->currentPath) {
                $this->pathData[$this->currentPath] = '';
            }
        }

        //Generate the tag, with attributes. Attribute names are also lower
        //cased, for consistency
        $data = '<' . $tag;
        foreach ($attributes as $key => $val) {
            $val = \htmlentities($val, ENT_QUOTES | ENT_XML1, "UTF-8");
            $data .= ' ' . $key . '="' . $val . '"';
            if (\strpos($key, 'xmlns') !== false) {
                $this->namespaces[$key] = $val;
            }
        }
        $data .= '>';

        //Add the data to the path data required
        $this->addData($parser, $data);
    }

    /**
     * Adds CDATA to any paths that require it
     *
     * @param resource $parser
     * @param string $data
     *
     * @return resource
     */
    protected function addCdata($parser, $data)
    {
        $this->addData($parser, '<![CDATA[' . $data . ']]>');

        return $parser;
    }

    /**
     * Adds data to any paths that require it
     *
     * @param resource $parser
     * @param string $data
     */
    protected function addData($parser, $data)
    {
        //Having a path data entry means at least 1 callback is interested in
        //the data. Loop through each path here and, if inside that path, add
        //the data
        foreach ($this->pathData as $key => &$val) {
            if (\strpos($this->currentPath, $key) !== false) {
                $val .= $data;
            }
        }
    }

    /**
     * Parses the end of a tag
     *
     * @param resource $parser
     * @param string $tag
     *
     */
    protected function end($parser, $tag)
    {
        //Add the data to the paths that require it
        $data = '</' . $tag . '>';
        $this->addData($parser, $data);

        //Loop through each callback and see if the path matches the
        //current path
        foreach ($this->callbacks as $path => $callbacks) {
            //If parsing should continue, and the paths match, then a callback
            //needs to be made
            if ($this->parse && $this->currentPath === $path) {
                if (!$this->fireCallbacks($tag, $path, $callbacks)) {
                    break;
                }
            }
        }

        //Unset the path data for this path, as it's no longer needed
        unset($this->pathData[$this->currentPath]);

        //Update the path with the new path (effectively moving up a directory)
        $this->currentPath = \substr(
            $this->currentPath,
            0,
            \strlen($this->currentPath) - (\strlen($tag) + 1)
        );
    }

    /**
     * Generates a SimpleXMLElement and passes it to each of the callbacks
     *
     * @param string $tag
     * @param string $path The path to create the SimpleXMLElement from
     * @param \Closure[] $callbacks An array of callbacks to be fired.
     *
     * @return boolean
     */
    protected function fireCallbacks($tag, $path, array $callbacks)
    {
        $namespaceStr = '';
        $pathData = \substr($this->pathData[$path], \strlen($tag) + 1);

        $line = \stristr($pathData, '>', true);
        foreach ($this->namespaces as $ns => $val) {
            if (false === \strpos($line, $ns)) {
                $namespaceStr .= ' ' . $ns . '="' . $val . '"';
            }
        }

        //Build the SimpleXMLElement object. As this is a partial XML
        //document suppress any warnings or errors that might arise
        //from invalid namespaces
        $data = new SimpleXMLElement(
            '<' . $tag . $namespaceStr . $pathData,
            \LIBXML_COMPACT
        );


        //Loop through each callback. If one of them stops the parsing
        //then cease operation immediately
        foreach ($callbacks as $callback) {
            if (false === $callback->__invoke($data)) {
                $this->parse = false;
                return false;
            }
        }

        return true;
    }

    /**
     * Traverses the passed attributes, assuming the currentPath, and invokes registered callbacks,
     * if there are any
     *
     * @param array $attributes Key-value map for the current element
     *
     * @return void
     */
    protected function fireCurrentAttributesCallbacks(array $attributes)
    {
        foreach ($attributes as $key => $val) {
            $path = $this->currentPath . '@' . $key . '/';

            if (isset($this->callbacks[$path])) {
                foreach ($this->callbacks[$path] as $callback) {
                    if (false === $callback->__invoke($val)) {
                        $this->parse = false;
                        return;
                    }
                }
            }
        }
    }

    /**
     * @return resource
     */
    protected function init()
    {
        $this->namespaces = [];
        $this->currentPath = '/';
        $this->pathData = [];
        $this->parse = true;

        $parser = \xml_parser_create('UTF-8');
        \xml_set_object($parser, $this);
        \xml_parser_set_option($parser, \XML_OPTION_CASE_FOLDING, false);
        \xml_set_element_handler($parser, 'start', 'end');
        \xml_set_character_data_handler($parser, 'addCdata');
        \xml_set_default_handler($parser, 'addData');

        return $parser;
    }

}