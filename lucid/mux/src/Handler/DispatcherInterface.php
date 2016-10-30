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

use Lucid\Mux\Matcher\ContextInterface;

/**
 * @interface DispatcherInterface
 *
 * @package Lucid\Mux
 * @version $Id$
 * @author Thomas Appel <mail@thomas-appel.com>
 */
interface DispatcherInterface
{
    /**
     * Dispatches a handler from a match context.
     *
     * @param ContextInterface $context
     *
     * @return mixed
     */
    public function dispatch(ContextInterface $context);
}
