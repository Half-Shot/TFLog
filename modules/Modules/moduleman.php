<?php
namespace Bread\Modules;
use Bread\Site as Site;
class ModuleManager
{
	private $modules;
	private $moduleList;
	private $moudleConfig;
	private $configuration;
	private $events;
	function __construct()
	{
		$this->modules = array();
		$this->moduleList = array();
		$this->moduleConfig = array();
		$this->events = array();
	}

	function LoadSettings($filepath)
	{
		if(!file_exists($filepath))
		{
			Site::$Logger->writeError('Cannot load themes. Manager Settings file not found');
		}
		$tmp = file_get_contents($filepath);
		$this->configuration = json_decode($tmp,true);
	}

	function LoadModulesFromConfig($filepath)
	{
		if(!file_exists($filepath))
		{
			Site::$Logger->writeError('Cannot load themes. Manager Settings file not found');
		}
		$tmp = file_get_contents($filepath);
		$mods = json_decode($tmp,true);
		$this->moduleList = array_merge($this->moduleList,$mods);
	}
	
	#Only load modules we need.
	function LoadRequiredModules($request)
	{
	    foreach($this->moduleList["enabled"] as $module)
	    {
	        $tmp = file_get_contents(Site::Configuration()["directorys"]["user-modules"] . "/" . $module);
		    $config = json_decode($tmp,true);
		    if( in_array("everything",$config["loadFor"]) or in_array($request->$command,$config["loadFor"]))
		    {
		        #Load it!
		        
		        $this->RegisterModule($config);
		    }
		    
	    }
	}

	function RegisterModule($jsonArray)
	{
	    $ModuleName = $jsonArray["name"];
		if(array_key_exists($ModuleName,$this->modules))
			Site::$Logger->writeError('Cannot register module. Module already exists');
		
		Site::$Logger->writeMessage('Registered module ' . $ModuleName);
		//Stupid PHP cannot validate files without running command trickery.
		include_once(Site::Configuration()["directorys"]["user-modules"] . "/" . $jsonArray["entryfile"]);
		//Modules should be inside the namespace Bread\Modules but can differ if need be.
		$class = 'Bread\Modules\\'  . $jsonArray["entryclass"];
		if(isset($jsonArray["namespace"])){
		    $namespace = $jsonArray["namespace"];
		    $class = $jsonArray["namespace"] . "\\" . $jsonArray["entryclass"];
		}
		$this->moduleConfig[$jsonArray["name"]] = $jsonArray;
		$this->modules[$jsonArray["name"]] = new $class($this,$ModuleName);
		$this->modules[$jsonArray["name"]]->RegisterEvents();
	}
	
	function RegisterEvent($moduleName,$eventName,$function)
	{
	    if(!array_key_exists($eventName,$this->events))
	    {
	        $this->events[$eventName] = array();
	        $this->events[$eventName][$moduleName] = $function;
	    }
	}
	
	function HookEvent($eventName,$arguments)
	{
	    $returnData = array();
		if(!array_key_exists($eventName,$this->events))
	        return False; //Event not used.
	    foreach($this->events[$eventName] as $module)
		{
			$function = $this->events[$eventName][$module];
			$this->modules[$modules]->$function($arguments);
		}
	    return "We hooked :P";
	}
	
	function HookSpecifedModuleEvent($eventName,$moduleName,$arguments)
	{
		if(!array_key_exists($moduleName,$this->modules))
	        return False; //Module not found.
	    $function = $this->events[$eventName][$moduleName];
	    return $this->modules[$moduleName]->$function($arguments);
	}
}
?>
