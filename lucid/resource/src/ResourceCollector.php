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
 * @class ResourceCollector
 *
 * @package Lucid\Resource
 * @version $Id$
 * @author iwyg <mail@thomas-appel.com>
 */
class ResourceCollection implements ResourceCollectionInterface
{
    /**
     * resources
     *
     * @var ResourceInterface[]
     */
    protected $resources;

    /**
     * Constructor.
     *
     * @param array $resources
     */
    public function __construct(array $resources = [])
    {
        $this->setResources($resources);
    }

    /**
     * setResources
     *
     * @param array $resources
     *
     * @return void
     */
    public function setResources(array $resources)
    {
        $this->resources = [];

        foreach ($resources as $resource) {
            $this->addResource($resource);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function addResource(ResourceInterface $resource)
    {
        $this->resources[] = $resource;
    }


    /**
     * {@inheritdoc}
     */
    public function addFileResource($file)
    {
        $this->addResource(new FileResource($file));
    }

    /**
     * {@inheritdoc}
     */
    public function addObjectResource($object)
    {
        $this->addResource(new ObjectResource($object));
    }

    /**
     * {@inheritdoc}
     */
    public function all()
    {
        return $this->resources;
    }

    /**
     * {@inheritdoc}
     */
    public function isValid($timestamp)
    {
        foreach ($this->resources as $resource) {
            if (!$resource->isValid()) {
                return false;
            }
        }

        return true;
    }
}