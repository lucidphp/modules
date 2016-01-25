<?php

/*
 * This File is part of the Lucid\Mux package
 *
 * (c) iwyg <mail@thomas-appel.com>
 *
 * For full copyright and license information, please refer to the LICENSE file
 * that was distributed with this package.
 */

namespace Lucid\Mux\Matcher;

use Lucid\Mux\RouteCollectionInterface;
use Lucid\Mux\Request\ContextInterface as Request;

/**
 * RequestMatcherInterface
 *
 * @package Lucid\Mux
 * @version $Id$
 * @author iwyg <mail@thomas-appel.com>
 */
interface RequestMatcherInterface
{
    const MATCH = 200;

    const NOMATCH = 500;

    /**
     * matchRequest
     *
     * @param RequestContextInterface $context
     *
     * @return array
     */
    public function matchRequest(Request $context, RouteCollectionInterface $routes);
}
