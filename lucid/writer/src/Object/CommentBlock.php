<?php

/*
 * This File is part of the Lucid\Writer package
 *
 * (c) iwyg <mail@thomas-appel.com>
 *
 * For full copyright and license information, please refer to the LICENSE file
 * that was distributed with this package.
 */

namespace Lucid\Writer\Object;

use Lucid\Writer\WriterInterface;

/**
 * @class CommentBlock
 *
 * @package Lucid\Writer
 * @version $Id$
 * @author iwyg <mail@thomas-appel.com>
 */
class CommentBlock extends DocBlock
{
    /**
     * {@inheritdoc}
     */
    protected function openBlock(WriterInterface $writer)
    {
        return $writer->writeln('/*');
    }
}
