<?php

/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

namespace Xmf;

/**
 * FilterInput is a class for filtering input from any data source
 *
 * Forked from the php input filter library by Daniel Morris
 *
 * Original Contributors: Gianpaolo Racca, Ghislain Picard,
 *                        Marco Wandschneider, Chris Tobin and Andrew Eddie.
 *
 * @category  Xmf\FilterInput
 * @package   Xmf
 * @author    Daniel Morris <dan@rootcube.com>
 * @author    Louis Landry <louis.landry@joomla.org>
 * @author    Grégory Mage (Aka Mage)
 * @author    trabis <lusopoemas@gmail.com>
 * @author    Richard Griffith <richard@geekwright.com>
 * @copyright 2005 Daniel Morris
 * @copyright 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @copyright 2011-2023 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2.0 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 * @link      https://xoops.org
 */
class FilterInput
{
    /**
     * Passes remove() makes before it gives up on a value. Real input settles
     * in at most a few passes; this bounds any input that would not.
     */
    private const MAX_FILTER_PASSES = 10;

    /**
     * Deepest array nesting process() descends into (PHP's own
     * max_input_nesting_level defaults to 64).
     */
    private const MAX_ARRAY_DEPTH = 32;

    protected $tagsArray;         // default is empty array
    protected $attrArray;         // default is empty array

    protected $tagsMethod;        // default is 0
    protected $attrMethod;        // default is 0

    protected $xssAuto;           // default is 1
    protected $tagBlacklist = array(
        'applet',
        'body',
        'bgsound',
        'base',
        'basefont',
        'embed',
        'frame',
        'frameset',
        'head',
        'html',
        'id',
        'iframe',
        'ilayer',
        'layer',
        'link',
        'meta',
        'name',
        'object',
        'script',
        'style',
        'title',
        'xml'
    );
    // also, it will strip ALL event handlers
    protected $attrBlacklist = array('action', 'background', 'codebase', 'dynsrc', 'lowsrc');

    /**
     * Constructor
     *
     * @param array $tagsArray  - list of user-defined tags
     * @param array $attrArray  - list of user-defined attributes
     * @param int   $tagsMethod - 0 = allow just user-defined, 1 = allow all but user-defined
     * @param int   $attrMethod - 0 = allow just user-defined, 1 = allow all but user-defined
     * @param int   $xssAuto    - 0 = only auto clean essentials, 1 = allow clean blacklisted tags/attr
     */
    protected function __construct(
        $tagsArray = array(),
        $attrArray = array(),
        $tagsMethod = 0,
        $attrMethod = 0,
        $xssAuto = 1
    ) {
        // make sure user defined arrays are in lowercase
        $tagsArrayCount = count($tagsArray);
        for ($i = 0; $i < $tagsArrayCount; ++$i) {
            $tagsArray[$i] = strtolower($tagsArray[$i]);
        }
        $attrArrayCount = count($attrArray);
        for ($i = 0; $i < $attrArrayCount; ++$i) {
            $attrArray[$i] = strtolower($attrArray[$i]);
        }
        // assign to member vars
        $this->tagsArray  = (array) $tagsArray;
        $this->attrArray  = (array) $attrArray;
        $this->tagsMethod = $tagsMethod;
        $this->attrMethod = $attrMethod;
        $this->xssAuto    = $xssAuto;
    }

    /**
     * Returns an input filter object, only creating it if it does not already exist.
     *
     * This method must be invoked as:
     *   $filter = FilterInput::getInstance();
     *
     * @param array $tagsArray  list of user-defined tags
     * @param array $attrArray  list of user-defined attributes
     * @param int   $tagsMethod WhiteList method = 0, BlackList method = 1
     * @param int   $attrMethod WhiteList method = 0, BlackList method = 1
     * @param int   $xssAuto    Only auto clean essentials = 0,
     *                          Allow clean blacklisted tags/attr = 1
     *
     * @return FilterInput object.
     */
    public static function getInstance(
        $tagsArray = array(),
        $attrArray = array(),
        $tagsMethod = 0,
        $attrMethod = 0,
        $xssAuto = 1
    ) {
        static $instances;

        $className = get_called_class(); // so an extender gets an instance of itself

        $sig = md5(serialize(array($className, $tagsArray, $attrArray, $tagsMethod, $attrMethod, $xssAuto)));

        if (!isset($instances)) {
            $instances = array();
        }

        if (empty($instances[$sig])) {
            $instances[$sig] = new static($tagsArray, $attrArray, $tagsMethod, $attrMethod, $xssAuto);
        }

        return $instances[$sig];
    }

    /**
     * Method to be called by another php script. Processes for XSS and
     * any specified bad code.
     *
     * @param mixed $source - input string/array-of-string to be 'cleaned'
     *
     * @return string|array $source - 'cleaned' version of input parameter
     */
    public function process($source)
    {
        if (is_array($source)) {
            return $this->processArray($source, 1);
        }
        if (is_string($source)) {
            // clean this string
            return $this->remove($this->decode($source));
        } else {
            // return parameter as given
            return $source;
        }
    }

    /**
     * Static method to be called by another php script.
     * Clean the supplied input using the default filter
     *
     * @param mixed  $source Input string/array-of-string to be 'cleaned'
     * @param string $type   Return/cleaning type for the variable, one of
     *                       (INTEGER, FLOAT, BOOLEAN, WORD, ALPHANUM, CMD, BASE64,
     *                        STRING, ARRAY, PATH, USERNAME, WEBURL, EMAIL, IP)
     *
     * @return mixed 'Cleaned' version of input parameter
     * @static
     */
    public static function clean($source, $type = 'string')
    {
        // getInstance() caches per class, so a subclass's policy never leaks
        // into FilterInput::clean() (a method-local static would be shared)
        return static::getInstance()->cleanVar($source, $type);
    }

    /**
     * Filter every string in an array, descending into nested arrays.
     * Arrays nested deeper than MAX_ARRAY_DEPTH are dropped.
     *
     * @param array<mixed> $source array to be 'cleaned'
     * @param int          $depth  nesting level of $source
     *
     * @return array<mixed> 'cleaned' array
     */
    private function processArray(array $source, int $depth): array
    {
        foreach ($source as $key => $value) {
            if (is_string($value)) {
                $source[$key] = $this->remove($this->decode($value));
            } elseif (is_array($value)) {
                if ($depth >= self::MAX_ARRAY_DEPTH) {
                    unset($source[$key]);
                } else {
                    $source[$key] = $this->processArray($value, $depth + 1);
                }
            }
        }
        return $source;
    }

    /**
     * Method to be called by another php script. Processes for XSS and
     * specified bad code according to rules supplied when this instance
     * was instantiated.
     *
     * @param mixed  $source Input string/array-of-string to be 'cleaned'
     * @param string $type   Return/cleaning type for the variable, one of
     *                       (INTEGER, FLOAT, BOOLEAN, WORD, ALPHANUM, CMD, BASE64,
     *                        STRING, ARRAY, PATH, USERNAME, WEBURL, EMAIL, IP)
     *
     * @return mixed 'Cleaned' version of input parameter
     * @static
     */
    public function cleanVar($source, $type = 'string')
    {
        // Handle the type constraint (null type, as Request::getHeader() passes, means string)
        switch (strtoupper((string) $type)) {
            case 'INT':
            case 'INTEGER':
                // Only use the first integer value
                preg_match('/-?\d+/', (string) $source, $matches);
                $result = isset($matches[0]) ? (int) $matches[0] : 0;
                break;

            case 'FLOAT':
            case 'DOUBLE':
                // Only use the first floating point value
                preg_match('/-?\d+(\.\d+)?/', (string) $source, $matches);
                $result = isset($matches[0]) ? (float) $matches[0] : 0;
                break;

            case 'BOOL':
            case 'BOOLEAN':
                $result = (bool) $source;
                break;

            case 'WORD':
                $result = (string) preg_replace('/[^A-Z_]/i', '', $source);
                break;

            case 'ALPHANUM':
            case 'ALNUM':
                $result = (string) preg_replace('/[^A-Z0-9]/i', '', $source);
                break;

            case 'CMD':
                $result = (string) preg_replace('/[^A-Z0-9_\.-]/i', '', $source);
                $result = strtolower($result);
                break;

            case 'BASE64':
                $result = (string) preg_replace('/[^A-Z0-9\/+=]/i', '', $source);
                break;

            case 'STRING':
                $result = (string) $this->process($source);
                break;

            case 'ARRAY':
                $result = (array) $this->process($source);
                break;

            case 'PATH':
                $source = trim((string) $source);
                $pattern = '/^([-_\.\/A-Z0-9=&%?~]+)(.*)$/i';
                preg_match($pattern, $source, $matches);
                $result = isset($matches[1]) ? (string) $matches[1] : '';
                break;

            case 'USERNAME':
                $result = (string) preg_replace('/[\x00-\x1F\x7F<>"\'%&]/', '', $source);
                break;

            case 'WEBURL':
                /** @var string $result */
                $result = trim((string) $this->process($source));
                // reject protocol-relative URLs (//evil.example) and non-http(s) schemes;
                // browsers read a backslash as a slash, so /\evil.example is protocol-relative too;
                // an entity before the first / ? # can hide a scheme from parse_url()
                // (javascript&colon;, java&Tab;script:, javascript&#0058) that the browser decodes,
                // and one right after a leading / can hide the second slash (/&sol;, /&Tab;/);
                // in the authority an entity or %-escape moves the host the browser uses
                // (https://evil&sol;@good is evil.example to a browser, good to parse_url())
                if (
                    str_starts_with($result, '//')
                    || str_contains($result, '\\')
                    || preg_match('#^(?:/&|[^/?\#]*&)#', $result)
                    || preg_match('#^https?://[^/?\#]*[&%]#i', $result)
                ) {
                    $result = '';
                }
                if ($result !== '') {
                    $urlparts = parse_url($result);
                    // an unparseable value (javascript://host:99999/...) has no checkable scheme.
                    // The scheme is read as a browser reads it: parse_url() takes javascript:1/x
                    // for host:port with no scheme. http(s) needs // and a host, since browsers
                    // send https:/&sol;host, http:/host and https:host to whatever host follows.
                    // A relative value has no host, though parse_url() reports one for good.example/a:80/x
                    $hasScheme = 1 === preg_match('#^[a-z][a-z0-9+.\-]*:#i', $result);
                    if (
                        !is_array($urlparts)
                        || ($hasScheme && (!preg_match('#^https?://#', $result) || empty($urlparts['host'])))
                        || (!$hasScheme && isset($urlparts['host']))
                    ) {
                        $result = '';
                    }
                }
                // do not allow quotes, tag brackets or controls
                if (!preg_match('#^[^"<>\x00-\x1F]+\z#', $result)) {
                    $result = '';
                }
                break;

            case 'EMAIL':
                $result = (string) $source;
                if (!filter_var((string) $source, FILTER_VALIDATE_EMAIL)) {
                    $result = '';
                }
                break;

            case 'IP':
                $result = (string) $source;
                // this may be too restrictive.
                // Should the FILTER_FLAG_NO_PRIV_RANGE flag be excluded?
                if (!filter_var((string) $source, FILTER_VALIDATE_IP)) {
                    $result = '';
                }
                break;

            default:
                $result = $this->process($source);
                break;
        }

        return $result;
    }

    /**
     * Internal method to iteratively remove all unwanted tags and attributes
     *
     * @param String $source - input string to be 'cleaned'
     *
     * @return String $source - 'cleaned' version of input parameter
     */
    protected function remove($source)
    {
        // provides nested-tag protection; re-filter until the output is stable
        for ($pass = 0; ($filtered = $this->filterTags($source)) !== $source; ++$pass) {
            if ($pass >= self::MAX_FILTER_PASSES) {
                // a value that does not settle is dropped rather than filtered without bound
                return '';
            }
            $source = $filtered;
        }

        return $source;
    }

    /**
     * Internal method to strip a string of certain tags
     *
     * @param String $source - input string to be 'cleaned'
     *
     * @return String $source - 'cleaned' version of input parameter
     */
    protected function filterTags($source)
    {
        // filter pass setup; $pos is where the unprocessed text starts. Walking $source by
        // offset (instead of re-copying the remainder for every tag) keeps a pass linear.
        $preTag = '';
        $pos = 0;
        // first ">" at or after $pos + 1, kept while $pos moves inside the same tag
        $tagClose = -1;
        // per tag name: next "</name" at or after $pos + 1, or false when there is none
        $closeTagAt = array();
        // iterate through string until no tags left
        while (($tagOpen = strpos($source, '<', $pos)) !== false) {
            // process tag iteratively
            $preTag .= substr($source, $pos, $tagOpen - $pos);
            $pos = $tagOpen;
            // end of tag
            if ($tagClose <= $pos) {
                $tagClose = strpos($source, '>', $pos + 1);
                if ($tagClose === false) {
                    break;
                }
            }
            $tagOpen_end = $tagClose - $pos - 1;
            // a "<" inside the tag (nested tag assessment) restarts the tag there
            $tagOpen_nested = strcspn($source, '<', $pos + 1, $tagOpen_end);
            if ($tagOpen_nested < $tagOpen_end) {
                $preTag .= $this->stripTagOpeners(substr($source, $pos, $tagOpen_nested + 1));
                $pos += $tagOpen_nested + 1;
                continue;
            }
            $currentTag = substr($source, $pos + 1, $tagOpen_end);
            $tagLength = $tagOpen_end;
            if (!$tagOpen_end) {
                // "<>" is not a tag: keep it as text and move past it. Appending the whole
                // remainder here made every pass longer, so remove() never terminated.
                $preTag .= '<>';
                $pos += 2;
                continue;
            }
            // iterate through tag finding attribute pairs - setup
            $attrSet = array();
            $currentSpace = strpos($currentTag, ' ');
            if (substr($currentTag, 0, 1) === "/") {
                // is end tag
                $isCloseTag = true;
                list($tagName) = explode(' ', $currentTag, 2);
                $tagName = substr($tagName, 1);
            } else {
                // is start tag
                $isCloseTag = false;
                list($tagName) = explode(' ', $currentTag, 2);
            }
            // excludes all "non-regular" tagnames OR remove if xssauto is on and tag is blacklisted.
            // An empty (or "0") tag name is already rejected by the regex below, so a
            // separate "!$tagName" test would be dead code.
            if (
                (!preg_match('/^[a-z][a-z0-9]*\z/i', $tagName))
                || ((in_array(strtolower($tagName), $this->tagBlacklist))
                    && ($this->xssAuto))
            ) {
                $pos += $tagLength + 2;
                // don't append this tag
                continue;
            }
            // this while is needed to support attribute values with spaces in!
            // Offsets into $currentTag; the quote positions are cached the same way as $tagClose.
            $lastEquals = strrpos($currentTag, '=');
            $openQuotes = -1;
            $closeQuotes = -1;
            while ($currentSpace !== false) {
                $attrStart = $currentSpace + 1;
                // first quote pair at or after the attribute start
                if ($openQuotes !== false && $openQuotes < $attrStart) {
                    $openQuotes = strpos($currentTag, '"', $attrStart);
                }
                if ($openQuotes !== false && $closeQuotes !== false && $closeQuotes <= $openQuotes) {
                    $closeQuotes = strpos($currentTag, '"', $openQuotes + 1);
                }
                if (
                    // another equals exists, and opening and closing quotes exist
                    ($lastEquals !== false && $lastEquals >= $attrStart)
                    && ($openQuotes !== false && $closeQuotes !== false)
                ) {
                    $attrLength = $closeQuotes - $attrStart + 1;
                } else {
                    // up to the next space; none left means an empty attr (the remainder, below)
                    $nextSpace = strpos($currentTag, ' ', $attrStart);
                    $attrLength = ($nextSpace === false) ? 0 : $nextSpace - $attrStart;
                }
                $attr = substr($currentTag, $attrStart, $attrLength);
                // last attr pair
                if (!$attr) {
                    $attr = substr($currentTag, $attrStart);
                }
                // add to attribute pairs array
                $attrSet[] = $attr;
                // next inc
                $currentSpace = strpos($currentTag, ' ', $attrStart + strlen($attr));
            }
            // appears in array specified by user
            $tagFound = in_array(strtolower($tagName), $this->tagsArray);
            // remove this tag on condition
            if ($tagFound !== (bool) $this->tagsMethod) {
                // reconstruct tag with allowed attributes
                if (!$isCloseTag) {
                    $attrSet = $this->filterAttr($attrSet);
                    $preTag .= '<' . $tagName;
                    $attrSetCount = count($attrSet);
                    for ($i = 0; $i < $attrSetCount; ++$i) {
                        $preTag .= ' ' . $attrSet[$i];
                    }
                    // reformat single tags to XHTML: a matching close tag anywhere later keeps it open.
                    // ponytail: one cached search per tag name; in blacklist mode many distinct
                    // unclosed names still cost one scan each (as before this rewrite).
                    $closeAt = $closeTagAt[$tagName] ?? -1;
                    if ($closeAt !== false && $closeAt <= $pos) {
                        $closeAt = $closeTagAt[$tagName] = strpos($source, '</' . $tagName, $pos + 1);
                    }
                    if ($closeAt !== false) {
                        $preTag .= '>';
                    } else {
                        $preTag .= ' />';
                    }
                } else {
                    // just the tagname
                    $preTag .= '</' . $tagName . '>';
                }
            }
            // find next tag's start
            $pos += $tagLength + 2;
        }
        // append any code after end of tags; an unclosed "<img ..." must not survive as a tag opener
        $preTag .= $this->stripTagOpeners(substr($source, $pos));

        return $preTag;
    }

    /**
     * Remove every "<" that could open a tag from text that is not a complete tag.
     *
     * Per the HTML tokenizer only "<" followed by a letter, "/", "!" or "?" starts
     * markup; any other "<" (as in "a < b" or "<>") is kept as text.
     *
     * @param string $text text outside any recognised tag
     *
     * @return string
     */
    protected function stripTagOpeners($text)
    {
        return preg_replace('#<(?=[a-z/!?])#i', '', $text) ?? '';
    }

    /**
     * Internal method to strip a tag of certain attributes
     *
     * @param array $attrSet attributes
     *
     * @return array $newSet stripped attributes
     */
    protected function filterAttr($attrSet)
    {
        $newSet = array();
        // process attributes
        $attrSetCount = count($attrSet);
        for ($i = 0; $i < $attrSetCount; ++$i) {
            // skip blank spaces in tag
            if (!$attrSet[$i]) {
                continue;
            }
            // split into attr name and value
            $attrSubSet = explode('=', trim($attrSet[$i]));
            list($attrSubSet[0]) = explode(' ', $attrSubSet[0]);
            $attrSubSet[1] = $attrSubSet[1] ?? '';
            // removes all "non-regular" attr names AND also attr blacklisted
            if (
                (!preg_match('/^[a-z][a-z0-9_:.-]*\z/i', $attrSubSet[0]))
                || (($this->xssAuto)
                    && ((in_array(strtolower($attrSubSet[0]), $this->attrBlacklist))
                        || (strncasecmp($attrSubSet[0], 'on', 2) === 0)))
            ) {
                continue;
            }
            // xss attr value filtering
            if ($attrSubSet[1]) {
                // strips unicode, hex, etc
                $attrSubSet[1] = str_replace('&#', '', $attrSubSet[1]);
                // strip normal newline within attr value
                $attrSubSet[1] = preg_replace('/\s+/', '', $attrSubSet[1]);
                // strip double quotes
                $attrSubSet[1] = str_replace('"', '', $attrSubSet[1]);
                // [requested feature] convert single quotes from either side to doubles
                // (Single quotes shouldn't be used to pad attr value)
                if (
                    (substr($attrSubSet[1], 0, 1) === "'")
                    && (substr($attrSubSet[1], (strlen($attrSubSet[1]) - 1), 1) === "'")
                ) {
                    $attrSubSet[1] = substr($attrSubSet[1], 1, (strlen($attrSubSet[1]) - 2));
                }
                // strip slashes
                $attrSubSet[1] = stripslashes($attrSubSet[1]);
            }
            // auto strip attr's with "javascript:
            // Check the value as a browser reads it: named entities decoded ("&colon;", "&Tab;")
            // and whitespace/control characters removed ("java\tscript:").
            $attrValue = strtolower(
                preg_replace('/[\x00-\x20]+/', '', html_entity_decode($attrSubSet[1], ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? ''
            );
            if (
                ((strpos($attrValue, 'expression') !== false)
                    && (strtolower($attrSubSet[0]) === 'style'))
                || (strpos($attrValue, 'javascript:') !== false)
                || (strpos($attrValue, 'behaviour:') !== false)
                || (strpos($attrValue, 'vbscript:') !== false)
                || (strpos($attrValue, 'mocha:') !== false)
                || (strpos($attrValue, 'livescript:') !== false)
            ) {
                continue;
            }

            // if matches user defined array
            $attrFound = in_array(strtolower($attrSubSet[0]), $this->attrArray);
            // keep this attr on condition
            if ($attrFound !== (bool) $this->attrMethod) {
                if ($attrSubSet[1]) {
                    // attr has value
                    $newSet[] = $attrSubSet[0] . '="' . $attrSubSet[1] . '"';
                } elseif ($attrSubSet[1] === "0") {
                    // attr has decimal zero as value
                    $newSet[] = $attrSubSet[0] . '="0"';
                } else {
                    // reformat single attributes to XHTML
                    $newSet[] = $attrSubSet[0] . '="' . $attrSubSet[0] . '"';
                }
            }
        }

        return $newSet;
    }

    /**
     * Try to convert to plaintext
     *
     * @param String $source string to decode
     *
     * @return String $source decoded
     */
    protected function decode($source)
    {
        // url decode
        $charset = defined('_CHARSET') ? constant('_CHARSET') : 'utf-8';
        $source = html_entity_decode($source, ENT_QUOTES, $charset);
        // convert decimal
        $source = preg_replace_callback(
            '/&#(\d+);/m',
            function ($matches) use ($charset) {
                return html_entity_decode('&#' . $matches[1] . ';', ENT_NOQUOTES, $charset);
            },
            $source
        );
        // convert hex notation
        $source = preg_replace_callback(
            '/&#x([a-f0-9]+);/mi',
            function ($matches) use ($charset) {
                return html_entity_decode('&#x' . $matches[1] . ';', ENT_NOQUOTES, $charset);
            },
            $source
        );

        return $source;
    }
}
