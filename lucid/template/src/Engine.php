<?php

/*
 * This File is part of the Lucid\Template package
 *
 * (c) iwyg <mail@thomas-appel.com>
 *
 * For full copyright and license information, please refer to the LICENSE file
 * that was distributed with this package.
 */

namespace Lucid\Template;

use SplStack;
use Exception;
use ErrorException;
use RuntimeException;
use Lucid\Template\Loader\LoaderInterface;
use Lucid\Template\Resource\FileResource;
use Lucid\Template\Resource\StringResource;
use Lucid\Template\Resource\ResourceInterface;
use Lucid\Template\IdentityParserInterface as Parser;
use Lucid\Template\Exception\LoaderException;
use Lucid\Template\Exception\RenderException;
use Lucid\Template\Exception\TemplateException;
use Lucid\Template\Extension\FunctionInterface;
use Lucid\Template\Extension\ExtensionInterface;

/**
 * @class Engine
 *
 * @package Lucid\Template
 * @version $Id$
 * @author iwyg <mail@thomas-appel.com>
 */
class Engine extends AbstractPhpEngine implements ViewAwareInterface
{
    /** @var string */
    const SUPPORT_TYPE = 'php';

    /** @var string */
    protected $encoding;

    /** @var array */
    protected $functions;

    /** @var array */
    protected $globals;

    /** @var array */
    public $sections;

    /** @var array */
    protected $parents;

    /** @var array */
    protected $proxy;

    /** @var \SplStack */
    protected $stack;

    /** @var callable */
    protected $errHandler;

    /** @var \Closure */
    protected $errFunc;

    /**
     * Constructor.
     *
     * @param LoaderInterface $loader
     * @param TemplateIdentityInterface $identity
     * @param array $helpers
     */
    public function __construct(LoaderInterface $loader, Parser $parser = null, $enc = 'UTF-8')
    {
        $this->globals = [];
        $this->sections = [];
        $this->functions = [];
        $this->setEncoding($enc);
        $this->stack = new SplStack;

        parent::__construct($loader, $parser);
        $this->addType(self::SUPPORT_TYPE);
    }

    /**
     * Sets the template encoding,
     *
     * @param string $enc the encoding type, e.g. UTF-8
     *
     * @return void
     */
    public function setEncoding($enc)
    {
        $this->encoding = $enc;
    }

    /**
     * Sets a set of "global" data
     *
     * Global variables are accessible by all templates during the rendering
     * cycle.
     *
     * @param array $globals a assocoiative array of vaiable key/value pairs
     *
     * @return void
     */
    public function setGlobals(array $globals)
    {
        $this->globals = $globals;
    }

    /**
     * Adds a global key/value pair to the globale template variables.
     *
     * @param string $key the variable name
     * @param mixed $parameter the variable value
     *
     * @return void
     */
    public function addGlobal($key, $parameter)
    {
        $this->globals[$key] = $parameter;
    }

    /**
     * Gets all the global data.
     *
     * @return array
     */
    public function getGlobals()
    {
        return $this->globals;
    }

    /**
     * Registers a template extension.
     *
     * @param ExtensionInterface $extension
     *
     * @return void
     */
    public function registerExtension(ExtensionInterface $extension)
    {
        $extension->setEngine($this);

        array_map([$this, 'registerFunction'], $extension->functions());
    }

    /**
     * Registers a function on the template engine.
     *
     * @param string $alias
     * @param callable $callable
     *
     * @return void
     */
    public function registerFunction(FunctionInterface $fn)
    {
        $this->functions[$fn->getName()] = $fn;
    }


    /**
     * Removes a template extension.
     *
     * @param ExtensionInterface $extension
     *
     * @return void
     */
    public function removeExtension(ExtensionInterface $extension)
    {
        foreach ($extension->functions() as $func) {
            unset($this->functions[$func->getName()]);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @throws RenderException
     */
    public function render($template, array $parameters = [])
    {
        $resource = $this->loadTemplate($template);

        $this->stack->push([
            $resource,
            $this->mergeShared($this->getParameters($template, $parameters))
        ]);

        $hash = $resource->getHash();

        if (isset($this->parents[$hash])) {
            throw new RenderException(sprintf('Circular reference in %s.', $template));
        }

        unset($this->parents[$hash]);

        $this->startErrorHandling();

        $content = $this->doRender();

        if (isset($this->parents[$hash])) {
            $content = $this->render($this->parents[$hash], $parameters);
        }

        $this->stopErrorHandling();

        $this->stack->pop();

        return $content;
    }

    /**
     * {@inheritdoc}
     */
    public function display($template, array $parameters = [])
    {
        echo $this->render($template, $parameters);
    }

    /**
     * Inserts a template at the given position.
     *
     * @param mixed $template
     * @param array $replacements template parameters to be merged with existing ones.
     *
     * @return string the rendered template as string
     */
    public function insert($template, array $replacements = [])
    {
        list($resource, $params) = $this->getCurrent();

        return $this->render($template, array_merge($params, $replacements));
    }

    /**
     * Extends a given template.
     *
     * @param mixed $template
     *
     * @return void
     */
    public function extend($template)
    {
        list($resource, ) = $this->getCurrent();

        //if (isset($this->parents[$hash = $resource->getHash()])) {
            //throw new RenderException('Circular reference.');
        //}

        $this->parents[$resource->getHash()] = $template;
    }

    /**
     * Starts a template section at the current postion.
     *
     * @param string $name
     *
     * @return void
     */
    public function section($name)
    {
        if (isset($this->sections[$name])) {
            $section = $this->sections[$name];
            unset($this->sections[$name]);
        } else {
            $section = new Section($name);
        }

        $this->sections[$name] = $section;

        $section->start();
    }

    /**
     * Ends a previuosly started section.
     *
     * @return void
     */
    public function endsection()
    {
        $section = $this->getLastSection();
        $section->stop();

        if ($this->hasParent()) {
            return;
        }

        $content = $section->getContent(0);
        $section->reset();

        return $content;
    }

    /**
     * execute
     *
     * @return void
     */
    public function func(...$args)
    {
        $fns  = explode('|', array_shift($args));
        $res  = null;

        foreach (array_reverse($fns) as $fnc) {
            if (!isset($this->functions[$fnc])) {
                throw new RuntimeException(sprintf('Template function "%s" does not exist.', $fnc));
            }

            $res = $this->functions[$fnc]->call($args);

            if (!$this->functions[$fnc]->getOption('is_safe_html')) {
                $res = $this->escape($res);
            }

            $args = [$res];
        }

        return $res;
    }

    /**
     * Escape a string
     *
     * @param mixed $string
     *
     * @return void
     */
    public function escape($string)
    {
        return htmlspecialchars($string, ENT_COMPAT, $this->getEncoding());
    }

    /**
     * hasParent
     *
     * @return boolean
     */
    protected function hasParent()
    {
        list($resource, ) = $this->getCurrent();

        return isset($this->parents[$resource->getHash()]);
    }

    /**
     * getParameters
     *
     * @param mixed $template
     * @param array $parameters
     *
     * @return array
     */
    protected function getParameters($template, array $parameters)
    {
        if (null !== ($view = $this->getManager())) {
            $view->notifyListeners($name = $this->getIdentity()->identify($template)->getName());

            if ($data = $view->flushData($name)) {
                $parameters = $data->all($parameters);
            }
        }

        return $parameters;
    }

    /**
     * getCurrent
     *
     * @return array
     */
    protected function getCurrent()
    {
        return $this->stack->top();
    }

    /**
     * getLastSection
     *
     * @return Section
     */
    protected function getLastSection()
    {
        if (0 === count($this->sections)) {
            throw new RenderException('Cannot end a section. You must start a section first.');
        }

        $keys = array_keys($this->sections);
        $key  = array_pop($keys);

        return $this->sections[$key];
    }

    /**
     * doRender
     *
     *
     * @return void
     */
    protected function doRender()
    {
        list($resource, $parameters) = $this->getCurrent();
        ob_start();
        try {
            if ($resource instanceof FileResource) {
                $this->displayFile($resource, $parameters);
            } elseif ($resource instanceof StringResource) {
                $this->displayString($resource, $parameters);
            }
        } catch (Exception $e) {
            ob_end_clean();

            if ($e instanceof TemplateException) {
                throw $e;
            }

            throw new RenderException($e->getMessage(), $e, $e->getCode());
        }

        return ob_get_clean();
    }

    /**
     * displayFile
     *
     * @param FileResource $resource
     * @param array $parameters
     *
     * @return void
     */
    protected function displayFile(FileResource $resource, array $parameters)
    {
        extract($parameters, EXTR_SKIP);
        include $resource->getResource();
    }

    /**
     * displayString
     *
     * @param StringResource $resource
     * @param array $parameters
     *
     * @return void
     */
    protected function displayString(StringResource $resource, array $parameters)
    {
        extract($parameters, EXTR_SKIP);
        eval('; ?>' . $resource->getContents() . '<?php ;');
    }

    /**
     * getProxy
     *
     * @return PhpRenderInterface
     */
    protected function getProxy()
    {
        return null === $this->proxy ? $this->proxy = new RenderEngineDecorator($this) : $this->proxy;
    }

    /**
     * getEncoding
     *
     * @return void
     */
    protected function getEncoding()
    {
        return $this->encoding ?: 'UTF-8';
    }

    /**
     * mergeShared
     *
     * @param array $params
     *
     * @return array
     */
    protected function mergeShared(array $params)
    {
        $proxy = $this->getProxy();

        return array_merge($this->globals, $params, ['view' => $proxy, 'func' => [$this, 'func']]);
    }

    /**
     * Starts error handling.
     *
     * @return void
     */
    protected function startErrorHandling()
    {
        $this->errHandler = set_error_handler($this->getErrFunc());
    }

    /**
     * Get the error handler.
     *
     * @return \Closure
     */
    protected function getErrFunc()
    {
        if (null === $this->errFunc) {
            $this->errFunc = function ($errno, $errstr, $errfile, $errline) {
                $this->stopErrorHandling();
                throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
            };
        }

        return $this->errFunc;
    }

    /**
     * Stops the errorhandler.
     *
     * @return void
     */
    protected function stopErrorHandling()
    {
        if (null !== $this->errHandler) {
            restore_error_handler();
        }

        $this->errHandler = null;
    }
}
