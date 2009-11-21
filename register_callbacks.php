<?php 
/**
 * RegisterCallbacksComponent class file
 * 
 * Executes callback functions for each plugin before each controller callback action.
 * Files should be located in: /plugins/[plugin_name]/[plugin_name]_callback.php
 *
 * Inspiration, architecture, and some code from:
 * http://bakery.cakephp.org/articles/view/pluginhandler-to-load-configuration-and-callbacks-for-plugins
 * 'PluginHandler to load configuration and callbacks for plugin' - Gediminas Morkevicius (sky_l3ppard)
 *
 * @filesource
 * @author          Jamie Nay
 * @copyright       Jamie Nay
 * @license         http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link            http://jamienay.com/2009/11/an-easy-plugin-callback-component-for-cakephp-12/
 */
class RegisterCallbacksComponent extends Object {
	/**
	 * Controller object
	 * 
	 * @var object
	 * @access private
	 */
	var $__controller = null;
	
	/**
	 * Component settings
	 * 
	 * @access public
	 * @var array
	 */
	public $settings = array();
	
    /**
	 * Default values for settings.
	 * - priority (optional): the order in which callbacks should be executed. If
	 * priority is left empty, or if some plugins are left out of the list, the
	 * plugins are just added in the order in which they're loaded via Configure.
	 *
	 * @access private
	 * @var array
	 */
    private $__defaults = array(
        'priority' => array()
    );
    
    /**
     * Registered plugins - plugins that have a PluginNameCallback class.
     *
     * @access private
     * @var array
     */
    private $__registered = array();

    /**
     * Configuration method.
     *
     * In addition to configuring the settings (see $__defaults above for settings explanation),
     * this function also loops through the installed plugins and 'registers' those that have a
     * PluginNameCallback class.
     *
     * @param object $controller    Controller object
     * @param array $settings       Component settings
     * @access public
     * @return void
     */
	public function initialize(&$controller, $settings = array()) {
		$this->__controller = &$controller;
		$this->settings = array_merge($this->__defaults, $settings);

		if (empty($this->settings['priority'])) {
			$this->settings['priority'] = Configure::listobjects('plugin');
		} else {
			foreach (Configure::listobjects('plugin') as $plugin) {
				if (!in_array($plugin, $this->settings['priority'])) {
					array_push($this->settings['priority'], $plugin);
				}
			}
		}
		
		foreach ($this->settings['priority'] as $plugin) {
		    $file = Inflector::underscore($plugin).'_callback';
		    $className = $plugin.'Callback';
		    if (App::import('File', $className, true, array(APP . 'plugins' . DS . Inflector::underscore($plugin)), $file.'.php')) {
    	        if (class_exists($className)) {
    	            $class = new $className();
    	            ClassRegistry::addObject($className, $class);
    	            $this->__registered[] = $className;
    	        }    	        
		    }
		}
        
        /**
         * Called before the controller's beforeFilter method.
         */
		$this->executeCallbacks('initialize');
	}
	
	/**
	 * Executes beforeFilter() methods in the callback classes.
	 * Called after the controller's beforeFilter() method but before
	 * the controller executes the current action handler.
	 * Uses 'beforeFilter' instead of 'startup' to make the action name
	 * more consistent with the controller name.
	 *
	 * @param object $controller    Controller object
	 * @access public
  	 * @return void
	 */
	public function startup(&$controller) {
		$this->executeCallbacks('beforeFilter');
	}

    /**
     * Executes beforeRender() methods in the callback classes.
     * Called after the controller's beforeRender method but before the
     * controller renders views and layout.
     *
 	 * @param object $controller    Controller object
 	 * @access public
 	 * @return void
     */
	public function beforeRender(&$controller) {
		$this->executeCallbacks('beforeRender');
	}
	
	/**
	 * Executes shutdown() methods in the callback classes.
	 * Called before output is sent to the browser.
	 *
	 * @param object $controller    Controller object
  	 * @access public
  	 * @return void
     */
    public function shutdown(&$controller) {
		$this->executeCallbacks('shutdown');
	}
	
	/**
	 * Executes beforeRedirect() methods in the callback classes.
	 * Called when the controller's redirect method is called but
	 * before any further action.
	 *
	 * @param object $controller    Controller object
	 * @param string $url
	 * @param string $status
	 * @param boolean $exit
  	 * @access public
  	 * @return void
     */
    public function beforeRedirect(&$controller, $url, $status = null, $exit = true) {
		$this->executeCallbacks('beforeRedirect', array($url, $status, $exit));
	}
	
	/**
	 * Executes the requested method in each Callback class, in order
	 * of priority, if that method exists in the class. Also sends any
	 * arguments, with the $this->__controller always being the first
	 * argument.
	 *
	 * @param string $method    Method name
	 * @param array $args       Optional arguments
	 * @access public
	 * @return void
	 */
	public function executeCallbacks($method, $args = array()) {
	    foreach ($this->__registered as $callback) {
	        $class = ClassRegistry::init($callback);
	        if ($class && in_array($method, get_class_methods($class))) {
	            call_user_func_array(array($class, $method), array_merge(array($this->__controller), $args));
	        }
	    }
	}

}
?>