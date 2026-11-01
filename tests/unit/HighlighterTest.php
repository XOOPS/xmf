<?php
namespace Xmf\Test;

use Xmf\Highlighter;

class HighlighterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Highlighter
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new Highlighter;
        $this->assertInstanceOf('Xmf\Highlighter', $this->object);
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }

    public function testApply()
    {
        $output = Highlighter::apply('test', 'This test is OK.');
        $this->assertEquals('This <mark>test</mark> is OK.', $output);

        $output = Highlighter::apply(array('test','ok'), 'This test is OK.', '<i>', '</i>');
        $this->assertEquals('This <i>test</i> is <i>OK</i>.', $output);

        $output = Highlighter::apply('test    ok', 'This test is OK.', '<i>', '</i>');
        $this->assertEquals('This <i>test</i> is <i>OK</i>.', $output);

        $output = Highlighter::apply(array('test','ok'), 'This test <test>is</test> OK.', '<i>', '</i>');
        $this->assertEquals('This <i>test</i> <test>is</test> <i>OK</i>.', $output);
    }

    public function testQuotedGreaterThanDoesNotEndTheTag()
    {
        $body = '<a title="x>y">y</a>';
        $this->assertSame('<a title="x>y"><mark>y</mark></a>', Highlighter::apply('y', $body));

        // a term spanning the quote must not close the attribute and expose its value as markup
        $body = '<a title="x>y" data-z=\'<img src=x onerror=alert(1)>\'>t</a>';
        $this->assertSame($body, Highlighter::apply('y"', $body));
    }

    public function testCommentsAndDeclarationsAreNotHighlighted()
    {
        $body = '<!DOCTYPE html><!-- foo -- a > b -->foo';
        $this->assertSame('<!DOCTYPE html><!-- foo -- a > b --><mark>foo</mark>', Highlighter::apply('foo', $body));
        $this->assertSame($body, Highlighter::apply('--', $body));
    }

    public function testUnclosedQuoteStopsHighlighting()
    {
        $body = 'y <a title="x>y y';
        $this->assertSame('<mark>y</mark> <a title="x>y y', Highlighter::apply('y', $body));
    }

    public function testEmptyTermsAreSkipped()
    {
        $this->assertSame('a <mark>foo</mark> b', Highlighter::apply(' foo', 'a foo b'));
        $this->assertSame('a <mark>foo</mark> b', Highlighter::apply('foo ', 'a foo b'));
        $this->assertSame('a foo b', Highlighter::apply('', 'a foo b'));
        $this->assertSame('a <mark>foo</mark> b', Highlighter::apply(array('', 'foo'), 'a foo b'));
    }

    public function testZeroIsAValidTerm()
    {
        $this->assertSame('page <mark>0</mark>', Highlighter::apply('0', 'page 0'));
    }
}
