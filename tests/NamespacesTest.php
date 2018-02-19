<?php
/**
 * @copyright Copyright (c) 2018 Dmitry Bashkarev
 * @license https://github.com/bashkarev/xml-stream/blob/master/LICENSE
 * @link https://github.com/bashkarev/xml-stream#readme
 */

namespace Bashkarev\XmlStream\Tests;

use Bashkarev\XmlStream\Parser;

/**
 * @group functional
 */
class NamespacesTest extends TestCase
{

    public function testNs()
    {
        $i = 0;
        (new Parser())
            ->on('/test/data', function (\SimpleXMLElement $xml) use (&$i) {
                $this->assertContains('<data xmlns="http:/example.com/xsi">', $xml->asXML());
                ++$i;
            })
            ->parse($this->getStream('/ns.xml'));

        $this->assertSame(2, $i);
    }

    public function testManyNs()
    {
        $i = 0;
        (new Parser())
            ->on('/test/ns1:data', function (\SimpleXMLElement $xml) use (&$i) {
                $this->assertContains('<ns1:data xmlns="http:/example.com/xsi" xmlns:ns1="http:/example.com/ns1" xmlns:ns2="http:/example.com/ns2" xmlns:ns3="http:/example.com/ns3">', $xml->asXML());
                ++$i;
            })
            ->on('/test/ns2:data', function (\SimpleXMLElement $xml) use (&$i) {
                $this->assertContains('<ns2:data xmlns="http:/example.com/xsi" xmlns:ns1="http:/example.com/ns1" xmlns:ns2="http:/example.com/ns2" xmlns:ns3="http:/example.com/ns3">', $xml->asXML());
                ++$i;
            })
            ->on('/test/ns3:data', function (\SimpleXMLElement $xml) use (&$i) {
                ++$i; // this should not fall
            })
            ->on('/test/ns3:Data', function (\SimpleXMLElement $xml) use (&$i) {
                $this->assertContains('<ns3:Data xmlns="http:/example.com/xsi" xmlns:ns1="http:/example.com/ns1" xmlns:ns2="http:/example.com/ns2" xmlns:ns3="http:/example.com/ns3">', $xml->asXML());
                ++$i;
            })
            ->parse($this->getStream('/ns-many.xml'));

        $this->assertSame(3, $i);
    }

}