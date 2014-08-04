#Object file for TF2LogTransmitter.py

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
    BuildingDestroyed = 12, #Not Used
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
    GameOver = 30,
    MilkAttack = 31,
    JarateAttack = 32,
    ScrambleTeams = 33
    
    
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
        self.Players = []
        self.Values = {}
