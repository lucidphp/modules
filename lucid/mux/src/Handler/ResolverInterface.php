<?php

/*
 * This File is part of the Lucid\Mux package
 *
 * (c) iwyg <mail@thomas-appel.com>
 *
 * For full copyright and license information, please refer to the LICENSE file
 * that was distributed with this package.
 */

namespace Lucid\Mux\Handler;

/**
 * @interface ResolverInterface
 *
 * @package Lucid\Mux
 * @version $Id$
 * @author iwyg <mail@thomas-appel.com>
 */
interface ResolverInterface
{
    /**
     * resolve
     *
     * @param mixed $handler
     *
     * @return Lucid\Mux\Handler\Reflector
     */
    public function resolve($handler);
}
