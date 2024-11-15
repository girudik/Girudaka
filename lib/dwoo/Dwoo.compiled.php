<?php define('DWOO_DIRECTORY', dirname(__FILE__) . DIRECTORY_SEPARATOR);
class Dwoo_Core
{
    const VERSION = '1.1.1';
    const RELEASE_TAG = 17;
    const CLASS_PLUGIN = 1;
    const FUNC_PLUGIN = 2;
    const NATIVE_PLUGIN = 4;
    const BLOCK_PLUGIN = 8;
    const COMPILABLE_PLUGIN = 16;
    const CUSTOM_PLUGIN = 32;
    const SMARTY_MODIFIER = 64;
    const SMARTY_BLOCK = 128;
    const SMARTY_FUNCTION = 256;
    const PROXY_PLUGIN = 512;
    const TEMPLATE_PLUGIN = 1024;
    protected $charset = 'utf-8';
    public $globals;
    protected $compileDir;
    protected $cacheDir;
    protected $cacheTime = 0;
    protected $securityPolicy = null;
    protected $plugins = array();
    protected $filters = array();
    protected $resources = array(
        'file' => array(
            'class' => 'Dwoo_Template_File',
            'compiler' => null
        ) ,
        'string' => array(
            'class' => 'Dwoo_Template_String',
            'compiler' => null
        )
    );
    protected $loader = null;
    protected $template = null;
    protected $runtimePlugins;
    protected $returnData;
    public $data;
    public $scope;
    protected $scopeTree;
    protected $stack;
    protected $curBlock;
    protected $buffer;
    protected $pluginProxy;
    public function __construct($compileDir = null, $cacheDir = null)
    {
        if ($compileDir !== null)
        {
            $this->setCompileDir($compileDir);
        }
        if ($cacheDir !== null)
        {
            $this->setCacheDir($cacheDir);
        }
        $this->initGlobals();
    }
    public function __clone()
    {
        $this->template = null;
        unset($this->data);
        unset($this->returnData);
    }
    public function output($tpl, $data = array() , Dwoo_ICompiler $compiler = null)
    {
        return $this->get($tpl, $data, $compiler, true);
    }
    public function get($_tpl, $data = array() , $_compiler = null, $_output = false)
    {
        if ($this->template instanceof Dwoo_ITemplate)
        {
            $clone = clone $this;
            return $clone->get($_tpl, $data, $_compiler, $_output);
        }
        if ($_tpl instanceof Dwoo_ITemplate)
        {
        }
        elseif (is_string($_tpl) && file_exists($_tpl))
        {
            $_tpl = new Dwoo_Template_File($_tpl);
        }
        else
        {
            throw new Dwoo_Exception('Dwoo->get/Dwoo->output\'s first argument must be a Dwoo_ITemplate (i.e. Dwoo_Template_File) or a valid path to a template file', E_USER_NOTICE);
        }
        $this->template = $_tpl;
        if ($data instanceof Dwoo_IDataProvider)
        {
            $this->data = $data->getData();
        }
        elseif (is_array($data))
        {
            $this->data = $data;
        }
        elseif ($data instanceof ArrayAccess)
        {
            $this->data = $data;
        }
        else
        {
            throw new Dwoo_Exception('Dwoo->get/Dwoo->output\'s data argument must be a Dwoo_IDataProvider object (i.e. Dwoo_Data) or an associative array', E_USER_NOTICE);
        }
        $this->globals['template'] = $_tpl->getName();
        $this->initRuntimeVars($_tpl);
        $file = $_tpl->getCachedTemplate($this);
        $doCache = $file === true;
        $cacheLoaded = is_string($file);
        if ($cacheLoaded === true)
        {
            if ($_output === true)
            {
                include $file;
                $this->template = null;
            }
            else
            {
                ob_start();
                include $file;
                $this->template = null;
                return ob_get_clean();
            }
        }
        else
        {
            if ($doCache === true)
            {
                $dynamicId = uniqid();
            }
            $compiledTemplate = $_tpl->getCompiledTemplate($this, $_compiler);
            $out = include $compiledTemplate;
            if ($out === false)
            {
                $_tpl->forceCompilation();
                $compiledTemplate = $_tpl->getCompiledTemplate($this, $_compiler);
                $out = include $compiledTemplate;
            }
            if ($doCache === true)
            {
                $out = preg_replace('/(<%|%>|<\?php|<\?|\?>)/', '<?php /*' . $dynamicId . '*/ echo \'$1\'; ?>', $out);
                if (!class_exists('Dwoo_plugin_dynamic', false))
                {
                    $this->getLoader()
                        ->loadPlugin('dynamic');
                }
                $out = Dwoo_Plugin_dynamic::unescape($out, $dynamicId, $compiledTemplate);
            }
            foreach ($this->filters as $filter)
            {
                if (is_array($filter) && $filter[0] instanceof Dwoo_Filter)
                {
                    $out = call_user_func($filter, $out);
                }
                else
                {
                    $out = call_user_func($filter, $this, $out);
                }
            }
            if ($doCache === true)
            {
                $file = $_tpl->cache($this, $out);
                if ($_output === true)
                {
                    include $file;
                    $this->template = null;
                }
                else
                {
                    ob_start();
                    include $file;
                    $this->template = null;
                    return ob_get_clean();
                }
            }
            else
            {
                $this->template = null;
                if ($_output === true)
                {
                    echo $out;
                }
                return $out;
            }
        }
    }
    protected function initGlobals()
    {
        $this->globals = array(
            'version' => self::VERSION,
            'ad' => '<a href="http://dwoo.org/">Powered by Dwoo</a>',
            'now' => $_SERVER['REQUEST_TIME'],
            'charset' => $this->charset,
        );
    }
    protected function initRuntimeVars(Dwoo_ITemplate $tpl)
    {
        $this->runtimePlugins = array();
        $this->scope = & $this->data;
        $this->scopeTree = array();
        $this->stack = array();
        $this->curBlock = null;
        $this->buffer = '';
        $this->returnData = array();
    }
    public function addPlugin($name, $callback, $compilable = false)
    {
        $compilable = $compilable ? self::COMPILABLE_PLUGIN : 0;
        if (is_array($callback))
        {
            if (is_subclass_of(is_object($callback[0]) ? get_class($callback[0]) : $callback[0], 'Dwoo_Block_Plugin'))
            {
                $this->plugins[$name] = array(
                    'type' => self::BLOCK_PLUGIN | $compilable,
                    'callback' => $callback,
                    'class' => (is_object($callback[0]) ? get_class($callback[0]) : $callback[0])
                );
            }
            else
            {
                $this->plugins[$name] = array(
                    'type' => self::CLASS_PLUGIN | $compilable,
                    'callback' => $callback,
                    'class' => (is_object($callback[0]) ? get_class($callback[0]) : $callback[0]) ,
                    'function' => $callback[1]
                );
            }
        }
        elseif (class_exists($callback, false))
        {
            if (is_subclass_of($callback, 'Dwoo_Block_Plugin'))
            {
                $this->plugins[$name] = array(
                    'type' => self::BLOCK_PLUGIN | $compilable,
                    'callback' => $callback,
                    'class' => $callback
                );
            }
            else
            {
                $this->plugins[$name] = array(
                    'type' => self::CLASS_PLUGIN | $compilable,
                    'callback' => $callback,
                    'class' => $callback,
                    'function' => ($compilable ? 'compile' : 'process')
                );
            }
        }
        elseif (function_exists($callback))
        {
            $this->plugins[$name] = array(
                'type' => self::FUNC_PLUGIN | $compilable,
                'callback' => $callback
            );
        }
        else
        {
            throw new Dwoo_Exception('Callback could not be processed correctly, please check that the function/class you used exists');
        }
    }
    public function removePlugin($name)
    {
        if (isset($this->plugins[$name]))
        {
            unset($this->plugins[$name]);
        }
    }
    public function addFilter($callback, $autoload = false)
    {
        if ($autoload)
        {
            $class = 'Dwoo_Filter_' . $callback;
            if (!class_exists($class, false) && !function_exists($class))
            {
                try
                {
                    $this->getLoader()
                        ->loadPlugin($callback);
                }
                catch(Dwoo_Exception $e)
                {
                    if (strstr($callback, 'Dwoo_Filter_'))
                    {
                        throw new Dwoo_Exception('Wrong filter name : ' . $callback . ', the "Dwoo_Filter_" prefix should not be used, please only use "' . str_replace('Dwoo_Filter_', '', $callback) . '"');
                    }
                    else
                    {
                        throw new Dwoo_Exception('Wrong filter name : ' . $callback . ', when using autoload the filter must be in one of your plugin dir as "name.php" containg a class or function named "Dwoo_Filter_name"');
                    }
                }
            }
            if (class_exists($class, false))
            {
                $callback = array(
                    new $class($this) ,
                    'process'
                );
            }
            elseif (function_exists($class))
            {
                $callback = $class;
            }
            else
            {
                throw new Dwoo_Exception('Wrong filter name : ' . $callback . ', when using autoload the filter must be in one of your plugin dir as "name.php" containg a class or function named "Dwoo_Filter_name"');
            }
            $this->filters[] = $callback;
        }
        else
        {
            $this->filters[] = $callback;
        }
    }
    public function removeFilter($callback)
    {
        if (($index = array_search('Dwoo_Filter_' . $callback, $this->filters, true)) !== false)
        {
            unset($this->filters[$index]);
        }
        elseif (($index = array_search($callback, $this->filters, true)) !== false)
        {
            unset($this->filters[$index]);
        }
        else
        {
            $class = 'Dwoo_Filter_' . $callback;
            foreach ($this->filters as $index => $filter)
            {
                if (is_array($filter) && $filter[0] instanceof $class)
                {
                    unset($this->filters[$index]);
                    break;
                }
            }
        }
    }
    public function addResource($name, $class, $compilerFactory = null)
    {
        if (strlen($name) < 2)
        {
            throw new Dwoo_Exception('Resource names must be at least two-character long to avoid conflicts with Windows paths');
        }
        if (!class_exists($class))
        {
            throw new Dwoo_Exception('Resource class does not exist');
        }
        $interfaces = class_implements($class);
        if (in_array('Dwoo_ITemplate', $interfaces) === false)
        {
            throw new Dwoo_Exception('Resource class must implement Dwoo_ITemplate');
        }
        $this->resources[$name] = array(
            'class' => $class,
            'compiler' => $compilerFactory
        );
    }
    public function removeResource($name)
    {
        unset($this->resources[$name]);
        if ($name === 'file')
        {
            $this->resources['file'] = array(
                'class' => 'Dwoo_Template_File',
                'compiler' => null
            );
        }
    }
    public function setLoader(Dwoo_ILoader $loader)
    {
        $this->loader = $loader;
    }
    public function getLoader()
    {
        if ($this->loader === null)
        {
            $this->loader = new Dwoo_Loader($this->getCompileDir());
        }
        return $this->loader;
    }
    public function getCustomPlugins()
    {
        return $this->plugins;
    }
    public function getCacheDir()
    {
        if ($this->cacheDir === null)
        {
            $this->setCacheDir(KU_ROOTDIR . 'dwoo' . DIRECTORY_SEPARATOR . 'cache');
        }
        return $this->cacheDir;
    }
    public function setCacheDir($dir)
    {
        $this->cacheDir = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR;
        if (is_writable($this->cacheDir) === false)
        {
            throw new Dwoo_Exception('The cache directory must be writable, chmod "' . $this->cacheDir . '" to make it writable');
        }
    }
    public function getCompileDir()
    {
        if ($this->compileDir === null)
        {
            $this->setCompileDir(KU_ROOTDIR . 'dwoo' . DIRECTORY_SEPARATOR . 'templates_c');
        }
        return $this->compileDir;
    }
    public function setCompileDir($dir)
    {
        $this->compileDir = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR;
        if (is_writable($this->compileDir) === false)
        {
            throw new Dwoo_Exception('The compile directory must be writable, chmod "' . $this->compileDir . '" to make it writable');
        }
    }
    public function getCacheTime()
    {
        return $this->cacheTime;
    }
    public function setCacheTime($seconds)
    {
        $this->cacheTime = (int)$seconds;
    }
    public function getCharset()
    {
        return $this->charset;
    }
    public function setCharset($charset)
    {
        $this->charset = strtolower((string)$charset);
    }
    public function getTemplate()
    {
        return $this->template;
    }
    public function setTemplate(Dwoo_ITemplate $tpl)
    {
        $this->template = $tpl;
    }
    public function setDefaultCompilerFactory($resourceName, $compilerFactory)
    {
        $this->resources[$resourceName]['compiler'] = $compilerFactory;
    }
    public function getDefaultCompilerFactory($resourceName)
    {
        return $this->resources[$resourceName]['compiler'];
    }
    public function setSecurityPolicy(Dwoo_Security_Policy $policy = null)
    {
        $this->securityPolicy = $policy;
    }
    public function getSecurityPolicy()
    {
        return $this->securityPolicy;
    }
    public function setPluginProxy(Dwoo_IPluginProxy $pluginProxy)
    {
        $this->pluginProxy = $pluginProxy;
    }
    public function getPluginProxy()
    {
        return $this->pluginProxy;
    }
    public function isCached(Dwoo_ITemplate $tpl)
    {
        return is_string($tpl->getCachedTemplate($this));
    }
    public function clearCache($olderThan = - 1)
    {
        $cacheDirs = new RecursiveDirectoryIterator($this->getCacheDir());
        $cache = new RecursiveIteratorIterator($cacheDirs);
        $expired = time() - $olderThan;
        $count = 0;
        foreach ($cache as $file)
        {
            if ($cache->isDot() || $cache->isDir() || substr($file, -5) !== '.html')
            {
                continue;
            }
            if ($cache->getCTime() < $expired)
            {
                $count += unlink((string)$file) ? 1 : 0;
            }
        }
        return $count;
    }
    public function templateFactory($resourceName, $resourceId, $cacheTime = null, $cacheId = null, $compileId = null, Dwoo_ITemplate $parentTemplate = null)
    {
        if (isset($this->resources[$resourceName]))
        {
            return call_user_func(array(
                $this->resources[$resourceName]['class'],
                'templateFactory'
            ) , $this, $resourceId, $cacheTime, $cacheId, $compileId, $parentTemplate);
        }
        else
        {
            throw new Dwoo_Exception('Unknown resource type : ' . $resourceName);
        }
    }
    public function isArray($value, $checkIsEmpty = false)
    {
        if (is_array($value) === true || $value instanceof ArrayAccess)
        {
            if ($checkIsEmpty === false)
            {
                return true;
            }
            else
            {
                return $this->count($value);
            }
        }
    }
    public function isTraversable($value, $checkIsEmpty = false)
    {
        if (is_array($value) === true)
        {
            if ($checkIsEmpty === false)
            {
                return true;
            }
            else
            {
                return count($value) > 0;
            }
        }
        elseif ($value instanceof Traversable)
        {
            if ($checkIsEmpty === false)
            {
                return true;
            }
            else
            {
                return $this->count($value);
            }
        }
        return false;
    }
    public function count($value)
    {
        if (is_array($value) === true || $value instanceof Countable)
        {
            return count($value);
        }
        elseif ($value instanceof ArrayAccess)
        {
            if ($value->offsetExists(0))
            {
                return true;
            }
        }
        elseif ($value instanceof Iterator)
        {
            $value->rewind();
            if ($value->valid())
            {
                return true;
            }
        }
        elseif ($value instanceof Traversable)
        {
            foreach ($value as $dummy)
            {
                return true;
            }
        }
        return 0;
    }
    public function triggerError($message, $level = E_USER_NOTICE)
    {
        if (!($tplIdentifier = $this
            ->template
            ->getResourceIdentifier()))
        {
            $tplIdentifier = $this
                ->template
                ->getResourceName();
        }
        trigger_error('Dwoo error (in ' . $tplIdentifier . ') : ' . $message, $level);
    }
    public function addStack($blockName, array $args = array())
    {
        if (isset($this->plugins[$blockName]))
        {
            $class = $this->plugins[$blockName]['class'];
        }
        else
        {
            $class = 'Dwoo_Plugin_' . $blockName;
        }
        if ($this->curBlock !== null)
        {
            $this
                ->curBlock
                ->buffer(ob_get_contents());
            ob_clean();
        }
        else
        {
            $this->buffer .= ob_get_contents();
            ob_clean();
        }
        $block = new $class($this);
        $cnt = count($args);
        if ($cnt === 0)
        {
            $block->init();
        }
        elseif ($cnt === 1)
        {
            $block->init($args[0]);
        }
        elseif ($cnt === 2)
        {
            $block->init($args[0], $args[1]);
        }
        elseif ($cnt === 3)
        {
            $block->init($args[0], $args[1], $args[2]);
        }
        elseif ($cnt === 4)
        {
            $block->init($args[0], $args[1], $args[2], $args[3]);
        }
        else
        {
            call_user_func_array(array(
                $block,
                'init'
            ) , $args);
        }
        $this->stack[] = $this->curBlock = $block;
        return $block;
    }
    public function delStack()
    {
        $args = func_get_args();
        $this
            ->curBlock
            ->buffer(ob_get_contents());
        ob_clean();
        $cnt = count($args);
        if ($cnt === 0)
        {
            $this
                ->curBlock
                ->end();
        }
        elseif ($cnt === 1)
        {
            $this
                ->curBlock
                ->end($args[0]);
        }
        elseif ($cnt === 2)
        {
            $this
                ->curBlock
                ->end($args[0], $args[1]);
        }
        elseif ($cnt === 3)
        {
            $this
                ->curBlock
                ->end($args[0], $args[1], $args[2]);
        }
        elseif ($cnt === 4)
        {
            $this
                ->curBlock
                ->end($args[0], $args[1], $args[2], $args[3]);
        }
        else
        {
            call_user_func_array(array(
                $this->curBlock,
                'end'
            ) , $args);
        }
        $tmp = array_pop($this->stack);
        if (count($this->stack) > 0)
        {
            $this->curBlock = end($this->stack);
            $this
                ->curBlock
                ->buffer($tmp->process());
        }
        else
        {
            $this->curBlock = null;
            echo $tmp->process();
        }
        unset($tmp);
    }
    public function getParentBlock(Dwoo_Block_Plugin $block)
    {
        $index = array_search($block, $this->stack, true);
        if ($index !== false && $index > 0)
        {
            return $this->stack[$index - 1];
        }
        return false;
    }
    public function findBlock($type)
    {
        if (isset($this->plugins[$type]))
        {
            $type = $this->plugins[$type]['class'];
        }
        else
        {
            $type = 'Dwoo_Plugin_' . str_replace('Dwoo_Plugin_', '', $type);
        }
        $keys = array_keys($this->stack);
        while (($key = array_pop($keys)) !== false)
        {
            if ($this->stack[$key] instanceof $type)
            {
                return $this->stack[$key];
            }
        }
        return false;
    }
    public function getObjectPlugin($class)
    {
        if (isset($this->runtimePlugins[$class]))
        {
            return $this->runtimePlugins[$class];
        }
        return $this->runtimePlugins[$class] = new $class($this);
    }
    public function classCall($plugName, array $params = array())
    {
        $class = 'Dwoo_Plugin_' . $plugName;
        $plugin = $this->getObjectPlugin($class);
        $cnt = count($params);
        if ($cnt === 0)
        {
            return $plugin->process();
        }
        elseif ($cnt === 1)
        {
            return $plugin->process($params[0]);
        }
        elseif ($cnt === 2)
        {
            return $plugin->process($params[0], $params[1]);
        }
        elseif ($cnt === 3)
        {
            return $plugin->process($params[0], $params[1], $params[2]);
        }
        elseif ($cnt === 4)
        {
            return $plugin->process($params[0], $params[1], $params[2], $params[3]);
        }
        else
        {
            return call_user_func_array(array(
                $plugin,
                'process'
            ) , $params);
        }
    }
    public function arrayMap($callback, array $params)
    {
        if ($params[0] === $this)
        {
            $addThis = true;
            array_shift($params);
        }
        if ((is_array($params[0]) || ($params[0] instanceof Iterator && $params[0] instanceof ArrayAccess)))
        {
            if (empty($params[0]))
            {
                return $params[0];
            }
            $out = array();
            $cnt = count($params);
            if (isset($addThis))
            {
                array_unshift($params, $this);
                $items = $params[1];
                $keys = array_keys($items);
                if (is_string($callback) === false)
                {
                    while (($i = array_shift($keys)) !== null)
                    {
                        $out[] = call_user_func_array($callback, array(
                            1 => $items[$i]
                        ) + $params);
                    }
                }
                elseif ($cnt === 1)
                {
                    while (($i = array_shift($keys)) !== null)
                    {
                        $out[] = $callback($this, $items[$i]);
                    }
                }
                elseif ($cnt === 2)
                {
                    while (($i = array_shift($keys)) !== null)
                    {
                        $out[] = $callback($this, $items[$i], $params[2]);
                    }
                }
                elseif ($cnt === 3)
                {
                    while (($i = array_shift($keys)) !== null)
                    {
                        $out[] = $callback($this, $items[$i], $params[2], $params[3]);
                    }
                }
                else
                {
                    while (($i = array_shift($keys)) !== null)
                    {
                        $out[] = call_user_func_array($callback, array(
                            1 => $items[$i]
                        ) + $params);
                    }
                }
            }
            else
            {
                $items = $params[0];
                $keys = array_keys($items);
                if (is_string($callback) === false)
                {
                    while (($i = array_shift($keys)) !== null)
                    {
                        $out[] = call_user_func_array($callback, array(
                            $items[$i]
                        ) + $params);
                    }
                }
                elseif ($cnt === 1)
                {
                    while (($i = array_shift($keys)) !== null)
                    {
                        $out[] = $callback($items[$i]);
                    }
                }
                elseif ($cnt === 2)
                {
                    while (($i = array_shift($keys)) !== null)
                    {
                        $out[] = $callback($items[$i], $params[1]);
                    }
                }
                elseif ($cnt === 3)
                {
                    while (($i = array_shift($keys)) !== null)
                    {
                        $out[] = $callback($items[$i], $params[1], $params[2]);
                    }
                }
                elseif ($cnt === 4)
                {
                    while (($i = array_shift($keys)) !== null)
                    {
                        $out[] = $callback($items[$i], $params[1], $params[2], $params[3]);
                    }
                }
                else
                {
                    while (($i = array_shift($keys)) !== null)
                    {
                        $out[] = call_user_func_array($callback, array(
                            $items[$i]
                        ) + $params);
                    }
                }
            }
            return $out;
        }
        else
        {
            return $params[0];
        }
    }
    public function readVarInto($varstr, $data, $safeRead = false)
    {
        if ($data === null)
        {
            return null;
        }
        if (is_array($varstr) === false)
        {
            preg_match_all('#(\[|->|\.)?((?:[^.[\]-]|-(?!>))+)\]?#i', $varstr, $m);
        }
        else
        {
            $m = $varstr;
        }
        unset($varstr);
        foreach($m[1] as $k => $sep)
        {
            if ($sep === '.' || $sep === '[' || $sep === '')
            {
                $m[2][$k] = preg_replace('#^(["\']?)(.*?)\1$#', '$2', $m[2][$k]);
                if ((is_array($data) || $data instanceof ArrayAccess) && ($safeRead === false || isset($data[$m[2][$k]])))
                {
                    $data = $data[$m[2][$k]];
                }
                else
                {
                    return null;
                }
            }
            else
            {
                if (is_object($data) && ($safeRead === false || isset($data->$m[2][$k])))
                {
                    $data = $data->$m[2][$k];
                }
                else
                {
                    return null;
                }
            }
        }
        return $data;
    }
    public function readParentVar($parentLevels, $varstr = null)
    {
        $tree = $this->scopeTree;
        $cur = $this->data;
        while ($parentLevels-- !== 0)
        {
            array_pop($tree);
        }
        while (($i = array_shift($tree)) !== null)
        {
            if (is_object($cur))
            {
                $cur = $cur->$i;
            }
            else
            {
                $cur = $cur[$i];
            }
        }
        if ($varstr !== null)
        {
            return $this->readVarInto($varstr, $cur);
        }
        else
        {
            return $cur;
        }
    }
    public function readVar($varstr)
    {
        if (is_array($varstr) === true)
        {
            $m = $varstr;
            unset($varstr);
        }
        else
        {
            if (strstr($varstr, '.') === false && strstr($varstr, '[') === false && strstr($varstr, '->') === false)
            {
                if ($varstr === 'dwoo')
                {
                    return $this->globals;
                }
                elseif ($varstr === '__' || $varstr === '_root')
                {
                    return $this->data;
                    $varstr = substr($varstr, 6);
                }
                elseif ($varstr === '_' || $varstr === '_parent')
                {
                    $varstr = '.' . $varstr;
                    $tree = $this->scopeTree;
                    $cur = $this->data;
                    array_pop($tree);
                    while (($i = array_shift($tree)) !== null)
                    {
                        if (is_object($cur))
                        {
                            $cur = $cur->$i;
                        }
                        else
                        {
                            $cur = $cur[$i];
                        }
                    }
                    return $cur;
                }
                $cur = $this->scope;
                if (isset($cur[$varstr]))
                {
                    return $cur[$varstr];
                }
                else
                {
                    return null;
                }
            }
            if (substr($varstr, 0, 1) === '.')
            {
                $varstr = 'dwoo' . $varstr;
            }
            preg_match_all('#(\[|->|\.)?((?:[^.[\]-]|-(?!>))+)\]?#i', $varstr, $m);
        }
        $i = $m[2][0];
        if ($i === 'dwoo')
        {
            $cur = $this->globals;
            array_shift($m[2]);
            array_shift($m[1]);
            switch ($m[2][0])
            {
                case 'get':
                    $cur = $_GET;
                break;
                case 'post':
                    $cur = $_POST;
                break;
                case 'session':
                    $cur = $_SESSION;
                break;
                case 'cookies':
                case 'cookie':
                    $cur = $_COOKIE;
                break;
                case 'server':
                    $cur = $_SERVER;
                break;
                case 'env':
                    $cur = $_ENV;
                break;
                case 'request':
                    $cur = $_REQUEST;
                break;
                case 'const':
                    array_shift($m[2]);
                    if (defined($m[2][0]))
                    {
                        return constant($m[2][0]);
                    }
                    else
                    {
                        return null;
                    }
            }
            if ($cur !== $this->globals)
            {
                array_shift($m[2]);
                array_shift($m[1]);
            }
        }
        elseif ($i === '__' || $i === '_root')
        {
            $cur = $this->data;
            array_shift($m[2]);
            array_shift($m[1]);
        }
        elseif ($i === '_' || $i === '_parent')
        {
            $tree = $this->scopeTree;
            $cur = $this->data;
            while (true)
            {
                array_pop($tree);
                array_shift($m[2]);
                array_shift($m[1]);
                if (current($m[2]) === '_' || current($m[2]) === '_parent')
                {
                    continue;
                }
                while (($i = array_shift($tree)) !== null)
                {
                    if (is_object($cur))
                    {
                        $cur = $cur->$i;
                    }
                    else
                    {
                        $cur = $cur[$i];
                    }
                }
                break;
            }
        }
        else
        {
            $cur = $this->scope;
        }
        foreach($m[1] as $k => $sep)
        {
            if ($sep === '.' || $sep === '[' || $sep === '')
            {
                if ((is_array($cur) || $cur instanceof ArrayAccess) && isset($cur[$m[2][$k]]))
                {
                    $cur = $cur[$m[2][$k]];
                }
                else
                {
                    return null;
                }
            }
            elseif ($sep === '->')
            {
                if (is_object($cur))
                {
                    $cur = $cur->$m[2][$k];
                }
                else
                {
                    return null;
                }
            }
            else
            {
                return null;
            }
        }
        return $cur;
    }
    public function assignInScope($value, $scope)
    {
        $tree = & $this->scopeTree;
        $data = & $this->data;
        if (!is_string($scope))
        {
            return $this->triggerError('Assignments must be done into strings, (' . gettype($scope) . ') ' . var_export($scope, true) . ' given', E_USER_ERROR);
        }
        if (strstr($scope, '.') === false && strstr($scope, '->') === false)
        {
            $this->scope[$scope] = $value;
        }
        else
        {
            preg_match_all('#(\[|->|\.)?([^.[\]-]+)\]?#i', $scope, $m);
            $cur = & $this->scope;
            $last = array(
                array_pop($m[1]) ,
                array_pop($m[2])
            );
            foreach($m[1] as $k => $sep)
            {
                if ($sep === '.' || $sep === '[' || $sep === '')
                {
                    if (is_array($cur) === false)
                    {
                        $cur = array();
                    }
                    $cur = & $cur[$m[2][$k]];
                }
                elseif ($sep === '->')
                {
                    if (is_object($cur) === false)
                    {
                        $cur = new stdClass;
                    }
                    $cur = & $cur->$m[2][$k];
                }
                else
                {
                    return false;
                }
            }
            if ($last[0] === '.' || $last[0] === '[' || $last[0] === '')
            {
                if (is_array($cur) === false)
                {
                    $cur = array();
                }
                $cur[$last[1]] = $value;
            }
            elseif ($last[0] === '->')
            {
                if (is_object($cur) === false)
                {
                    $cur = new stdClass;
                }
                $cur->$last[1] = $value;
            }
            else
            {
                return false;
            }
        }
    }
    public function setScope($scope, $absolute = false)
    {
        $old = $this->scopeTree;
        if (is_string($scope) === true)
        {
            $scope = explode('.', $scope);
        }
        if ($absolute === true)
        {
            $this->scope = & $this->data;
            $this->scopeTree = array();
        }
        while (($bit = array_shift($scope)) !== null)
        {
            if ($bit === '_' || $bit === '_parent')
            {
                array_pop($this->scopeTree);
                $this->scope = & $this->data;
                $cnt = count($this->scopeTree);
                for ($i = 0;$i < $cnt;$i++) $this->scope = & $this->scope[$this->scopeTree[$i]];
            }
            elseif ($bit === '__' || $bit === '_root')
            {
                $this->scope = & $this->data;
                $this->scopeTree = array();
            }
            elseif (isset($this->scope[$bit]))
            {
                if ($this->scope instanceof ArrayAccess)
                {
                    $tmp = $this->scope[$bit];
                    $this->scope = & $tmp;
                }
                else
                {
                    $this->scope = & $this->scope[$bit];
                }
                $this->scopeTree[] = $bit;
            }
            else
            {
                unset($this->scope);
                $this->scope = null;
            }
        }
        return $old;
    }
    public function getData()
    {
        return $this->data;
    }
    public function setReturnValue($name, $value)
    {
        $this->returnData[$name] = $value;
    }
    public function getReturnValues()
    {
        return $this->returnData;
    }
    public function &getScope()
    {
        return $this->scope;
    }
    public function __call($method, $args)
    {
        $proxy = $this->getPluginProxy();
        if (!$proxy)
        {
            throw new Dwoo_Exception('Call to undefined method ' . __CLASS__ . '::' . $method . '()');
        }
        return call_user_func_array($proxy->getCallback($method) , $args);
    }
}
class Dwoo extends Dwoo_Core
{
}
interface Dwoo_IPluginProxy
{
    public function handles($name);
    public function getCode($name, $params);
    public function getCallback($name);
    public function getLoader($name);
}
interface Dwoo_IElseable
{
}
interface Dwoo_ILoader
{
    public function loadPlugin($class, $forceRehash = true);
}
class Dwoo_Loader implements Dwoo_ILoader
{
    protected $paths = array();
    protected $classPath = array();
    protected $cacheDir;
    protected $corePluginDir;
    public function __construct($cacheDir)
    {
        $this->corePluginDir = DWOO_DIRECTORY . 'plugins';
        $this->cacheDir = rtrim($cacheDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $cacheFile = $this->cacheDir . 'classpath.cache.d' . Dwoo_Core::RELEASE_TAG . '.php';
        if (file_exists($cacheFile))
        {
            $classpath = file_get_contents($cacheFile);
            $this->classPath = unserialize($classpath) + $this->classPath;
        }
        else
        {
            $this->rebuildClassPathCache($this->corePluginDir, $cacheFile);
        }
    }
    protected function rebuildClassPathCache($path, $cacheFile)
    {
        if ($cacheFile !== false)
        {
            $tmp = $this->classPath;
            $this->classPath = array();
        }
        $list = glob(rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*');
        if (is_array($list))
        {
            foreach ($list as $f)
            {
                if (is_dir($f))
                {
                    $this->rebuildClassPathCache($f, false);
                }
                else
                {
                    $this->classPath[str_replace(array(
                        'function.',
                        'block.',
                        'modifier.',
                        'outputfilter.',
                        'filter.',
                        'prefilter.',
                        'postfilter.',
                        'pre.',
                        'post.',
                        'output.',
                        'shared.',
                        'helper.'
                    ) , '', basename($f, '.php')) ] = $f;
                }
            }
        }
        if ($cacheFile !== false)
        {
            if (!file_put_contents($cacheFile, serialize($this->classPath)))
            {
                throw new Dwoo_Exception('Could not write into ' . $cacheFile . ', either because the folder is not there (create it) or because of the chmod configuration (please ensure this directory is writable by php), alternatively you can change the directory used with $dwoo->setCompileDir() or provide a custom loader object with $dwoo->setLoader()');
            }
            $this->classPath += $tmp;
        }
    }
    public function loadPlugin($class, $forceRehash = true)
    {
        if (!isset($this->classPath[$class]) || !is_readable($this->classPath[$class]) || !(include $this->classPath[$class]))
        {
            if ($forceRehash)
            {
                $this->rebuildClassPathCache($this->corePluginDir, $this->cacheDir . 'classpath.cache.d' . Dwoo_Core::RELEASE_TAG . '.php');
                foreach ($this->paths as $path => $file)
                {
                    $this->rebuildClassPathCache($path, $file);
                }
                if (isset($this->classPath[$class]))
                {
                    include $this->classPath[$class];
                }
                else
                {
                    throw new Dwoo_Exception('Plugin <em>' . $class . '</em> can not be found, maybe you forgot to bind it if it\'s a custom plugin ?', E_USER_NOTICE);
                }
            }
            else
            {
                throw new Dwoo_Exception('Plugin <em>' . $class . '</em> can not be found, maybe you forgot to bind it if it\'s a custom plugin ?', E_USER_NOTICE);
            }
        }
    }
    public function addDirectory($pluginDirectory)
    {
        $pluginDir = realpath($pluginDirectory);
        if (!$pluginDir)
        {
            throw new Dwoo_Exception('Plugin directory does not exist or can not be read : ' . $pluginDirectory);
        }
        $cacheFile = $this->cacheDir . 'classpath-' . substr(strtr($pluginDir, '/\\:' . PATH_SEPARATOR, '----') , strlen($pluginDir) > 80 ? -80 : 0) . '.d' . Dwoo_Core::RELEASE_TAG . '.php';
        $this->paths[$pluginDir] = $cacheFile;
        if (file_exists($cacheFile))
        {
            $classpath = file_get_contents($cacheFile);
            $this->classPath = unserialize($classpath) + $this->classPath;
        }
        else
        {
            $this->rebuildClassPathCache($pluginDir, $cacheFile);
        }
    }
}
class Dwoo_Exception extends Exception
{
}
class Dwoo_Security_Policy
{
    const PHP_ENCODE = 1;
    const PHP_REMOVE = 2;
    const PHP_ALLOW = 3;
    const CONST_DISALLOW = false;
    const CONST_ALLOW = true;
    protected $allowedPhpFunctions = array(
        'str_repeat' => true,
        'number_format' => true,
        'htmlentities' => true,
        'htmlspecialchars' => true,
        'long2ip' => true,
        'strlen' => true,
        'list' => true,
        'empty' => true,
        'count' => true,
        'sizeof' => true,
        'in_array' => true,
        'is_array' => true,
        'urlencode' => true,
        'urldecode' => true,
        'str_replace' => true
    );
    protected $allowedMethods = array();
    protected $allowedDirectories = array();
    protected $phpHandling = self::PHP_REMOVE;
    protected $constHandling = self::CONST_DISALLOW;
    public function allowPhpFunction($func)
    {
        if (is_array($func)) foreach ($func as $fname) $this->allowedPhpFunctions[strtolower($fname) ] = true;
        else $this->allowedPhpFunctions[strtolower($func) ] = true;
    }
    public function disallowPhpFunction($func)
    {
        if (is_array($func)) foreach ($func as $fname) unset($this->allowedPhpFunctions[strtolower($fname) ]);
        else unset($this->allowedPhpFunctions[strtolower($func) ]);
    }
    public function getAllowedPhpFunctions()
    {
        return $this->allowedPhpFunctions;
    }
    public function allowMethod($class, $method = null)
    {
        if (is_array($class)) foreach ($class as $elem) $this->allowedMethods[strtolower($elem[0]) ][strtolower($elem[1]) ] = true;
        else $this->allowedMethods[strtolower($class) ][strtolower($method) ] = true;
    }
    public function disallowMethod($class, $method = null)
    {
        if (is_array($class)) foreach ($class as $elem) unset($this->allowedMethods[strtolower($elem[0]) ][strtolower($elem[1]) ]);
        else unset($this->allowedMethods[strtolower($class) ][strtolower($method) ]);
    }
    public function getAllowedMethods()
    {
        return $this->allowedMethods;
    }
    public function allowDirectory($path)
    {
        if (is_array($path)) foreach ($path as $dir) $this->allowedDirectories[realpath($dir) ] = true;
        else $this->allowedDirectories[realpath($path) ] = true;
    }
    public function disallowDirectory($path)
    {
        if (is_array($path)) foreach ($path as $dir) unset($this->allowedDirectories[realpath($dir) ]);
        else unset($this->allowedDirectories[realpath($path) ]);
    }
    public function getAllowedDirectories()
    {
        return $this->allowedDirectories;
    }
    public function setPhpHandling($level = self::PHP_REMOVE)
    {
        $this->phpHandling = $level;
    }
    public function getPhpHandling()
    {
        return $this->phpHandling;
    }
    public function setConstantHandling($level = self::CONST_DISALLOW)
    {
        $this->constHandling = $level;
    }
    public function getConstantHandling()
    {
        return $this->constHandling;
    }
    public function callMethod(Dwoo_Core $dwoo, $obj, $method, $args)
    {
        foreach ($this->allowedMethods as $class => $methods)
        {
            if (!isset($methods[$method]))
            {
                continue;
            }
            if ($obj instanceof $class)
            {
                return call_user_func_array(array(
                    $obj,
                    $method
                ) , $args);
            }
        }
        $dwoo->triggerError('The current security policy prevents you from calling ' . get_class($obj) . '::' . $method . '()');
        return null;
    }
    public function isMethodAllowed($class, $method = null)
    {
        if (is_array($class))
        {
            list($class, $method) = $class;
        }
        foreach ($this->allowedMethods as $allowedClass => $methods)
        {
            if (!isset($methods[$method]))
            {
                continue;
            }
            if ($class === $allowedClass || is_subclass_of($class, $allowedClass))
            {
                return true;
            }
        }
        return false;
    }
}
class Dwoo_Security_Exception extends Dwoo_Exception
{
}
interface Dwoo_ICompilable
{
}
interface Dwoo_ICompiler
{
    public function compile(Dwoo_Core $dwoo, Dwoo_ITemplate $template);
    public function setCustomPlugins(array $customPlugins);
    public function setSecurityPolicy(Dwoo_Security_Policy $policy = null);
}
interface Dwoo_IDataProvider
{
    public function getData();
}
interface Dwoo_ITemplate
{
    public function getCacheTime();
    public function setCacheTime($seconds = null);
    public function getCachedTemplate(Dwoo_Core $dwoo);
    public function cache(Dwoo_Core $dwoo, $output);
    public function clearCache(Dwoo_Core $dwoo, $olderThan = - 1);
    public function getCompiledTemplate(Dwoo_Core $dwoo, Dwoo_ICompiler $compiler = null);
    public function getName();
    public function getResourceName();
    public function getResourceIdentifier();
    public function getSource();
    public function getUid();
    public function getCompiler();
    public function getIsModifiedCode();
    public static function templateFactory(Dwoo_Core $dwoo, $resourceId, $cacheTime = null, $cacheId = null, $compileId = null, Dwoo_ITemplate $parentTemplate = null);
}
interface Dwoo_ICompilable_Block
{
}
abstract class Dwoo_Plugin
{
    protected $dwoo;
    public function __construct(Dwoo_Core $dwoo)
    {
        $this->dwoo = $dwoo;
    }
    public static function paramsToAttributes(array $params, $delim = '\'', Dwoo_Compiler $compiler = null)
    {
        if (isset($params['*']))
        {
            $params = array_merge($params, $params['*']);
            unset($params['*']);
        }
        $out = '';
        foreach ($params as $attr => $val)
        {
            $out .= ' ' . $attr . '=';
            if (trim($val, '"\'') == '' || $val == 'null')
            {
                $out .= str_replace($delim, '\\' . $delim, '""');
            }
            elseif (substr($val, 0, 1) === $delim && substr($val, -1) === $delim)
            {
                $out .= str_replace($delim, '\\' . $delim, '"' . substr($val, 1, -1) . '"');
            }
            else
            {
                if (!$compiler)
                {
                    $escapedVal = '.(is_string($tmp2=' . $val . ') ? htmlspecialchars($tmp2, ENT_QUOTES, $this->charset, false) : $tmp2).';
                }
                elseif (!$compiler->getAutoEscape() || false === strpos($val, 'isset($this->scope'))
                {
                    $escapedVal = '.(is_string($tmp2=' . $val . ') ? htmlspecialchars($tmp2, ENT_QUOTES, $this->charset) : $tmp2).';
                }
                else
                {
                    $escapedVal = '.' . $val . '.';
                }
                $out .= str_replace($delim, '\\' . $delim, '"') . $delim . $escapedVal . $delim . str_replace($delim, '\\' . $delim, '"');
            }
        }
        return ltrim($out);
    }
}
abstract class Dwoo_Block_Plugin extends Dwoo_Plugin
{
    protected $buffer = '';
    public function buffer($input)
    {
        $this->buffer .= $input;
    }
    public function end()
    {
    }
    public function process()
    {
        return $this->buffer;
    }
    public static function preProcessing(Dwoo_Compiler $compiler, array $params, $prepend, $append, $type)
    {
        return Dwoo_Compiler::PHP_OPEN . $prepend . '$this->addStack("' . $type . '", array(' . Dwoo_Compiler::implode_r($compiler->getCompiledParams($params)) . '));' . $append . Dwoo_Compiler::PHP_CLOSE;
    }
    public static function postProcessing(Dwoo_Compiler $compiler, array $params, $prepend, $append, $content)
    {
        return $content . Dwoo_Compiler::PHP_OPEN . $prepend . '$this->delStack();' . $append . Dwoo_Compiler::PHP_CLOSE;
    }
}
abstract class Dwoo_Filter
{
    protected $dwoo;
    public function __construct(Dwoo_Core $dwoo)
    {
        $this->dwoo = $dwoo;
    }
    abstract public function process($input);
}
abstract class Dwoo_Processor
{
    protected $compiler;
    public function __construct(Dwoo_Compiler $compiler)
    {
        $this->compiler = $compiler;
    }
    abstract public function process($input);
}
class Dwoo_Template_String implements Dwoo_ITemplate
{
    protected $name;
    protected $compileId;
    protected $cacheId;
    protected $cacheTime;
    protected $compilationEnforced;
    protected static $cache = array(
        'cached' => array() ,
        'compiled' => array()
    );
    protected $compiler;
    protected $chmod = 0777;
    public function __construct($templateString, $cacheTime = null, $cacheId = null, $compileId = null)
    {
        $this->template = $templateString;
        if (function_exists('hash'))
        {
            $this->name = hash('md4', $templateString);
        }
        else
        {
            $this->name = md5($templateString);
        }
        $this->cacheTime = $cacheTime;
        if ($compileId !== null)
        {
            $this->compileId = str_replace('../', '__', strtr($compileId, '\\%?=!:;' . PATH_SEPARATOR, '/-------'));
        }
        if ($cacheId !== null)
        {
            $this->cacheId = str_replace('../', '__', strtr($cacheId, '\\%?=!:;' . PATH_SEPARATOR, '/-------'));
        }
    }
    public function getCacheTime()
    {
        return $this->cacheTime;
    }
    public function setCacheTime($seconds = null)
    {
        $this->cacheTime = $seconds;
    }
    public function getChmod()
    {
        return $this->chmod;
    }
    public function setChmod($mask = null)
    {
        $this->chmod = $mask;
    }
    public function getName()
    {
        return $this->name;
    }
    public function getResourceName()
    {
        return 'string';
    }
    public function getResourceIdentifier()
    {
        return false;
    }
    public function getSource()
    {
        return $this->template;
    }
    public function getUid()
    {
        return $this->name;
    }
    public function getCompiler()
    {
        return $this->compiler;
    }
    public function forceCompilation()
    {
        $this->compilationEnforced = true;
    }
    public function getCachedTemplate(Dwoo_Core $dwoo)
    {
        if ($this->cacheTime !== null)
        {
            $cacheLength = $this->cacheTime;
        }
        else
        {
            $cacheLength = $dwoo->getCacheTime();
        }
        if ($cacheLength == 0)
        {
            return false;
        }
        $cachedFile = $this->getCacheFilename($dwoo);
        if (isset(self::$cache['cached'][$this->cacheId]) === true && file_exists($cachedFile))
        {
            return $cachedFile;
        }
        elseif ($this->compilationEnforced !== true && file_exists($cachedFile) && ($cacheLength === - 1 || filemtime($cachedFile) > ($_SERVER['REQUEST_TIME'] - $cacheLength)) && $this->isValidCompiledFile($this->getCompiledFilename($dwoo)))
        {
            self::$cache['cached'][$this->cacheId] = true;
            return $cachedFile;
        }
        else
        {
            return true;
        }
    }
    public function cache(Dwoo_Core $dwoo, $output)
    {
        $cacheDir = $dwoo->getCacheDir();
        $cachedFile = $this->getCacheFilename($dwoo);
        $temp = tempnam($cacheDir, 'temp');
        if (!($file = @fopen($temp, 'wb')))
        {
            $temp = $cacheDir . uniqid('temp');
            if (!($file = @fopen($temp, 'wb')))
            {
                trigger_error('Error writing temporary file \'' . $temp . '\'', E_USER_WARNING);
                return false;
            }
        }
        fwrite($file, $output);
        fclose($file);
        $this->makeDirectory(dirname($cachedFile) , $cacheDir);
        if (!@rename($temp, $cachedFile))
        {
            @unlink($cachedFile);
            @rename($temp, $cachedFile);
        }
        if ($this->chmod !== null)
        {
            chmod($cachedFile, $this->chmod);
        }
        self::$cache['cached'][$this->cacheId] = true;
        return $cachedFile;
    }
    public function clearCache(Dwoo_Core $dwoo, $olderThan = - 1)
    {
        $cachedFile = $this->getCacheFilename($dwoo);
        return !file_exists($cachedFile) || (filectime($cachedFile) < (time() - $olderThan) && unlink($cachedFile));
    }
    public function getCompiledTemplate(Dwoo_Core $dwoo, Dwoo_ICompiler $compiler = null)
    {
        $compiledFile = $this->getCompiledFilename($dwoo);
        if ($this->compilationEnforced !== true && isset(self::$cache['compiled'][$this
            ->compileId]) === true)
        {
        }
        elseif ($this->compilationEnforced !== true && $this->isValidCompiledFile($compiledFile))
        {
            self::$cache['compiled'][$this->compileId] = true;
        }
        else
        {
            $this->compilationEnforced = false;
            if ($compiler === null)
            {
                $compiler = $dwoo->getDefaultCompilerFactory($this->getResourceName());
                if ($compiler === null || $compiler === array(
                    'Dwoo_Compiler',
                    'compilerFactory'
                ))
                {
                    if (class_exists('Dwoo_Compiler', false) === false)
                    {
                        include DWOO_DIRECTORY . 'Dwoo/Compiler.php';
                    }
                    $compiler = Dwoo_Compiler::compilerFactory();
                }
                else
                {
                    $compiler = call_user_func($compiler);
                }
            }
            $this->compiler = $compiler;
            $compiler->setCustomPlugins($dwoo->getCustomPlugins());
            $compiler->setSecurityPolicy($dwoo->getSecurityPolicy());
            $this->makeDirectory(dirname($compiledFile) , $dwoo->getCompileDir());
            file_put_contents($compiledFile, $compiler->compile($dwoo, $this));
            if ($this->chmod !== null)
            {
                chmod($compiledFile, $this->chmod);
            }
            self::$cache['compiled'][$this->compileId] = true;
        }
        return $compiledFile;
    }
    protected function isValidCompiledFile($file)
    {
        return file_exists($file);
    }
    public static function templateFactory(Dwoo_Core $dwoo, $resourceId, $cacheTime = null, $cacheId = null, $compileId = null, Dwoo_ITemplate $parentTemplate = null)
    {
        return new self($resourceId, $cacheTime, $cacheId, $compileId);
    }
    protected function getCompiledFilename(Dwoo_Core $dwoo)
    {
        if ($this->compileId === null)
        {
            $this->compileId = $this->name;
        }
        return $dwoo->getCompileDir() . $this->compileId . '.d' . Dwoo_Core::RELEASE_TAG . '.php';
    }
    protected function getCacheFilename(Dwoo_Core $dwoo)
    {
        if ($this->cacheId === null)
        {
            if (isset($_SERVER['REQUEST_URI']) === true)
            {
                $cacheId = $_SERVER['REQUEST_URI'];
            }
            elseif (isset($_SERVER['SCRIPT_FILENAME']) && isset($_SERVER['argv']))
            {
                $cacheId = $_SERVER['SCRIPT_FILENAME'] . '-' . implode('-', $_SERVER['argv']);
            }
            else
            {
                $cacheId = '';
            }
            $this->getCompiledFilename($dwoo);
            $this->cacheId = str_replace('../', '__', $this->compileId . strtr($cacheId, '\\%?=!:;' . PATH_SEPARATOR, '/-------'));
        }
        return $dwoo->getCacheDir() . $this->cacheId . '.html';
    }
    public function getIsModifiedCode()
    {
        return null;
    }
    protected function makeDirectory($path, $baseDir = null)
    {
        if (is_dir($path) === true)
        {
            return;
        }
        if ($this->chmod === null)
        {
            $chmod = 0777;
        }
        else
        {
            $chmod = $this->chmod;
        }
        $retries = 3;
        while ($retries--)
        {
            @mkdir($path, $chmod, true);
            if (is_dir($path))
            {
                break;
            }
            usleep(20);
        }
        if (strpos(PHP_OS, 'WIN') !== 0 && $baseDir !== null)
        {
            $path = strtr(str_replace($baseDir, '', $path) , '\\', '/');
            $folders = explode('/', trim($path, '/'));
            foreach ($folders as $folder)
            {
                $baseDir .= $folder . DIRECTORY_SEPARATOR;
                if (!chmod($baseDir, $chmod))
                {
                    throw new Exception("Unable to chmod " . "$baseDir to $chmod: " . print_r(error_get_last() , true));
                }
            }
        }
    }
}
class Dwoo_Template_File extends Dwoo_Template_String
{
    protected $file;
    protected $includePath = null;
    protected $resolvedPath = null;
    public function __construct($file, $cacheTime = null, $cacheId = null, $compileId = null, $includePath = null)
    {
        $this->file = $file;
        $this->name = basename($file);
        $this->cacheTime = $cacheTime;
        if ($compileId !== null)
        {
            $this->compileId = str_replace('../', '__', strtr($compileId, '\\%?=!:;' . PATH_SEPARATOR, '/-------'));
        }
        if ($cacheId !== null)
        {
            $this->cacheId = str_replace('../', '__', strtr($cacheId, '\\%?=!:;' . PATH_SEPARATOR, '/-------'));
        }
        if (is_string($includePath))
        {
            $this->includePath = array(
                $includePath
            );
        }
        elseif (is_array($includePath))
        {
            $this->includePath = $includePath;
        }
    }
    public function setIncludePath($paths)
    {
        if (is_array($paths) === false)
        {
            $paths = array(
                $paths
            );
        }
        $this->includePath = $paths;
        $this->resolvedPath = null;
    }
    public function getIncludePath()
    {
        return $this->includePath;
    }
    protected function isValidCompiledFile($file)
    {
        return parent::isValidCompiledFile($file) && (int)$this->getUid() <= filemtime($file);
    }
    public function getSource()
    {
        return file_get_contents($this->getResourceIdentifier());
    }
    public function getResourceName()
    {
        return 'file';
    }
    public function getResourceIdentifier()
    {
        if ($this->resolvedPath !== null)
        {
            return $this->resolvedPath;
        }
        elseif ($this->includePath === null)
        {
            return $this->file;
        }
        else
        {
            foreach ($this->includePath as $path)
            {
                $path = rtrim($path, DIRECTORY_SEPARATOR);
                if (file_exists($path . DIRECTORY_SEPARATOR . $this->file) === true)
                {
                    $this->resolvedPath = $path . DIRECTORY_SEPARATOR . $this->file;
                    return $this->resolvedPath;
                }
            }
            throw new Dwoo_Exception('Template "' . $this->file . '" could not be found in any of your include path(s)');
        }
    }
    public function getUid()
    {
        return (string)filemtime($this->getResourceIdentifier());
    }
    public static function templateFactory(Dwoo_Core $dwoo, $resourceId, $cacheTime = null, $cacheId = null, $compileId = null, Dwoo_ITemplate $parentTemplate = null)
    {
        if (DIRECTORY_SEPARATOR === '\\')
        {
            $resourceId = str_replace(array(
                "\t",
                "\n",
                "\r",
                "\f",
                "\v"
            ) , array(
                '\\t',
                '\\n',
                '\\r',
                '\\f',
                '\\v'
            ) , $resourceId);
        }
        $resourceId = strtr($resourceId, '\\', '/');
        $includePath = null;
        if (file_exists($resourceId) === false)
        {
            if ($parentTemplate === null)
            {
                $parentTemplate = $dwoo->getTemplate();
            }
            if ($parentTemplate instanceof Dwoo_Template_File)
            {
                if ($includePath = $parentTemplate->getIncludePath())
                {
                    if (strstr($resourceId, '../'))
                    {
                        throw new Dwoo_Exception('When using an include path you can not reference a template into a parent directory (using ../)');
                    }
                }
                else
                {
                    $resourceId = dirname($parentTemplate->getResourceIdentifier()) . DIRECTORY_SEPARATOR . $resourceId;
                    if (file_exists($resourceId) === false)
                    {
                        return null;
                    }
                }
            }
            else
            {
                return null;
            }
        }
        if ($policy = $dwoo->getSecurityPolicy())
        {
            while (true)
            {
                if (preg_match('{^([a-z]+?)://}i', $resourceId))
                {
                    throw new Dwoo_Security_Exception('The security policy prevents you to read files from external sources : <em>' . $resourceId . '</em>.');
                }
                if ($includePath)
                {
                    break;
                }
                $resourceId = realpath($resourceId);
                $dirs = $policy->getAllowedDirectories();
                foreach ($dirs as $dir => $dummy)
                {
                    if (strpos($resourceId, $dir) === 0)
                    {
                        break 2;
                    }
                }
                throw new Dwoo_Security_Exception('The security policy prevents you to read <em>' . $resourceId . '</em>');
            }
        }
        $class = 'Dwoo_Template_File';
        if ($parentTemplate)
        {
            $class = get_class($parentTemplate);
        }
        return new $class($resourceId, $cacheTime, $cacheId, $compileId, $includePath);
    }
    protected function getCompiledFilename(Dwoo_Core $dwoo)
    {
        if ($this->compileId === null)
        {
            $this->compileId = str_replace('../', '__', strtr($this->getResourceIdentifier() , '\\:', '/-'));
        }
        return $dwoo->getCompileDir() . $this->compileId . '.d' . Dwoo_Core::RELEASE_TAG . '.php';
    }
    public function getIsModifiedCode()
    {
        return '"' . $this->getUid() . '" == filemtime(' . var_export($this->getResourceIdentifier() , true) . ')';
    }
}
class Dwoo_Data implements Dwoo_IDataProvider
{
    protected $data = array();
    public function getData()
    {
        return $this->data;
    }
    public function clear($name = null)
    {
        if ($name === null)
        {
            $this->data = array();
        }
        elseif (is_array($name))
        {
            foreach ($name as $index) unset($this->data[$index]);
        }
        else
        {
            unset($this->data[$name]);
        }
    }
    public function setData(array $data)
    {
        $this->data = $data;
    }
    public function mergeData(array $data)
    {
        $args = func_get_args();
        foreach($args as $v)
        {
            if (is_array($v))
            {
                $this->data = array_merge($this->data, $v);
            }
        }
    }
    public function assign($name, $val = null)
    {
        if (is_array($name))
        {
            reset($name);
            foreach($name as $k => $v)
            {
                $this->data[$k] = $v;
            }
        }
        else
        {
            $this->data[$name] = $val;
        }
    }
    public function __set($name, $value)
    {
        $this->assign($name, $value);
    }
    public function assignByRef($name, &$val)
    {
        $this->data[$name] = & $val;
    }
    public function append($name, $val = null, $merge = false)
    {
        if (is_array($name))
        {
            foreach ($name as $key => $val)
            {
                if (isset($this->data[$key]) && !is_array($this->data[$key]))
                {
                    settype($this->data[$key], 'array');
                }
                if ($merge === true && is_array($val))
                {
                    $this->data[$key] = $val + $this->data[$key];
                }
                else
                {
                    $this->data[$key][] = $val;
                }
            }
        }
        elseif ($val !== null)
        {
            if (isset($this->data[$name]) && !is_array($this->data[$name]))
            {
                settype($this->data[$name], 'array');
            }
            elseif (!isset($this->data[$name]))
            {
                $this->data[$name] = array();
            }
            if ($merge === true && is_array($val))
            {
                $this->data[$name] = $val + $this->data[$name];
            }
            else
            {
                $this->data[$name][] = $val;
            }
        }
    }
    public function appendByRef($name, &$val, $merge = false)
    {
        if (isset($this->data[$name]) && !is_array($this->data[$name]))
        {
            settype($this->data[$name], 'array');
        }
        if ($merge === true && is_array($val))
        {
            foreach ($val as $key => & $val)
            {
                $this->data[$name][$key] = & $val;
            }
        }
        else
        {
            $this->data[$name][] = & $val;
        }
    }
    public function isAssigned($name)
    {
        return isset($this->data[$name]);
    }
    public function __isset($name)
    {
        return isset($this->data[$name]);
    }
    public function unassign($name)
    {
        unset($this->data[$name]);
    }
    public function __unset($name)
    {
        unset($this->data[$name]);
    }
    public function get($name)
    {
        return $this->__get($name);
    }
    public function __get($name)
    {
        if (isset($this->data[$name]))
        {
            return $this->data[$name];
        }
        else
        {
            throw new Dwoo_Exception('Tried to read a value that was not assigned yet : "' . $name . '"');
        }
    }
}
