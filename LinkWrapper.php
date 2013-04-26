<?php

namespace Scraper;

use ArrayObject,
    Symfony\Component\DomCrawler\Link,
    Exception,
    LogicException,
    BadMethodCallException;

/**
 * A class that wraps around Symfony's DomCrawler\Link object
 * and provides an easier way of supplying and retrieving data
 * about the link.
 */
class LinkWrapper
{

    /**
     * The Link object
     * @var Symfony\Component\DomCrawler\Link
     */
    protected $link;


    /**
     * The link after processing by parse_url()
     * @var array
     */
    protected $parsed;

    /**
     *
     * @param Symfony\Component\DomCrawler\Link $link - The Link object
     * @return
     */
    function __construct(Link $link)
    {
        $this->link = $link;
    }

    /**
     *
     * @return
     */
    function getLink()
    {
        return $this->link;
    }

    /**
     *
     * @return
     */
    function setLink(Link $link)
    {
        $this->link = $link;
    }

    /**
     * @param boolean $fresh - Whether to use the cached data, or to re-parse.
     * @return
     */
    function getParsed()
    {
        if(isset($this->parsed))
        {
            return $this->parsed;
        }

        $defaults = [
            'scheme'   => 'http',
            'user'     => '',
            'pass'     => '',
            'host'     => 'example.com',
            'port'     => '',
            'path'     => '/',
            'query'    => '',
            'fragment' => '',
        ];
        $parts = array_merge($defaults, parse_url($this->getHref()));
        $parts = array_map('trim', $parts);
        return $this->parsed = $parts;
    }

    /**
     * Get the user, or user:password combo.
     * @throws LogicException if the password exists, but the user does not.
     * @return
     */
    function getAuth()
    {
        $user = $this->getUser();
        $pass = $this->getPass();

        if($pass && empty($user))
        {
            $errstr = 'A `Link` can not have a password with no user.';
            throw new LogicException($errstr);
        }

        return empty($pass)
            ? $user
            : trim(sprintf('%s:%s', $user, $pass), ':');
    }

    /**
     *
     * @return
     */
    function getUser()
    {
        return $this->getParsed()['user'];
    }

    /**
     *
     * @return
     */
    function getPass()
    {
        return $this->getParsed()['pass'];
    }

    /**
     *
     * @return
     */
    function getScheme()
    {
        return $this->getParsed()['scheme'];
    }

    /**
     *
     * @return
     */
    function getSchemeDecorated()
    {
        $scheme = $this->getScheme();
        $format = (0 === stripos($scheme, 'mailto')) ? '%s:' : '%s://';
        $result = sprintf($format, $scheme);
        return '://' === $result ? '' : $result;
    }

    /**
     *
     * @return
     */
    function getHost()
    {
        return $this->getParsed()['host'];
    }

    /**
     *
     * @return
     */
    function getPort()
    {
        return $this->getParsed()['port'];
    }


    /**
     *
     * @return
     */
    function getPortDecorated()
    {
        $port = (string) $this->getPort();
        return rtrim(':'. $port, ':');
    }

    /**
     *
     * @return
     */
    function getPath()
    {
        return $this->getParsed()['path'];
    }

    /**
     *
     * @return
     */
    function getPathDecorated()
    {
        $path = sprintf('/%s', ltrim($this->getPath(), '/'));
        return '/' === $path ? '/' : '';
    }

    /**
     * @see static::getHostAndPort()
     * @return string
     */
    function getHostDecorated()
    {
        return $this->getHostAndPort();
    }

    /**
     *
     * @return
     */
    function getQuery()
    {
        return $this->getParsed()['query'];
    }

    /**
     *
     * @return
     */
    function getFragment()
    {
        return $this->getParsed()['fragment'];
    }

    /**
     *
     * @return
     */
    function getHostAndPort()
    {
        $host = $this->getHost();
        $port = $this->getPortDecorated();
        return $host . $port;
    }

    /**
     *
     * @return
     */
    function getBase()
    {
        $scheme = $this->getSchemeDecorated();
        $auth = $this->getAuth();
        $host_port = $this->getHostAndPort();
        $auth_host = implode('@', [$auth, $host_port]);
        $ret = $scheme . trim($auth_host, '@');
        return rtrim($ret, '/');
    }

    /**
     *
     * @return
     */
    function getBaseDecorated()
    {
        $base = sprintf('%s/', $this->getBase());
        return '/' === $base ? '' : $base;
    }

    /**
     * @throws BadMethodCallException if a specified argument is not
            a valid class method.
     * @example ->build('scheme!', 'host', 'query');
     *      => ->getScheme() .
     * @return
     */
    function build()
    {
        $ret = [];
        $args = func_get_args();
        foreach($args as $fn)
        {
            $fn = 'get'. ucfirst(str_replace('_', '', $fn));

            # `!` can be used as a shorthand for `decorated`
            if('!' === substr($fn, -1))
            {
                $fn = rtrim($fn, '!') . 'Decorated';
            }

            if(is_callable([$this, $fn]))
            {
                $ref = [&$this];
                $ret[] = call_user_func_array([$this, $fn], $ref);
            }
            else
            {
                throw new BadMethodCallException(sprintf(
                    "Undefined method `%s` in %s::%s();",
                    htmlentities($fn, \ENT_QUOTES),
                    __CLASS__,
                    __METHOD__
                ));
            }
        }
        return implode('', $ret);
    }

    /**
     *
     * @return
     */
    function getQueryDecorated()
    {
        $query = sprintf('?%s', ltrim($this->getQuery(), '?'));
        return '?' === $query ? '' : $query;
    }

    /**
     *
     * @return DOMElement
     */
    function getFragmentDecorated()
    {
        $fragment = sprintf('#', ltrim($this->getFragment(), '#'));
        return '#' === $fragment ? '' : $fragment;
    }

    /**
     *
     * @return
     */
    function getTail()
    {
        $path = $this->getPath();
        $query = $this->getQueryDecorated();
        $fragment = $this->getFragmentDecorated();
        return $path . $query . $fragment;
    }

    /**
     *
     * @return
     */
    function getTailDecorated()
    {
        $path = $this->getPathDecorated();
        $query = $this->getQueryDecorated();
        $fragment = $this->getFragmentDecorated();
        return $path . $query . $fragment;
    }

    /**
     *
     * @return
     */
    function getNext()
    {
        $base = $this->getBase();
        $tail = $this->getTailDecorated();
        return $base . $tail;
    }

    /**
     *
     * @return DOMElement
     */
    function getNode()
    {
        return $this->getLink()->getNode();
    }

    /**
     *
     * @return string
     */
    function getHref()
    {
        $node = $this->getNode();
        return  $node->hasAttribute('href')
              ? $node->getAttribute('href')
              : '';
    }

    /**
     *
     * @return
     */
    function getUri()
    {
        return $this->getLink()->getUri();
    }

    /**
     * Alias of getUri(), because the name is confusing,
     * but implemented for compatibility with Goutte.
     * @return
     */
    function getRequestUri()
    {
        return $this->getUri();
    }

    /**
     * Get a `DomCrawler\Link` object's URI and DOMElement attributes,
     * and a matching `href` attribute from the DOMElement, if it exists.
     *
     * @return ArrayObject - containing the link's found properties.
     */
    function getAttributes()
    {
        $request_uri = $this->getRequestUri();
        $node = $this->getNode();
        $href = $this->getHref();
        $base = $this->getBase();
        $path = $this->getPath();
        $next = $this->getNext();
        $ret = compact('request_uri', 'node', 'href', 'base', 'path', 'next');
        return new ArrayObject($ret, ArrayObject::ARRAY_AS_PROPS);
    }

    /**
     *
     * @return
     */

    /**
     *
     * @return
     */


    /**
     *
     * @return
     */


    /**
     *
     * @return
     */


    /**
     *
     * @return
     */

}
