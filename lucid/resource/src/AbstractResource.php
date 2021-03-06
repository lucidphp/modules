<?php

/*
 * This File is part of the Lucid\Resource package
 *
 * (c) iwyg <mail@thomas-appel.com>
 *
 * For full copyright and license information, please refer to the LICENSE file
 * that was distributed with this package.
 */

namespace Lucid\Resource;

/**
 * @class AbstractResource
 *
 * @package Lucid\Resource
 * @version $Id$
 * @author iwyg <mail@thomas-appel.com>
 */
abstract class AbstractResource implements ResourceInterface
{
    /** @var string */
    protected $resource;

    /**
     * {@inheritdoc}
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize([
            'resource' => $this->getResource()
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return $this->getResource();
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($data)
    {
        $data = unserialize($data);

        $this->resource = $data['resource'];
    }

    /**
     * {@inheritdoc}
     */
    public function isLocal()
    {
        return is_file($this->getResource()) && stream_is_local($this->getResource());
    }

    /**
     * {@inheritdoc}
     */
    public function isValid($now)
    {
        try {
            return filemtime($this) <= $now;
        } catch (\Exception $e) {
        }

        return false;
    }
}
