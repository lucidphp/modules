<?php

/*
 * This File is part of the Lucid\Mux package
 *
 * (c) iwyg <mail@thomas-appel.com>
 *
 * For full copyright and license information, please refer to the LICENSE file
 * that was distributed with this package.
 */

namespace Lucid\Mux;

use SplStack;
use InvalidArgumentException;
use Lucid\Mux\Request\UrlGenerator;
use Lucid\Mux\Matcher\RequestMatcher;
use Lucid\Mux\Exception\MatchException;
use Lucid\Mux\Request\PassResponseMapper;
use Lucid\Mux\Matcher\Context as MatchContext;
use Lucid\Mux\Request\Context as RequestContext;
use Lucid\Mux\Request\UrlGeneratorInterface as Url;
use Lucid\Mux\Handler\Dispatcher as HandlerDispatcher;
use Lucid\Mux\Matcher\RequestMatcherInterface as Matcher;
use Lucid\Mux\Handler\DispatcherInterface as Dispatcher;
use Lucid\Mux\Request\ResponseMapperInterface as ResponseMapper;
use Lucid\Mux\Matcher\ContextInterface as MatchContextInterface;
use Lucid\Mux\Request\ContextInterface as RequestContextInterface;

/**
 * @class Router
 *
 * @package Lucid\Mux
 * @version $Id$
 * @author iwyg <mail@thomas-appel.com>
 */
class Router implements RouterInterface
{
    /** @var RouteCollectionInterface */
    private $routes;

    /** @var mixed */
    private $matcher;

    /** @var HandlerDispatcherInterface */
    private $dispatcher;

    /** @var ResponseMapperInterface */
    private $mapper;

    /** @var UrlGeneratorInterface */
    private $url;

    /** @var SplStack */
    private $routeStack;

    /**
     * Construtor.
     *
     * @param RouteCollectionInterface $routes
     * @param Matcher $matcher
     * @param Dispatcher $dispatcher
     * @param ResponseMapper $mapper
     * @param Url $url
     */
    public function __construct(
        RouteCollectionInterface $routes,
        Matcher $matcher = null,
        Dispatcher $dispatcher = null,
        ResponseMapper $mapper = null,
        Url $url = null
    ) {
        $this->routes     = $routes;
        $this->matcher    = $matcher ?: new RequestMatcher;
        $this->dispatcher = $dispatcher ?: new HandlerDispatcher;
        $this->mapper     = $mapper ?: new PassResponseMapper;
        $this->url        = $url ?: new UrlGenerator($this->routes);
        $this->routeStack = new SplStack;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch(RequestContextInterface $request)
    {
        if (($match = $this->matcher->matchRequest($request, $this->routes)) && $match->isMatch()) {
            return $this->dispatchRequest($request, $match);
        }

        throw MatchException::noRouteMatch($request);
    }

    /**
     * {@inheritdoc}
     */
    public function route($name, array $vars = [], array $options = [])
    {
        $options = $this->getOptions($options);

        $rel = 'localhost' === $options['host'] ? true : false;

        $request = $this->createRequestContextFromOptions($options);
        $path    = $this->getUrl($name, $options['host'], $vars, $rel);

        return $this->dispatchRequest($request, $this->createMatchContextFromParameters($vars, $name, $path));
    }

    /**
     * {@inheritdoc}
     */
    public function getFirstRoute()
    {
        if (null === ($name = $this->getFirstRouteName())) {
            return;
        }

        if ($this->routes->has($name)) {
            return $this->routes->get($name);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getFirstRouteName()
    {
        if (0 < $this->routeStack->count()) {
            return $this->routeStack->bottom();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentRoute()
    {
        if (null === ($name = $this->getCurrentRouteName())) {
            return;
        }

        if ($this->routes->has($name)) {
            return $this->routes->get($name);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentRouteName()
    {
        if (0 < $this->routeStack->count()) {
            return $this->routeStack->top();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getUrl($name, $host = null, array $vars = [], $rel = true)
    {
        $rel = $rel ? $type = Url::RELATIVE_PATH : Url::ABSOLUTE_PATH;

        return $this->url->generate($name, $vars, $host, $rel);
    }

    /**
     * Dispatches a request.
     *
     * @param RequestContextInterface $request
     * @param MatchContextInterface $match
     *
     * @return mixed the request response.
     */
    private function dispatchRequest(RequestContextInterface $request, MatchContextInterface $match)
    {
        // store the previous request context.
        $previous = $this->url->getRequestContext();

        $this->url->setRequestContext($request);
        $this->routeStack->push($match->getName());

        $response = $this->mapper->mapResponse($this->dispatcher->dispatch($match));

        $this->routeStack->pop();

        // restore the previous request context.
        if (null !== $previous) {
            $this->url->setRequestContext($previous);
        }

        return $response;
    }

    /**
     * createRequestContextFromOptions
     *
     * @param array $options
     *
     * @return RequestContextInterface
     */
    private function createRequestContextFromOptions(array $options)
    {
        return new RequestContext(
            '/',
            $options['method'],
            $options['query'],
            $options['host'],
            $options['scheme'],
            $options['port']
        );
    }

    /**
     * createMatchContextFromParameters
     *
     * @param array $parameters
     * @param string $url
     *
     * @return MatchContextInterface
     */
    private function createMatchContextFromParameters(array $vars, $name, $url)
    {
        $handler = $this->routes->get($name)->getHandler();

        return new MatchContext(Matcher::MATCH, $name, $url, $handler, $vars);
    }

    /**
     * getOptions
     *
     * @param array $options
     *
     * @return array
     */
    private function getOptions(array $options)
    {
        return array_merge([
            'method'    => 'GET',
            'host'      => 'localhost',
            'port'      => 80,
            'query'     => '',
            'scheme'    => 'http'
        ], $options);
    }
}
