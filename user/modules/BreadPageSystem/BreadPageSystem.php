<?php
namespace Bread\Modules;
use Bread\Site as Site;
class BreadPageSystem extends Module
{
        private $settings;
            function __construct($manager,$name)
	{
		parent::__construct($manager,$name);
	}

	function RegisterEvents()
	{
            //$this->manager->RegisterEvent($this->name,"Bread.ProcessRequest","CreatePageIndex");
            $this->manager->RegisterEvent($this->name,"Bread.ProcessRequest","Setup");
            $this->manager->RegisterEvent($this->name,"Bread.GenerateNavbar","GenerateNavbar");
	}
        
        function GenerateNavbar($args)
        {
            $pages = array();
            foreach($this->settings->Pageindex as $url => $page)
            {
                $pages[$page->name] = $url;
            }
            return $pages;
        }
                
        function Setup()
        {
            //Get a settings file.
            $rootSettings = Site::$settingsManager->CreateModDir("breadpages");
            Site::$settingsManager->CreateSettingsFiles($rootSettings . "settings.json", new BreadPageSystemSettings());
            $this->settings = Site::$settingsManager->RetriveSettings($rootSettings . "settings.json");
            if( ( time() - $this->settings->BuildTime) > $this->settings->CheckIndexEvery){
                $this->BuildIndex();
            }
        }
        
        
        function BuildIndex()
        {
            foreach(new \recursiveIteratorIterator( new \recursiveDirectoryIterator($this->settings->Pagedir)) as $file)
            {
                if(pathinfo($file->getFilename())['extension'] == "json")
                {
                    $path = $file->getPathname();
                    $this->settings->Pageindex[$path] = Site::$settingsManager->RetriveSettings($path,True);
                }
            }
            $this->settings->BuildTime = time();
            Site::$Logger->writeMessage("BPS: Built Page Index!");
        }
}

class BreadPageSystemSettings
{
    public $Pageindex = array();
    public $Pagedir;
    public $BuildTime = 0;
    public $CheckIndexEvery = 4;
    
    function __construct() {
       $this->Pagedir = Site::ResolvePath("%user-pages");
    }
}