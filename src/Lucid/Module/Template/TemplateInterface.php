<?php

/*
 * This File is part of the Lucid\Module\Template package
 *
 * (c) iwyg <mail@thomas-appel.com>
 *
 * For full copyright and license information, please refer to the LICENSE file
 * that was distributed with this package.
 */

namespace Lucid\Module\Template;

/**
 * @class TemplateInterface
 *
 * @package Lucid\Module\Template
 * @version $Id$
 * @author iwyg <mail@thomas-appel.com>
 */
interface TemplateInterface
{
    /**
     * Render the tempalte
     *
     * @param mixed $template
     * @param array $parameters
     *
     * @return string
     */
    public function render(array $parameters = []);
}
