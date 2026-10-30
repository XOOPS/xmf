<?php
namespace Xmf\Test;

use PHPUnit\Framework\Attributes\DataProvider;
use Xmf\FilterInput;

class FilterInputTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var FilterInput
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = FilterInput::getInstance();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }

    public function testGetInstance()
    {
        $this->assertInstanceOf('\Xmf\FilterInput', $this->object);

        $instance = FilterInput::getInstance();
        $this->assertSame($instance, $this->object);

        $instance = FilterInput::getInstance(array(), array(), 0, 0, 0);
        $this->assertNotSame($instance, $this->object);
    }

    public function testProcess()
    {
        $input = 'Lorem ipsum </i><script>alert();</script>';
        $expected = 'Lorem ipsum alert();';
        $this->assertEquals($expected, $this->object->process($input));

        $input = 'Lorem ipsum';
        $this->assertEquals($input, $this->object->process($input));
    }

    public function testClean()
    {
        $input = 'Lorem ipsum </i><script>alert();</script>';
        $expected = 'Lorem ipsum alert();';
        $this->assertEquals($expected, FilterInput::clean($input, 'string'));

        $input = 'Lorem ipsum &#x3C;&#x73;&#x63;&#x72;&#x69;&#x70;&#x74;&#x3E;&#x61;&#x6C;&#x65;&#x72;&#x74;&#x28;&#x29;&#x3B;&#x3C;&#x2F;&#x73;&#x63;&#x72;&#x69;&#x70;&#x74;&#x3E;';
        $expected = 'Lorem ipsum alert();';
        $this->assertEquals($expected, FilterInput::clean($input, 'string'), FilterInput::clean($input, 'string'));

        $input = 'Lorem ipsum';
        $expected = $input;
        $this->assertEquals($expected, FilterInput::clean($input, 'string'));
    }

    public function testCleanVarDefault()
    {
        $filter = FilterInput::getInstance();
        $safeTest = '<p>This is a <em>simple</em> test.</p>';
        $this->assertEquals('This is a simple test.', $filter->cleanVar($safeTest));
    }

    public function testCleanVarFilter()
    {
        $filter = FilterInput::getInstance(array(), array(), 1, 1);

        $safeTest = '<p>This is a <em>simple</em> test.</p>';
        $this->assertEquals($safeTest, $filter->cleanVar($safeTest));
    }

    public function testCleanVarFilterXss()
    {
        $filter = FilterInput::getInstance(array(), array(), 1, 1);

        $xssTest = '<p>This is a <em>xss</em> <script>alert();</script> test.</p>';
        $xssTestExpect = '<p>This is a <em>xss</em> alert(); test.</p>';
        $this->assertEquals($xssTestExpect, $filter->cleanVar($xssTest));
    }

    public function getTestForCleanVarType()
    {
        return array(
            array('100', 'int', 100),
            array('100', 'INTEGER', 100),
            array('55.1', 'FLOAT', 55.1),
            array('55.1', 'DOUBLE', 55.1),
            array('1', 'BOOL', true),
            array('0', 'BOOLEAN', false),
            array('Value', 'WORD', 'Value'),
            array('Alpha99', 'ALPHANUM', 'Alpha99'),
            array('Alpha99', 'ALNUM', 'Alpha99'),
            array('value', 'ARRAY', array('value')),
//          ['value', 'type', 'expected'],
        );
    }

    /**
     * @dataProvider getTestForCleanVarType
     */
    public function testCleanVarTypes($value, $type, $expected)
    {
        $this->assertSame($expected, $this->object->cleanVar($value, $type));
    }

    public function testEmptyTagIsKeptWithoutGrowing()
    {
        // "<>" stays as text; it used to re-append the remainder on every pass, so remove() never settled
        $this->assertSame('a<>b', FilterInput::clean('a<>b', 'string'));
        $this->assertSame('x<> y<>', FilterInput::clean('x<> y<>', 'string'));
        $this->assertSame('<>keep', FilterInput::clean('<><b>keep</b>', 'string'));
    }

    public function testIncompleteTagDoesNotSurviveAsTag()
    {
        // text before a nested or missing ">" is kept, but without a "<" that would open a tag
        $this->assertSame('img src="<>" onerror=alert(1)>', FilterInput::clean('<img src="<>" onerror=alert(1)>', 'string'));
        $this->assertSame('img src=x onerror=alert(1) ', FilterInput::clean('<img src=x onerror=alert(1) <b>', 'string'));
        $this->assertSame('ximg src=x onerror=alert(1) ', FilterInput::clean('x<img src=x onerror=alert(1) ', 'string'));
        $this->assertSame('<>', FilterInput::clean('<><script>', 'string'));
        // a "<" that cannot open a tag stays
        $this->assertSame('a < b', FilterInput::clean('a < b', 'string'));
    }

    public function testAllowedHtmlBareTagTerminates()
    {
        // "/" of the reformatted "<a />" used to pass the attribute-name check and grow on every pass
        $filter = FilterInput::getInstance([], [], 1, 1);
        $this->assertSame('<a />', $filter->cleanVar('<a>', 'html'));
        $this->assertSame('<a href="x">t</a>', $filter->cleanVar('<a href="x" onclick="y">t</a>', 'html'));
    }

    public function testAllowedHtmlDropsEventHandlersInAnyCase()
    {
        $filter = FilterInput::getInstance([], [], 1, 1);
        $this->assertSame('<img src="x" />', $filter->cleanVar('<img src=x ONERROR=alert(1)>', 'html'));
    }

    public function testAllowedHtmlDropsEntityEncodedScriptUrls()
    {
        // the scheme check reads the value as a browser decodes it
        $filter = FilterInput::getInstance([], [], 1, 1);
        $this->assertSame('<a>x</a>', $filter->cleanVar('<a href="javascript&colon;alert(1)">x</a>', 'html'));
        $this->assertSame('<a>x</a>', $filter->cleanVar('<a href="java&Tab;script:alert(1)">x</a>', 'html'));
        $this->assertSame('<a href="https://example.com/recipes">x</a>', $filter->cleanVar('<a href="https://example.com/recipes">x</a>', 'html'));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function allowedTagShapesProvider(): array
    {
        // outputs of the substr-based filterTags(), kept by the offset-based rewrite
        return [
            'quoted value with spaces' => ['<b title="a b c">x</b>', '<b title="abc">x</b>'],
            'quoted then bare value'   => ['<img src="a b" title=c>', '<img src="ab" title="c" />'],
            'two quoted values'        => ['<a title="a b" href="c d">x</a>', '<a title="ab" href="cd">x</a>'],
            'attribute "0"'            => ['<b 0 title="x">y</b>', '<b>y</b>'],
            'double space'             => ['<b  title="x">y</b>', '<b title="x">y</b>'],
            'unclosed single tag'      => ['<img src=x>', '<img src="x" />'],
            'close tag only earlier'   => ['<b>x</b><img src=y><b>z', '<b>x</b><img src="y" /><b />z'],
            'nested opener'            => ['a<<b>x</b>', 'a<<b>x</b>'],
            'adjacent attributes'      => ['<b title="x"y="z">q</b>', '<b title="x">q</b>'],
            'close name other case'    => ['<b>x</B>', '<b />x</B>'],
        ];
    }

    /**
     * @dataProvider allowedTagShapesProvider
     */
    #[DataProvider('allowedTagShapesProvider')]
    public function testAllowedTagShapes(string $input, string $expected)
    {
        $filter = FilterInput::getInstance(['b', 'img', 'a'], ['title', 'src', 'href']);
        $this->assertSame($expected, $filter->process($input));
    }

    public function testLargeInputIsFilteredInLinearTime()
    {
        // filterTags() re-copied the remaining text for every tag and attribute; ~1 MB took minutes
        $filter = FilterInput::getInstance(['b']);
        $tags = str_repeat('<b>x</b><u>y</u> ', 40000);
        $attrs = '<b' . str_repeat(' t="x y"', 40000) . '>z</b>';
        $start = hrtime(true);
        $this->assertSame(str_repeat('<b>x</b>y ', 40000), $filter->process($tags));
        $this->assertSame('<b>z</b>', $filter->process($attrs));
        $this->assertLessThan(5000, (hrtime(true) - $start) / 1e6, 'filtering ~1 MB took over 5 s');
    }

    public function testManyDistinctUnclosedTagsAreFilteredInLinearTime()
    {
        // blacklist mode searched the rest of the input once per distinct unclosed name; ~1 MB took minutes
        $filter = FilterInput::getInstance(['script'], [], 1, 1);
        // a later close tag whose name starts with the tag name keeps it open, as before
        $this->assertSame('<a>y</abbr><p1 />', $filter->process('<a>y</abbr><p1>'));
        $input = '';
        $expected = '';
        for ($i = 0; $i < 100000; ++$i) {
            $input .= '<t' . $i . '>';
            $expected .= '<t' . $i . ' />';
        }
        $start = hrtime(true);
        $this->assertSame($expected, $filter->process($input));
        // close tags far behind their openers use up the search budget; later names count as unclosed
        $farClosed = $filter->process($input . str_replace('<', '</', $input));
        $this->assertIsString($farClosed);
        $this->assertStringStartsWith('<t0>', $farClosed);
        $this->assertStringContainsString('<t99999 />', $farClosed);
        // Xdebug (the coverage job) slows this loop over tenfold; the other jobs keep the bound
        if (!extension_loaded('xdebug')) {
            $this->assertLessThan(5000, (hrtime(true) - $start) / 1e6, 'filtering ~2 MB took over 5 s');
        }
    }

    public function testRemoveDropsAValueThatNeverSettles()
    {
        // a filterTags() that always changes its output must not loop without bound
        $filter = new class () extends FilterInput {
            public function __construct()
            {
                parent::__construct();
            }

            protected function filterTags($source)
            {
                return $source . 'x';
            }

            public function runRemove($source)
            {
                return $this->remove($source);
            }
        };
        $this->assertSame('', $filter->runRemove('a'));
    }

    public function testNamesWithATrailingNewlineAreRejected()
    {
        // "$" also matches before a final "\n", so "script\n" passed the name check and missed the blacklist
        $filter = FilterInput::getInstance([], [], 1, 1);
        $this->assertSame('alert(1)', $filter->cleanVar("<script\n>alert(1)</script\n>", 'html'));
        $this->assertSame('<form />', $filter->cleanVar("<form action\n=\"https://evil.example\">", 'html'));
        $this->assertStringNotContainsString("\n", FilterInput::clean("https://example.com/a\n", 'weburl'));
    }
}
