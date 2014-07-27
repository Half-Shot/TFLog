<?php
namespace Bread\Modules;
use Bread\Site as Site;
use Bread\Utilitys as Util;
class TFLog extends Module
{
    private $GameLogs;
    private $PlayersInfomation = array();
    private $HiddenEventTypes = array(0,1,9,28,29);
    private $SteamPlayerSummary = "http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=";
    function __construct($manager,$name)
    {
            parent::__construct($manager,$name);
            $SteamAPIKey = "DEBB2268D244BBE422714C29563900FE";
            $this->SteamPlayerSummary .= $SteamAPIKey . "&steamids=";
    }
    
    function ShowStream(){
        $HTML = "";
        $this->GetGameLogs();
        $TableOfEvents = new \Bread\Structures\BreadTableElement();
        $TableOfEvents->headingRow = new \Bread\Structures\BreadTableRow();
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
        $NItems = Util::EmptySubArray("items",Site::getRequest()->arguments, 50);
        $StartItem = Util::EmptySubArray("start",Site::getRequest()->arguments, 0);
        $FileContents = file_get_contents($Game);
        $GameLog = json_decode($FileContents);
        $TableOfEvents->headingRow->FillOutRow(array(""));
        $Items = 1;
        foreach($GameLog as $i => $Event){
            if(in_array($Event->EventType, $this->HiddenEventTypes)){
                unset($GameLog[$i]);
                continue;
            }            
            if($Items < $StartItem)
                continue;
            if($Items > $NItems)
                break;
            foreach($Event->Players as $Player){
                if($this->SteamIDValid($Player->SteamID)){
                    $SteamID = $this->Steam_Convert32to64($Player->SteamID);
                    $this->PlayersInfomation[$SteamID] = false;
                }
            }
            $Items += 1;
        }
        $SteamRequest = $this->Steam_GetPlayerSummaries(array_keys($this->PlayersInfomation));
        foreach($SteamRequest as $player){
            $this->PlayersInfomation[$player->steamid] = $player;
        }
        foreach($GameLog as $i => $Event){
            if($i < $StartItem)
                continue;
            if($i > $NItems)
                break;
            $EventHTML = $this->BuildEvent($Event);
            if(!empty($EventHTML)){
                $Row = new \Bread\Structures\BreadTableRow();
                $TableOfEvents->rows[] = $Row;
                $Row->FillOutRow(array($EventHTML));
            }
        }
        $HTML .= $this->manager->FireEvent("Theme.Table",$TableOfEvents);
        return $HTML;
    }   
    
    private function SteamIDValid($SteamID){
        if(empty($SteamID))
            return false;
        if($SteamID == "BOT")
            return false;
        if($SteamID == "NOTSET")
            return false;
        return true;
    }

    private function BuildEvent($Event){
            $CommentHTML = "";
            $CommentStruct = array();
            $AvatarPath = "";
            //Player
            $Player = $Event->Players[0];
            $SteamPlayer = False;
            if($this->SteamIDValid($Player->SteamID)){
                $SteamID = $this->Steam_Convert32to64($Player->SteamID);
                $SteamPlayer = $this->PlayersInfomation[$SteamID];
                $CommentStruct["thumbnailurl"] = "index.php?request=tflogPlayerProfile&SteamID=" . $SteamID; //Needs a author profile link
                $CommentStruct["thumbnail"] = $SteamPlayer->avatarmedium;
            }
            else if($Player->SteamID == "BOT"){
                $CommentStruct["thumbnail"] = "content/tf2icon.png";
            }

            switch($Player->Team){
                case 0:
                    $CommentStruct["class"] = "eventRed";
                    break;
                case 1:
                    $CommentStruct["class"] = "eventBlu";
                    break;
                default:
                    $CommentStruct["class"] = "eventDefault";
                    break;
            }
           
            //Sub in proper names
            
            if(isset($Event->Values->objType)){
                switch($Event->Values->objType){
                   case "OBJ_SENTRYGUN":
                       $Event->Values->objType = "Sentry Gun";
                       break;
                   case "OBJ_TELEPORTER":
                       $Event->Values->objType = "Teleporter";
                       break;
                   case "OBJ_DISPENSER":
                       $Event->Values->objType = "Dispenser";
                       break;
                }
            }
            
                        
            if(!isset($Event->Values->objType)){
                $Event->Values->objType = "Dun Goof";
            }
            
            
            //Build the header
            switch($Event->EventType){
                case 0:
                    $CommentStruct["header"] = "Who knows?";
                    break;
                case 1:
                    $CommentStruct["header"] = "A Server CVAR was changed.";
                    break;
                case 2:
                    $CommentStruct["header"] = sprintf("%s built a %s.",$Player->Name,$Event->Values->objType);
                    break;
                case 3:
                    $CommentStruct["header"] = "The round begins.";
                    break;
                case 4:
                    $CommentStruct["header"] = "Setup Time Begins.";
                    break;
                case 5:
                    $CommentStruct["header"] = "Setup Time Ends.";
                    break;
                case 6:
                    $CommentStruct["header"] = sprintf("%s extinguised %s.",$Player->Name,$Event->Players[1]->Name);
                    break;
                case 7:
                    $CommentStruct["header"] = sprintf("%s depolyed an uberchage on %s.",$Player->Name,$Event->Players[1]->Name);
                    break;
                case 8:
                    $CommentStruct["header"] = sprintf("%s got a kill assist against %s.",$Player->Name,$Event->Players[1]->Name);
                    break;
                case 9:
                    $CommentStruct["header"] = sprintf("%s killed a medic  %s.",$Player->Name,$Event->Players[1]->Name);
                    break;
                case 10:
                    $CommentStruct["header"] = sprintf("%s destroyed a %s by  %s.",$Player->Name,$Event->Values->objType,$Event->Players[1]->Name);
                    $CommentStruct["body"] =  sprintf("Used a %s ",$Event->Values->buildingWeapon);
                    break;
                case 11:
                    $CommentStruct["header"] = sprintf("%s killed %s with a %s.",$Player->Name,$Event->Players[1]->Name,$Event->Values->killer_weapon);
                    break;
                case 12:
                    //Not Used
                    return "";
                    break;
                case 13:
                    $CommentStruct["header"] = sprintf("%s changed role to %s.",$Player->Name,$Event->Values->role);
                    if($Player->Team == 0){
                        $CommentStruct["body"] =  sprintf('<img class="teamemblem" src="content/emblem_%1$s_red.png"></img>',$Event->Values->role);
                    }
                    else{
                        $CommentStruct["body"] =  sprintf('<img class="teamemblem" src="content/emblem_%1$s_blu.png"></img>',$Event->Values->role);
                    }
                    break;
                case 28:
                    $CommentStruct["header"] = "Mini Round Selected";
                    break;
                case 29:
                    $CommentStruct["header"] = "Mini Round Started";
                    break;
                default:
                    $CommentStruct["header"] = $Event->EventType;
                    break;
                    
            }
            //$CommentStruct["body"] = "Some infomation about it";
            $CommentHTML = Site::$moduleManager->FireEvent("Theme.Comment",$CommentStruct);
            return $CommentHTML;
    }
    
    function Steam_GetPlayerSummaries($SteamIDArray){
        //Check the cache
        $jsonObj = array();
        $filename = Util::ResolvePath("%system-temp/playerdata.json");
        if(file_exists($filename)){
            if(filectime($filename) - time() < 3600){
                $cacheFile = file_get_contents($filename);
                $jsonObj = json_decode($cacheFile);
                $steamids = array_keys(Util::ArraySetKeyByProperty($jsonObj, "steamid"));
                $SteamIDArray = array_diff($SteamIDArray,$steamids);
            }
        }
        if(count($SteamIDArray) > 0){
            $URL = $this->SteamPlayerSummary;
            foreach($SteamIDArray as $SteamID){
                $URL .= $SteamID . ",";
            }
            $Response = json_decode(file_get_contents($URL));
            foreach($Response->response->players as $Player){
                $jsonObj[] = $Player;
            }
            file_put_contents($filename, json_encode($jsonObj));
        }
        return $jsonObj;
    }
    
    private function Steam_Convert32to64($steam_id)
    {
        list( , $m1, $m2) = explode(':', $steam_id, 3);
        list($steam_cid, ) = explode('.', (((int) $m2 * 2) + $m1) + intval('76561197960265728'), 2);
        return $steam_cid;
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
