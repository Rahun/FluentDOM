<?php
/**
* Collection of test for the FluentDOM class supporting PHP 5.2
*
* @version $Id$
* @license http://www.opensource.org/licenses/mit-license.php The MIT License
* @copyright Copyright (c) 2009 Bastian Feder, Thomas Weinert
*
* @package FluentDOM
* @subpackage unitTests
*/

/**
* load necessary files
*/
require_once (dirname(__FILE__).'/FluentDOMTestCase.php');

PHPUnit_Util_Filter::addFileToFilter(__FILE__);

/**
* Test class for FluentDOM.
*
* @package FluentDOM
* @subpackage unitTests
*/
class FluentDOMTest extends FluentDOMTestCase {

  const XML = '
    <items version="1.0">
      <group id="1st">
        <item index="0">text1</item>
        <item index="1">text2</item>
        <item index="2">text3</item>
      </group>
      <html>
        <div class="test1 test2">class testing</div>
        <div class="test2">class testing</div>
      </html>
    </items>
  ';

  /**
  * @group Functions
  */
  public function testFunction() {
    $fd = FluentDOM();
    $this->assertTrue($fd instanceof FluentDOM);
  }

  /**
  * @group Functions
  */
  public function testFunctionWithContent() {
    $dom = new DOMDocument();
    $node = $dom->appendChild($dom->createElement('html'));
    $fd = FluentDOM($node);
    $this->assertTrue($fd instanceof FluentDOM);
    $this->assertEquals('html', $fd->document->documentElement->nodeName);
  }

  /*
  * Load
  */

  /**
  * @group Load
  */
  public function testLoadWithInvalidSource() {
    $fd = new FluentDOM();
    try {
      $fd->load(1);
      $this->fail('An expected exception has not been raised.');
    } catch (InvalidArgumentException $expected) {
    }
  }

  /**
  * @group Load
  */
  public function testLoaderMechanism() {
    $firstLoaderMock = $this->getMock('FluentDOMLoader');
    $firstLoaderMock->expects($this->once())
                    ->method('load')
                    ->with($this->equalTo('test load string'), $this->equalTo('text/xml'))
                    ->will($this->returnValue(FALSE));
    $secondLoaderMock = $this->getMock('FluentDOMLoader');
    $secondLoaderMock->expects($this->once())
                     ->method('load')
                     ->with($this->equalTo('test load string'), $this->equalTo('text/xml'))
                     ->will($this->returnValue(new DOMDocument()));

    $fd = new FluentDOM();
    $fd->setLoaders(array($firstLoaderMock, $secondLoaderMock));

    $this->assertSame(
      $fd,
      $fd->load('test load string')
    );
  }

  /**
  * @group Load
  */
  public function testLoaderMechanismIncludingSelection() {
    $dom = new DOMDocument();
    $domNode = $dom->appendChild($dom->createElement('root'));
    $loaderMock = $this->getMock('FluentDOMLoader');
    $loaderMock->expects($this->once())
               ->method('load')
               ->with($this->equalTo($domNode), $this->equalTo('text/xml'))
               ->will($this->returnValue(array($dom, array($domNode))));

    $fd = new FluentDOM();
    $fd->setLoaders(array($loaderMock));

    $this->assertSame(
      $fd,
      $fd->load($domNode)
    );
  }

  public function testSetLoadersInvalid() {
    try {
      $fd = new FluentDOM();
      $fd->setLoaders(array(new stdClass));
      $this->fail('An expected exception has not been raised.');
    } catch (InvalidArgumentException $expected) {
    }
  }

  /*
  * Properties
  */

  /**
  * @group Properties
  */
  public function testPropertyDocument() {
    $fd = $this->getFixtureFromString(self::XML);
    $this->assertTrue(isset($fd->document));
    $this->assertTrue($fd->document instanceof DOMDocument);
    try {
      $fd->document = NULL;
      $this->fail('An expected exception has not been raised.');
    } catch (BadMethodCallException $expected) {
    }
  }

  /**
  * @group Properties
  */
  public function testPropertyXpath() {
    $fd = $this->getFixtureFromString(self::XML);
    $this->assertTrue(isset($fd->xpath));
    $this->assertTrue($fd->xpath instanceof DOMXPath);
    try {
      $fd->xpath = NULL;
      $this->fail('An expected exception has not been raised.');
    } catch (BadMethodCallException $expected) {
    }
  }

  /**
  * @group Properties
  */
  public function testPropertyLength() {
    $fd = $this->getFixtureFromString(self::XML);
    $this->assertTrue(isset($fd->length));
    $this->assertEquals(0, $fd->length);
    $fd = $fd->find('/items');
    $this->assertEquals(1, $fd->length);
    try {
      $fd->length = 50;
      $this->fail('An expected exception has not been raised.');
    } catch (BadMethodCallException $expected) {
    }
  }

  /**
  * @group  Properties
  * @dataProvider getContentTypeSamples
  */
  public function testPropertyContenttype($contentType, $expected) {
    $fd = new FluentDOM();
    $fd->contentType = $contentType;
    $this->assertSame($expected, $fd->contentType);
  }

  public function getContentTypeSamples() {
    return array(
      array('text/xml', 'text/xml'),
      array('text/html', 'text/html'),
      array('xml', 'text/xml'),
      array('html', 'text/html'),
      array('TEXT/XML', 'text/xml'),
      array('TEXT/HTML', 'text/html'),
      array('XML', 'text/xml'),
      array('HTML', 'text/html')
    );
  }

  /**
  * @group  Properties
  */
  public function testSetInvalidPropertyContenttype() {
    try {
      $fd = new FluentDOM();
      $fd->contentType = 'INVALID_TYPE';
      $this->fail('An expected exception has not been raised.');
    } catch (UnexpectedValueException $expected) {
    }
  }

  /**
  * @group Properties
  */
  public function testDynamicProperty() {
    $fd = $this->getFixtureFromString(self::XML);
    $this->assertEquals(FALSE, isset($fd->dynamicProperty));
    $this->assertEquals(NULL, $fd->dynamicProperty);
    $fd->dynamicProperty = 'test';
    $this->assertEquals(TRUE, isset($fd->dynamicProperty));
    $this->assertEquals('test', $fd->dynamicProperty);
  }

  /*
  * __toString() method
  */

  /**
  * @group MagicFunctions
  */
  public function testMagicToString() {
    $fd = $this->getFixtureFromString(self::XML);
    $this->assertEquals($fd->document->saveXML(), (string)$fd);
  }

  /**
  * @group MagicFunctions
  */
  public function testMagicToStringHtml() {
    $dom = new DOMDocument();
    $dom->loadHTML('<html><body><br></body></html>');
    $loader = $this->getMock('FluentDOMLoader');
    $loader->expects($this->once())
           ->method('load')
           ->with($this->equalTo(''), $this->equalTo('text/html'))
           ->will($this->returnValue($dom));
    $fd = new FluentDOM();
    $fd->setLoaders(array($loader));
    $fd = $fd->load('', 'text/html');
    $this->assertEquals($dom->saveHTML(), (string)$fd);
  }

  /**
  * @group MagicFunctions
  */
  public function testMagicCallUnknown() {
    try {
      $fd = new FluentDOM();
      $fd->invalidDynamicMethodName();
      $this->fail('An expected exception has not been raised.');
    } catch (BadMethodCallException $expected) {
    }
  }

  /*
  * Interfaces
  */

  /**
  * @group Interfaces
  */
  public function testInterfaceArrayAccessIsset() {
    $fd = $this->getFixtureFromString(self::XML)->find('//item');
    $this->assertTrue($fd instanceof ArrayAccess);
    $this->assertEquals(TRUE, isset($fd[1]));
    $this->assertEquals(FALSE, isset($fd[200]));
  }

  /**
  * @group Interfaces
  */
  public function testInterfaceArrayAccessGet() {
    $fd = $this->getFixtureFromString(self::XML)->find('//item');
    $this->assertTrue($fd instanceof ArrayAccess);
    $this->assertEquals('item', $fd[1]->nodeName);
    $this->assertEquals(1, $fd[1]->getAttribute('index'));
  }

  /**
  * @group Interfaces
  */
  public function testInterfaceArrayAccessSet() {
    $fd = $this->getFixtureFromString(self::XML)->find('//item');
    $this->assertTrue($fd instanceof ArrayAccess);
    try {
      $fd[1] = NULL;
      $this->fail('An expected exception has not been raised.');
    } catch (BadMethodCallException $expected) {
    }
  }

  /**
  * @group Interfaces
  */
  public function testInterfaceArrayAccessUnset() {
    $fd = $this->getFixtureFromString(self::XML)->find('//item');
    $this->assertTrue($fd instanceof ArrayAccess);
    try {
      unset($fd[1]);
      $this->fail('An expected exception has not been raised.');
    } catch (BadMethodCallException $expected) {
    }
  }

  /**
  * @group Interfaces
  */
  public function testInterfaceCountable() {
    $fd = $this->getFixtureFromString(self::XML);
    $this->assertTrue($fd instanceof Countable);
    $this->assertEquals(0, count($fd));
    $fd = $fd->find('//item');
    $this->assertEquals(3, count($fd));
  }

  /**
  * @group Interfaces
  */
  public function testInterfaceIteratorLoop() {
    $fd = $this->getFixtureFromString(self::XML)->find('//item');
    $counter = 0;
    foreach ($fd as $item) {
      $this->assertEquals('item', $item->nodeName);
      $this->assertEquals($counter, $item->getAttribute('index'));
      ++$counter;
    }
    $this->assertEquals(3, $counter);
  }

  /**
  * @group Interfaces
  */
  public function testInterfaceRecursiveIterator() {
    $iterator = new RecursiveIteratorIterator(
      $this->getFixtureFromString(self::XML)->find('/*'),
      RecursiveIteratorIterator::SELF_FIRST
    );
    $counter = 0;
    foreach ($iterator as $key => $value) {
      if ($value->nodeName == 'item') {
        ++$counter;
      }
    }
    $this->assertEquals(3, $counter);
  }

  /*
  * Core functions
  */

  /**
  * @group CoreFunctions
  */
  public function testItem() {
    $fd = $this->getFixtureFromString(self::XML)->find('/items');
    $this->assertEquals($fd->document->documentElement, $fd->item(0));
    $this->assertEquals(NULL, $fd->item(-10));
  }

  /**
  * @group CoreFunctions
  */
  public function testEach() {
    $fd = $this->getFixtureFromFile(__FUNCTION__);
    $fd ->find('//body//*')
      ->each(
        create_function(
          '$node, $item',
          '$fd = new FluentDOM();
           $fd->load($node);
           $fd->prepend("EACH > ");
          ')
      );
    $this->assertTrue($fd instanceof FluentDOM);
    $this->assertFluentDOMEqualsXMLFile(__FUNCTION__, $fd);
  }

  /**
  * @group CoreFunctions
  */
  public function testEachWithInvalidFunction() {
    try {
      $this->getFixtureFromString(self::XML)
        ->find('//body//*')
        ->each('invalidCallbackFunctionName');
      $this->fail('An expected exception has not been raised.');
    } catch (BadFunctionCallException $expected) {
    }
  }

  /**
  * @group CoreFunctions
  */
  public function testNode() {
    $fd = $this->getFixtureFromFile(__FUNCTION__);
    $fdItems = $this->getFixtureFromString(
      '<samples><b id="first">Paragraph. </b></samples>'
    );
    $fd->node(
        $fdItems
          ->find('//b[@id = "first"]')
          ->removeAttr('id')
          ->addClass('imported')
      )
      ->replaceAll('//p');
    $this->assertTrue($fd instanceof FluentDOM);
    $this->assertFluentDOMEqualsXMLFile(__FUNCTION__, $fd);
  }

  /**
  * @group CoreFunctions
  */
  public function testNodeWithDomelement() {
    $fd = $this->getFixtureFromString(self::XML);
    $nodes = $fd->node($fd->document->createElement('div'));
    $this->assertTrue($fd instanceof FluentDOM);
    $this->assertEquals(1, count($nodes));
  }

  /**
  * @group CoreFunctions
  */
  public function testNodeWithDomtext() {
    $fd = $this->getFixtureFromString(self::XML);
    $nodes = $fd->node($fd->document->createTextNode('div'));
    $this->assertTrue($fd instanceof FluentDOM);
    $this->assertEquals(1, count($nodes));
  }

  /**
  * @group CoreFunctions
  */
  public function testNodeWithInvalidContent() {
    try {
      $fd = $this->getFixtureFromString(self::XML)
        ->node(NULL);
      $this->fail('An expected exception has not been raised.');
    } catch (InvalidArgumentException $expected) {
    }
  }

  /**
  * @group CoreFunctions
  */
  public function testNodeWithEmptyContent() {
    try {
      $fd = $this->getFixtureFromString(self::XML)
        ->node('');
      $this->fail('An expected exception has not been raised.');
    } catch (UnexpectedValueException $expected) {
    }
  }

  /**
  * @group CoreFunctions
  */
  public function testNodeWithEmptyList() {
    try {
      $fd = $this->getFixtureFromString(self::XML);
      $fd->node(
        $fd->find('UnknownTagName')
      );
      $this->fail('An expected exception has not been raised.');
    } catch (UnexpectedValueException $expected) {
    }
  }

  /**
  * @group CoreFunctions
  */
  public function testFormatOutput() {
    $fd = new FluentDOM();
    $fd->append('<html><body><br/></body></html>')
       ->formatOutput();
    $expected =
       "<?xml version=\"1.0\"?>\n".
       "<html>\n".
       "  <body>\n".
       "    <br/>\n".
       "  </body>\n".
       "</html>\n";
    $this->assertSame('text/xml', $this->readAttribute($fd, '_contentType'));
    $this->assertSame($expected, (string)$fd);
  }


  /**
  * @group CoreFunctions
  */
  public function testFormatOutputWithContentTypeHtml() {
    $fd = new FluentDOM();
    $fd->append('<html><body><br/></body></html>')
       ->formatOutput('text/html');
    $expected = "<html><body><br></body></html>\n";
    $this->assertSame('text/html', $this->readAttribute($fd, '_contentType'));
    $this->assertSame($expected, (string)$fd);
  }

  /*
  * Traversing - Filtering
  */

  /**
  * @group TraversingFilter
  */
  public function testEq() {
    $fd = $this->getFixtureFromString(self::XML)->find('//*');
    $this->assertTrue($fd->length > 1);
    $eqFd = $fd->eq(0);
    $this->assertEquals(1, $eqFd->length);
    $this->assertTrue($eqFd !== $fd);
  }

  /**
  * @group TraversingFilter
  */
  public function testFilter() {
    $fd = $this->getFixtureFromString(self::XML)->find('//*');
    $this->assertTrue($fd->length > 1);
    $filterFd = $fd->filter('name() = "items"');
    $this->assertEquals(1, $filterFd->length);
    $this->assertTrue($filterFd !== $fd);
  }

  /**
  * @group TraversingFilter
  */
  public function testFilterWithFunction() {
    $fd = $this->getFixtureFromString(self::XML)->find('//*');
    $this->assertTrue($fd->length > 1);
    $filterFd = $fd->filter(array($this, 'callbackTestFilterWithFunction'));
    $this->assertEquals(1, $filterFd->length);
    $this->assertTrue($filterFd !== $fd);
  }

  /**
  * @group TraversingFilter
  */
  public function testIs() {
    $fd = $this->getFixtureFromString(self::XML)->find('//*');
    $this->assertTrue($fd->length > 1);
    $this->assertTrue($fd->is('name() = "items"'));
    $this->assertFalse($fd->is('name() = "invalidItemName"'));
  }

  /**
  * @group TraversingFilter
  */
  public function testIsOnEmptyList() {
    $fd = $this->getFixtureFromString(self::XML);
    $this->assertTrue($fd->length == 0);
    $this->assertFalse($fd->is('name() = "items"'));
  }

  /**
  * @group TraversingFilter
  */
  public function testMap() {
    $fd = $this->getFixtureFromFile(__FUNCTION__);
    $fd ->find('//p')
      ->append(
        implode(
          ', ',
          $fd
            ->find('//input')
            ->map(
              create_function(
                '$node, $index',
                '$fd = new FluentDOM();
                 return $fd->load($node)->attr("value");'
              )
            )
        )
      );
    $this->assertTrue($fd instanceof FluentDOM);
    $this->assertFluentDOMEqualsXMLFile(__FUNCTION__, $fd);
  }

  /**
  * @group TraversingFilter
  */
  public function testMapMixedResult() {
    $fd = $this->getFixtureFromFile(__FUNCTION__);
    $fd ->find('//p')
      ->append(
        implode(
          ', ',
          $fd
            ->find('//input')
            ->map(
              create_function(
                '$node, $index',
                '
                  switch($index) {
                  case 0:
                    return NULL;
                  case 1:
                    return 3;
                  default:
                    return array(1,2);
                  }
                ')
            )
        )
      );
    $this->assertTrue($fd instanceof FluentDOM);
    $this->assertFluentDOMEqualsXMLFile(__FUNCTION__, $fd);
  }

  /**
  * @group TraversingFilter
  */
  public function testMapInvalidCallback() {
    $fd = $this->getFixtureFromFile('testMap');
    try {
      $fd->find('//p')
        ->map('invalidCallbackFunctionName');
        $this->fail('An expected exception has not been raised.');
    } catch (BadFunctionCallException $expected) {
    }
  }

  /**
  * @group TraversingFilter
  */
  public function testNot() {
    $fd = $this->getFixtureFromString(self::XML)->find('//*');
    $this->assertTrue($fd->length > 1);
    $notDoc = $fd->not('name() != "items"');
    $this->assertEquals(1, $notDoc->length);
    $this->assertTrue($notDoc !== $fd);
  }

  /**
  * @group TraversingFilter
  */
  public function testNotWithFunction() {
    $fd = $this->getFixtureFromString(self::XML)->find('//*');
    $this->assertTrue($fd->length > 1);
    $notDoc = $fd->not(array($this, 'callbackTestNotWithFunction'));
    $this->assertEquals(1, $notDoc->length);
    $this->assertTrue($notDoc !== $fd);
  }

  /**
  * @group TraversingFilter
  */
  public function testSliceByRangeStartLtEnd() {
    $fd = $this->getFixtureFromFile(__FUNCTION__);
    $fd ->find('//p')
      ->slice(0, 3)
      ->replaceAll('//div');
    $this->assertTrue($fd instanceof FluentDOM);
    $this->assertFluentDOMEqualsXMLFile(__FUNCTION__, $fd);
  }

  /**
  * @group TraversingFilter
  */
  public function testSliceByRangeStartGtEnd() {
    $fd = $this->getFixtureFromFile(__FUNCTION__);
    $fd ->find('//p')
      ->slice(5, 2)
      ->replaceAll('//div');
    $this->assertTrue($fd instanceof FluentDOM);
    $this->assertFluentDOMEqualsXMLFile(__FUNCTION__, $fd);
  }

  /**
  * @group TraversingFilter
  */
  public function testSliceByNegRange() {
    $fd = $this->getFixtureFromFile(__FUNCTION__);
    $fd ->find('//p')
      ->slice(1, -2)
      ->replaceAll('//div');
    $this->assertTrue($fd instanceof FluentDOM);
    $this->assertFluentDOMEqualsXMLFile(__FUNCTION__, $fd);
  }

  /**
  * @group TraversingFilter
  */
  public function testSliceToEnd() {
    $fd = $this->getFixtureFromFile(__FUNCTION__);
    $fd ->find('//p')
      ->slice(3)
      ->replaceAll('//div');
    $this->assertTrue($fd instanceof FluentDOM);
    $this->assertFluentDOMEqualsXMLFile(__FUNCTION__, $fd);
  }

  /*
  * Traversing - Finding
  */

  /**
  * @group TraversingFind
  */
  public function testAddElements() {
    $fd = $this->getFixtureFromFile(__FUNCTION__);
    $fd ->add(
        $fd->find('//div')
      )
      ->toggleClass('inB');
    $this->assertTrue($fd instanceof FluentDOM);
    $this->assertFluentDOMEqualsXMLFile(__FUNCTION__, $fd);
  }

  /**
  * @group TraversingFind
  */
  public function testAddFromExpression() {
    $fd = $this->getFixtureFromFile(__FUNCTION__);
    $fd ->add('//div')
      ->toggleClass('inB');
    $this->assertTrue($fd instanceof FluentDOM);
    $this->assertFluentDOMEqualsXMLFile(__FUNCTION__, $fd);
  }

  /**
  * @group TraversingFind
  */
  public function testAddInContext() {
    $fd = $this->getFixtureFromFile(__FUNCTION__);
    $fd ->find('//p')
      ->add('//p/b')
      ->toggleClass('inB');
    $this->assertTrue($fd instanceof FluentDOM);
    $this->assertFluentDOMEqualsXMLFile(__FUNCTION__, $fd);
  }

  /**
  * @group TraversingFind
  */
  public function testAddInvalidForeignNodes() {
    $fd = $this->getFixtureFromString(self::XML);
    $itemsFd = $this->getFixtureFromString(self::XML)->find('//item');
    try {
      $fd
        ->find('/items')
        ->add($itemsFd);
      $this->fail('An expected exception has not been raised.');
        $this->fail('An expected exception has not been raised.');
    } catch (OutOfBoundsException $expected) {
    }
  }

  /**
  * @group TraversingFind
  */
  public function testAddInvalidForeignNode() {
    $fd = $this->getFixtureFromString(self::XML);
    $itemsFd = $this->getFixtureFromString(self::XML)->find('//item');
    try {
      $fd
        ->find('/items')
        ->add($itemsFd[0]);
      $this->fail('An expected exception has not been raised.');
        $this->fail('An expected exception has not been raised.');
    } catch (OutOfBoundsException $expected) {
    }
  }

  /**
  * @group TraversingFind
  */
  public function testChildren() {
    $fd = $this->getFixtureFromFile(__FUNCTION__);
    $fd ->find('//div[@id = "container"]/p')
      ->children()
      ->toggleClass('child');
    $this->assertTrue($fd instanceof FluentDOM);
    $this->assertFluentDOMEqualsXMLFile(__FUNCTION__, $fd);
  }

  /**
  * @group TraversingFind
  */
  public function testChildrenExpression() {
    $fd = $this->getFixtureFromFile(__FUNCTION__);
    $fd ->find('//div[@id = "container"]/p')
      ->children('name() = "em"')
      ->toggleClass('child');
    $this->assertTrue($fd instanceof FluentDOM);
    $this->assertFluentDOMEqualsXMLFile(__FUNCTION__, $fd);
  }

  /**
  * @group TraversingFind
  */
  public function testFind() {
    $fd = $this->getFixtureFromString(self::XML)->find('/*');
    $this->assertEquals(1, $fd->length);
    $findFd = $fd->find('group/item');
    $this->assertEquals(3, $findFd->length);
    $this->assertTrue($findFd !== $fd);
  }

  /**
  * @group TraversingFind
  */
  public function testFindFromRootNode() {
    $fd = $this->getFixtureFromString(self::XML)->find('/*');
    $this->assertEquals(1, $fd->length);
    $findFd = $this->getFixtureFromString(self::XML)->find('/items');
    $this->assertEquals(1, $findFd->length);
    $this->assertTrue($findFd !== $fd);
  }

  /**
  * @group TraversingFind
  */
  public function testFindWithNamespaces() {
    $fd = $this->getFixtureFromFile(__FUNCTION__);
    $doc = $fd ->find('//_:entry');
    $this->assertEquals(25, $doc->length);
    $value = $fd ->find('//openSearch:totalResults')->text();
    $this->assertEquals(38, $value);
  }

  /**
  * @group TraversingFind
  */
  public function testNext() {
    $fd = $this->getFixtureFromFile(__FUNCTION__);
    $fd ->find('//button[@disabled]')
      ->next()
      ->text('This button is disabled.');
    $this->assertTrue($fd instanceof FluentDOM);
    $this->assertFluentDOMEqualsXMLFile(__FUNCTION__, $fd);
  }

  /**
  * @group TraversingFind
  */
  public function testNextAll() {
    $fd = $this->getFixtureFromFile(__FUNCTION__);
    $fd ->find('//div[position() = 1]')
      ->nextAll()
      ->addClass('after');
    $this->assertTrue($fd instanceof FluentDOM);
    $this->assertFluentDOMEqualsXMLFile(__FUNCTION__, $fd);
  }

  /**
  * @group TraversingFind
  */
  public function testParent() {
    $fd = $this->getFixtureFromFile(__FUNCTION__);
    $fd ->find('//body//*')
      ->each(
        create_function(
          '$node, $item',
          '$fd = new FluentDOM();
           $fd->load($node);
           $fd->prepend(
             $fd->document->createTextNode(
               $fd->parent()->item(0)->tagName." > "
             )
            );
          ')
      );
    $this->assertTrue($fd instanceof FluentDOM);
    $this->assertFluentDOMEqualsXMLFile(__FUNCTION__, $fd);
  }

  /**
  * @group TraversingFind
  */
  public function testParents() {
    $fd = $this->getFixtureFromFile(__FUNCTION__);
    $this->assertTrue($fd instanceof FluentDOM);
    $parents = $fd
      ->find('//b')
      ->parents()
      ->map(
          create_function('$node', 'return $node->tagName;')
        );
    $this->assertTrue(is_array($parents));
    $this->assertContains('span', $parents);
    $this->assertContains('p', $parents);
    $this->assertContains('div', $parents);
    $this->assertContains('body', $parents);
    $this->assertContains('html', $parents);
    $parents = implode(', ', $parents);
    $doc = $fd
      ->find('//b')
      ->append('<strong>'.htmlspecialchars($parents).'</strong>');
    $this->assertTrue($doc instanceof FluentDOM);
    $this->assertFluentDOMEqualsXMLFile(__FUNCTION__, $doc);
  }

  /**
  * @group TraversingFind
  */
  public function testPrev() {
    $fd = $this->getFixtureFromFile(__FUNCTION__);
    $fd ->find('//div[@id = "start"]')
      ->prev()
      ->addClass('before');
    $this->assertTrue($fd instanceof FluentDOM);
    $this->assertFluentDOMEqualsXMLFile(__FUNCTION__, $fd);
  }

  /**
  * @group TraversingFind
  */
  public function testPrevExpression() {
    $fd = $this->getFixtureFromFile(__FUNCTION__);
    $fd ->find('//div[@class = "here"]')
      ->prev()
      ->addClass('nextTest');
    $this->assertTrue($fd instanceof FluentDOM);
    $this->assertFluentDOMEqualsXMLFile(__FUNCTION__, $fd);
  }

  /**
  * @group TraversingFind
  */
  public function testPrevAll() {
    $fd = $this->getFixtureFromFile(__FUNCTION__);
    $fd ->find('//div[@id = "start"]')
      ->prev()
      ->addClass('before');
    $this->assertTrue($fd instanceof FluentDOM);
    $this->assertFluentDOMEqualsXMLFile(__FUNCTION__, $fd);
  }

  /**
  * @group TraversingFind
  */
  public function testPrevAllExpression() {
    $fd = $this->getFixtureFromFile(__FUNCTION__);
    $fd ->find('//div[@class= "here"]')
      ->prevAll('.//span')
      ->addClass('nextTest');
    $this->assertTrue($fd instanceof FluentDOM);
    $this->assertFluentDOMEqualsXMLFile(__FUNCTION__, $fd);
  }

  /**
  * @group TraversingFind
  */
  public function testSiblings() {
    $fd = $this->getFixtureFromFile(__FUNCTION__);
    $fd ->find('//li[@class = "hilite"]')
      ->siblings()
      ->addClass('before');
    $this->assertTrue($fd instanceof FluentDOM);
    $this->assertFluentDOMEqualsXMLFile(__FUNCTION__, $fd);
  }

  /**
  * @group TraversingFind
  */
  public function testClosest() {
    $attribute = $this->getFixtureFromString(self::XML)
      ->find('//item')
      ->closest('name() = "group"')
      ->attr("id");
    $this->assertEquals('1st', $attribute);
  }

  /**
  * @group TraversingFind
  */
  public function testClosestIsCurrentNode() {
    $attribute = $this->getFixtureFromString(self::XML)
      ->find('//item')
      ->closest('self::item[@index = "1"]')
      ->attr("index");
    $this->assertEquals('1', $attribute);
  }

  /*
  * Traversing - Chaining
  */

  /**
  * @group TraversingChain
  */
  public function testAndSelf() {
    $fd = $this->getFixtureFromString(self::XML)->find('/items')->find('.//item');
    $this->assertEquals(3, $fd->length);
    $andSelfFd = $fd->andSelf();
    $this->assertEquals(4, $andSelfFd->length);
    $this->assertTrue($andSelfFd !== $fd);
  }

  /**
  * @group TraversingChain
  */
  public function testEnd() {
    $fd = $this->getFixtureFromString(self::XML)->find('/items')->find('.//item');
    $this->assertEquals(3, $fd->length);
    $endFd = $fd->end();
    $this->assertEquals(1, $endFd->length);
    $this->assertTrue($endFd !== $fd);
    $endFdRoot = $endFd->end();
    $this->assertTrue($endFd !== $endFdRoot);
    $endFdRoot2 = $endFdRoot->end();
    $this->assertTrue($endFdRoot === $endFdRoot2);
  }

  /**
  * @group TraversingChain
  */
  public function testXmlRead() {
    $expect = '<item index="0">text1</item>'.
      '<item index="1">text2</item>'.
      '<item index="2">text3</item>';
    $fd = $this->getFixtureFromString(self::XML)->find('//group')->xml();
    $this->assertEquals($expect, $fd);
  }

  /**
  * @group TraversingChain
  */
  public function testXmlWrite() {
    $fd = $this->getFixtureFromFile(__FUNCTION__);
    $fd ->find('//p[position() = last()]')
      ->xml('<b>New</b>World');
    $this->assertTrue($fd instanceof FluentDOM);
    $this->assertFluentDOMEqualsXMLFile(__FUNCTION__, $fd);
  }

  /**
  * @group TraversingChain
  */
  public function testTextRead() {
    $expect = 'text1text2text3';
    $text = $this->getFixtureFromString(self::XML)->formatOutput()->find('//group')->text();
    $this->assertEquals($expect, $text);
  }

  /**
  * @group TraversingChain
  */
  public function testTextWrite() {
    $fd = $this->getFixtureFromString(self::XML)->find('//item');
    $this->assertEquals('text1', $fd[0]->textContent);
    $this->assertEquals('text2', $fd[1]->textContent);
    $textFd = $fd->text('changed');
    $this->assertEquals('changed', $fd[0]->textContent);
    $this->assertEquals('changed', $fd[1]->textContent);
    $this->assertTrue($fd === $textFd);
  }

  /*
  * Manipulation - Inserting Inside
  */

  /**
  * @group Manipulation
  */
  public function testAppend() {
    $fd = $this->getFixtureFromFile(__FUNCTION__);
    $fd->find('//p')
       ->append('<strong>Hello</strong>');
    $this->assertTrue($fd instanceof FluentDOM);
    $this->assertFluentDOMEqualsXMLFile(__FUNCTION__, $fd);
  }

  /**
  * @group Manipulation
  */
  public function testAppendDomelement() {
    $fd = new FluentDOM();
    $fd->append('<strong>Hello</strong>');
    $this->assertEquals('strong', $fd->find('/strong')->item(0)->nodeName);
  }

  /**
  * @group Manipulation
  */
  public function testAppendDomnodelist() {
    $fd = $this->getFixtureFromFile(__FUNCTION__);
    $items = $fd->find('//item');
    $this->assertTrue($fd instanceof FluentDOM);
    $doc = $fd
      ->find('//html/div')
      ->append($items);
    $this->assertTrue($doc instanceof FluentDOM);
    $this->assertFluentDOMEqualsXMLFile(__FUNCTION__, $fd);
  }

  /**
  * @group Manipulation
  */
  public function testAppendTo() {
    $fd = $this->getFixtureFromFile(__FUNCTION__);
    $fd ->find('//span')
      ->appendTo('//div[@id = "foo"]');
    $this->assertTrue($fd instanceof FluentDOM);
    $this->assertFluentDOMEqualsXMLFile(__FUNCTION__, $fd);
  }

  /**
  * @group Manipulation
  */
  public function testPrepend() {
    $fd = $this->getFixtureFromFile(__FUNCTION__);
    $fd ->find('//p')
      ->prepend('<strong>Hello</strong>');
    $this->assertTrue($fd instanceof FluentDOM);
    $this->assertFluentDOMEqualsXMLFile(__FUNCTION__, $fd);
  }

  /**
  * @group Manipulation
  */
  public function testPrependTo() {
    $fd = $this->getFixtureFromFile(__FUNCTION__);
    $fd ->find('//span')
      ->prependTo('//div[@id = "foo"]');
    $this->assertTrue($fd instanceof FluentDOM);
    $this->assertFluentDOMEqualsXMLFile(__FUNCTION__, $fd);
  }

  /*
  * Manipulation - Inserting Outside
  */

  /**
  * @group Manipulation
  */
  public function testAfter() {
    $fd = $this->getFixtureFromFile(__FUNCTION__);
    $fd ->formatOutput()
      ->find('//p')
      ->after('<b>Hello</b>')
      ->after(' World');
    $this->assertTrue($fd instanceof FluentDOM);
    $this->assertFluentDOMEqualsXMLFile(__FUNCTION__, $fd);
  }

  /**
  * @group Manipulation
  */
  public function testBefore() {
    $fd = $this->getFixtureFromFile(__FUNCTION__);
    $fd ->formatOutput()
      ->find('//p')
      ->before(' World')
      ->before('<b>Hello</b>');
    $this->assertTrue($fd instanceof FluentDOM);
    $this->assertFluentDOMEqualsXMLFile(__FUNCTION__, $fd);
  }

  /**
  * @group Manipulation
  */
  public function testInsertAfter() {
    $fd = $this->getFixtureFromFile(__FUNCTION__);
    $fd ->find('//p')
      ->insertAfter('//div[@id = "foo"]');
    $this->assertTrue($fd instanceof FluentDOM);
    $this->assertFluentDOMEqualsXMLFile(__FUNCTION__, $fd);
  }

  /**
  * @group Manipulation
  */
  public function testInsertBefore() {
    $fd = $this->getFixtureFromFile(__FUNCTION__);
    $fd ->find('//p')
      ->insertBefore('//div[@id = "foo"]');
    $this->assertTrue($fd instanceof FluentDOM);
    $this->assertFluentDOMEqualsXMLFile(__FUNCTION__, $fd);
  }

  /*
  * Manipulation - Inserting Around
  */

  /**
  * @group Manipulation
  */
  public function testWrap() {
    $fd = $this->getFixtureFromFile(__FUNCTION__);
    $fd ->find('//p')
      ->wrap('<div class="outer"><div class="inner"></div></div>');
    $this->assertTrue($fd instanceof FluentDOM);
    $this->assertFluentDOMEqualsXMLFile(__FUNCTION__, $fd);
  }

  /**
  * @group Manipulation
  */
  public function testWrapWithDomelement() {
    $fd = $this->getFixtureFromFile(__FUNCTION__);
    $dom = $fd->document;
    $div = $dom->createElement('div');
    $div->setAttribute('class', 'wrapper');
    $fd->find('//p')->wrap($div);
    $this->assertTrue($fd instanceof FluentDOM);
    $this->assertFluentDOMEqualsXMLFile(__FUNCTION__, $fd);
  }

  /**
  * @group Manipulation
  */
  public function testWrapWithDomnodelist() {
    $fd = $this->getFixtureFromFile(__FUNCTION__);
    $divs = $fd->xpath->query('//div[@class = "wrapper"]');
    $this->assertTrue($fd instanceof FluentDOM);
    $fd->find('//p')->wrap($divs);
    $this->assertFluentDOMEqualsXMLFile(__FUNCTION__, $fd);
  }

  /**
  * @group Manipulation
  */
  public function testWrapWithInvalidArgument() {
    try {
      $this->getFixtureFromString(self::XML)
        ->find('//item')
        ->wrap(NULL);
      $this->fail('An expected exception has not been raised.');
    } catch (InvalidArgumentException $expected) {
    }
  }

  /**
  * @group Manipulation
  */
  public function testWrapWithArray() {
    $fd = $this->getFixtureFromFile(__FUNCTION__);
    $dom = $fd->document;
    $divs[0] = $dom->createElement('div');
    $divs[0]->setAttribute('class', 'wrapper');
    $divs[1] = $dom->createElement('div');
    $fd->find('//p')->wrap($divs);
    $this->assertTrue($fd instanceof FluentDOM);
    $this->assertFluentDOMEqualsXMLFile(__FUNCTION__, $fd);
  }

  /**
  * @group Manipulation
  */
  public function testWrapAllSingle() {
    $fd = $this->getFixtureFromFile(__FUNCTION__);
    $fd ->find('//p')
      ->wrapAll('<div class="wrapper"/>');
    $this->assertTrue($fd instanceof FluentDOM);
    $this->assertFluentDOMEqualsXMLFile(__FUNCTION__, $fd);
  }

  /**
  * @group Manipulation
  */
  public function testWrapAllComplex() {
    $fd = $this->getFixtureFromFile(__FUNCTION__);
    $fd ->find('//p')
      ->wrapAll('<div class="wrapper"><div>INNER</div></div>');
    $this->assertTrue($fd instanceof FluentDOM);
    $this->assertFluentDOMEqualsXMLFile(__FUNCTION__, $fd);
  }

  /**
  * @group Manipulation
  */
  public function testWrapInner() {
    $fd = $this->getFixtureFromFile(__FUNCTION__);
    $fd ->find('//p')
      ->wrapInner('<b></b>');
    $this->assertTrue($fd instanceof FluentDOM);
    $this->assertFluentDOMEqualsXMLFile(__FUNCTION__, $fd);
  }

  /*
  * Manipulation - Replacing
  */

  /**
  * @group Manipulation
  */
  public function testReplaceWith() {
    $fd = $this->getFixtureFromFile(__FUNCTION__);
    $fd ->find('//p')
      ->replaceWith('<b>Paragraph. </b>');
    $this->assertTrue($fd instanceof FluentDOM);
    $this->assertFluentDOMEqualsXMLFile(__FUNCTION__, $fd);
  }

  /**
  * @group Manipulation
  */
  public function testReplaceAll() {
    $fd = $this->getFixtureFromFile(__FUNCTION__);
    $fd ->node('<b id="sample">Paragraph. </b>')
      ->replaceAll('//p');
    $this->assertTrue($fd instanceof FluentDOM);
    $this->assertFluentDOMEqualsXMLFile(__FUNCTION__, $fd);
  }

  /**
  * @group Manipulation
  */
  public function testReplaceAllWithNode() {
    $fd = $this->getFixtureFromFile(__FUNCTION__);
    $fd ->node('<b id="sample">Paragraph. </b>')
      ->replaceAll(
        $fd->find('//p')->item(1)
      );
    $this->assertTrue($fd instanceof FluentDOM);
    $this->assertFluentDOMEqualsXMLFile(__FUNCTION__, $fd);
  }

  /**
  * @group Manipulation
  */
  public function testReplaceAllWithInvalidArgument() {
    try {
      $this->getFixtureFromString(self::XML)
        ->node('<b id="sample">Paragraph. </b>')
        ->replaceAll(
          NULL
        );
      $this->fail('An expected exception has not been raised.');
    } catch (InvalidArgumentException $expected) {
    }
  }

  /*
  * Manipulation - Removing
  */

  /**
  * @group Manipulation
  */
  public function testEmpty() {
    $fd = $this->getFixtureFromFile(__FUNCTION__);
    $fd ->find('//p[@class = "first"]')
      ->empty();
    $this->assertTrue($fd instanceof FluentDOM);
    $this->assertFluentDOMEqualsXMLFile(__FUNCTION__, $fd);
  }

  /**
  * @group Manipulation
  */
  public function testRemove() {
    $fd = $this->getFixtureFromFile(__FUNCTION__);
    $fd ->find('//p[@class = "first"]')
      ->remove();
    $this->assertTrue($fd instanceof FluentDOM);
    $this->assertFluentDOMEqualsXMLFile(__FUNCTION__, $fd);
  }

  /**
  * @group Manipulation
  */
  public function testRemoveWithExpression() {
    $fd = $this->getFixtureFromFile(__FUNCTION__);
    $fd->find('//p')
       ->remove('@class = "first"');
    $this->assertTrue($fd instanceof FluentDOM);
    $this->assertFluentDOMEqualsXMLFile(__FUNCTION__, $fd);
  }

  /*
  * Manipulation - Copying
  */

  /**
  * @group Manipulation
  */
  public function testClone() {
    $fd = $this->getFixtureFromString(self::XML)->find('//item');
    $clonedNodes = $fd->clone();
    $this->assertTrue($fd instanceof FluentDOM);
    $this->assertTrue($clonedNodes instanceof FluentDOM);
    $this->assertTrue($fd[0] !== $clonedNodes[0]);
    $this->assertEquals($fd[0]->nodeName, $clonedNodes[0]->nodeName);
    $this->assertEquals($fd[1]->getAttribute('index'), $clonedNodes[1]->getAttribute('index'));
    $this->assertEquals(count($fd), count($clonedNodes));
  }


  /*
  * Attributes
  */

  /**
  * @group Attributes
  */
  public function testAttrRead() {
    $fd = $this->getFixtureFromString(self::XML)
      ->find('//group/item')
      ->attr('index');
    $this->assertEquals('0', $fd);
  }
  /**
  * @group Attributes
  */
  public function testAttrReadFromRoot() {
    $fd = $this->getFixtureFromString(self::XML);
    $this->assertEquals('1.0', $fd->find('/*')->attr('version'));
    $this->assertEquals('1.0', $fd->find('/items')->attr('version'));
    $this->assertEquals('1.0', $fd->find('//items')->attr('version'));
  }

  /**
  * @group Attributes
  */
  public function testAttrReadInvalid() {
    try {
      $this->getFixtureFromString(self::XML)
        ->find('//item')
        ->attr('');
      $this->fail('An expected exception has not been raised.');
    } catch (UnexpectedValueException $expected) {
    }
  }

  /**
  * @group Attributes
  */
  public function testAttrReadNoMatch() {
    $fd = $this->getFixtureFromString(self::XML)->attr('index');
    $this->assertTrue(empty($fd));
  }

  /**
  * @group Attributes
  */
  public function testAttrReadOnDomtext() {
    $fd = $this->getFixtureFromString(self::XML)
      ->find('//item/text()')
      ->attr('index');
    $this->assertTrue(empty($fd));
  }

  /**
  * @group Attributes
  */
  public function testAttrWrite() {
    $fd = $this->getFixtureFromString(self::XML)
      ->find('//group/item')
      ->attr('index', '15')
      ->attr('index');
    $this->assertEquals('15', $fd);
  }

  /**
  * @group Attributes
  * @dataProvider getInvalidAttributeNames
  */
  public function testAttrWriteWithInvalidNames($attrName) {
    try {
      $this->getFixtureFromString(self::XML)
        ->find('//item')
        ->attr($attrName, '');
      $this->fail('An expected exception has not been raised.');
    } catch (UnexpectedValueException $expected) {
    }
  }

  public static function getInvalidAttributeNames() {
    return array(
      array('1foo'),
      array('1bar:foo'),
      array('bar:1foo'),
      array('bar:foo<>'),
      array('bar:'),
      array(':foo')
    );
  }

  /**
  * @group Attributes
  * @dataProvider getValidAttributeNames
  */
  public function testAttrWriteWithValidNames($attrName) {
    $fd = $this->getFixtureFromString(self::XML)
      ->find('//item')
      ->attr($attrName, 'foo');
    $this->assertTrue($fd->item(0)->hasAttribute($attrName));
    $this->assertEquals('foo', $fd->item(0)->getAttribute($attrName));
  }

  public static function getValidAttributeNames() {
    return array(
      array('foo'),
      array('bar:foo')
    );
  }

  /**
  * @group Attributes
  */
  public function testAttrWriteWithArray() {
    $fd = $this->getFixtureFromString(self::XML)
      ->find('//group/item')
      ->attr(array('index' => '15', 'length' => '34', 'label' => 'box'));
    $this->assertEquals('15', $fd->attr('index'));
    $this->assertEquals('34', $fd->attr('length'));
    $this->assertEquals('box', $fd->attr('label'));
  }

  /**
  * @group Attributes
  */
  public function testAttrWriteWithCallback() {
    $fd = $this->getFixtureFromString(self::XML)
      ->find('//group/item')
      ->attr('callback', array($this, 'callbackForAttr'));
    $this->assertEquals($fd[0]->nodeName, $fd->attr('callback'));
  }

  /**
  * @group Attributes
  */
  public function testRemoveAttr() {
    $fd = $this->getFixtureFromFile(__FUNCTION__);
    $fd->find('//p')
       ->removeAttr('index');
    $this->assertTrue($fd instanceof FluentDOM);
    $this->assertFluentDOMEqualsXMLFile(__FUNCTION__, $fd);
  }

  /**
  * @group Attributes
  */
  public function testRemoveAttrWithInvalidParameter() {
    $fd = new FluentDOM();
    try {
      $fd->removeAttr(1);
      $this->fail('An expected exception has not been raised.');
    } catch (InvalidArgumentException $expected) {
    }
  }

  /**
  * @group Attributes
  */
  public function testRemoveAttrWithListParameter() {
    $fd = $this->getFixtureFromFile(__FUNCTION__);
    $fd->find('//p')
       ->removeAttr(array('index', 'style'));
    $this->assertTrue($fd instanceof FluentDOM);
    $this->assertFluentDOMEqualsXMLFile(__FUNCTION__, $fd);
  }

  /**
  * @group Attributes
  */
  public function testRemoveAttrWithAsteriskParameter() {
    $fd = $this->getFixtureFromFile(__FUNCTION__);
    $fd->find('//p')
       ->removeAttr('*');
    $this->assertTrue($fd instanceof FluentDOM);
    $this->assertFluentDOMEqualsXMLFile(__FUNCTION__, $fd);
  }

  /*
  * Attributes - Classes
  */

  /**
  * @group Attributes
  */
  public function testAddClass() {
    $fd = $this->getFixtureFromString(self::XML)->find('//html/div');
    $this->assertTrue($fd->hasClass('added') === FALSE);
    $fd->addClass('added');
    $this->assertTrue($fd->hasClass('added') === TRUE);
  }

  /**
  * @group Attributes
  */
  public function testHasClass() {
    $fd = $this->getFixtureFromString(self::XML)->find('//html/div');
    $this->assertTrue($fd->hasClass('test1') === TRUE);
    $this->assertTrue($fd->hasClass('unknown') === FALSE);
  }

  /**
  * @group Attributes
  */
  public function testRemoveClass() {
    $fd = $this->getFixtureFromString(self::XML)->find('//html/div');
    $this->assertEquals('test1 test2', $fd[0]->getAttribute('class'));
    $this->assertEquals('test2', $fd[1]->getAttribute('class'));
    $fd->removeClass('test2');
    $this->assertEquals('test1', $fd[0]->getAttribute('class'));
    $this->assertTrue($fd[1]->hasAttribute('class') === FALSE);
  }

  /**
  * @group Attributes
  */
  public function testToggleClass() {
    $fd = $this->getFixtureFromString(self::XML)->find('//html/div');
    $this->assertEquals('test1 test2', $fd[0]->getAttribute('class'));
    $this->assertEquals('test2', $fd[1]->getAttribute('class'));
    $fd->toggleClass('test1');
    $this->assertEquals('test2', $fd[0]->getAttribute('class'));
    $this->assertEquals('test2 test1', $fd[1]->getAttribute('class'));
  }


  /*
  * helper
  */

  /**
  * @uses testAttrWriteCallback
  */
  public function callbackForAttr($node, $index) {
    return $node->nodeName;
  }

  /**
  * @uses testNotWithFunction()
  */
  public function callbackTestNotWithFunction($node, $index) {
    return $node->nodeName != "items";
  }

  /**
  * @uses testFilterWithFunction()
  */
  public function callbackTestFilterWithFunction($node, $index) {
    return $node->nodeName == "items";
  }
}
?>