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

    /**
     * Tag names that are empty or do not start with a letter are rejected by the
     * tag-name regex, so they are stripped. Locks in the removal of the redundant
     * "!$tagName" test in filterTags().
     */
    public function testCleanStripsMalformedTagNames()
    {
        // "<>" is not recognised as a tag at all, so it is left untouched.
        $this->assertEquals('<>', FilterInput::clean('<>', 'string'));
        // A digit-led tag name fails the regex and is stripped.
        $this->assertEquals('', FilterInput::clean('<0>', 'string'));
        $this->assertEquals('hello', FilterInput::clean('<0>hello', 'string'));
        $this->assertEquals('text', FilterInput::clean('<0img src=x>text', 'string'));
        // A well-formed tag is still stripped, content preserved.
        $this->assertEquals('keep', FilterInput::clean('<img src=x>keep', 'string'));
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

    public static function getTestForCleanVarType()
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

    public function testWeburlRejectsProtocolRelativeUrl()
    {
        $result = FilterInput::clean('//evil.example/path', 'WEBURL');
        $this->assertSame('', $result);
    }

    public function testWeburlAllowsHttpUrl()
    {
        $result = FilterInput::clean('http://example.com/page', 'WEBURL');
        $this->assertSame('http://example.com/page', $result);
    }

    public function testWeburlAllowsRelativeUrl()
    {
        $result = FilterInput::clean('/local/path', 'WEBURL');
        $this->assertSame('/local/path', $result);
    }

    public function testWeburlRejectsProtocolRelativeUrlWithLeadingWhitespace()
    {
        $result = FilterInput::clean('  //evil.example/path', 'WEBURL');
        $this->assertSame('', $result);
    }

    public function testWeburlRejectsJavascriptScheme()
    {
        $result = FilterInput::clean('javascript:alert(1)', 'WEBURL');
        $this->assertSame('', $result);
    }

    /**
     * @dataProvider entityHiddenSchemeProvider
     */
    public function testWeburlRejectsEntityHiddenScheme(string $url)
    {
        $this->assertSame('', FilterInput::clean($url, 'WEBURL'));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function entityHiddenSchemeProvider(): array
    {
        return array(
            'html5 named colon' => array('javascript&colon;alert(1)'),
            'html5 named tab' => array('java&Tab;script:alert(1)'),
            'numeric without semicolon' => array('javascript&#0058alert(1)'),
            'entity in host part' => array('page&colon;x'),
            'encoded second slash' => array('/&sol;evil.example'),
            'encoded backslash' => array('/&bsol;evil.example'),
            'numeric slash without semicolon' => array('/&#47evil.example'),
            'tab between slashes' => array('/&Tab;/evil.example'),
            'newline between slashes' => array('/&NewLine;/evil.example'),
            'https with encoded second slash' => array('https:/&sol;evil.example'),
            'http with one slash' => array('http:/evil.example'),
            'https without slashes' => array('https:evil.example'),
            'entity slash before userinfo' => array('https://evil.example&sol;@good.example'),
            'entity hash before userinfo' => array('https://evil.example&num;@good.example'),
            'double-encoded slash before userinfo' => array('https://evil.example&amp;sol;@good.example'),
            'entity tab in host' => array('https://evil&Tab;.example'),
            'numeric slash in userinfo' => array('https://good&#47@evil.example'),
            'percent-encoded host' => array('https://%65vil.example'),
            'javascript read as host:port' => array('javascript:1/alert(1)'),
            'data read as host:port' => array('data:80/text/html,test'),
            'http read as host:port' => array('http:80/evil'),
            'uppercase http scheme' => array('HTTP://example.com/'),
            'relative path parse_url reads as host:port' => array('good.example/a:80/x'),
        );
    }

    public function testWeburlKeepsAmpersandsAfterPathOrQueryStart()
    {
        $this->assertSame('https://example.com/a?b=1&c=2', FilterInput::clean('https://example.com/a?b=1&c=2', 'WEBURL'));
        $this->assertSame('page.php?a=1&colon=2', FilterInput::clean('page.php?a=1&colon=2', 'WEBURL'));
        $this->assertSame('/p&q', FilterInput::clean('/p&q', 'WEBURL'));
        $this->assertSame('#a&b', FilterInput::clean('#a&b', 'WEBURL'));
        $this->assertSame('https://good.example/a&sol;b?x=%41&y=1', FilterInput::clean('https://good.example/a&sol;b?x=%41&y=1', 'WEBURL'));
        $this->assertSame('a%20b.html', FilterInput::clean('a%20b.html', 'WEBURL'));
        $this->assertSame('page.php?time=12:30', FilterInput::clean('page.php?time=12:30', 'WEBURL'));
    }

    /**
     * @dataProvider getTestForCleanVarType
     */
    public function testCleanVarTypes($value, $type, $expected)
    {
        $this->assertSame($expected, $this->object->cleanVar($value, $type));
    }

    public function testHexEntityDecode()
    {
        // &#x41; = 'A', &#x42; = 'B', &#x43; = 'C'
        $input = '&#x41;&#x42;&#x43;';
        $result = FilterInput::clean($input, 'string');
        $this->assertStringContainsString('ABC', $result);
    }

    public function testDecimalEntityDecode()
    {
        // &#65; = 'A', &#66; = 'B'
        $input = '&#65;&#66;';
        $result = FilterInput::clean($input, 'string');
        $this->assertStringContainsString('AB', $result);
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

    public function testNestedArraysAreFiltered()
    {
        $input = array('a' => '<script>x</script>', 'child' => array('x' => '<script>y</script>', 'n' => 5));
        $result = $this->object->cleanVar($input, 'array');
        $this->assertSame(array('a' => 'x', 'child' => array('x' => 'y', 'n' => 5)), $result);
    }

    public function testArraysNestedTooDeeplyAreDropped()
    {
        $deep = '<b>leaf</b>';
        for ($i = 0; $i < 40; $i++) {
            $deep = array($deep);
        }
        $result = $this->object->cleanVar(array('ok' => '<b>v</b>', 'deep' => $deep), 'array');
        $this->assertSame('v', $result['ok']);
        $this->assertStringNotContainsString('<b>', serialize($result));
        $this->assertLessThan(33, substr_count(serialize($result), 'a:'));
    }

    public function testSubclassPolicyDoesNotLeakIntoBaseClean()
    {
        $this->assertSame('<b>x</b>', PermissiveFilterInput::clean('<b>x</b>'));
        $this->assertSame('x', FilterInput::clean('<b>x</b>'));
    }

    public function testWebUrlRejectsBackslashForms()
    {
        foreach (array('/\\evil.example', '\\\\evil.example', '\\/evil.example', 'https://ok.example/a\\b') as $url) {
            $this->assertSame('', FilterInput::clean($url, 'weburl'), $url);
        }
        $this->assertSame('/local/page', FilterInput::clean('/local/page', 'weburl'));
    }

    public function testWebUrlRejectsUnparseableValues()
    {
        $this->assertSame('', FilterInput::clean('javascript://example.com:99999/%0Aalert(1)', 'weburl'));
        $this->assertSame('https://example.com/a', FilterInput::clean('https://example.com/a', 'weburl'));
    }
}

class PermissiveFilterInput extends FilterInput
{
    protected function __construct()
    {
        parent::__construct(array('b'));
    }
}
