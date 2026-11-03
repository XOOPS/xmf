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

    public function testProcessingInstructionsAreNotHighlighted()
    {
        // a browser reads "<?" up to the first ">" as a comment: a mark inside would close it early
        $body = '<?x foo <img src=x onerror=alert(1)//>foo';
        $this->assertSame('<?x foo <img src=x onerror=alert(1)//><mark>foo</mark>', Highlighter::apply('foo', $body));
        $this->assertSame('<?x "foo>"', Highlighter::apply('foo', '<?x "foo>"'));
        $this->assertSame('<?x foo', Highlighter::apply('foo', '<?x foo'));
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

    public function testRepeatedTermsWrapOnce()
    {
        $this->assertSame('a <mark>foo</mark> b', Highlighter::apply('foo FOO foo Foo', 'a foo b'));
        $this->assertSame("<mark>\u{e4}</mark> <mark>\u{c4}</mark>", Highlighter::apply("\u{e4} \u{c4}", "\u{e4} \u{c4}"));
        // a body that is not UTF-8 is matched bytewise, where the two differ: both stay terms
        $this->assertStringContainsString("<mark>\u{c4}</mark>", Highlighter::apply("\u{e4} \u{c4}", "\u{e4} \u{c4}\xff"));
    }

    public function testTermCountIsBounded()
    {
        $words = array();
        for ($i = 0; $i < 32; $i++) {
            $words[] = 'absent' . $i;
        }
        $words[] = 'foo';
        $this->assertSame('a foo b', Highlighter::apply($words, 'a foo b'));
        array_shift($words);
        $this->assertSame('a <mark>foo</mark> b', Highlighter::apply($words, 'a foo b'));
    }

    public function testZeroIsAValidTerm()
    {
        $this->assertSame('page <mark>0</mark>', Highlighter::apply('0', 'page 0'));
    }

    public function testLongBodiesAreHighlighted()
    {
        // the old splitter exhausted the PCRE JIT stack on ~40 KB of text and returned null
        $body = str_repeat('foo bar ', 20000);
        $this->assertSame(str_repeat('<mark>foo</mark> bar ', 20000), Highlighter::apply('foo', $body));

        // a comment too long for a lazy regex still ends at its "-->"
        $comment = '<!--' . str_repeat('foo -', 300000) . '-->';
        $this->assertSame($comment . ' <mark>foo</mark>', Highlighter::apply('foo', $comment . ' foo'));
    }

    public function testTermsMatchLiterallyAndCaseInsensitively()
    {
        $this->assertSame('[Äpfel] und [ÄPFEL]', Highlighter::apply('äpfel', 'Äpfel und ÄPFEL', '[', ']'));
        $this->assertSame('[a.b] axb [A.B]', Highlighter::apply('a.b', 'a.b axb A.B', '[', ']'));
        $this->assertSame('cost <i>$1</i> \1', Highlighter::apply('$1', 'cost $1 \1', '<i>', '</i>'));
        $this->assertSame('[aa][aa]a', Highlighter::apply('aa', 'aaaaa', '[', ']'));
    }

    public function testInvalidUtf8IsKeptByteForByte()
    {
        $this->assertSame("\xFF[ab] [AB]", Highlighter::apply('ab', "\xFFab AB", '[', ']'));
        // in such text only ASCII letters match in either case
        $this->assertSame("\xFFÄpfel [äpfel]", Highlighter::apply('äpfel', "\xFFÄpfel äpfel", '[', ']'));
        // invalid bytes inside a tag do not change where the tag ends
        $this->assertSame("<a title=\"\xFFab\">[ab]</a>", Highlighter::apply('ab', "<a title=\"\xFFab\">ab</a>", '[', ']'));

        // a term that is not valid UTF-8, or too long to compile, must not raise a PCRE warning
        $warnings = [];
        set_error_handler(static function (int $errno, string $errstr) use (&$warnings): bool {
            $warnings[] = $errstr;
            return true;
        });
        try {
            $this->assertSame("[\xFF]ab", Highlighter::apply(["\xFF"], "\xFFab", '[', ']'));
            $long = str_repeat("\u{1F600}", 20000);
            $this->assertSame("x[$long]", Highlighter::apply([$long], "x$long", '[', ']'));
            // the Kelvin sign matches "K" only through the unicode pattern, used up to 8,192 bytes
            $atLimit = str_repeat('a', 8189);
            $this->assertSame("[{$atLimit}K]", Highlighter::apply([$atLimit . "\u{212A}"], $atLimit . 'K', '[', ']'));
            $overLimit = str_repeat('a', 8190);
            $this->assertSame($overLimit . 'K', Highlighter::apply([$overLimit . "\u{212A}"], $overLimit . 'K', '[', ']'));
        } finally {
            restore_error_handler();
        }
        $this->assertSame([], $warnings);
    }

    public function testQuotesAndCommentsBoundMarkup()
    {
        $this->assertSame("<a title='x\">y'>[y]</a>", Highlighter::apply('y', "<a title='x\">y'>y</a>", '[', ']'));
        $this->assertSame('<?x y>[y]', Highlighter::apply('y', '<?x y>y', '[', ']'));
        $this->assertSame('<!-->y-->[y]', Highlighter::apply('y', '<!-->y-->y', '[', ']'));
        $this->assertSame('[y] <b', Highlighter::apply('y', 'y <b', '[', ']'));
    }
}
