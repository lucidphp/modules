<?php declare(strict_types=1);

/*
 * This File is part of the Lucid\Mux package
 *
 * (c) Thomas Appel <mail@thomas-appel.com>
 *
 * For full copyright and license information, please refer to the LICENSE file
 * that was distributed with this package.
 */

namespace Lucid\Mux\Handler;

use Lucid\Mux\Matcher\ContextInterface as Match;

/**
 * @class Dispatcher
 *
 * @package Lucid\Mux
 * @version $Id$
 * @author Thomas Appel <mail@thomas-appel.com>
 */
class Dispatcher implements DispatcherInterface
{
    /** @var Resolver */
    private $resolver;

    /** @var PassParameterMapper  */
    private $mapper;

    /**
     * Dispatcher constructor.
     * @param ResolverInterface|null $resolver
     * @param ParameterMapperInterface|null $mapper
     */
    public function __construct(ResolverInterface $resolver = null, ParameterMapperInterface $mapper = null)
    {
        $this->resolver = $resolver ?: new Resolver;
        $this->mapper   = $mapper ?: new PassParameterMapper;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch(Match $context)
    {
        $args = $this->mapper->map(
            $handler = $this->resolver->resolve($context->getHandler()),
            $context->getVars()
        );

        return $handler->invokeArgs($args);
    }
}
