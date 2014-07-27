<?php
namespace Bread\Modules;
use Bread\Site as Site;
use Bread\Utilitys as Util;
class TFLog extends Module
{
    private $GameLogs;
    function __construct($manager,$name)
    {
            parent::__construct($manager,$name);
    }

    function ShowStream(){
        $HTML = "";
        $this->GetGameLogs();
        $TableOfEvents = new Bread\Structures\BreadTableElement();
        $TableOfEvents->
        if(!array_key_exists("GameID",Site::getRequest()->arguments)){
            $Game = current($this->GameLogs);
        }
        else{
            $GameID = Site::getRequest()->arguments["GameID"];
            if(array_key_exists($GameID, $this->GameLogs)){
                $Game = $this->GameLogs[$GameID];
            }
            else{
                return "No such game.";
            }
        }
        $FileContents = file_get_contents($Game);
        $GameLog = json_decode($FileContents);
        return "<pre>" . print_r($GameLog,true) . "</pre>";
    }   

    function SetSiteTitle(){
        return "Current Stream";
    }

    function GetGameLogs(){
        if(!empty($this->GameLogs)){
            return;
        }
        $this->GameLogs = array();
        $path = Site::ResolvePath("%user-tflogs");
        foreach(new \recursiveIteratorIterator( new \recursiveDirectoryIterator($path)) as $file)
        {
            if(pathinfo($file->getFilename())['extension'] == "json")
            {
               $time = substr($file->getFilename(), 6,10);
               $time = intval($time);
               $this->GameLogs[$time] = $file->getPathName();
            }
        }
    }
}
