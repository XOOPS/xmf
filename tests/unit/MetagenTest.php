<?php
namespace Xmf\Test;

use Xmf\Metagen;

class MetagenTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Metagen
     */
    protected $object;

    // a known block of text used in some tests
    const DOI_TEXT = <<<EOT
When in the Course of human events, it becomes necessary for one people to dissolve
the political bands which have connected them with another, and to assume among the
powers of the earth, the separate and equal station to which the Laws of Nature and
of Nature's God entitle them, a decent respect to the opinions of mankind requires
that they should declare the causes which impel them to the separation.

We hold these truths to be self-evident, that all men are created equal, that they
are endowed by their Creator with certain unalienable Rights, that among these are
Life, Liberty and the pursuit of Happiness.

That to secure these rights, Governments are instituted among Men, deriving their
just powers from the consent of the governed, That whenever any Form of Government
becomes destructive of these ends, it is the Right of the People to alter or to
abolish it, and to institute new Government, laying its foundation on such principles
and organizing its powers in such form, as to them shall seem most likely to effect
their Safety and Happiness. Prudence, indeed, will dictate that Governments long
established should not be changed for light and transient causes; and accordingly
all experience hath shewn, that mankind are more disposed to suffer, while evils are
sufferable, than to right themselves by abolishing the forms to which they are
accustomed. But when a long train of abuses and usurpations, pursuing invariably the
same Object evinces a design to reduce them under absolute Despotism, it is their
right, it is their duty, to throw off such Government, and to provide new Guards for
their future security.
EOT;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new Metagen;
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }

    public function testAssignTitle()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    public function testAssignKeywords()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    public function testAssignDescription()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    public function testGenerateKeywords()
    {
        $this->markTestSkipped(
            'Needs stopwords'
        );
        $body = self::DOI_TEXT;
        $numKeys = 20;
        $forced = array('declaration', 'independence');
        $keys = $this->object->generateKeywords($body, $numKeys, 3, $forced);
        $this->assertTrue(count($keys) == $numKeys);
        // test that forced keywords and words with more than 2 occurances are present
        // There should be one more word, one of the words with only one occurance.
        // While repeatable, the choice of that is undefined behavior.
        $this->assertTrue(in_array('independence', $keys));
        $this->assertTrue(in_array('government', $keys));
        $this->assertTrue(in_array('among', $keys));
        $this->assertTrue(in_array('right', $keys));
        $this->assertTrue(in_array('powers', $keys));
        $this->assertTrue(in_array('causes', $keys));
        $this->assertTrue(in_array('mankind', $keys));
        $this->assertTrue(in_array('nature', $keys));
        $this->assertTrue(in_array('men', $keys));
        $this->assertTrue(in_array('governments', $keys));
        $this->assertTrue(in_array('long', $keys));
        $this->assertTrue(in_array('new', $keys));
        $this->assertTrue(in_array('equal', $keys));
        $this->assertTrue(in_array('happiness', $keys));
        $this->assertTrue(in_array('rights', $keys));
        $this->assertTrue(in_array('form', $keys));
        $this->assertTrue(in_array('people', $keys));
        $this->assertTrue(in_array('becomes', $keys));
        $this->assertFalse(in_array('wombat', $keys));
    }

    public function testGenerateDescription()
    {
        $body = self::DOI_TEXT;
        $numWords = 110;
        $desc = $this->object->generateDescription($body, $numWords);
        $actual = mb_substr($desc, -21, null, 'UTF-8');
        $expected = 'pursuit of Happiness.';
        $this->assertEquals($expected, $actual, $actual);

        $numWords = 20;
        $desc = $this->object->generateDescription($body, $numWords);
        $actual = mb_substr($desc, -(mb_strlen(Metagen::ELLIPSIS)), null, 'UTF-8');
        $expected = Metagen::ELLIPSIS;
        $this->assertEquals($expected, $actual, $actual);
    }

    public function testGenerateMetaTags()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    public function testGenerateSeoTitle()
    {
        $this->markTestSkipped(
            'Needs stopwords'
        );
        $title = 'XOOPS generates your SEO titles for you.';
        $expected = 'XOOPS-generates-SEO-titles';
        $actual = Metagen::generateSeoTitle($title);
        $this->assertEquals($expected, $actual, $actual);
        $expected = $expected . '.html';
        $actual = Metagen::generateSeoTitle($title, '.html');
        $this->assertEquals($expected, $actual, $actual);
        $title = 'catégorie. 2 xmarticle';
        $expected = 'catégorie-2-xmarticle';
        $actual = Metagen::generateSeoTitle($title);
        $this->assertEquals($expected, $actual, $actual);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function encodedMarkupProvider(): array
    {
        return array(
            'triple-encoded tag' => array('&amp;amp;lt;b onmouseover=x&amp;amp;gt;hi'),
            'hex entity tag'     => array('&#x3c;svg onload=x&#x3e;hi'),
        );
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('encodedMarkupProvider')]
    public function testGetSearchSummaryNeverDecodesEntitiesIntoMarkup(string $haystack)
    {
        $actual = Metagen::getSearchSummary($haystack, 'hi');
        $this->assertSame('hi', $actual);
    }

    public function testGetSearchSummaryKeepsLiteralLessThanText()
    {
        $this->assertSame('x < 5', Metagen::getSearchSummary('x &lt; 5', '5'));
        $this->assertSame('x <5 and y <10', Metagen::getSearchSummary('x &amp;#x3c;5 and y &amp;#x3c;10', 'y'));
    }

    public function testGetSearchSummaryKeepsTextAfterAnUnclosedTagButCannotOpenOne()
    {
        $actual = Metagen::getSearchSummary('x &amp;#x3c;b onmouseover=x and more text', 'more');
        $this->assertSame('x < b onmouseover=x and more text', $actual);
    }

    public function testGetSearchSummaryCountsCaseVariantsAsOneSearchTerm()
    {
        $haystack = 'Testing this method will require a long string that will exceed one hundred '
            . 'twenty characters and has some very different significant keywords.';
        $needles = array();
        for ($mask = 0; $mask < 40; $mask++) { // 40 spellings of "absent": aBsent, ABsent, ...
            $variant = '';
            foreach (str_split('absent') as $bit => $letter) {
                $variant .= ($mask >> $bit) & 1 ? strtoupper($letter) : $letter;
            }
            $needles[] = $variant;
        }
        $needles[] = 'significant';

        $actual = Metagen::getSearchSummary($haystack, $needles, 40);
        $this->assertSame(Metagen::ELLIPSIS . 'very different significant keywords.', $actual);
    }

    public function testGetSearchSummaryIgnoresTermsBeyondTheSearchBudget()
    {
        $haystack = 'Testing this method will require a long string that will exceed one hundred '
            . 'twenty characters and has some very different significant keywords.';
        $needles = array();
        for ($i = 0; $i < 40; $i++) {
            $needles[] = 'absent' . $i;
        }
        $needles[] = 'significant';

        $actual = Metagen::getSearchSummary($haystack, $needles, 40);
        $this->assertSame('Testing this method will require a long' . Metagen::ELLIPSIS, $actual);
    }

    public function testGetSearchSummary()
    {
        $ellipsis = Metagen::ELLIPSIS;
        $haystack = <<<'EOT'
Testing this method will require a long string that will exceed one hundred twenty
characters and will need to have in the middle and at each end some very different
significant keywords.
EOT;

        $needles = array();
        $expected = 'Testing this method will require a long' . $ellipsis;
        $actual = Metagen::getSearchSummary($haystack, $needles, 40);
        $this->assertEquals($expected, $actual, $actual);

        $needles = array('testing');
        $expected = 'Testing this method will require a long' . $ellipsis;
        $actual = Metagen::getSearchSummary($haystack, $needles, 40);
        $this->assertEquals($expected, $actual, $actual);

        $needles = array('significant');
        $expected = $ellipsis . 'very different significant keywords.';
        $actual = Metagen::getSearchSummary($haystack, $needles, 40);
        $this->assertEquals($expected, $actual, $actual);

        $needles = array('one hundred');
        $expected = $ellipsis . 'that will exceed one hundred twenty' . $ellipsis;
        $actual = Metagen::getSearchSummary($haystack, $needles, 40);
        $this->assertEquals($expected, $actual, $actual);

        $needles = array('testing', 'significant', 'one hundred');
        $expected = 'Testing this method will require a long' . $ellipsis;
        $actual = Metagen::getSearchSummary($haystack, $needles, 40);
        $this->assertEquals($expected, $actual, $actual);

        $needles = array('significant', 'one hundred', 'testing');
        $expected = 'Testing this method will require a long' . $ellipsis;
        $actual = Metagen::getSearchSummary($haystack, $needles, 40);
        $this->assertEquals($expected, $actual, $actual);

        $needles = array('will');
        $expected = 'Testing this method will require a long' . $ellipsis;
        $actual = Metagen::getSearchSummary($haystack, $needles, 40);
        $this->assertEquals($expected, $actual, $actual);

        $nowhitespace = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0abcdefghijklmnopqrstuvwxyz';
        $needles = array('0');
        $expected = $ellipsis . 'GHIJKLMNOPQRSTUVWXYZ0abcdefghijklmnopqrs' . $ellipsis;
        $actual = Metagen::getSearchSummary($nowhitespace, $needles, 40);
        $this->assertEquals($expected, $actual, $actual);
    }

    public function testAsPlainText()
    {
        $method = new \ReflectionMethod('Xmf\Metagen', 'asPlainText');
        $method->setAccessible(true);
        $input = " <p><pre> This is\r\na test   of\ncleaning\rup <i>a string.  </pre> ";
        $expected = 'This is a test of cleaning up a string.';
        $actual = $method->invokeArgs($this->object, array($input));
        $this->assertEquals($expected, $actual, $actual);
    }

    public function testHtml2textNumericEntities()
    {
        $method = new \ReflectionMethod('Xmf\Metagen', 'html2text');
        $method->setAccessible(true);
        // Use codepoint > 255 (&#8364; = Euro sign €) to test the preg_replace_callback
        // path, since codepoints <= 255 are already handled by the earlier $search/$replace
        $input = 'Price: &#8364;50';
        $actual = $method->invokeArgs(null, array($input));
        $expected = html_entity_decode('&#8364;', ENT_NOQUOTES, Metagen::ENCODING); // €
        $this->assertStringContainsString($expected, $actual);
        $this->assertStringNotContainsString('&#8364;', $actual);
    }

    public function testPurifyTextReplacesNewlines()
    {
        $method = new \ReflectionMethod('Xmf\Metagen', 'purifyText');
        $method->setAccessible(true);
        $actual = $method->invokeArgs(null, array("line one\nline two\rline three"));
        $this->assertStringNotContainsString("\n", $actual);
        $this->assertStringNotContainsString("\r", $actual);
        $this->assertStringContainsString('line one', $actual);
        $this->assertStringContainsString('line two', $actual);
        $this->assertStringContainsString('line three', $actual);
    }

    public function testPurifyTextDecodesEntitiesWithEncoding()
    {
        $method = new \ReflectionMethod('Xmf\Metagen', 'purifyText');
        $method->setAccessible(true);
        $input = 'caf&eacute; cr&egrave;me';
        $actual = $method->invokeArgs(null, array($input));
        $this->assertStringContainsString('café', $actual);
        $this->assertStringContainsString('crème', $actual);
    }
}
