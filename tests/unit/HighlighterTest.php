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

    public function testRawTextElementsAreMarkup()
    {
        // script text that looks like a comment opener must not swallow the next tag
        $body = '<script type="application/json">"<!--"</script>'
            . '<a title="-->y" data-z=\'<img src=x onerror=alert(1)>\'>t</a>';
        $this->assertSame($body, Highlighter::apply('y"', $body));
        $this->assertSame(
            '<textarea><b>foo</textarea> <mark>foo</mark>',
            Highlighter::apply('foo', '<textarea><b>foo</textarea> foo')
        );
        $this->assertSame(
            '<style>p{content:"foo"}</style ><mark>foo</mark>',
            Highlighter::apply('foo', '<style>p{content:"foo"}</style >foo')
        );
        $this->assertSame('<script>foo', Highlighter::apply('foo', '<script>foo'));
        $this->assertSame('<plaintext>foo</plaintext>foo', Highlighter::apply('foo', '<plaintext>foo</plaintext>foo'));
    }

    public function testQuotesOpenValuesOnlyAfterEquals()
    {
        // the quote in the unquoted value y' is literal; title='foo>bar' stays one value
        $body = '<a x=y\'><a title=\'foo>bar\' data-z="<img src=x onerror=alert(1)>">t</a>';
        $this->assertSame($body, Highlighter::apply("bar'", $body));
        $this->assertSame('<a b\'c>x <mark>foo</mark>', Highlighter::apply('foo', '<a b\'c>x foo'));
        $this->assertSame('<a href = "x>foo">t</a>', Highlighter::apply('foo', '<a href = "x>foo">t</a>'));
    }

    public function testCommentEdgeCases()
    {
        $this->assertSame('<!--><mark>foo</mark>', Highlighter::apply('foo', '<!-->foo'));
        $this->assertSame('<!---><mark>foo</mark>', Highlighter::apply('foo', '<!--->foo'));
        $this->assertSame('</3 x><mark>foo</mark>', Highlighter::apply('foo', '</3 x>foo'));
        $this->assertSame('<!DOCTYPE html><mark>foo</mark>', Highlighter::apply('foo', '<!DOCTYPE html>foo'));
        // a ">" in a quoted identifier ends the DOCTYPE as well
        $this->assertSame('<!DOCTYPE html SYSTEM "x><mark>foo</mark>"', Highlighter::apply('foo', '<!DOCTYPE html SYSTEM "x>foo"'));
    }

    public function testRepeatedTermsWrapOnce()
    {
        $this->assertSame('a <mark>foo</mark> b', Highlighter::apply('foo FOO foo Foo', 'a foo b'));
        $words = "\u{e4} \u{c4}";
        $this->assertSame("<mark>\u{e4}</mark> <mark>\u{c4}</mark>", Highlighter::apply($words, $words));
        // a body that is not UTF-8 is matched bytewise, where the two differ: both stay terms
        $this->assertStringContainsString("<mark>\u{c4}</mark>", Highlighter::apply($words, $words . "\xff"));
    }

    public function testCaseVariantsWrapOnceWhereMatchedAsUnicode()
    {
        // the invalid byte is in markup only, so the text is matched with /iu
        $this->assertSame("<a title=\"\xff\"><mark>\u{e4}</mark></a>", Highlighter::apply(["\u{e4}", "\u{c4}"], "<a title=\"\xff\">\u{e4}</a>"));
        // in valid UTF-8 a variant wraps nothing new and takes no place in the term limit
        $words = array();
        for ($i = 0; $i < 40; $i++) {
            // äääääää with the letters for the set bits of $i upper-cased: 40 distinct variants
            $words[] = implode('', array_map(static fn ($b) => ($i >> $b) & 1 ? "\u{c4}" : "\u{e4}", range(0, 6)));
        }
        $words[] = 'foo';
        $this->assertSame('<mark>' . $words[0] . '</mark> <mark>foo</mark>', Highlighter::apply($words, $words[0] . ' foo'));
    }

    /**
     * Bodies whose markup a browser tokenizes in a state readMarkup() fails closed on:
     * highlighting y" must not insert a wrapper that exposes the inert image
     */
    public static function unsupportedStateProvider(): array
    {
        $attack = '<a title="-->y" data-z=\'<img src=x onerror=alert(1)>\'>t</a>';
        return [
            'svg style' => ['<svg><style><a title="</style>y" data-z=\'<img src=x onerror=alert(1)>\'>t</a></style></svg>'],
            'svg self-closed script' => ['<svg><script /></svg><a title="</script>y" data-z=\'<img src=x onerror=alert(1)>\'>t</a>'],
            'svg cdata' => ['<svg><![CDATA[ > <a title=\']]><b title="\'>y" data-z=\'<img src=x onerror=alert(1)>\'>'],
            'script double escape' => ['<script type="application/json"><!--<script></script><!--</script>' . $attack],
            // with scripting off, the noscript content is tags and title runs past "</noscript>"
            'noscript markup' => ['<noscript><a title="</noscript>y" data-z=\'<img src=x onerror=alert(1)>\'>t</a></noscript>'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('unsupportedStateProvider')]
    public function testUnsupportedTokenizerStatesStopHighlighting(string $body)
    {
        $this->assertSame($body, Highlighter::apply('y"', $body));
    }

    public function testCommentEndsAtBangClose()
    {
        $body = '<!--x--!><a title="-->y" data-z=\'<img src=x onerror=alert(1)>\'>t</a>';
        $this->assertSame($body, Highlighter::apply('y"', $body));
        $this->assertSame('<!--x--!><mark>foo</mark>', Highlighter::apply('foo', '<!--x--!>foo'));
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
        // "<!-->" is a whole comment, so the y after it is text
        $this->assertSame('<!-->[y]-->[y]', Highlighter::apply('y', '<!-->y-->y', '[', ']'));
        $this->assertSame('<!-- y -->[y]', Highlighter::apply('y', '<!-- y -->y', '[', ']'));
        $this->assertSame('[y] <b', Highlighter::apply('y', 'y <b', '[', ']'));
    }
}
