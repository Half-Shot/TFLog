<?php
namespace Bread\Modules;
use Bread\Site as Site;
use Bread\Utilitys as Util;
class TFLog extends Module
{
    private $HiddenEventTypes = array(0,1,9,18,28,29);
    private $SteamPlayerSummary = "http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=";
    private $settings = false;
    private $players = false;
    private $logs = false;
    function __construct($manager,$name)
    {
            parent::__construct($manager,$name);
            $SteamAPIKey = "DEBB2268D244BBE422714C29563900FE";
            $this->SteamPlayerSummary .= $SteamAPIKey . "&steamids=";
    }
    
    function ShowStream(){
        $HTML = "";
        $this->GetGameLogs();
        Site::AddScript(Util::ResolvePath("%user-modules/TFLog/livefeed.js"),true);
        
        $HeaderPanel = array(new \stdClass(),new \stdClass(),new \stdClass());
        $FooterPanel = array(new \stdClass());
        $Panel = array(new \stdClass(),new \stdClass(), new \stdClass());
        
        $FooterPanel[0]->offset = 5;
        $FooterPanel[0]->size = 2;
        
        $HeaderPanel[0]->offset = 1;
        $HeaderPanel[0]->size = 2;
        $HeaderPanel[1]->offset = 1;
        $HeaderPanel[1]->size = 4;
        $HeaderPanel[2]->offset = 1;
        $HeaderPanel[2]->size = 2;
        $TableOfEvents = new \Bread\Structures\BreadTableElement();
        $TableOfEvents->headingRow = new \Bread\Structures\BreadTableRow();
        if(!array_key_exists("GameID",Site::getRequest()->arguments)){
            $GameLog = current((array)$this->logs);
            $GameID = array_keys((array)$this->logs, $GameLog)[0];
        }
        else{
            $GameID = Site::getRequest()->arguments["GameID"];
            if(isset($this->logs->$GameID)){
                $GameLog = $this->logs->$GameID;
            }
            else{
                return "No such game.";
            }
        }
        $Events = array_reverse(json_decode(file_get_contents($GameLog->logfilepath)));
        $NItems = Util::EmptySubArray("items",Site::getRequest()->arguments, 50);
        $StartItem = Util::EmptySubArray("start",Site::getRequest()->arguments, 0);
        
        $RequestArray = Site::getRequest()->arguments;
        if(!array_key_exists("start", $RequestArray)){
            $RequestArray["start"] = 0;
            $start = 0;
        }
        else{
            $start = $RequestArray["start"];
        }
        $RequestArray["GameID"] = $GameID;
        unset($RequestArray["BASEURL"]);
        
        $RequestArray["start"] = 0;
        $FooterPanel[0]->body =    "<a href='" . Util::CondenseURLParams(false, $RequestArray) . "' class='icon-navigate'>" . $this->manager->FireEvent("Theme.Icon","fast-backward") . "</a>";
        $RequestArray["start"] = $start;
        if(intval($RequestArray["start"]) - $NItems >= 0){
            $RequestArray["start"] = intval($RequestArray["start"]) - $NItems; 
            $FooterPanel[0]->body .= "<a class='icon-navigate' href='" . Util::CondenseURLParams(false, $RequestArray) . "'>" . $this->manager->FireEvent("Theme.Icon","left-arrow") . "</a>";
        }
        
        if(intval($start) + $NItems < count($Events)){
            $RequestArray["start"] = intval($start) + $NItems; 
            $FooterPanel[0]->body .= "<a class='icon-navigate' href='" . Util::CondenseURLParams(false, $RequestArray) . "'>" . $this->manager->FireEvent("Theme.Icon","right-arrow") . "</a>";
        }
        $RequestArray["start"] = count($Events) - $NItems;
        $FooterPanel[0]->body .= "<a class='icon-navigate' href='" . Util::CondenseURLParams(false, $RequestArray) . "'>" . $this->manager->FireEvent("Theme.Icon","fast-forward") . "</a>";
        
        $StartItem = Util::EmptySubArray("start",Site::getRequest()->arguments, 0);
        
        $TableOfEvents->headingRow->FillOutRow(array(""));
        $Items = 1;
        foreach($Events as $i => $Event){    
            if($Items >= $NItems){
                break;
            }
            if($i < $StartItem){
                continue;
            }
            else{
                $Items += 1;
            }
            
            if(in_array($Event->EventType, $this->HiddenEventTypes)){
                unset($Events[$i]);
                $Items -= 1;
            }     
        }
        //foreach($this->SteamPlayerCache as $player){
        //    $this->players[$player->steamid] = $player;
        //}
        $HeaderPanel[0]->body = '<div class="redscore scorepanel">' . $this->manager->FireEvent("Theme.Panel",array("body"=>$GameLog->stats["RedScore"])) . '</div>';
        $HeaderPanel[1]->body = '<div class="livepanel scorepanel">' . $this->manager->FireEvent("Theme.Panel",array("body"=>"<div class='islive'>Not Live</div>")) . '</div>';
        $HeaderPanel[2]->body = '<div class="bluscore scorepanel">' . $this->manager->FireEvent("Theme.Panel",array("body"=>$GameLog->stats["BluScore"])) . '</div>';
        $Items = 0;
        $i = -1;
        foreach($Events as $Event){
            $i++;
            if($Items >= $NItems){
                break;
            }
            if($i < $StartItem){
                continue;
            }
            else{
                $Items++;
            }
            
            $EventHTML = $this->BuildEvent($Event);
            if(!empty($EventHTML)){
                $Row = new \Bread\Structures\BreadTableRow();
                $TableOfEvents->rows[] = $Row;
                $Row->FillOutRow(array($EventHTML));
            }
        }
        
        
        $Panel[0]->size = 2;
        $Panel[2]->size = 2;
        
        //Red Team
        $RedTeamlist = new \Bread\Structures\BreadTableElement();
        $RedTeamlist->headingRow = new \Bread\Structures\BreadTableRow();$RedTeamlist->headingRow->FillOutRow(array("Name","Points","Class","Kills","Deaths"));
        
        //Blu Team
        $BluTeamList = new \Bread\Structures\BreadTableElement();
        $BluTeamList->headingRow = new \Bread\Structures\BreadTableRow();$BluTeamList->headingRow->FillOutRow(array("Name","Points","Class","Kills","Deaths"));
        
        $Panel[0]->body = $this->manager->FireEvent("Theme.Table",$RedTeamlist);
        $Panel[2]->body = $this->manager->FireEvent("Theme.Table",$BluTeamList);
        
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

    function ProcessWebEvent(){
        $rootSettings = Site::$settingsManager->FindModuleDir("TfLog");
        Site::$settingsManager->CreateSettingsFiles($rootSettings . "settings.json", new TFLogSettingsFile());
        $this->settings = Site::$settingsManager->RetriveSettings($rootSettings . "settings.json");
        $event = json_decode($_REQUEST["tfevent"]);
        $HTML = $this->BuildEvent($event);
        return $HTML;
    }
    
    private function BuildEvent($Event){
            $CommentHTML = "";
            $CommentStruct = array();
            $AvatarPath = "";
            //Player
            if(count($Event->Players) > 0){
                $Player = $Event->Players[0];
                $SteamPlayer = False;
                if($this->SteamIDValid($Player->SteamID)){
                    $SteamID = $this->Steam_Convert32to64($Player->SteamID);
                    if(is_array($this->settings->SteamPlayerCache)){
                        $SteamPlayer = $this->settings->SteamPlayerCache[$SteamID];
                    }
                    else{
                        $SteamPlayer = $this->settings->SteamPlayerCache->$SteamID;
                    }
                    
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
                   case "OBJ_ATTACHMENT_SAPPER":
                       $Event->Values->objType = "Sapper";
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
                    if($Event->Values->objType == "OBJ_ATTACHENT_SAPPER"){
                        $CommentStruct["header"] = sprintf("%s attached a sapper.",$Player->Name);
                    }
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
                    if(array_key_exists(1,$Event->Players)){
                        $CommentStruct["header"] = sprintf("%s depolyed an uberchage on %s.",$Player->Name,$Event->Players[1]->Name);
                    }
                    else{
                        $CommentStruct["header"] = sprintf("%s depolyed an uberchage.",$Player->Name);
                    }
                    break;
                case 8:
                    $CommentStruct["header"] = sprintf("%s got a kill assist against %s.",$Player->Name,$Event->Players[1]->Name);
                    break;
                case 9:
                    $CommentStruct["header"] = sprintf("%s killed a medic  %s.",$Player->Name,$Event->Players[1]->Name);
                    break;
                case 10:
                    $CommentStruct["header"] = sprintf("%s destroyed a %s.",$Player->Name,$Event->Values->objType);
                    if(isset($Event->Values->buildingWeapon)){
                        $CommentStruct["body"] =  sprintf("Used a %s ",$this->GetWeaponName($Event->Values->buildingWeapon));
                    }
                    break;
                case 11:
                    $CommentStruct["header"] = sprintf("%s killed %s with a %s.",$Player->Name,$Event->Players[1]->Name,$this->GetWeaponName($Event->Values->killer_weapon));
                    $CommentStruct["body"] = sprintf('<img class="weaponiocn" src="content/weapons/%1$s.png"></img>',$Event->Values->killer_weapon);
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
                        $CommentStruct["body"] =  sprintf('<img class="teamemblem" src="content/emblem/emblem_%1$s_blu.png"></img>',$Event->Values->role);
                    }
                    break;
                case 14:
                    $Names = "";
                    foreach($Player as $Event->Players){
                        $Names .= $Player->Name . " ";
                    }
                    if($Player->Team == 0){
                        $CommentStruct["header"] = sprintf("Red captured point %s.",$Event->Values->capturePointNumber);
                    }
                    else{
                        $CommentStruct["header"] = sprintf("Blu captured point %s.",$Event->Values->capturePointNumber);
                    }
                    //$CommentStruct["body"] =  sprintf("This was captured by %s ." ,$Names);
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
                case 31:
                    $CommentStruct["header"] = sprintf("%s threw mad milk on %s",$Player->Name,$Event->Players[1]->Name);
                    break;
                case 32:
                    $CommentStruct["header"] = sprintf("%s jarated %s",$Player->Name,$Event->Players[1]->Name);
                    break;
                case 33:
                    $CommentStruct["header"] = "Teams were scrambled";
                    break;
                default:
                    $CommentStruct["header"] = $Event->EventType;
                    break;
                    
            }
            //$CommentStruct["body"] = "Some infomation about it";
            $CommentHTML = Site::$moduleManager->FireEvent("Theme.Comment",$CommentStruct);
            return $CommentHTML;
    }
    
    function GetWeaponName($WeaponID){
        switch($WeaponID){
            case "sniperrifle":
                return "Sniper Rifle";
            case "tf_projectile_pipe_remote":
                return "Sticky Bomb";
            case "tf_projectile_rocket":
                return "Rocket Launcher";
            case "deflect_promode":
                return "Deflected Pipebomb";
            case "knife":
                return "Spy Knife";
            case "tf_projectile_pipe":
                return "Pipe Bomb";
            case "world":
                return "Environmental Stuff";
            case "scattergun":
                return "Scatter Gun";
            case "flamethrower":
                return "Flamethrower";
            case "minigun":
                return "Minigun";
            case "deflect_rocket":
                return "Deflected Rocket";
            case "obj_sentrygun3":
                return "Level 3 Sentry Gun";
            case "obj_sentrygun2":
                return "Level 2 Sentry Gun";
            case "obj_sentrygun1":
                return "Level 1 Sentry Gun";
            case "obj_sentrygun":
                return "Level 1 Sentry Gun";
            case "pistol_scout":
                return "Pistol";
            case "shotgun_soldier":
                return "Shotgun";
            case "loch_n_load":
                return "Load n Load";
            case "shotgun_primary":
                return "Shotgun";
            case "shotgun_pyro":
                return "Shotgun";
            default:
                return $WeaponID;
        }
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
            \Unirest::timeout($this->settings->SteamTimeout);
            $Response = \Unirest::get($URL)->body;
            foreach($Response->response->players as $Player){
                $jsonObj[] = $Player;
            }
            file_put_contents($filename, json_encode($jsonObj));
        }
        $this->settings->SteamPlayerCache = Util::ArraySetKeyByProperty($jsonObj, "steamid");
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

    
    private function GetMapThumbnail($mapname){
        if($mapname == "")
            return "";
        $Name = explode('_',$mapname)[1];
        return glob( 'content\\maps\\' . $Name . '.')[0];
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
    
    private function GetGameLogs(){
        //Get a settings file.
        $rootSettings = Site::$settingsManager->FindModuleDir("TfLog");
        Site::$settingsManager->CreateSettingsFiles($rootSettings . "settings.json", new TFLogSettingsFile());
        $this->settings = Site::$settingsManager->RetriveSettings($rootSettings . "settings.json");
        
        Site::$settingsManager->CreateSettingsFiles($rootSettings . "players.json", new \stdClass());
        $this->players = Site::$settingsManager->RetriveSettings($rootSettings . "players.json");
        
        Site::$settingsManager->CreateSettingsFiles($rootSettings . "logs.json", new \stdClass());
        $this->logs = Site::$settingsManager->RetriveSettings($rootSettings . "logs.json");

        foreach($this->logs as $LogFile){
            if(!file_exists($LogFile->logfilepath)){
               $this->DeleteLog($LogFile->time);
            }
        }
        
        $path = Site::ResolvePath("%user-tflogs");
        foreach(new \recursiveIteratorIterator( new \recursiveDirectoryIterator($path)) as $file)
        {
            if(pathinfo($file->getFilename())['extension'] == "json")
            {
               $time = substr($file->getFilename(), 6,10);
               $time = intval($time);
               $LogFile = $this->GetLogFile($time,$file->getPathName());
               if(md5_file($LogFile->logfilepath) != $LogFile->MD5){
                $this->ReadSettingsFile($LogFile);
               }
            }
            
        }
        
        $this->SteamPlayerCache = Util::ArraySetKeyByProperty($this->Steam_GetPlayerSummaries(array_keys((array)$this->players)),"steamid");
    }
    
    private function DeleteLog($logtime){
        foreach($this->logs as $i => $log){
            if($log->time == $logtime){
                unset($this->logs->$i);
            }
        }
    }

    private function ReadSettingsFile($LogFile)
    {
        $json = json_decode(file_get_contents($LogFile->logfilepath));
        if($json == null)
            return false;
        foreach($json as $Event){
            //Add New Players
            foreach($Event->Players as $Player){
                if($this->SteamIDValid($Player->SteamID)){
                    $SteamID = intval($this->Steam_Convert32to64($Player->SteamID));
                    if(array_key_exists($SteamID,$LogFile->players)){
                        $LogFile->players[] = $SteamID;
                    }
                    if(!isset($this->players->$SteamID)){
                        $this->AddNewPlayer($SteamID);
                    }
                }
            }
        }
        $LogFile->stats = $this->CalculateLogStats($json);
    }
    
    private function GetLogFile($time,$Path){
        foreach($this->logs as $log){
            if($log->time == $time){
                return $log;
            }
        }

        $LogFile = new TFLogFile();
        $LogFile->time = $time;
        $LogFile->logfilepath = $Path;
        $LogFile->lastCheck = 0;
        $LogFile->MD5 = "";
        $this->logs->$time = $LogFile;
        return $LogFile;
    }
    
    private function AddNewPlayer($PlayerID){
        if(!isset($this->settings->players->$PlayerID)){
            $Player = new TFLogPlayer();
            $Player->SteamID = $PlayerID;
            $this->players->$PlayerID = $Player;
        }
        
        return $this->players->$PlayerID;
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
                    if(isset($Event->Values->redScore)){
                        $Stats["RedScore"] = $Event->Values->redScore;
                    }
                    break;
                case 23:
                    if(isset($Event->Values->bluScore)){
                        $Stats["BluScore"] = $Event->Values->bluScore;
                    }
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
    public $SteamPlayerCache;
    function __construct(){
        $this->SteamPlayerCache = new \stdClass();
    }
}

class TFLogFile{
    public $logfilepath = "";
    public $date = 0;
    public $map = "";
    public $json;
    public $MD5 = "";
    public $players = array();
    function __construct(){
        $this->stats = new \stdClass();
    }
}

class TFLogPlayer{
    public $SteamID;
    function __construct(){
        $this->stats = new \stdClass();
    }
}