<?php
namespace Bread\Modules;
use Bread\Site as Site;
use Bread\Utilitys as Util;
class TFLog extends Module
{
    private $HiddenEventTypes = array(0,1,9,28,29);
    private $SteamPlayerSummary = "http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=";
    private $settings = false;
    function __construct($manager,$name)
    {
            parent::__construct($manager,$name);
            $SteamAPIKey = "DEBB2268D244BBE422714C29563900FE";
            $this->SteamPlayerSummary .= $SteamAPIKey . "&steamids=";
    }
    
    function ShowStream(){
        $HTML = "";
        $this->GetGameLogs();
        
        $HeaderPanel = array(new \stdClass(),new \stdClass(),new \stdClass());
        $FooterPanel = array(new \stdClass());
        $Panel = array(new \stdClass(),new \stdClass(), new \stdClass());
        
        $FooterPanel[0]->offset = 3;
        $FooterPanel[0]->size = 6;
        
        $FooterPanel[0]->body = "NExt Back Etc";
        
        $HeaderPanel[0]->offset = 1;
        $HeaderPanel[0]->size = 2;
        $HeaderPanel[1]->offset = 1;
        $HeaderPanel[1]->size = 4;
        $HeaderPanel[2]->offset = 1;
        $HeaderPanel[2]->size = 2;
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
        $StatsArray = $this->CalculateTotalStats($GameLog);
        
        $HeaderPanel[0]->body = '<div class="redscore scorepanel">' . $this->manager->FireEvent("Theme.Panel",array("body"=>$StatsArray["RedScore"])) . '</div>';
        $HeaderPanel[1]->body = '<div class="generalscore scorepanel">' . $this->manager->FireEvent("Theme.Panel",array("body"=>"")) . '</div>';
        $HeaderPanel[2]->body = '<div class="bluscore scorepanel">' . $this->manager->FireEvent("Theme.Panel",array("body"=>$StatsArray["BluScore"])) . '</div>';
        
        $GameLog = array_reverse($GameLog);
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
        
        
        $Panel[0]->size = 2;
        $Panel[2]->size = 2;
        
        $Panel[0]->body = "";
        $Panel[2]->body = "";
        
        $Panel[1]->size = 8;
        $Panel[1]->body = $this->manager->FireEvent("Theme.Table",$TableOfEvents);
        return $this->manager->FireEvent("Theme.Layout.Grid.HorizonalStack",$HeaderPanel) . $this->manager->FireEvent("Theme.Layout.Grid.HorizonalStack",$Panel) . $this->manager->FireEvent("Theme.Layout.Grid.HorizonalStack",$FooterPanel);
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
                        $CommentStruct["body"] =  sprintf('<img class="teamemblem" src="content/emblem/emblem_%1$s_red.png"></img>',$Event->Values->role);
                    }
                    else{
                        $CommentStruct["body"] =  sprintf('<img class="teamemblem" src="content/emblem/ emblem_%1$s_blu.png"></img>',$Event->Values->role);
                    }
                    break;
                case 14:
                    $Names = "";
                    foreach($Player as $Event->Players){
                        $Names .= $Player->Name . " ";
                    }
                    $CommentStruct["header"] = sprintf("%s captured point %s.",$Player->Team,$Event->Values->capturePointNumber);
                    $CommentStruct["body"] =  sprintf("This was captured by %s ." ,$Names);
                    break;
                case 15:
                    $CommentStruct["header"] = sprintf("%s is <strong>dominating</strong> %s.",$Player->Name,$Event->Players[1]->Name);
                    break;
                case 16:
                    $CommentStruct["header"] = "Overtime";
                    break;
                case 17:
                    $CommentStruct["header"] = sprintf("%s won the minor round!",$Event->Values->winningTeam);
                    break;
                case 18:
                    $CommentStruct["header"] = sprintf("%s won the minor round!",$Event->Values->winningTeam);
                    break;
                case 21:
                    $CommentStruct["header"] = sprintf("The minor round took %s seconds to finish!",$Event->Values->roundTime);
                    break;
                case 20:
                    $CommentStruct["header"] = sprintf("The round was won by %s !",$Event->Values->winningTeam);
                    break;
                case 18:
                    //Not Used
                    return "";
                    break;
                case 22:
                    //Not Used
                    return "";
                    break;
                    //Not Used
                    return "";
                    break;
                case 23:
                    //Not Used
                    return "";
                    break;
                case 24:
                    $CommentStruct["header"] = sprintf("%s says ",$Player->Name);
                    $CommentStruct["body"] = $Event->Values->message;
                    break;
                case 25:
                    $CommentStruct["header"] = sprintf("%s says to his team ",$Player->Name);
                    $CommentStruct["body"] = $Event->Values->message;
                    break;
                case 26:
                    $CommentStruct["header"] = sprintf("Capture point %s was blocked by %s",$Event->Values->capturePointNumber,$Player->Name);
                    break;
                case 27:
                    $CommentStruct["header"] = sprintf("%s got revenge on %s",$Player->Name,$Event->Players[1]->Name);
                    break;
                case 28:
                    $CommentStruct["header"] = "Mini Round Selected";
                    break;
                case 29:
                    $CommentStruct["header"] = "Mini Round Started";
                    break;
                case 30:
                    $CommentStruct["header"] = "Game Over!";
                    $CommentStruct["body"] = $Event->Values->reason;
                    
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
            \Unirest::timeout($this->settings->seconds->SteamTimeout);
            $Response = \Unirest::get($URL)->body;
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

    private function GetGameLogs(){
        //Get a settings file.
        $rootSettings = Site::$settingsManager->FindModuleDir("TfLog");
        Site::$settingsManager->CreateSettingsFiles($rootSettings . "settings.json", new TFLogSettingsFile());
        $this->settings = Site::$settingsManager->RetriveSettings($rootSettings . "settings.json");
        if($this->settings == false){
            return;
        }
        $path = Site::ResolvePath("%user-tflogs");
        foreach(new \recursiveIteratorIterator( new \recursiveDirectoryIterator($path)) as $file)
        {
            if(pathinfo($file->getFilename())['extension'] == "json")
            {
               $time = substr($file->getFilename(), 6,10);
               $time = intval($time);
               $this->UpdateSettingsLogs($time,$file->getPathName());
            }
        }
        
        $Players = array_keys(get_object_vars($this->settings->players));
        $this->SteamPlayerCache = Util::ArraySetKeyByProperty($this->Steam_GetPlayerSummaries($Players),"steamid");
    }
    private function GetMapThumbnail($mapname){
        if($mapname == "")
            return "";
        $Name = explode('_',$mapname)[1];
        return glob( 'content\maps' . $Name . '.')[0];
    }
    
    private function GetPlayerAvatars($players){
        $HTML = "";
        foreach($players as $i => $player){
            $i += 1;
            $avatar = $this->SteamPlayerCache[$player]->avatar;
            $HTML .= sprintf('<a href="%s" target="_new"><img src="%s"></img></a>',"index.php?request=tflogPlayerProfile&SteamID=" . $player,$avatar);
            if($i % 8 == 0){
                $HTML .= "</br>";
            }
        }
        return $HTML;
    }
    
    private function DeleteLog($logtime){
    }


    private function UpdateSettingsLogs($time,$Path){
        foreach($this->settings->LogFiles as $log){
            if($log->time == $time){
                return;
            }
        }

        $LogFile = new TFLogFile();
        $LogFile->time = $time;
        $LogFile->logfilepath = $Path;

        $FileContents = file_get_contents($Path);
        $LogFile->json = json_decode($FileContents);
        $this->settings->LogFiles[] = $LogFile;

        foreach($LogFile->json as $Event){
            //Add New Players
            foreach($Event->Players as $Player){
                if($this->SteamIDValid($Player->SteamID)){
                    $SteamID = intval($this->Steam_Convert32to64($Player->SteamID));
                    if(!in_array($SteamID, $LogFile->players)){
                        $LogFile->players[] = $SteamID;
                    }
                    $PlayerObj = $this->AddNewPlayer($SteamID);
                    $PlayerObj->stats = $this->CalculatePlayerStats($PlayerObj,$LogFile->json);
                }
            }
        }
        $this->stats = $this->CalculateLogStats($LogFile->json);
    }
    
    private function AddNewPlayer($PlayerID){
        if(!isset($this->settings->players->$PlayerID)){
            $Player = new TFLogPlayer();
            $Player->SteamID = $PlayerID;
            $this->settings->players->$PlayerID = $Player;
        }
        
        return $this->settings->players->$PlayerID;
    }
    
    
    private function CalculatePlayerStats($Player,$Events){
        return $Player->stats;
    }
    
    private function CalculateLogStats($Events){
        $Stats = array();
        $Stats["RedScore"] = 0;
        $Stats["BluScore"] = 0;
        foreach($Events as $Event){
        //Gather Data
            switch($Event->EventType){
                case 22:
                    $Stats["RedScore"] = $Event->Values->redScore;
                    break;
                case 23:
                    $Stats["BluScore"] = $Event->Values->bluScore;
                    break;
            }
        }
        return $Stats;
    }

    function ShowSelector(){
        $this->GetGameLogs();
        $Panel = array(new \stdClass());
        $Panel[0]->offset = 2;
        $Panel[0]->size = 8;
        $FooterPanel = array(new \stdClass());
        $FooterPanel[0]->offset = 4;
        $FooterPanel[0]->size = 4;
        $FooterPanel[0]->body = $this->manager->FireEvent("Theme.Panel",array("body"=>"Yay"));
        
        $TableOfLogs = new \Bread\Structures\BreadTableElement();
        $TableOfLogs->headingRow = new \Bread\Structures\BreadTableRow();
        $TableOfLogs->headingRow->FillOutRow(array("Map","Date","Players",""));
        
        $CopyOfLogs = Util::ArraySetKeyByProperty($this->settings->LogFiles, "time");
        krsort($CopyOfLogs);
        foreach($CopyOfLogs as $timestamp => $log){
            $Row = new \Bread\Structures\BreadTableRow();
            $Button = new \Bread\Structures\BreadFormElement();
            $Row->FillOutRow(array(sprintf('<img src="%1$s" class="mapthumb">%1$s</img>',$this->GetMapThumbnail($log->map)), date("F j, Y, g:i a",$timestamp),$this->GetPlayerAvatars($log->players),"ButtonToVisitPage"));
            $TableOfLogs->rows[] = $Row;
        }
        $Panel[0]->body = $this->manager->FireEvent("Theme.Panel",array("body"=>$this->manager->FireEvent("Theme.Table",$TableOfLogs)));
        
        return $this->manager->FireEvent("Theme.Layout.Grid.HorizonalStack",$Panel) . $this->manager->FireEvent("Theme.Layout.Grid.HorizonalStack",$FooterPanel);
    }
}

class TFLogSettingsFile{
    public $LogFiles = array();
    public $SteamTimeout = 5;
    public $lastAccessToSteam = array();
    public $SteamPlayerCache = array();
    function __construct(){
        $this->players = new \stdClass();
    }
}

class TFLogFile{
    public $logfilepath = "";
    public $date = 0;
    public $map = "";
    public $json = "";
    public $players = array();
    function __construct(){
        $this->stats = new \stdClass();
    }
}

class TFLogPlayer{
    public $SteamID;
    public $Name;
    function __construct(){
        $this->stats = new \stdClass();
    }
}