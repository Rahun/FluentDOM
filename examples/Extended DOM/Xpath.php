<?php
require_once(__DIR__.'/../../vendor/autoload.php');

/*
 * FluentDOM\Xpath::firstOf() returns the first node
 * from a location path. It allows you to fetch a single node
 * result as a node (or null).
 */
$dom = new FluentDOM\Document();
$dom->loadXml('<xml>Hello World</xml>');
$xpath = new FluentDOM\Xpath($dom);
echo $xpath->firstOf('//xml');

/*
 * FluentDOM\xpath::quote() quotes a value according to the Xpath 1.0
 * rules. Because here is no escaping, it will use concat() if
 * the string contains both types of quote characters.
 */
$value = "World";
$dom = new FluentDOM\Document();
$dom->loadXml('<xml>Hello World</xml>');
$xpath = new FluentDOM\Xpath($dom);
echo $xpath->evaluate(
  'string(//xml[contains(., '.$xpath->quote($value).')])'
);