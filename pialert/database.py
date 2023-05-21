""" all things database to support Pi.Alert """

import sqlite3

from const import fullDbPath
from logger import print_log, mylog

#===============================================================================
# SQL queries
#===============================================================================
sql_devices_all = "select dev_MAC, dev_Name, dev_DeviceType, dev_Vendor, dev_Group, dev_FirstConnection, dev_LastConnection, dev_LastIP, dev_StaticIP, dev_PresentLastScan, dev_LastNotification, dev_NewDevice, dev_Network_Node_MAC_ADDR, dev_Network_Node_port,  dev_Icon from Devices"
sql_devices_stats =  "SELECT Online_Devices as online, Down_Devices as down, All_Devices as 'all', Archived_Devices as archived, (select count(*) from Devices a where dev_NewDevice = 1 ) as new, (select count(*) from Devices a where dev_Name = '(unknown)' or dev_Name = '(name not found)' ) as unknown from Online_History order by Scan_Date desc limit  1"
sql_nmap_scan_all = "SELECT  * FROM Nmap_Scan"
sql_pholus_scan_all = "SELECT  * FROM Pholus_Scan"
sql_events_pending_alert = "SELECT  * FROM Events where eve_PendingAlertEmail is not 0"
sql_settings = "SELECT  * FROM Settings"
sql_plugins_objects = "SELECT  * FROM Plugins_Objects"
sql_language_strings = "SELECT  * FROM Plugins_Language_Strings"
sql_plugins_events = "SELECT  * FROM Plugins_Events"
sql_plugins_history = "SELECT  * FROM Plugins_History ORDER BY 'Index' DESC"
sql_new_devices = """SELECT * FROM ( SELECT eve_IP as dev_LastIP, eve_MAC as dev_MAC FROM Events_Devices
                                                                WHERE eve_PendingAlertEmail = 1
                                                                AND eve_EventType = 'New Device'
                                    ORDER BY eve_DateTime ) t1
                                    LEFT JOIN 
                                    (
                                        SELECT dev_Name, dev_MAC as dev_MAC_t2 FROM Devices 
                                    ) t2 
                                    ON t1.dev_MAC = t2.dev_MAC_t2"""


class DB():

    def __init__(self):
        self.sql = None
        self.sql_connection = None
       
    def openDB (self):
        # Check if DB is open
        if self.sql_connection != None :
            mylog('debug','openDB: databse already open')
            return

        mylog('none', 'Opening DB' )
        # Open DB and Cursor
        self.sql_connection = sqlite3.connect (fullDbPath, isolation_level=None)
        self.sql_connection.execute('pragma journal_mode=wal') #
        self.sql_connection.text_factory = str
        self.sql_connection.row_factory = sqlite3.Row
        self.sql = self.sql_connection.cursor()

  
    #-------------------------------------------------------------------------------
    def commitDB (self):
        if self.sql_connection == None :
            mylog('debug','commitDB: databse is not open')
            return
        mylog('debug','commitDB: comiting DB changes')

        # Commit changes to DB
        self.sql_connection.commit()
    
    #-------------------------------------------------------------------------------
    def get_sql_array(self, query):    
        if self.sql_connection == None :
            mylog('debug','getQueryArray: databse is not open')
            return
        
        self.sql.execute(query)
        rows = self.sql.fetchall()
        self.commitDB()

        #  convert result into list of lists
        arr = []
        for row in rows:
            r_temp = []
            for column in row:
                r_temp.append(column)
            arr.append(r_temp)

        return arr







#-------------------------------------------------------------------------------
def initOrSetParam(db, parID, parValue):    
    sql_connection = db.sql_connection
    sql = db.sql

    sql.execute ("INSERT INTO Parameters(par_ID, par_Value) VALUES('"+str(parID)+"', '"+str(parValue)+"') ON CONFLICT(par_ID) DO UPDATE SET par_Value='"+str(parValue)+"' where par_ID='"+str(parID)+"'")        

    db.commitDB()

#-------------------------------------------------------------------------------
def updateState(db, newState):    

    db.sql.execute ("UPDATE Parameters SET par_Value='"+ newState +"' WHERE par_ID='Back_App_State'")        

    db.commitDB()



#-------------------------------------------------------------------------------
def upgradeDB(db: DB()):
    sql = db.sql  #TO-DO 

    # indicates, if Online_History table is available 
    onlineHistoryAvailable = db.sql.execute("""
    SELECT name FROM sqlite_master WHERE type='table'
    AND name='Online_History'; 
    """).fetchall() != []

    # Check if it is incompatible (Check if table has all required columns)
    isIncompatible = False
    
    if onlineHistoryAvailable :
      isIncompatible = sql.execute ("""
      SELECT COUNT(*) AS CNTREC FROM pragma_table_info('Online_History') WHERE name='Archived_Devices'
      """).fetchone()[0] == 0
    
    # Drop table if available, but incompatible
    if onlineHistoryAvailable and isIncompatible:      
      mylog('none','[upgradeDB] Table is incompatible, Dropping the Online_History table')
      sql.execute("DROP TABLE Online_History;")
      onlineHistoryAvailable = False

    if onlineHistoryAvailable == False :
      sql.execute("""      
      CREATE TABLE "Online_History" (
        "Index"	INTEGER,
        "Scan_Date"	TEXT,
        "Online_Devices"	INTEGER,
        "Down_Devices"	INTEGER,
        "All_Devices"	INTEGER,
        "Archived_Devices" INTEGER,
        PRIMARY KEY("Index" AUTOINCREMENT)
      );      
      """)

    # Alter Devices table
    # dev_Network_Node_MAC_ADDR column
    dev_Network_Node_MAC_ADDR_missing = sql.execute ("""
      SELECT COUNT(*) AS CNTREC FROM pragma_table_info('Devices') WHERE name='dev_Network_Node_MAC_ADDR'
      """).fetchone()[0] == 0

    if dev_Network_Node_MAC_ADDR_missing :
      mylog('verbose', ["[upgradeDB] Adding dev_Network_Node_MAC_ADDR to the Devices table"])   
      sql.execute("""      
      ALTER TABLE "Devices" ADD "dev_Network_Node_MAC_ADDR" TEXT      
      """)

    # dev_Network_Node_port column
    dev_Network_Node_port_missing = sql.execute ("""
      SELECT COUNT(*) AS CNTREC FROM pragma_table_info('Devices') WHERE name='dev_Network_Node_port'
      """).fetchone()[0] == 0

    if dev_Network_Node_port_missing :
      mylog('verbose', ["[upgradeDB] Adding dev_Network_Node_port to the Devices table"])     
      sql.execute("""      
      ALTER TABLE "Devices" ADD "dev_Network_Node_port" INTEGER 
      """)

    # dev_Icon column
    dev_Icon_missing = sql.execute ("""
      SELECT COUNT(*) AS CNTREC FROM pragma_table_info('Devices') WHERE name='dev_Icon'
      """).fetchone()[0] == 0

    if dev_Icon_missing :
      mylog('verbose', ["[upgradeDB] Adding dev_Icon to the Devices table"])     
      sql.execute("""      
      ALTER TABLE "Devices" ADD "dev_Icon" TEXT 
      """)

    # indicates, if Settings table is available 
    settingsMissing = sql.execute("""
    SELECT name FROM sqlite_master WHERE type='table'
    AND name='Settings'; 
    """).fetchone() == None

    # Re-creating Settings table    
    mylog('verbose', ["[upgradeDB] Re-creating Settings table"])

    if settingsMissing == False:   
        sql.execute("DROP TABLE Settings;")       

    sql.execute("""      
    CREATE TABLE "Settings" (        
    "Code_Name"	    TEXT,
    "Display_Name"	TEXT,
    "Description"	TEXT,        
    "Type"          TEXT,
    "Options"       TEXT,
    "RegEx"         TEXT,
    "Value"	        TEXT,
    "Group"	        TEXT,
    "Events"	    TEXT
    );      
    """)

    # indicates, if Pholus_Scan table is available 
    pholusScanMissing = sql.execute("""
    SELECT name FROM sqlite_master WHERE type='table'
    AND name='Pholus_Scan'; 
    """).fetchone() == None

    # if pholusScanMissing == False:
    #     # Re-creating Pholus_Scan table  
    #     sql.execute("DROP TABLE Pholus_Scan;")       
    #     pholusScanMissing = True  

    if pholusScanMissing:
        mylog('verbose', ["[upgradeDB] Re-creating Pholus_Scan table"])
        sql.execute("""      
        CREATE TABLE "Pholus_Scan" (        
        "Index"	          INTEGER,
        "Info"	          TEXT,
        "Time"	          TEXT,
        "MAC"	          TEXT,
        "IP_v4_or_v6"	  TEXT,
        "Record_Type"	  TEXT,
        "Value"           TEXT,
        "Extra"           TEXT,
        PRIMARY KEY("Index" AUTOINCREMENT)
        );      
        """)

    # indicates, if Nmap_Scan table is available 
    nmapScanMissing = sql.execute("""
    SELECT name FROM sqlite_master WHERE type='table'
    AND name='Nmap_Scan'; 
    """).fetchone() == None

     # Re-creating Parameters table
    mylog('verbose', ["[upgradeDB] Re-creating Parameters table"])
    sql.execute("DROP TABLE Parameters;")

    sql.execute("""      
      CREATE TABLE "Parameters" (
        "par_ID" TEXT PRIMARY KEY,
        "par_Value"	TEXT
      );      
      """)

    # Initialize Parameters if unavailable
    initOrSetParam(db, 'Back_App_State','Initializing')

    # if nmapScanMissing == False:
    #     # Re-creating Nmap_Scan table    
    #     sql.execute("DROP TABLE Nmap_Scan;")       
    #     nmapScanMissing = True  

    if nmapScanMissing:
        mylog('verbose', ["[upgradeDB] Re-creating Nmap_Scan table"])
        sql.execute("""      
        CREATE TABLE "Nmap_Scan" (        
        "Index"	          INTEGER,
        "MAC"	          TEXT,
        "Port"	          TEXT,
        "Time"	          TEXT,        
        "State"	          TEXT,
        "Service"	      TEXT,       
        "Extra"           TEXT,
        PRIMARY KEY("Index" AUTOINCREMENT)
        );      
        """)

    # Plugin state
    sql_Plugins_Objects = """ CREATE TABLE IF NOT EXISTS Plugins_Objects(
                        "Index"	          INTEGER,
                        Plugin TEXT NOT NULL,
                        Object_PrimaryID TEXT NOT NULL,
                        Object_SecondaryID TEXT NOT NULL,
                        DateTimeCreated TEXT NOT NULL,                        
                        DateTimeChanged TEXT NOT NULL,                        
                        Watched_Value1 TEXT NOT NULL,
                        Watched_Value2 TEXT NOT NULL,
                        Watched_Value3 TEXT NOT NULL,
                        Watched_Value4 TEXT NOT NULL,
                        Status TEXT NOT NULL,  
                        Extra TEXT NOT NULL,
                        UserData TEXT NOT NULL,
                        ForeignKey TEXT NOT NULL,
                        PRIMARY KEY("Index" AUTOINCREMENT)
                    ); """
    sql.execute(sql_Plugins_Objects)

    # Plugin execution results
    sql_Plugins_Events = """ CREATE TABLE IF NOT EXISTS Plugins_Events(
                        "Index"	          INTEGER,
                        Plugin TEXT NOT NULL,
                        Object_PrimaryID TEXT NOT NULL,
                        Object_SecondaryID TEXT NOT NULL,
                        DateTimeCreated TEXT NOT NULL,                        
                        DateTimeChanged TEXT NOT NULL,                         
                        Watched_Value1 TEXT NOT NULL,
                        Watched_Value2 TEXT NOT NULL,
                        Watched_Value3 TEXT NOT NULL,
                        Watched_Value4 TEXT NOT NULL,
                        Status TEXT NOT NULL,              
                        Extra TEXT NOT NULL,
                        UserData TEXT NOT NULL,
                        ForeignKey TEXT NOT NULL,
                        PRIMARY KEY("Index" AUTOINCREMENT)
                    ); """
    sql.execute(sql_Plugins_Events)

    # Plugin execution history
    sql_Plugins_History = """ CREATE TABLE IF NOT EXISTS Plugins_History(
                        "Index"	          INTEGER,
                        Plugin TEXT NOT NULL,
                        Object_PrimaryID TEXT NOT NULL,
                        Object_SecondaryID TEXT NOT NULL,
                        DateTimeCreated TEXT NOT NULL,                        
                        DateTimeChanged TEXT NOT NULL,                         
                        Watched_Value1 TEXT NOT NULL,
                        Watched_Value2 TEXT NOT NULL,
                        Watched_Value3 TEXT NOT NULL,
                        Watched_Value4 TEXT NOT NULL,
                        Status TEXT NOT NULL,              
                        Extra TEXT NOT NULL,
                        UserData TEXT NOT NULL,
                        ForeignKey TEXT NOT NULL,
                        PRIMARY KEY("Index" AUTOINCREMENT)
                    ); """                    
    sql.execute(sql_Plugins_History)

    # Dynamically generated language strings
    # indicates, if Language_Strings table is available 
    languageStringsMissing = sql.execute("""
    SELECT name FROM sqlite_master WHERE type='table'
    AND name='Plugins_Language_Strings'; 
    """).fetchone() == None
    
    if languageStringsMissing == False:
        sql.execute("DROP TABLE Plugins_Language_Strings;") 

    sql.execute(""" CREATE TABLE IF NOT EXISTS Plugins_Language_Strings(
                        "Index"	          INTEGER,
                        Language_Code TEXT NOT NULL,
                        String_Key TEXT NOT NULL,
                        String_Value TEXT NOT NULL,
                        Extra TEXT NOT NULL,                                                    
                        PRIMARY KEY("Index" AUTOINCREMENT)
                    ); """)   
    
    db.commitDB()    

