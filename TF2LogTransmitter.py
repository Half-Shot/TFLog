#!/bin/env python3
#Python daemon to read TF2 Logs and write them to a websocket.
import queue
import threading
import time
import os
import glob
import sys
import re
import datetime
import json
from ws4py.async_websocket import EchoWebSocket

from enum import Enum
class TF2Player(object):
    def __init__(self):
        self.Name = "playername"
        self.SteamID = "NOTSET"
        self.Team = TF2Team.Unknown
        self.Class = ""
    

class TF2EventType(Enum):
    Unknown = 0,
    ServerChangedValue = 1,
    BuiltObject = 2, #triggered
    RoundStart = 3, #triggered
    SetupBegins = 4, #triggered
    SetupEnds = 5, #triggered
    PlayerExtingished = 6, #triggered
    UberchargeDeployed = 7, #triggered
    KillAssist = 8, #triggered
    MedicDeath = 9, #triggered
    KilledObject = 10, #triggered
    KilledPlayer = 11,
    BuildingDestroyed = 12,
    RoleChange = 13,
    PointCaptured = 14, #triggered
    Dominiation = 15, #triggered
    Overtime = 16,
    MiniRoundWin = 17, #triggered
    MiniRoundTime = 18, #triggered
    RoundWin = 19, #triggered
    WinningTeam = 20,
    RoundTime = 21,
    RedScore = 22,
    BluScore = 23,
    ChatMessage = 24,
    TeamChatMessage = 25,
    CaptureBlocked = 26,
    Revenge = 27,
    MiniRoundSelected = 28,
    MiniRoundStart = 29,
    GameOver = 30
    
class TF2Team(Enum):
    Red = 0,
    Blu = 1,
    Unknown = 2,
    Unassigned = 3,
    Spectator = 4,
    NotJoined = 5

class TF2Event(object):
    def __init__(self):
        self.Timestamp = 0
        self.EventType = TF2EventType.Unknown
        self.Players = [TF2Player(),TF2Player()]
        self.Values = {}

class TF2Logger(object):
    FILE_WAITTIME = 1
    WEBSOCKET_PORT = 1521
    logFileIsCurrent = True
    OutputPath = False
    shouldRun = True
    RegexList ={"timestamp":                    '(?<=L )(.*?)(?=: )',
                "cvar_name":                    '(?<=: server_cvar: ")(.*?)(?=")',
                "cvar_value":                   '(?<=" ")(.*?)(?=")',
                "triggered_playername":         '(?<=")(.*?)(?=<)',
                "triggered_playertags":         '(?<=\<)(.*?)(?=>)',
                "triggered_againstplayername":  '(?<=against ")(.*?)(?=<)', #Againstplayertags can be found by checking for 4,5,6 in groups
                "triggered_victimplayername":   '(?<=\>" killed ")(.*?)(?=\<)',
                "triggered_type":               '(?<= triggered ")(.*?)(?=")',
                "world_triggered":              '(?<=World triggered ")(.*?)(?=")',
                "position":                     '(?<=\(position ")(.*?)(?=")',
                "attacker_position":            '(?<=\(position ")(.*?)(?=")',
                "assister_position":            '(?<=\(position ")(.*?)(?=")',
                "victim_position":              '(?<=\(position ")(.*?)(?=")',
                "object_type":                  '(?<=\(object ")(.*?)(?=")',
                "round_number":                 '(?<=\(round "round_)(.*?)(?=")',
                "killer_weapon":                '(?<= with ")(.*?)(?=")',
                "healing":                      '(?<=" \(healing ")(.*?)(?="\))',
                "ubercharge":                    '(?<=\) \(ubercharge ")(.*?)(?="\))',
                "majorEventType":               '(?<=" )(.*?)(?= ")', # E.g. killed or triggered
                "buildingWeapon":               '(?<=\) \(weapon ")(.*?)(?="\))', #Weapon used to kill a building
                "roleChange":                   '(?<=" changed role to ")(.*?)(?=")',
                "capturePointNumber":           '(?<=\(cp ")(.*?)(?="\))',
                "numberOfCapturers":            '(?<=\(numcappers ")(.*?)(?="\))',
                "winningTeam":                  '(?<=\(winner ")(.*?)(?="\))',
                "roundTime":                    '(?<=\(seconds ")(.*?)(?="\))',
                "RedScore":                     '(?<=Team "Red" current score ")(.*?)(?=")',
                "BluScore":                     '(?<=Team "Blue" current score ")(.*?)(?=")',
                "TeamScorePlayers":             '(?<= with ")(.*?)(?=" players)',
                "PlayerChatMessage":            '(?<=" say ")(.*?)(?=")',
                "PlayerChatMessageTeam":        '(?<=" say_team ")(.*?)(?=")',
                "ReasonForEvent":               '(?<=" reason ")(.*?)(?=")',
                }
    TriggerList = { "builtobject": TF2EventType.BuiltObject,
                    "Round_Start": TF2EventType.RoundStart,
                    "Round_Setup_Begin": TF2EventType.SetupBegins,
                    "Round_Setup_End": TF2EventType.SetupEnds,
                    "Round_Overtime": TF2EventType.Overtime,
                    "player_extinguished": TF2EventType.PlayerExtingished,
                    "chargedeployed": TF2EventType.UberchargeDeployed,
                    "kill assist": TF2EventType.KillAssist,
                    "medic_death": TF2EventType.MedicDeath,
                    "killedobject": TF2EventType.KilledObject,
                    "pointcaptured": TF2EventType.PointCaptured,
                    "domination": TF2EventType.Dominiation,
                    "Mini_Round_Win": TF2EventType.MiniRoundWin,
                    "Mini_Round_Length": TF2EventType.MiniRoundTime,
                    "Mini_Round_Selected": TF2EventType.MiniRoundSelected,
                    "Mini_Round_Start": TF2EventType.MiniRoundStart,
                    "Round_Win": TF2EventType.RoundWin,
                    "captureblocked": TF2EventType.CaptureBlocked,
                    "revenge": TF2EventType.Revenge,
                    "Game_Over" : TF2EventType.GameOver
                  }
    TeamList = {
        "Red" : TF2Team.Red,
        "Blue": TF2Team.Blu,
        "Unassigned": TF2Team.Unassigned,
        "Spectator": TF2Team.Spectator,
        "": TF2Team.NotJoined
    }
                
    def RegexReader(self,string):
        eventObj = TF2Event()
        for RegName, RegPattern in self.RegexList.items():
            regex = re.findall(RegPattern,string)
            if len(regex) > 0:
                value = regex[0]
                #try:
                if RegName == "timestamp":
                    try:
                        eventObj.Timestamp = value
                    except:
                        print("Error processing timestamp",value)
                elif RegName == "cvar_name":
                    eventObj.EventType = TF2EventType.ServerChangedValue
                    eventObj.Values["cvar"] = value
                    regex = re.search(self.RegexList["cvar_value"],string)
                    eventObj.Values["value"] = regex.groups(0)
                elif RegName == "triggered_type":
                    eventObj.EventType = self.TriggerList[value]
                elif RegName == "triggered_playername":
                    eventObj.Players[0].Name = value
                elif RegName == "triggered_againstplayername":
                    eventObj.Players[1].Name = value
                elif RegName == "triggered_victimplayername":
                    eventObj.EventType = TF2EventType.KilledPlayer
                    eventObj.Players[1].Name = value
                elif RegName == "triggered_playertags":
                    if len(regex) > 2:
                        eventObj.Players[0].SteamID = regex[1]
                        try:
                            eventObj.Players[0].Team = self.TeamList[regex[2]]
                        except KeyError:
                            print("Unknown Team:",regex[2])
                        if len(regex) > 5:
                            eventObj.Players[1].SteamID = regex[4]
                            try:
                                eventObj.Players[1].Team = self.TeamList[regex[5]]
                            except KeyError:
                                print("Unknown Team:",regex[5])
                elif RegName == "object_type":
                    eventObj.Values["objType"] = value
                elif RegName == "assister_position":
                    eventObj.Values["assister_position"] = value
                elif RegName == "healing":
                    eventObj.Values["healing"] = value
                elif RegName == "ubercharge":
                    eventObj.Values["ubercharge"] = value   
                elif RegName == "buildingWeapon":
                    eventObj.EventType = TF2EventType.BuildingDestroyed
                    eventObj.Values["buildingWeapon"] = value   
                elif RegName == "round_number":
                    eventObj.Values["roundNumber"] = value   
                elif RegName == "killer_weapon":
                    eventObj.Values["killer_weapon"] = value   
                elif RegName == "majorEventType":
                    if value == "killed":
                        eventObj.EventType = TF2EventType.KilledPlayer
                elif RegName == "roleChange":
                    eventObj.EventType = TF2EventType.RoleChange
                    eventObj.Values["role"] = value   
                elif RegName == "capturePointNumber":
                    eventObj.Values["capturePointNumber"] = value   
                elif RegName == "numberOfCapturers":
                    eventObj.Values["numberOfCapturers"] = value  
                elif RegName == "winningTeam":
                    eventObj.Values["winningTeam"] = self.TeamList[value]
                    eventObj.EventType = TF2EventType.WinningTeam
                elif RegName == "roundTime":
                    eventObj.EventType = TF2EventType.RoundTime
                elif RegName == "RedScore":
                    eventObj.Values["redScore"] = value
                    eventObj.EventType = TF2EventType.RedScore
                elif RegName == "RedScore":
                    eventObj.Values["redScore"] = value
                    eventObj.EventType = TF2EventType.RedScore
                elif RegName == "PlayerChatMessage":
                    eventObj.Values["message"] = value
                    eventObj.EventType = TF2EventType.ChatMessage
                elif RegName == "PlayerChatMessageTeam":
                    eventObj.Values["message"] = value
                    eventObj.EventType = TF2EventType.TeamChatMessage
                elif RegName == "ReasonForEvent":
                    eventObj.Values["reason"] = value
                    
                    
                #except:
                #    print("An error occured parsing a log line.")
                #    print("Infomation key:",RegName)
                #    print("value:",value)
                
        if eventObj.EventType == TF2EventType.Unknown:  
            return False      
        return eventObj
                
    def GetLatestLog(self,directory):
        return max(glob.iglob(directory + '/*.log'), key=os.path.getctime)
        

    def show_help(self):
        print("====Help===")
        print("There is no help for you.")

    def start(self):
        if len(sys.argv) < 2:
            print("Missing directory path to logs!")
            sys.exit(1)
        arg = sys.argv[1]
        if arg == "":
            print("Missing directory path to logs!")
            sys.exit(1)
        if arg == "help" or arg == "--help":
            show_help()
            sys.exit(0)
        if len(sys.argv) == 3:
            self.OutputPath = sys.argv[2]
            
        path = os.path.abspath(sys.argv[1])
        print("Selected log path",path)
        rawQueue = queue.Queue()
        eventQueue = queue.Queue()
        
        readThread   =  threading.Thread(target=self.StatReader ,args = (path,rawQueue))
        readThread.daemon = True
        decodeThread =  threading.Thread(target=self.StatDecoder,args = (rawQueue,eventQueue))
        decodeThread.daemon = True
        writeThread  =  threading.Thread(target=self.StatWriter ,args = (eventQueue,False))
        writeThread.daemon = True
        
        readThread.start()
        decodeThread.start()
        writeThread.start()

        try:
            readThread.join()
            decodeThread.join()
            writeThread.join()
        except (KeyboardInterrupt, SystemExit):
            print("Closing Threads")
            self.shouldRun = False #Mostly always on
            print("All done. Goodbye")
            sys.exit(0)

    def StatReader(self,path,rawQueue):
        lastLogFile = ""
        filepath = "NOLOG"
        while self.shouldRun:
            filepath = self.GetLatestLog(path)
            if lastLogFile != filepath:
                logFile = open(filepath, 'r')
                lastLogFile = filepath
                print("Selected log file",filepath)
                rawQueue.put("NewLogFile") 
            self.logFileIsCurrent = True  #Set to false at the end of a log file.
            while self.logFileIsCurrent:
                where = logFile.tell()
                line = logFile.readline()
                if not line:
                    logFile.seek(where)
                    time.sleep(self.FILE_WAITTIME)
                else:
                    rawQueue.put(line) 
        logFile.close()
                
    def StatDecoder(self,rawQueue,eventQueue):
        while self.shouldRun:
            try:
                currentItem = rawQueue.get(True,10)
                if currentItem == "NewLogFile":
                    eventQueue.put("NewLogFile")
                    
                eventObj = self.RegexReader(currentItem)
                if eventObj != False:
                    eventQueue.put(eventObj)
            except queue.Empty:
                self.logFileIsCurrent = False
    
        
    def StatWriter(self,eventQueue,BlankThing):
        #ws = socket.socket()
        #ws.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
        #ws.bind(('', self.WEBSOCKET_PORT))
        #ws.setblocking(False)
        #ws.listen(5)
        #clients = []
        EventArray = []
        while self.shouldRun:
            if self.OutputPath != False:
                outFile = open(self.OutputPath + "/tflog_" + str(int(time.time())) + ".json", 'w')
            else:
                outFile = open("/dev/null", 'w')
            while self.shouldRun:   
                try:
                    currentItem = eventQueue.get(True,10)
                    if currentItem == "NewLogFile":
                        print("New Output File")
                        break;
                    jsonObject = self.JsonifyTF2Event(currentItem)
                    try:
                        jsonString = json.dumps(jsonObject)
                        EventArray.append(jsonObject)
                    except TypeError:
                        pass#print(EventArray)
                        
                    outFile.seek(0)
                    outFile.write(json.dumps(EventArray))
                except queue.Empty:
                    continue;
                
    def handshake (client):
        data = client.recv(1024)
        headers = parse_headers(data)
        digest = create_hash(
            headers['Sec-WebSocket-Key1'],
            headers['Sec-WebSocket-Key2'],
            headers['code']
        )
        shake = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n"
        shake += "Upgrade: WebSocket\r\n"
        shake += "Connection: Upgrade\r\n"
        shake += "Sec-WebSocket-Origin: %s\r\n" % (headers['Origin'])
        shake += "Sec-WebSocket-Location: ws://%s/stuff\r\n" % (headers['Host'])
        shake += "Sec-WebSocket-Protocol: sample\r\n\r\n"
        shake += digest
        return client.send(shake)
    
    def JsonifyTF2Event(self,currentItem):
        currentItem = currentItem.__dict__
        currentItem["EventType"] = currentItem["EventType"].value
        currentItem["EventType"] = currentItem["EventType"][0]
        for i in range(len(currentItem["Players"])):
            Player = currentItem["Players"][i]
            currentItem["Players"][i] = Player.__dict__
            currentItem["Players"][i]["Team"] = currentItem["Players"][i]["Team"].value
            currentItem["Players"][i]["Team"] = currentItem["Players"][i]["Team"][0]
                
        return currentItem
        
if __name__ == "__main__":
    logger = TF2Logger()
    logger.start()
    sys.exit(0)
    
