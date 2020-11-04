<?php
/**
* CMS des GutsMuths Gymnasiums Quedlinburg
* @author Noah Wiederhold & Bernhard Birnbaum
* @copyright 2020 Noah Wiederhold & Bernhard Birnbaum
* @version 1.02.00b
* 
* backend.php - Backendbereich des CMS.
*/
?><?php
//Ausgabepufferung aktivieren (HTML wird erst dann zurückgegeben, wenn das PHP-Script durchgelaufen ist).
ob_start();

  /////////////////////////////////
 //           Klassen           // 
/////////////////////////////////  

//Codeobject-Klasse zur Verwaltung von Quelltext.
class CODE_OBJECT {
	//Konstruktor.
	public function __construct($LOGIN_PROTECTION=false) {
		$this->SOURCE="";
		
		$this->LOGIN_PROTECTION=$LOGIN_PROTECTION;
	}
	
	//Quelltext anhängen.
	public function ADD_SOURCE($SOURCE) {
		$this->SOURCE.=$SOURCE;
	}
	
	//Quelltext zurückgeben.
	public function GET_SOURCE() {
		if($this->LOGIN_PROTECTION){
			if(LOGGED_IN()){
				return $this->SOURCE;
			}else{
				header("Location: ?page=login&redirect=".urlencode($_SERVER["REQUEST_URI"]));
				return "";
			}
		}else{
			return $this->SOURCE;
		}
	}
}

  ////////////////////////////////
 //           Basics           // 
////////////////////////////////  

//Dateieinbindung.
include("common.php");

//Sessions aktivieren.
session_start();

//SQL-Verbindung aufbauen.
SQL_CONNECT();

//Logging für SQL wird initialisiert.
DEBUGGING_LOGGER::INIT();

//Angeforderte Seite wird bestimmt.
$PARAM_PAGE;
if(isset($_GET["page"])){
	$PARAM_PAGE=$_GET["page"];
}else{
	$PARAM_PAGE="home";
}

  ///////////////////////////////////
 //           Utilities           // 
///////////////////////////////////  

{
	//Gibt ID des angemeldeten Benutzers zurück.
	function GET_USER_ID() {
		return SQL_READ("users","id,username","username",$_SESSION["backend"])["id"];
	}
	
	//Gibt Nutzername des angemeldeten Nutzers zurück.
	function GET_USER_NAME() {
		return SQL_READ("users","id,username","username",$_SESSION["backend"])["username"];
	}
	
	//Gibt NutzergruppenId des angemeldeten Nutzers zurück.
	function GET_USER_GROUP_ID() {
		return SQL_READ("users","id,usergroup","id",GET_USER_ID())["usergroup"];
	}
	
	//Backend-Mainmenu 
	function GET_BIG_BUTTON($TITLE,$ICON,$LINK) {
		return "<button class=\"speedbutton\" title=\"".$TITLE."\" onclick=\"window.location.href='".$LINK."';\"><i class=\"".$ICON."\"></i><p>".$TITLE."</p></button>";
	}
	
	//Prüft auf einen bestehenden Login.
	function LOGGED_IN(){
		if(isset($_SESSION["backend"])) {
			return true;
		} else {
			return false;
		}
	}
}

  ///////////////////////////////////////////
 //           Header & Defaults           // 
///////////////////////////////////////////  

{
	//Standard CodeObject für Header.
	$HEADER=new CODE_OBJECT();
	
	//Titel.
	$HEADER->ADD_SOURCE("<title>Administration</title>");
	
	//Metadaten.
	$HEADER->ADD_SOURCE("<meta charset=\"utf-8\"/>");
	
	//JavaScript Einbindung.
	$HEADER->ADD_SOURCE("<script language=\"javascript\" type=\"text/javascript\" src=\"thirdparty/jquery-3.4.1.js\"></script>");
	$HEADER->ADD_SOURCE("<script language=\"javascript\" type=\"text/javascript\" src=\"javascript/basic.js\"></script>");
	$HEADER->ADD_SOURCE("<script language=\"javascript\" type=\"text/javascript\" src=\"javascript/box.js\"></script>");
	$HEADER->ADD_SOURCE("<script language=\"javascript\" type=\"text/javascript\" src=\"thirdparty/jquery-fileupload-widget.js\"></script>");
	$HEADER->ADD_SOURCE("<script language=\"javascript\" type=\"text/javascript\" src=\"thirdparty/jquery-fileupload-iframe.js\"></script>");
	$HEADER->ADD_SOURCE("<script language=\"javascript\" type=\"text/javascript\" src=\"thirdparty/jquery-fileupload.js\"></script>");
	
	//CSS-Einbindung.
	$HEADER->ADD_SOURCE("<link rel=\"stylesheet\" type=\"text/css\" href=\"thirdparty/google-roboto.css\"/>");
	$HEADER->ADD_SOURCE("<link rel=\"stylesheet\" type=\"text/css\" href=\"thirdparty/google-raleway.css\"/>");
	$HEADER->ADD_SOURCE("<link rel=\"stylesheet\" href=\"thirdparty/fontawesome-5.12.1.css\"/>");
	$HEADER->ADD_SOURCE("<link rel=\"stylesheet\" type=\"text/css\" href=\"stylesheets/backend.css\"/>");
	$HEADER->ADD_SOURCE("<link rel=\"stylesheet\" type=\"text/css\" href=\"stylesheets/menu.css\"/>");
	$HEADER->ADD_SOURCE("<link rel=\"stylesheet\" type=\"text/css\" href=\"stylesheets/forms.css\"/>");
	$HEADER->ADD_SOURCE("<link rel=\"stylesheet\" type=\"text/css\" href=\"stylesheets/tables.css\"/>");
	$HEADER->ADD_SOURCE("<link rel=\"stylesheet\" type=\"text/css\" href=\"stylesheets/box.css\"/>");
	$HEADER->ADD_SOURCE("<link rel=\"stylesheet\" type=\"text/css\" href=\"stylesheets/editor.css\"/>");
	$HEADER->ADD_SOURCE("<link rel=\"stylesheet\" type=\"text/css\" href=\"stylesheets/format.css\"/>");
	$HEADER->ADD_SOURCE("<link rel=\"stylesheet\" type=\"text/css\" href=\"stylesheets/mainformat.css\"/>");
	
	//Standard CodeObject das Menü.
	$MENU=new CODE_OBJECT(true);
	$MENU->ADD_SOURCE("<nav>");
	$MENU->ADD_SOURCE("<ul>");
	$MENU->ADD_SOURCE("<li><a href=\"?page=home\">Startseite</a></li>");
	$MENU->ADD_SOURCE("<li><a href=\"#\">Benutzer</a><ul>");
	$MENU->ADD_SOURCE("<li><a href=\"?page=users\">Benutzer</a></li>");
	$MENU->ADD_SOURCE("<li><a href=\"?page=usergroups\">Benutzergruppen</a></li>");
	$MENU->ADD_SOURCE("</ul></li>");
	$MENU->ADD_SOURCE("<li><a href=\"#\">Men&uuml;s</a><ul>");
	$MENU->ADD_SOURCE("<li><a href=\"?page=menu\">Hauptmen&uuml;eintr&auml;ge</a></li>");
	$MENU->ADD_SOURCE("<li><a href=\"?page=submenu\">Unterpunkte &auml;ndern</a></li>");
	$MENU->ADD_SOURCE("</ul></li>");
	$MENU->ADD_SOURCE("<li><a href=\"#\">Inhalt</a><ul>");
	$MENU->ADD_SOURCE("<li><a href=\"?page=pages\">Seiten</a></li>");
	$MENU->ADD_SOURCE("<li><a href=\"?page=media\">Medien</a></li>");
	$MENU->ADD_SOURCE("<li><a href=\"?page=categories\">Kategorien</a></li>");
	$MENU->ADD_SOURCE("<li><a href=\"?page=kollegium\">Kollegium</a></li>");
	$MENU->ADD_SOURCE("</ul></li>");
	$MENU->ADD_SOURCE("<li><a href=\"#\">Weiteres</a><ul>");
	$MENU->ADD_SOURCE("<li><a href=\"?page=statistics\">Statistik und Meta</a></li>");
	$MENU->ADD_SOURCE("<li><a href=\"?page=reset\">Zur&uuml;cksetzen</a></li>");
	$MENU->ADD_SOURCE("</ul></li>");
	$MENU->ADD_SOURCE("<li><a href=\"?page=logout\">Abmelden</a></li>");
	$MENU->ADD_SOURCE("</ul></nav>");
}

//Klasse für Backend-Pages.
class PAGE {
	//Konstruktor.
	public function __construct($TITLE,$DESCRIPTION,$WITH_HEADER=true) {
		$this->SOURCE=new CODE_OBJECT();
		$this->TITLE=$TITLE;
		$this->DESCRIPTION=$DESCRIPTION;
		$this->BTNLIST=new MODULE_BUTTONLIST();
		$this->CUSTOMSOURCE=new CODE_OBJECT();
		$this->WITH_HEADER=$WITH_HEADER;
	}
	
	//Navigation-Button.
	public function ADD_BUTTON_NAVIGATION($CAPTION,$LOCATION) {
		$this->BTNLIST->ADD_BUTTON_NAVIGATION($CAPTION,$LOCATION);
	}
	
	//Button mit Textbox.
	public function ADD_BUTTON_TEXT($LABEL,$MESSAGE,$ITEMS,$NAVIGATION) {
		$this->BTNLIST->ADD_BUTTON_TEXT($LABEL,$MESSAGE,$ITEMS,$NAVIGATION);
	}
	
	//Button mit SelectBox.
	public function ADD_BUTTON_SELECTION($LABEL,$MESSAGE,$ITEMS,$ITEM_VALUES,$NAVIGATION) {
		$this->BTNLIST->ADD_BUTTON_SELECTION($LABEL,$MESSAGE,$ITEMS,$ITEM_VALUES,$NAVIGATION);
	}
	
	//Button mit JS-Popup.
	public function ADD_BUTTON_JSWINDOW($LABEL,$NAME,$SOURCE) {
		$this->BTNLIST->ADD_BUTTON_JSWINDOW($LABEL,$NAME,$SOURCE);
	}
	
	//Button mit ConfirmationBox.
	public function ADD_BUTTON_CONFIRMATION($LABEL,$NAME,$SOURCE) {
		$this->BTNLIST->ADD_BUTTON_CONFIRMATION($LABEL,$NAME,$SOURCE);
	}
	
	//Eigenen Quelltext einbinden.
	public function ADD_CUSTOMSOURCE($SOURCE) {
		$this->CUSTOMSOURCE->ADD_SOURCE($SOURCE);
	}
	
	//Weitere Headerbox auf der Seite.
	public function ADD_NEW_HEADERBOX($TITLE,$DESCRIPTION) {
		$this->CUSTOMSOURCE->ADD_SOURCE("<div class=\"centered\"><div id=\"headerbox\">".$TITLE."</div><p class=\"description\">".$DESCRIPTION."</p></div>");
	}
	
	//Button mit UploadBox.
	public function ADD_BUTTON_UPLOAD($LABEL,$TITLE,$MESSAGE,$UPLOADURI,$NAVIGATION=null) {
		$this->BTNLIST->ADD_BUTTON_UPLOAD($LABEL,$TITLE,$MESSAGE,$UPLOADURI,$NAVIGATION);
	}
	
	//Seitenquelltext ausgeben.
	public function GET_SOURCE() {
		global $MENU;
		if($this->WITH_HEADER){
			$this->SOURCE->ADD_SOURCE("<div id=\"header\">".$MENU->GET_SOURCE()."</div>");
		}
		$this->SOURCE->ADD_SOURCE("<div id=\"content\">");
		$this->SOURCE->ADD_SOURCE("<div class=\"centered\"><div id=\"headerbox\">".$this->TITLE."</div><p class=\"description\">".$this->DESCRIPTION."</p></div>");
		$this->SOURCE->ADD_SOURCE($this->BTNLIST->GET_SOURCE());
		$this->SOURCE->ADD_SOURCE($this->CUSTOMSOURCE->GET_SOURCE());
		$this->SOURCE->ADD_SOURCE("</div>");
		return $this->SOURCE->GET_SOURCE();
	}
}

//CodeObject für Body.
$BODY=new CODE_OBJECT();

//SeitenSwitch.
switch($PARAM_PAGE) {
	//Home.
	case "home":
		{
			$BODY->ADD_SOURCE("<div id=\"header\">".$MENU->GET_SOURCE()."</div>");
			$BODY->ADD_SOURCE("<div id=\"content\">");
			
			//Zeile mit Buttons.
			$BODY->ADD_SOURCE("<div class=\"line\">");
			$BODY->ADD_SOURCE(GET_BIG_BUTTON("Benutzer","fas fa-user","?page=users"));
			$BODY->ADD_SOURCE(GET_BIG_BUTTON("Benutzergruppen","fas fa-users","?page=usergroups"));
			$BODY->ADD_SOURCE(GET_BIG_BUTTON("Hauptmen&uuml;","fas fa-list-ol","?page=menu"));
			$BODY->ADD_SOURCE(GET_BIG_BUTTON("Unterpunkte","fas fa-list-ul","?page=submenu"));
			$BODY->ADD_SOURCE(GET_BIG_BUTTON("Seiten","fas fa-file","?page=pages"));
			$BODY->ADD_SOURCE("</div>");
			
			//Zeile mit Buttons.
			$BODY->ADD_SOURCE("<div class=\"line\">");
			$BODY->ADD_SOURCE(GET_BIG_BUTTON("Medien","fas fa-image","?page=media"));
			$BODY->ADD_SOURCE(GET_BIG_BUTTON("Kategorien","fas fa-indent","?page=categories"));
			$BODY->ADD_SOURCE(GET_BIG_BUTTON("Kollegium","fas fa-user-friends","?page=kollegium"));
			$BODY->ADD_SOURCE(GET_BIG_BUTTON("Statistik &amp; Meta","fas fa-chart-bar","?page=statistics"));
			$BODY->ADD_SOURCE(GET_BIG_BUTTON("Setup &amp; Reset","fas fa-table","?page=reset"));
			$BODY->ADD_SOURCE("</div>");
			
			//Zeile mit Buttons.
			$BODY->ADD_SOURCE("<div class=\"line\">");
			$BODY->ADD_SOURCE(GET_BIG_BUTTON("Frontend","fas fa-sitemap","/"));
			$BODY->ADD_SOURCE(GET_BIG_BUTTON("Datenbank","fas fa-database","adminer.php?server=$SQL_SERVER&username=$SQL_USERNAME&db=$SQL_DBNAME"));
			$BODY->ADD_SOURCE(GET_BIG_BUTTON("Abmelden","fas fa-power-off","?page=logout"));
			$BODY->ADD_SOURCE("</div>");
			$BODY->ADD_SOURCE("</div>");
		}
		break;
	//Nutzerverwaltung.
	case "users":
		{
			//Formular, um Nutzer hinzuzufügen.
			if(isset($_GET["add"])){
				$CURRENT_PAGE=new PAGE("Benutzer hinzuf&uuml;gen","Einen neuen Benutzer anlegen.");
				$CURRENT_PAGE->ADD_BUTTON_NAVIGATION("Zur&uuml;ck zur &Uuml;bersicht","?page=users");
				$FORM_USER=new MODULE_FORM("?page=users&func_add");
				$FORM_USER->ADD_INPUT_TEXT("Benutzername","user_username");
				$FORM_USER->ADD_INPUT_TEXT("Vorname","user_vorname");
				$FORM_USER->ADD_INPUT_TEXT("Nachname","user_name");
				$FORM_USER->ADD_INPUT_TEXT("E-Mail Adresse","user_email");
				$FORM_USER->ADD_INPUT_PASSWORD("Passwort","user_password");
				$FORM_USER->ADD_INPUT_PASSWORD("Passwort wiederholen","user_passwordrepeat");
				$FORM_USER->ADD_INPUT_SELECT_MYSQL("Nutzergruppe","user_usergroup","usergroups","id","name","1");
				$FORM_USER->ADD_SUBMIT("Hinzuf&uuml;gen","Nutzer hinzuf&uuml;gen");
				$CURRENT_PAGE->ADD_CUSTOMSOURCE($FORM_USER->GET_SOURCE());

				$BODY->ADD_SOURCE($CURRENT_PAGE->GET_SOURCE());
			
			//Fügt neuen Nutzer hinzu.
			}elseif(isset($_GET["func_add"])){
				$USER_USERNAME=str_replace(" ","_",SIMPLIFY_STRING(strtolower($_POST["user_username"])));
				$USER_NAME=$_POST["user_name"];
				$USER_VORNAME=$_POST["user_vorname"];
				$USER_EMAIL=$_POST["user_email"];
				$USER_PASSWORD=hash("sha512",$_POST["user_password"]);
				$USER_PASSWORDRPT=hash("sha512",$_POST["user_passwordrepeat"]);
				$USER_USERGROUP=$_POST["user_usergroup"];
				if($USER_PASSWORD==$USER_PASSWORDRPT) {
					SQL_ADD("users","username,name,vorname,email,password,usergroup,active,logins,date_register","'$USER_USERNAME','$USER_NAME','$USER_VORNAME','$USER_EMAIL','$USER_PASSWORD',$USER_USERGROUP,0,0,'".date("Y-m-d H:i:s")."'");		   
					$CURRENT_PAGE=new PAGE("Benutzer hinzugef&uuml;gt","Der Benutzer wurde hinzugef&uuml;gt.");
					$CURRENT_PAGE->ADD_BUTTON_NAVIGATION("Zur&uuml;ck zur &Uuml;bersicht","?page=users");
					$CURRENT_PAGE->ADD_BUTTON_NAVIGATION("Weiteren hinzuf&uuml;gen","?page=users&add");
					LOGGING_PUSH("BACKEND/INFO","Neuer Nutzer '".$USER_USERNAME."' wurde von '".GET_USER_NAME()." (".GET_USER_ID().")' hinzugefügt.");
				}else{
					$CURRENT_PAGE=new PAGE("Benutzer nicht hinzugef&uuml;gt","Der Benutzer konnte nicht hinzugef&uuml;gt werden: Passw&ouml;rter stimmen nicht &uuml;berein.");
					$CURRENT_PAGE->ADD_BUTTON_NAVIGATION("Zur&uuml;ck zur &Uuml;bersicht","?page=users");
					$CURRENT_PAGE->ADD_BUTTON_NAVIGATION("Erneut versuchen","?page=users&add");
				}
				$BODY->ADD_SOURCE($CURRENT_PAGE->GET_SOURCE());
			
			//Bearbeitung von Nutzerdaten.
			}elseif(isset($_GET["edit"])){
				$CURRENT_PAGE=new PAGE("Benutzer bearbeiten","Einen Benutzer bearbeiten.");
				$CURRENT_PAGE->ADD_BUTTON_NAVIGATION("Zur &Uuml;bersicht","?page=users");
				
				//Nutzerdaten werden ausgelesen.
				$USER=SQL_READ("users","id,username,name,vorname,email,usergroup,active,logins,date_register,date_lastlogin","id",$_GET["edit"]);
				$FORM_USER=new MODULE_FORM("?page=users&func_edit");
				$FORM_USER->ADD_SUBMIT("Speichern","Nutzerdaten speichern");
				$FORM_USER->ADD_INPUT_TEXT("Nutzer-ID","user_id",true,$USER["id"]);
				$FORM_USER->ADD_INPUT_TEXT("Benutzername","user_username",false,$USER["username"]);
				$FORM_USER->ADD_INPUT_TEXT("Vorname","user_vorname",false,$USER["vorname"]);
				$FORM_USER->ADD_INPUT_TEXT("Nachname","user_name",false,$USER["name"]);
				$FORM_USER->ADD_INPUT_TEXT("E-Mail Adresse","user_email",false,$USER["email"]);
				$FORM_USER->ADD_BUTTON_BOX_PASSWORD("Passwort","Neues Passwort setzen","?page=users&func_password=".$USER["id"]."&val=");
				$FORM_USER->ADD_INPUT_SELECT_MYSQL("Nutzergruppe","user_usergroup","usergroups","id","name",$USER["usergroup"]);
				$FORM_USER->ADD_INPUT_SELECT("Freigegeben","user_active","Ja|Nein","1|0",($USER["active"] == "0") ? 1 : 0);
				$FORM_USER->ADD_INPUT_TEXT("Gesamte Logins","",true,$USER["logins"]);
				$FORM_USER->ADD_INPUT_TEXT("Registriert am","",true,date("d.m.Y H:i", strtotime($USER["date_register"])));
				$FORM_USER->ADD_INPUT_TEXT("Zuletzt eingeloggt am","",true,($USER["date_lastlogin"] == "0000-00-00 00:00:00") ? "nie" : date("d.m.Y H:i", strtotime($USER["date_lastlogin"])));
				$FORM_USER->ADD_SUBMIT("Speichern","Nutzerdaten speichern");
				$CURRENT_PAGE->ADD_CUSTOMSOURCE($FORM_USER->GET_SOURCE());
				$BODY->ADD_SOURCE($CURRENT_PAGE->GET_SOURCE());
			
			//Änderungen speichern.
			}elseif(isset($_GET["func_edit"])){
				$USER_ID=$_POST["user_id"];
				$USER_USERNAME=$_POST["user_username"];
				$USER_NAME=$_POST["user_name"];
				$USER_VORNAME=$_POST["user_vorname"];
				$USER_EMAIL=$_POST["user_email"];
				$USER_USERGROUP=$_POST["user_usergroup"];
				$USER_ACTIVE=$_POST["user_active"];
				SQL_CHANGE_MULTIPLE("users","id",$USER_ID,"username='$USER_USERNAME',name='$USER_NAME',vorname='$USER_VORNAME',email='$USER_EMAIL',usergroup=$USER_USERGROUP,active=$USER_ACTIVE");
				LOGGING_PUSH("BACKEND/INFO","Nutzer '".$USER_USERNAME."' wurde von '".GET_USER_NAME()." (".GET_USER_ID().")' bearbeitet.");
				header("Location: ?page=users");
			
			//Löscht einen Nutzer.
			}elseif(isset($_GET["func_delete"])){
				SQL_DELETE("users","id",$_GET["func_delete"]);
				LOGGING_PUSH("BACKEND/INFO","Nutzer (".$_GET["func_delete"].") wurde von '".GET_USER_NAME()." (".GET_USER_ID().")' gelöscht.");
				header("Location: ?page=users");
				
			//Ändert das Passwort.
			}elseif(isset($_GET["func_password"])){
				if(isset($_GET["val"])){
					SQL_CHANGE("users","id",$_GET["func_password"],"password",hash("sha512",$_GET["val"]));
					LOGGING_PUSH("BACKEND/INFO","Passwort von Nutzer (".$_GET["func_password"].") wurde von '".GET_USER_NAME()." (".GET_USER_ID().")' geändert.");
					header("Location: ?page=users&edit=".$_GET["func_password"]);
				}else{
					$CURRENT_PAGE=new PAGE("Passwort setzen fehlgeschlagen","Fehlender Parameter.");
					$CURRENT_PAGE->ADD_BUTTON_NAVIGATION("Abbrechen","?page=users");
					$BODY->ADD_SOURCE($CURRENT_PAGE->GET_SOURCE());
				}
				
			//Seite online/offline.
			}elseif(isset($_GET["func_toggleactive"])){
				$USER=SQL_READ("users","id,username,active","id",$_GET["func_toggleactive"]);
				if($USER["active"]==0){
					SQL_CHANGE("users","id",$_GET["func_toggleactive"],"active","1");
					LOGGING_PUSH("BACKEND/INFO","Nutzer '".$USER["username"]." (".$USER["id"].")' freigeschaltet von '".GET_USER_NAME()." (".GET_USER_ID().")'.");
				}else{
					SQL_CHANGE("users","id",$_GET["func_toggleactive"],"active","0");
					LOGGING_PUSH("BACKEND/INFO","Nutzer '".$USER["username"]." (".$USER["id"].")' gesperrt von '".GET_USER_NAME()." (".GET_USER_ID().")'.");
				}
				header("Location: ?page=users");
				
			//Nutzerübersicht.
			}else{
				//Abfrage, ob Nutzerliste hochgeladen.
				$LIST_FNAME="./media/userlist/userlist.csv";
				if(file_exists($LIST_FNAME)) {
					$FHANDLE=fopen($LIST_FNAME,"r");
					
					//Wenn Datei geöffnet werden konnte:
					if($FHANDLE){
						//Datei Zeilenweise auslesen.
						for($i=0;($FLINE=fgets($FHANDLE))!==false;$i++) {
							//Kopfzeile wird ignoriert.
							if($i!=0){
								//Daten werden geparsed und in die Datenbank geschrieben.
								$FLINEARR=explode(";",$FLINE);
								SQL_ADD("users","username,name,vorname,email,password,usergroup,active,logins,date_register","'".$FLINEARR[0]."','".$FLINEARR[1]."','".$FLINEARR[2]."','".$FLINEARR[3]."','".hash("sha512",$FLINEARR[4])."',".$FLINEARR[5].",".$FLINEARR[6].",0,'".date("Y-m-d H:i:s")."'");
							}
						}
						fclose($FHANDLE);
					}
					
					//Datei wird gelöscht.
					unlink($LIST_FNAME);
					
					header("Location: ?page=users");
				}
				
				//Buttonlist.
				$CURRENT_PAGE=new PAGE("Benutzer","Benutzer hinzuf&uuml;gen und/oder verwalten.");
				$CURRENT_PAGE->ADD_BUTTON_NAVIGATION("Benutzer hinzuf&uuml;gen","?page=users&add");
				$CURRENT_PAGE->ADD_BUTTON_NAVIGATION("Vorlage herunterladen","./media/userlist/userlist_vorlage.csv");
				$CURRENT_PAGE->ADD_BUTTON_UPLOAD("CSV hochladen","Nutzerliste hochladen","CSV-Liste ausw&auml;hlen","?page=editor&func_upload&sub=userlist&name=userlist.csv","?page=users");

				//Tabelle für die Nutzeranzeige.
				$TABLE_USERS=new MODULE_TABLE("ID|Name|E-Mail|Nutzergruppe|Freigeschaltet|Registriert seit|Letzter Login|Logins|");
				
				//Auslesen der Benutzer.
				$SQL_QUERY=SQL_READLOOP("users","id,username,name,vorname,email,usergroup,active,logins,date_register,date_lastlogin","name");
				while($ROW=mysqli_fetch_array($SQL_QUERY)){
					$USER_ID=$ROW["id"];
					$USER_USERNAME=$ROW["username"];
					$USER_VORNAME=$ROW["vorname"];
					$USER_NAME=$ROW["name"];
					$USER_EMAIL=$ROW["email"];
					$USER_USERGROUP=SQL_READ("usergroups","id,name","id",$ROW["usergroup"])["name"];
					$USER_ACTIVE;
					if($ROW["active"]==0){
						$USER_ACTIVE="<a onClick=\"dpBoxConfirm('Nutzer freischalten','Soll der Nutzer \'$USER_USERNAME\' wirklich online geschaltet werden?',function(){jsNav('?page=users&func_toggleactive=$USER_ID');});\" title=\"Nutzer online schalten\"><i class=\"fa fa-times\"></i></a>";
					}else{
						$USER_ACTIVE="<a onClick=\"dpBoxConfirm('Nutzer sperren','Soll der Nutzer \'$USER_USERNAME\' wirklich gesperrt werden?<br>Er kann sich danach nicht mehr anmelden.',function(){jsNav('?page=users&func_toggleactive=$USER_ID');});\" title=\"Nutzer sperren\"><i class=\"fa fa-check\"></i></a>";
					}
					$USER_LOGINS=$ROW["logins"];
					$USER_REGDATE=date("d.m.Y H:i", strtotime($ROW["date_register"]));
					$USER_LOGDATE=($ROW["date_lastlogin"] == "0000-00-00 00:00:00") ? "nie" : date("d.m.Y H:i", strtotime($ROW["date_lastlogin"]));
					
					//Nutzer wird hinzugefügt.
					$TABLE_USERS->ADD_LINE("$USER_ID|<a title=\"Diesen Benutzer bearbeiten und Berechtigungen &auml;ndern\" href=\"?page=users&edit=$USER_ID\">$USER_NAME, $USER_VORNAME</a> ($USER_USERNAME)|<a href=\"mailto:$USER_EMAIL\">$USER_EMAIL</a>|$USER_USERGROUP|$USER_ACTIVE|$USER_REGDATE|$USER_LOGDATE|$USER_LOGINS|<a onClick=\"dpBoxConfirm('Nutzer l&ouml;schen','Soll der Benutzer \'$USER_USERNAME\' wirklich gel&ouml;scht werden? Bitte beachten Sie, dass die Nutzerdaten nicht wiederhergestellt werden k&ouml;nnen.',function(){jsNav('?page=users&func_delete=$USER_ID');});\" title=\"Nutzer vom Server l&ouml;schen\"><i class=\"fa fa-times\"></i></a>");
				}
				
				//Tabelle wird an die Seite weitergegeben.
				$CURRENT_PAGE->ADD_CUSTOMSOURCE($TABLE_USERS->GET_SOURCE());
				$BODY->ADD_SOURCE($CURRENT_PAGE->GET_SOURCE());
			}
		}
		break;
	//Nutzergruppen.
	case "usergroups":
		{
			//Formular, um neue Nutzergruppe hinzuzufügen.
			if(isset($_GET["add"])){
				$CURRENT_PAGE=new PAGE("Benutzergruppe hinzuf&uuml;gen","Eine Benutzergruppe hinzuf&uuml;gen.");
				$CURRENT_PAGE->ADD_BUTTON_NAVIGATION("Zur&uuml;ck","?page=usergroups");
				
				$FORM_ADD=new MODULE_FORM("?page=usergroups&func_add");
				$FORM_ADD->ADD_INPUT_TEXT("Gruppenname","group_name",false,"Neue Nutzergruppe");
				$FORM_ADD->ADD_INPUT_DATE("L&auml;uft ab","group_expires");
				$FORM_ADD->ADD_INPUT_TEXT("Beschreibung","group_desc",false,"Das ist eine neue Gruppe.");
				$FORM_ADD->ADD_SUBMIT("Hinzuf&uuml;gen", "Speichern");
				
				$CURRENT_PAGE->ADD_CUSTOMSOURCE($FORM_ADD->GET_SOURCE());
				$BODY->ADD_SOURCE($CURRENT_PAGE->GET_SOURCE());
				
			//Fügt einen neue Nutzergruppe hinzu.
			}elseif(isset($_GET["func_add"])){
				SQL_ADD("usergroups","name,expires,description","'".$_POST["group_name"]."','".$_POST["group_expires"]."','".$_POST["group_desc"]."'");
				LOGGING_PUSH("BACKEND/INFO","Neue Nutzergruppe ('".$_POST["group_name"]."') erstellt von '".GET_USER_NAME()." (".GET_USER_ID().")'.");
				header("Location: ?page=usergroups");
				
			//Löscht alle abgelaufenen Nutzergruppen und die Nutzerdaten der in dieser Gruppe enthaltenen User.
			}elseif(isset($_GET["func_clear"])){
				$CURRENT_DATE=date("Y-n-j");
				$CURRENT_TIMESTAMP=strtotime($CURRENT_DATE);
				$SQL_QUERY=SQL_READLOOP("usergroups","id,expires");
				while($ROW=mysqli_fetch_array($SQL_QUERY)){
					//Wenn die Gruppe ein Ablaufdatum hat:
					if($ROW["expires"] != "0000-00-00") {
						$TIMESTAMP_DATA=strtotime($ROW["expires"]);
						//Wenn abgelaufen:
						if($CURRENT_TIMESTAMP > $TIMESTAMP_DATA) {
							//Alle Nutzer der Gruppe werden durchgegangen.
							$SQL_QUERY2=SQL_READLOOP_PARAM("users","usergroup",$ROW["id"],$ORDERBY = "id");
							while($ROW2=mysqli_fetch_array($SQL_QUERY2)){
								//Und gelöscht.
								SQL_DELETE("users","id",$ROW2["id"]);
								LOGGING_PUSH("BACKEND/INFO","Abgelaufener Nutzer (".$ROW2["id"].") gelöscht von '".GET_USER_NAME()." (".GET_USER_ID().")'.");
							}
							SQL_DELETE("usergroups","id",$ROW["id"]);
							LOGGING_PUSH("BACKEND/INFO","Abgelaufene Nutzergruppe (".$ROW["id"].") gelöscht von '".GET_USER_NAME()." (".GET_USER_ID().")'.");
						}
					}
				}
				header("Location: ?page=usergroups");
				
			//Funktion löscht einen Benutzer.
			}elseif(isset($_GET["func_delete"])){
				SQL_DELETE("usergroups","id",$_GET["func_delete"]);
				LOGGING_PUSH("BACKEND/INFO","Nutzergruppe (".$_GET["func_delete"].") gelöscht von '".GET_USER_NAME()." (".GET_USER_ID().")'.");
				header("Location: ?page=usergroups");
				
			//Nutzergruppenübersicht.
			}else{
				$CURRENT_PAGE=new PAGE("Benutzergruppen","Benutzergruppen hinzuf&uuml;gen oder entfernen.");
				$CURRENT_PAGE->ADD_BUTTON_NAVIGATION("Gruppe hinzuf&uuml;gen","?page=usergroups&add");
				$CURRENT_PAGE->ADD_BUTTON_NAVIGATION("Abgelaufene Gruppen entfernen","?page=usergroups&func_clear");
				
				$GROUP_ADMIN=SQL_READ("usergroups","name,description","name","admin");
				$GROUP_PUBLIC=SQL_READ("usergroups","name,description","name","public");
				
				//Tabelle wird erstellt.
				$GROUP_TABLE=new MODULE_TABLE("ID|Gruppe|L&auml;uft ab|Beschreibung|");
				
				//Statische Nutzergruppe "Administrator" wird eingetragen.
				$GROUP_TABLE->ADD_LINE("ADMIN|".$GROUP_ADMIN["name"]."|nie|".$GROUP_ADMIN["description"]);
				$SQL_QUERY=SQL_READLOOP("usergroups","id,name,expires,description");
				while($ROW=mysqli_fetch_array($SQL_QUERY)){
					$GROUP_ID=$ROW["id"];
					$GROUP_NAME=$ROW["name"];
					if($GROUP_NAME!="public") {
						if($GROUP_NAME!="admin") {
							$GROUP_EXPIRES=date("d.m.Y", strtotime($ROW["expires"]));
							if($ROW["expires"]=="0000-00-00") {
								$GROUP_EXPIRES="nie";
							}
							$GROUP_DESCRIPTION=$ROW["description"];
							
							//Nutzergruppen werden angezeigt.
							$GROUP_TABLE->ADD_LINE("$GROUP_ID|$GROUP_NAME|".$GROUP_EXPIRES."|$GROUP_DESCRIPTION|<a onClick=\"dpBoxConfirm('Nutzergruppe entfernen?','Soll die Gruppe \'$GROUP_NAME\' entfernt werden?',function(){jsNav('?page=usergroups&func_delete=$GROUP_ID');})\" title=\"Nutzergruppe entfernen\"><i class=\"fa fa-times\"></i></a>");
						}
					}
				}
				
				//Statische Nutzergruppe "Öffentliche Nutzer" wird eingetragen.
				$GROUP_TABLE->ADD_LINE("PUBLIC|".$GROUP_PUBLIC["name"]."|nie|".$GROUP_PUBLIC["description"]);

				$CURRENT_PAGE->ADD_CUSTOMSOURCE($GROUP_TABLE->GET_SOURCE());
				$BODY->ADD_SOURCE($CURRENT_PAGE->GET_SOURCE());
			}
		}
		break;
	//Hauptmenü.
	case "menu":
		{
			//Menüeintrag wird hinzugefügt.
			if(isset($_GET["func_add"])){
				SQL_ADD("menu","pageid,seq",$_GET["func_add"].",".(SQL_COUNT_ROWS("menu")+1));
				LOGGING_PUSH("BACKEND/INFO","Menüeintrag hinzugefügt von '".GET_USER_NAME()." (".GET_USER_ID().")'.");
				header("Location: ?page=menu");
			
			//Menüeintrag wird gelöscht.
			}elseif(isset($_GET["func_delete"])){
				$DELETE_SEQUENCE=$_GET["func_delete"];
				$ENTRYS_COUNT=SQL_COUNT_ROWS("menu");
				for($i=$DELETE_SEQUENCE;$i<$ENTRYS_COUNT;$i++){
					$ID_1=SQL_READ("menu","id","seq",$i)["id"]; 
					$ID_2=SQL_READ("menu","id","seq",$i+1)["id"];
					SQL_CHANGE("menu","id",$ID_1,"seq",$i+1);
					SQL_CHANGE("menu","id",$ID_2,"seq",$i);
				}
				SQL_DELETE("menu","seq",$ENTRYS_COUNT);
				LOGGING_PUSH("BACKEND/INFO","Menüeintrag gelöscht von '".GET_USER_NAME()." (".GET_USER_ID().")'.");
				header("Location: ?page=menu");
			
			//Menüeinträge werden vertauscht.
			}elseif(isset($_GET["func_swap"])){
				$SWAP_1=explode(",",$_GET["func_swap"])[0];
				$SWAP_2=explode(",",$_GET["func_swap"])[1];
				$ID_1=SQL_READ("menu","id,seq","seq",$SWAP_1)["id"];
				$ID_2=SQL_READ("menu","id,seq","seq",$SWAP_2)["id"];
				SQL_CHANGE("menu","id",$ID_1,"seq",$SWAP_2);
				SQL_CHANGE("menu","id",$ID_2,"seq",$SWAP_1);
				LOGGING_PUSH("BACKEND/INFO","Menüeintrag verschoben von '".GET_USER_NAME()." (".GET_USER_ID().")'.");
				header("Location: ?page=menu");
			
			//Alle Menüeinträge werden ausgegeben.
			}else{
				$SQL_QUERY=SQL_READLOOP("pages","id,title","title");
				$SELECT_STR_VALS;
				$SELECT_STR;
				while($ROW=mysqli_fetch_array($SQL_QUERY)){
					$SELECT_STR_VALS.=$ROW["id"].";";
					$SELECT_STR.=$ROW["title"].";";
				}
				$SELECT_STR_VALS=substr($SELECT_STR_VALS,0,-1);
				$SELECT_STR=substr($SELECT_STR,0,-1);
				
				$CURRENT_PAGE=new PAGE("Hauptmen&uuml;","Eintr&auml;ge dem Men&uuml; hinzuf&uuml;gen, verschieben oder l&ouml;schen.");
				$CURRENT_PAGE->ADD_BUTTON_SELECTION("Eintrag hinzuf&uuml;gen","W&auml;hlen Sie die hinzuzuf&uuml;gende Seite aus:","$SELECT_STR","$SELECT_STR_VALS","?page=menu&func_add=");
				
				$MENU_TABLE=new MODULE_TABLE("Position|Seitenname (alias)|||");
				$MAX_COUNTS=SQL_COUNT_ROWS("menu");
				
				$SQL_QUERY=SQL_READLOOP("menu","pageid,seq","seq");
				while($ROW=mysqli_fetch_array($SQL_QUERY)){
					$PAGE_SEQUENCE=$ROW["seq"];
					$SQL_TMP=SQL_READ("pages","id,title,page","id",$ROW["pageid"]);
					$PAGE_TITLE=$SQL_TMP["title"];
					$PAGE_PAGE=$SQL_TMP["page"];
					
					//Ausgabe der Menüpunkte.
					if($PAGE_SEQUENCE <= 1) {
						$MENU_TABLE->ADD_LINE("$PAGE_SEQUENCE|$PAGE_TITLE ($PAGE_PAGE)|<a href=\"?page=menu&func_swap=".$PAGE_SEQUENCE.",".($PAGE_SEQUENCE+1)."\" title=\"Nach hinten\"><i class=\"fa fa-angle-down\"></i></a>||<a onClick=\"dpBoxConfirm('Seite entfernen?','Soll die Seite \'$PAGE_PAGE\' aus dem Hauptmen&uuml; entfernt werden?',function(){jsNav('?page=menu&func_delete=$PAGE_SEQUENCE');})\" title=\"Seite aus dem Hauptmen&uuml; entfernen\"><i class=\"fa fa-times\"></i></a>");
					} elseif($PAGE_SEQUENCE >= $MAX_COUNTS) {
						$MENU_TABLE->ADD_LINE("$PAGE_SEQUENCE|$PAGE_TITLE ($PAGE_PAGE)||<a href=\"?page=menu&func_swap=".$PAGE_SEQUENCE.",".($PAGE_SEQUENCE-1)."\" title=\"Nach vorne\"><i class=\"fa fa-angle-up\"></i></a>|<a onClick=\"dpBoxConfirm('Seite entfernen?','Soll die Seite \'$PAGE_PAGE\' aus dem Hauptmen&uuml; entfernt werden?',function(){jsNav('?page=menu&func_delete=$PAGE_SEQUENCE');})\" title=\"Seite aus dem Hauptmen&uuml; entfernen\"><i class=\"fa fa-times\"></i></a>");
					} else {
						$MENU_TABLE->ADD_LINE("$PAGE_SEQUENCE|$PAGE_TITLE ($PAGE_PAGE)|<a href=\"?page=menu&func_swap=".$PAGE_SEQUENCE.",".($PAGE_SEQUENCE+1)."\" title=\"Nach hinten\"><i class=\"fa fa-angle-down\"></i></a>|<a href=\"?page=menu&func_swap=".$PAGE_SEQUENCE.",".($PAGE_SEQUENCE-1)."\" title=\"Nach vorne\"><i class=\"fa fa-angle-up\"></i></a>|<a onClick=\"dpBoxConfirm('Seite entfernen?','Soll die Seite \'$PAGE_PAGE\' aus dem Hauptmen&uuml; entfernt werden?',function(){jsNav('?page=menu&func_delete=$PAGE_SEQUENCE');})\" title=\"Seite aus dem Hauptmen&uuml; entfernen\"><i class=\"fa fa-times\"></i></a>");
					}
				}
				$CURRENT_PAGE->ADD_CUSTOMSOURCE($MENU_TABLE->GET_SOURCE());
				$BODY->ADD_SOURCE($CURRENT_PAGE->GET_SOURCE());
			}
		}
		break;
	//Untermenüpunkte.
	case "submenu":
		{
			//Hauptmenüpunkt wird übergeben.
			if(isset($_GET["edit"])){
				//Fügt einen Untermenüpunkt hinzu.
				if(isset($_GET["func_add"])) {
					SQL_ADD("submenu","pageid,parentid,seq",$_GET["func_add"].",".$_GET["edit"].",".(SQL_COUNT_ROWS_PARAM("submenu","parentid",$_GET["edit"])+1));			
					LOGGING_PUSH("BACKEND/INFO","Submenüeintrag hinzugefügt von '".GET_USER_NAME()." (".GET_USER_ID().")'.");
					header("Location: ?page=submenu&edit=".$_GET["edit"]);
				
				//Untermenüpunkte werden gelöscht.
				} elseif(isset($_GET["func_delete"])) {
					SQL_DELETE("submenu","parentid",$_GET["edit"]);
					LOGGING_PUSH("BACKEND/INFO","Submenüeintrag gelöscht von '".GET_USER_NAME()." (".GET_USER_ID().")'.");
					header("Location: ?page=submenu&edit=".$_GET["edit"]);
				
				//Unterpunkte werden gelistet.
				} else {
					$SELECT_STR_VALS;
					$SELECT_STR;
					$SQL_QUERY=SQL_READLOOP("pages","id,title","title");
					while($ROW=mysqli_fetch_array($SQL_QUERY)){
						$SELECT_STR_VALS.=$ROW["id"].";";
						$SELECT_STR.=$ROW["title"].";";
					}
					$SELECT_STR_VALS=substr($SELECT_STR_VALS,0,-1);
					$SELECT_STR=substr($SELECT_STR,0,-1);

					$PAGE_TMP=SQL_READ("pages","id,title,page","id",$_GET["edit"]);
					$CURRENT_PAGE=new PAGE("Unterpunkte bearbeiten","Bearbeitung von: ".$PAGE_TMP["title"]." (".$PAGE_TMP["page"].")");
					$CURRENT_PAGE->ADD_BUTTON_NAVIGATION("Zur&uuml;ck","?page=submenu");
					$CURRENT_PAGE->ADD_BUTTON_SELECTION("Eintrag hinzuf&uuml;gen","W&auml;hlen Sie die hinzuzuf&uuml;gende Seite aus:","$SELECT_STR","$SELECT_STR_VALS","?page=submenu&edit=".$_GET["edit"]."&func_add=");
					$CURRENT_PAGE->ADD_BUTTON_CONFIRMATION("Eintr&auml;ge entfernen","Sollen wirklich alle Eintr&auml;ge entfernt werden?","?page=submenu&edit=".$_GET["edit"]."&func_delete");

					$MENU_TABLE=new MODULE_TABLE("Position|Seitenname (alias)");

					$SQL_QUERY=SQL_READLOOP_PARAM("submenu","parentid",$PAGE_TMP["id"],"seq");
					while($ROW=mysqli_fetch_array($SQL_QUERY)){
						$SQL_TMP=SQL_READ("pages","id,title,page","id",$ROW["pageid"]);
						$PAGE_TITLE=$SQL_TMP["title"];
						$PAGE_PAGE=$SQL_TMP["page"];
						$PAGE_SEQUENCE=$ROW["seq"];
						$MENU_TABLE->ADD_LINE("$PAGE_SEQUENCE|$PAGE_TITLE ($PAGE_PAGE)");
					}
					$CURRENT_PAGE->ADD_CUSTOMSOURCE($MENU_TABLE->GET_SOURCE());
					$BODY->ADD_SOURCE($CURRENT_PAGE->GET_SOURCE());
				}
			
			//Alle Hauptmenüpunkte werden gelistet.
			}else{
				$CURRENT_PAGE=new PAGE("Men&uuml;unterpunkte &auml;ndern","Untereintr&auml;ge des Hauptmen&uuml;s bearbeiten.");
				$MENU_TABLE=new MODULE_TABLE("Position|Seitenname (alias)|");
				
				$SQL_QUERY=SQL_READLOOP("menu","pageid,seq","seq");
				while($ROW=mysqli_fetch_array($SQL_QUERY)){
					$PAGE_SEQUENCE=$ROW["seq"];
					$SQL_TMP=SQL_READ("pages","id,title,page","id",$ROW["pageid"]);
					$PAGE_TITLE=$SQL_TMP["title"];
					$PAGE_PAGE=$SQL_TMP["page"];
					
					$MENU_TABLE->ADD_LINE("$PAGE_SEQUENCE|$PAGE_TITLE ($PAGE_PAGE)|<a href=\"?page=submenu&edit=".$SQL_TMP["id"]."\" title=\"Unterpunkte bearbeiten\"><i class=\"fa fa-angle-double-right\"></i></a>");
				}
				
				$CURRENT_PAGE->ADD_CUSTOMSOURCE($MENU_TABLE->GET_SOURCE());
				$BODY->ADD_SOURCE($CURRENT_PAGE->GET_SOURCE());
			}
		}
		break;
	//Seitenübersicht.
	case "pages":
		{
			//Seite wird hinzugefügt.
			if(isset($_GET["func_add"])){
				$PAGE_NAME=str_replace(" ","_",strtolower(SIMPLIFY_STRING($_GET["func_add"])));
				if($PAGE_NAME == "") {
					$PAGE_NAME = rand();
				}
				$PAGE_USER=SQL_READ("users","id,username","id",GET_USER_ID())["username"];
				
				//Neuer Berechtigungseintrag wird erstellt.
				$NEW_PERM_ID=PERMSYS_MAKE_ENTRY("NONE","VIEW","EDIT",GET_USER_GROUP_ID());
				SQL_ADD("pages","title,page,category,source,active,views,permissionid","'".$_GET["func_add"]."','$PAGE_NAME',1,'<h1>".$_GET["func_add"]."</h1><div>Neue Seite erstellt von $PAGE_USER.</div>',0,0,$NEW_PERM_ID");
				LOGGING_PUSH("BACKEND/INFO","Seite '".$PAGE_NAME."' erstellt von '".GET_USER_NAME()." (".GET_USER_ID().")'.");
				header("Location: ?page=pages");
			
			//Seite wird gelöscht.
			}elseif(isset($_GET["func_delete"])){
				$PID=SQL_READ("pages","id,permissionid","id",$_GET["func_delete"])["permissionid"];
				SQL_DELETE("pages","id",$_GET["func_delete"]);
				PERMSYS_DELETE($PID);
				LOGGING_PUSH("BACKEND/INFO","Seite (".$_GET["func_delete"].") gelöscht von '".GET_USER_NAME()." (".GET_USER_ID().")'.");
				header("Location: ?page=pages");
			
			//Seite online/offline.
			}elseif(isset($_GET["func_toggleactive"])){
				$PAGE=SQL_READ("pages","id,page,active","id",$_GET["func_toggleactive"]);
				if($PAGE["active"]==0){
					SQL_CHANGE("pages","id",$_GET["func_toggleactive"],"active","1");
					LOGGING_PUSH("BACKEND/INFO","Seite '".$PAGE["page"]." (".$PAGE["id"].")' freigeschaltet von '".GET_USER_NAME()." (".GET_USER_ID().")'.");
				}else{
					SQL_CHANGE("pages","id",$_GET["func_toggleactive"],"active","0");
					LOGGING_PUSH("BACKEND/INFO","Seite '".$PAGE["page"]." (".$PAGE["id"].")' gesperrt von '".GET_USER_NAME()." (".GET_USER_ID().")'.");
				}
				header("Location: ?page=pages");
			
			//Alle Seiten werden aufgelistet.
			}else{
				$CURRENT_PAGE=new PAGE("Seiten&uuml;bersicht","In diesem Bereich k&ouml;nnen die Seiten der Website verwaltet und bearbeitet werden.");
				$CURRENT_PAGE->ADD_BUTTON_TEXT("Seite hinzuf&uuml;gen","Geben Sie einen Namen f&uuml;r die Seite ein:","?page=pages&func_add=");
				
				$PAGES_TABLE=new MODULE_TABLE("ID|Seitenname (alias)|Kategorie|Freigegeben|Autor|Aufrufe|");
				
				//Wenn Suche:
				if(isset($_GET["search"])){
					//SQL-Funktion berücksichtigt Suchparameter.
					$SQL_QUERY=SQL_SEARCH("pages","id,title,page,category,active,views,permissionid","title",$_GET["search"]);
					$CURRENT_PAGE->ADD_BUTTON_NAVIGATION("Suche zur&uuml;cksetzen","?page=pages");
				}else{
					//Alle Seiten werden ausgegeben.
					$SQL_QUERY=SQL_READLOOP("pages","id,title,page,category,active,views,permissionid","title");
					$CURRENT_PAGE->ADD_BUTTON_TEXT("Seite suchen","Geben Sie den Namen der Seite ein (keine Stichworte): ","?page=pages&search=");
				}
				
				//Loop durch alle Ergebnisse der SQL-Anfrage.
				while($ROW=mysqli_fetch_array($SQL_QUERY)){
					$PAGE_ID=$ROW["id"];
					$PAGE_TITLE=$ROW["title"];
					$PAGE_NAME=$ROW["page"];
					
					//Abfrage nach Status einer Spezialseite.
					$PAGE_IS_SPECIAL=false;
					if(mysqli_fetch_array(SQL_EXEC("SELECT EXISTS(SELECT * FROM pages_special WHERE page = '".$PAGE_NAME."')"))[0] == 1) {
						$PAGE_IS_SPECIAL=true;
					}
					
					$PAGE_CATEGORY=SQL_READ("categories","id,category","id",$ROW["category"])["category"];
					$PAGE_ACTIVE;
					if($ROW["active"]==0){
						$PAGE_ACTIVE="<a onClick=\"dpBoxConfirm('Ver&ouml;ffentlichen?','Soll die Seite \'$PAGE_NAME\' wirklich &ouml;ffentlich geschaltet werden?',function(){jsNav('?page=pages&func_toggleactive=$PAGE_ID');});\" title=\"Seite online stellen\"><i class=\"fa fa-times\"></i></a>";
					}else{
						$PAGE_ACTIVE="<a onClick=\"dpBoxConfirm('Widerrufen?','Soll die Seite \'$PAGE_NAME\' wirklich offline genommen werden?',function(){jsNav('?page=pages&func_toggleactive=$PAGE_ID');});\" title=\"Seite offline stellen\"><i class='fa fa-check'></i></a>";
					}
					$PAGE_DELETE="<a onClick=\"dpBoxConfirm('Seite l&ouml;schen?','Soll die Seite \'$PAGE_NAME\' wirklich gel&ouml;scht werden? Bitte beachten Sie, dass die Seite nicht wiederhergestellt werden kann und Verweise, die auf diese Seite zeigen dadurch ung&uuml;ltig werden.',function(){jsNav('?page=pages&func_delete=$PAGE_ID');});\" title=\"Seite vom Server l&ouml;schen\"><i class=\"fa fa-times\"></i></a>";
					$PAGE_AUTHOR=SQL_READ("users","id,username","id",SQL_READ("permissions","id,owner","id",$ROW["permissionid"])["owner"])["username"];
					$PAGE_VIEWS=$ROW["views"];
					
					//Wenn Spezialseite: Online/Offline Button und Löschen-Button sind nicht verfügbar.
					if($PAGE_IS_SPECIAL){
						$PAGE_ACTIVE = "";
						$PAGE_DELETE = "";
					}
					
					//Ausgabe der Seite.
					$PAGES_TABLE->ADD_LINE("$PAGE_ID|<a title=\"Diese Seite bearbeiten und Berechtigungen &auml;ndern\" href=\"?page=editor&id=$PAGE_ID\">$PAGE_TITLE</a> ($PAGE_NAME)|$PAGE_CATEGORY|$PAGE_ACTIVE|$PAGE_AUTHOR|$PAGE_VIEWS|$PAGE_DELETE");
				}
				
				$CURRENT_PAGE->ADD_CUSTOMSOURCE($PAGES_TABLE->GET_SOURCE());
				$BODY->ADD_SOURCE($CURRENT_PAGE->GET_SOURCE());
			}
		}
		break;
	//Medienübersicht.
	case "media":
		{
			//Mediendatei wird gelöscht.
			if(isset($_GET["func_delete"])){
				$MEDIA=SQL_READ("media","id,path","id",$_GET["func_delete"]);
				
				//Datei wird vom Server gelöscht.
				unlink($MEDIA["path"]);
				
				//Referenz wird aus der Datenbank entfernt.
				SQL_DELETE("media","id",$_GET["func_delete"]);
				LOGGING_PUSH("BACKEND/INFO","Mediendatei '".$MEDIA["path"]." (".$MEDIA["id"].")' von '".GET_USER_NAME()." (".GET_USER_ID().")' vom Server gelöscht.");
				header("Location: ?page=media");
			
			//Datei hochladen.
			}elseif(isset($_GET["upload"])){
				$CURRENT_PAGE=new PAGE("Datei hochladen","Datei ausw&auml;hlen und hochladen.");
				$CURRENT_PAGE->ADD_BUTTON_NAVIGATION("Zur&uuml;ck","?page=media");
				$UPLOAD_FORM=new MODULE_FORM("?page=media&func_upload","post",true);
				
				$UPLOAD_FORM->ADD_CUSTOMSOURCE("<label id=\"value_files_lbl\">Datei ausw&auml;hlen: </label><input id=\"fileupload\" type=\"file\" name=\"attachements[]\"/>");
				$UPLOAD_FORM->ADD_CUSTOMSOURCE("<label id=\"value_current_lbl\">Fortschritt:</label><div id=\"progressbar\"><div id=\"value_current\"></div></div>");
				
				//Scripteinbindung.
				$UPLOAD_FORM->ADD_CUSTOMSOURCE("<script>\$(function(){");
				$UPLOAD_FORM->ADD_CUSTOMSOURCE("\$('#fileupload').fileupload({");
				$UPLOAD_FORM->ADD_CUSTOMSOURCE("url:'?page=media&func_upload',");
				$UPLOAD_FORM->ADD_CUSTOMSOURCE("dataType:'json'");
				$UPLOAD_FORM->ADD_CUSTOMSOURCE("}).on('fileuploadprogressall',function(e,data){var progress=((data.loaded/data.total)*100).toFixed(2);$('#progressbar #value_current').css('width',progress+'%');$('#value_current_lbl').html('Fortschritt: '+progress+'% hochgeladen.');");
				$UPLOAD_FORM->ADD_CUSTOMSOURCE("if(progress==100){\$('#value_current_lbl').html('Fortschritt: Bitte warten...');}");
				$UPLOAD_FORM->ADD_CUSTOMSOURCE("}).on('fileuploadadd',function(e,data){\$('#fileupload').attr('disabled','true');data.submit();");
				$UPLOAD_FORM->ADD_CUSTOMSOURCE("}).on('fileuploaddone',function(e,data){");
				$UPLOAD_FORM->ADD_CUSTOMSOURCE("window.location.href='?page=media&upload_result=Datei erfolgreich hochgeladen.';");
				$UPLOAD_FORM->ADD_CUSTOMSOURCE("}).on('fileuploadfail',function(e,data){");
				$UPLOAD_FORM->ADD_CUSTOMSOURCE("var message=encodeURI(data.jqXHR.responseJSON.message);window.location.href='?page=media&upload_result='+message;");
				$UPLOAD_FORM->ADD_CUSTOMSOURCE("});});</script>");
				
				$CURRENT_PAGE->ADD_CUSTOMSOURCE($UPLOAD_FORM->GET_SOURCE());
				$BODY->ADD_SOURCE($CURRENT_PAGE->GET_SOURCE());
			
			//Funktion, welche hochgeladene Daten verarbeitet und JSON-Code zurückgibt.
			}elseif(isset($_GET["func_upload"])){
				header("Content-Type: application/json");
				DEBUGGING_LOGGER::SUPPRESS_LOGGING();
				$RTN_MSG=array("status" => 4, "message" => "Unbekannter Fehler bei der Daten&uuml;bertragung.");
				if(isset($_FILES["attachements"])) {
					//Ordner werden erstellt.
					$baseDirectory="./media/".date("Y-m-d");
					mkdir("./media");
					mkdir($baseDirectory);
					
					//Dateiname wird erstellt.
					$targetFile=$baseDirectory . "/" . hash("crc32b", date("Y-m-d-H-i-s")) . "." . SIMPLIFY_STRING(basename($_FILES["attachements"]["name"][0]));
					
					//Wenn Datei nicht vorhanden: Upload.
					if(!file_exists($targetFile)) {
						//Datei wird nach dem Upload an die gewünschte Stelle verschoben.
						if(move_uploaded_file($_FILES["attachements"]["tmp_name"][0], $targetFile)) {
							//Datei wird in der Datenbank verlinkt.
							SQL_ADD("media","path,uploaddate","'$targetFile','".date("Y-m-d H:i:s")."'");
							LOGGING_PUSH("BACKEND/INFO","Mediendatei nach '".$targetFile."' von '".GET_USER_NAME()." (".GET_USER_ID().")' hochgeladen.");
							$RTN_MSG=array("status" => 0, "message" => "Datei erfolgreich hochgeladen.");
						} else {
							LOGGING_PUSH("BACKEND/WARNING","Mediendatei konnte nicht hochgeladen werden (FehlerCode 1).");
							$RTN_MSG=array("status" => 1, "message" => "Hochgeladene Daten konnten nicht verarbeitet werden.");
						}
					} else {
						LOGGING_PUSH("BACKEND/WARNING","Mediendatei konnte nicht hochgeladen werden (FehlerCode 2).");
						$RTN_MSG=array("status" => 2, "message" => "Die hochgeladene Datei existiert bereits.");
					}
				} else {
					LOGGING_PUSH("BACKEND/WARNING","Mediendatei konnte nicht hochgeladen werden (FehlerCode 3).");
					$RTN_MSG=array("status" => 3, "message" => "Die Anfrage konnte aufgrund fehlender Parameter nicht verarbeitet werden.");
				}
				exit(json_encode($RTN_MSG));
			
			//Zeigt das Ergebnis des Uploads an.
			}elseif(isset($_GET["upload_result"])){
				$CURRENT_PAGE=new PAGE("Dateiupload",$_GET["upload_result"]);
				$CURRENT_PAGE->ADD_BUTTON_NAVIGATION("Zur Medien&uuml;bersicht","?page=media");
				$CURRENT_PAGE->ADD_BUTTON_NAVIGATION("Weitere Datei hochladen","?page=media&upload");
				$BODY->ADD_SOURCE($CURRENT_PAGE->GET_SOURCE());
			
			//Medien werden ausgegeben.
			}else{
				$CURRENT_PAGE=new PAGE("Medienverwaltung","Medien hochladen und verwalten.");
				$CURRENT_PAGE->ADD_BUTTON_NAVIGATION("Datei hochladen","?page=media&upload");
				$MEDIA_TABLE=new MODULE_TABLE("ID|Dateiname|Upload-Datum||");
				
				//Loop durch alle Medien.
				$SQL_QUERY=SQL_READLOOP("media","*","path");
				while($ROW=mysqli_fetch_array($SQL_QUERY)){
					$MEDIA_ID=$ROW["id"];
					$MEDIA_NAME=explode("/",$ROW["path"])[3];
					
					//Ausgabe in die Tabelle.
					$MEDIA_TABLE->ADD_LINE($MEDIA_ID."|".$MEDIA_NAME."|".date("d.m.Y H:i", strtotime($ROW["uploaddate"]))."|<a onClick=\"window.open('".$ROW["path"]."','media$MEDIA_ID','width=960,height=540');\" title=\"Vorschau in neuem Fenster\"><i class=\"fa fa-angle-double-right\"></i></a>|<a onClick=\"dpBoxConfirm('Datei l&ouml;schen?','Soll die Datei \'$MEDIA_NAME\' wirklich gel&ouml;scht werden? Bitte beachten Sie, dass die Datei nicht wiederhergestellt werden kann und Verweise, die auf diese zeigen dadurch ung&uuml;ltig werden.',function(){jsNav('?page=media&func_delete=$MEDIA_ID');});\" title=\"Datei vom Server l&ouml;schen\"><i class=\"fa fa-times\"></i></a>");
				}
				$CURRENT_PAGE->ADD_CUSTOMSOURCE($MEDIA_TABLE->GET_SOURCE());
				$BODY->ADD_SOURCE($CURRENT_PAGE->GET_SOURCE());
			}
		}
		break;
	//Kategorien.
	case "categories":
		{
			//Kategorie wird hinzugefügt.
			if(isset($_GET["func_add"])){
				SQL_ADD("categories","category","'".$_GET["func_add"]."'");
				LOGGING_PUSH("BACKEND/INFO","Kategorie '".$_GET["func_add"]."' von '".GET_USER_NAME()." (".GET_USER_ID().")' erstellt.");
				header("Location: ?page=categories");
			
			//Kategorie wird gelöscht.
			}elseif(isset($_GET["func_delete"])){
				SQL_DELETE("categories","id",$_GET["func_delete"]);
				LOGGING_PUSH("BACKEND/INFO","Kategorie (".$_GET["func_delete"].") von '".GET_USER_NAME()." (".GET_USER_ID().")' erstellt.");
				header("Location: ?page=categories");
			
			//Alle Kategorien werden in einer Übersicht dargestellt.
			}else{
				$CURRENT_PAGE=new PAGE("Kategorien","Kategorien hinzuf&uuml;gen und verwalten.");
				$CURRENT_PAGE->ADD_BUTTON_TEXT("Kategorie hinzuf&uuml;gen","Geben Sie einen Namen f&uuml;r die neue Kategorie ein:","?page=categories&func_add=");
				$CATEGORY_TABLE=new MODULE_TABLE("ID|Kategorie|");
				
				//Loop durch alle Kategorien.
				$SQL_QUERY=SQL_READLOOP("categories","id,category","category");
				while($ROW=mysqli_fetch_array($SQL_QUERY)){
					//Ausgabe in der Tabelle.
					if($ROW["id"] == 1 || $ROW["id"] == 2 || $ROW["id"] == 3) {
						$CATEGORY_TABLE->ADD_LINE($ROW["id"]."|".$ROW["category"]."|");
					} else {
						$CATEGORY_TABLE->ADD_LINE($ROW["id"]."|".$ROW["category"]."|"."<a onClick=\"dpBoxConfirm('Kategorie entfernen?','Soll die Kategorie \'".$ROW["category"]."\' wirklich gel&ouml;scht werden? Bitte beachten Sie, dass die Kategorie nicht wiederhergestellt werden kann und Verweise, die auf diese zeigen dadurch ung&uuml;ltig werden.',function(){jsNav('?page=categories&func_delete=".$ROW["id"]."');});\" title=\"Kategorie entfernen\"><i class=\"fa fa-times\"></i></a>");
					}
				}
				$CURRENT_PAGE->ADD_CUSTOMSOURCE($CATEGORY_TABLE->GET_SOURCE());
				$BODY->ADD_SOURCE($CURRENT_PAGE->GET_SOURCE());
			}
		}
		break;
	//Kollegium.
	case "kollegium":
		{
			//Änderungen am Text werden gespeichert.
			if(isset($_GET["func_save"])){
				if(isset($_POST["text"])){
					SQL_CHANGE("longtexts","name","fachschaften","text",$_POST["text"]);
					SQL_CHANGE("longtexts","name","tier1","text",$_POST["tier1"]);
					SQL_CHANGE("longtexts","name","tier2","text",$_POST["tier2"]);
					SQL_CHANGE("longtexts","name","tier3","text",$_POST["tier3"]);
				}
				header("Location: ?page=kollegium");
			
			//Formular zum Bearbeiten wird angezeigt.
			}else{
				$CURRENT_PAGE=new PAGE("Kollegium","Bilder des Kollegiums &auml;ndern und Fachschaften bearbeiten.");
				
				//Upload-Buttons.
				$BTNLIST_KOLLEGIUM=new MODULE_BUTTONLIST();
				$BTNLIST_KOLLEGIUM->ADD_BUTTON_UPLOAD("Bild Schulleiter","Neues Bild","Neues Bild f&uuml;r den Schulleiter hochladen:","?page=editor&func_upload&sub=kollegium&name=kollegium1st.png");
				$BTNLIST_KOLLEGIUM->ADD_BUTTON_UPLOAD("Bild stellv. Schulleiter","Neues Bild","Neues Bild f&uuml;r den stellv. Schulleiter hochladen:","?page=editor&func_upload&sub=kollegium&name=kollegium2nd.png");
				$BTNLIST_KOLLEGIUM->ADD_BUTTON_UPLOAD("Bild Oberstufenkoordinator","Neues Bild","Neues Bild f&uuml;r den Oberstufenkoordinator hochladen:","?page=editor&func_upload&sub=kollegium&name=kollegium3rd.png");
				$CURRENT_PAGE->ADD_CUSTOMSOURCE($BTNLIST_KOLLEGIUM->GET_SOURCE());
				
				//Formular.
				$FORM_KOLLEGIUM=new MODULE_FORM("?page=kollegium&func_save");
				$FORM_KOLLEGIUM->ADD_INPUT_TEXT("Schulleiter","tier1",false,FACHSCHAFTEN_GETTIER1());
				$FORM_KOLLEGIUM->ADD_INPUT_TEXT("stellv. Schulleiter","tier2",false,FACHSCHAFTEN_GETTIER2());
				$FORM_KOLLEGIUM->ADD_INPUT_TEXT("Oberstufenkoordinator","tier3",false,FACHSCHAFTEN_GETTIER3());
				$FORM_KOLLEGIUM->ADD_INPUT_TEXTAREA("Fachschaften-Text","text",FACHSCHAFTEN_GETCNT());
				$FORM_KOLLEGIUM->ADD_SUBMIT("Speichern", "Text speichern");
				$CURRENT_PAGE->ADD_CUSTOMSOURCE($FORM_KOLLEGIUM->GET_SOURCE());
				$BODY->ADD_SOURCE($CURRENT_PAGE->GET_SOURCE());
			}
		}
		break;
	//Statistik und Metadaten, sowie Log.
	case "statistics":
		{
			//Speicherung der Metadaten.
			if(isset($_GET["func_save"])){
				if(isset($_POST["metadata_title"])){
					$DATA=$_POST["metadata_title"];
					SQL_CHANGE("metadata","id","1","metadata","$DATA");
				}
				if(isset($_POST["metadata_description"])){
					$DATA=$_POST["metadata_description"];
					SQL_CHANGE("metadata","id","2","metadata","$DATA");
				}
				if(isset($_POST["metadata_revisit"])){
					$DATA=$_POST["metadata_revisit"];
					SQL_CHANGE("metadata","id","3","metadata","$DATA");
				}
				if(isset($_POST["metadata_index"])){
					$DATA=$_POST["metadata_index"];
					SQL_CHANGE("metadata","id","4","metadata","$DATA");
				}
				if(isset($_POST["metadata_onlinestate"])){
					$DATA=$_POST["metadata_onlinestate"];
					SQL_CHANGE("metadata","id","5","metadata","$DATA");
					
					if($DATA == "true") {
						LOGGING_PUSH("BACKEND/IMPORTANT","Komplette Seite von '".GET_USER_NAME()." (".GET_USER_ID().")' online gesetzt.");
					} else {
						LOGGING_PUSH("BACKEND/IMPORTANT","Komplette Seite von '".GET_USER_NAME()." (".GET_USER_ID().")' offline genommen.");
					}
				}
				if(isset($_POST["metadata_debugging"])){
					$DATA=$_POST["metadata_debugging"];
					SQL_CHANGE("metadata","id","6","metadata","$DATA");
					
					if($DATA == "true") {
						LOGGING_PUSH("BACKEND/IMPORTANT","SQL-Debugging von '".GET_USER_NAME()." (".GET_USER_ID().")' eingeschaltet.");
					} else {
						LOGGING_PUSH("BACKEND/IMPORTANT","SQL-Debugging von '".GET_USER_NAME()." (".GET_USER_ID().")' ausgeschaltet.");
					}
				}
				LOGGING_PUSH("BACKEND/INFO","Metainformationen von '".GET_USER_NAME()." (".GET_USER_ID().")' geändert.");
				header("Location: ?page=statistics");
			
			//Löscht das Seitenlog.
			}else if(isset($_GET["func_clearlog"])){
				LOGGING_CLEAR();
				header("Location: ?page=statistics");
				
			//Exportiert das Log.
			}else if(isset($_GET["func_export"])){
				echo "<script>window.location.href='".LOGGING_EXPORT()."';</script>";
				
			//Zeigt allgemeine Informationen zur Website an.
			}else{
				$CURRENT_PAGE=new PAGE("Seiteninformationen","In diesem Bereich werden allgemeine Statistiken der Website angezeigt.");
				$STATS_TABLE=new MODULE_TABLE("Statistik|Wert");
				
				$MAXCLICKS_SQL=SQL_MAXVALUE("pages","views");
				$MAXCLICKS_SITE=SQL_READ("pages","title,views","views",$MAXCLICKS_SQL["views"])["title"];
				$MAXCLICKS_VALUE=$MAXCLICKS_SQL["views"];
				
				$STATS_TABLE->ADD_LINE("Beliebteste Seite|".$MAXCLICKS_SITE." (".$MAXCLICKS_VALUE." Aufrufe)");
				$CURRENT_PAGE->ADD_CUSTOMSOURCE($STATS_TABLE->GET_SOURCE());
				$CURRENT_PAGE->ADD_NEW_HEADERBOX("Metadaten","Metadaten bearbeiten.");
				
				//Metainformationen werden ausgelesen.
				$META_TITLE=SQL_READ("metadata","name,metadata","name","title")["metadata"];
				$META_DESCRIPTION=SQL_READ("metadata","name,metadata","name","description")["metadata"];
				$META_REVISIT=SQL_READ("metadata","name,metadata","name","revisit")["metadata"];
				$META_INDEX=SQL_READ("metadata","name,metadata","name","index")["metadata"];
				$META_ONLINESTATE=SQL_READ("metadata","name,metadata","name","onlinestate")["metadata"];
				$META_DEBUGGING=SQL_READ("metadata","name,metadata","name","debugging")["metadata"];
				
				//Formular.
				$META_FORM=new MODULE_FORM("?page=statistics&func_save");
				$META_FORM->ADD_INPUT_TEXT("Titel","metadata_title",false,"$META_TITLE");
				$META_FORM->ADD_INPUT_TEXT("Beschreibung","metadata_description",false,"$META_DESCRIPTION");
				$META_FORM->ADD_INPUT_TEXT("Robotbesuchabst&auml;nde in Tagen","metadata_revisit",false,"$META_REVISIT");
				$META_INDEX_STANDARD;
				if($META_INDEX=="INDEX"){
					$META_INDEX_STANDARD=0;
				}else{
					$META_INDEX_STANDARD=1;
				}
				$META_FORM->ADD_INPUT_SELECT("Seite Indexieren","metadata_index","Indexieren|Nicht indexieren","INDEX|NOINDEX","$META_INDEX_STANDARD");
				$META_ONLINESTATE_STANDARD;
				if($META_ONLINESTATE=="true"){
					$META_ONLINESTATE_STANDARD=0;
				}else{
					$META_ONLINESTATE_STANDARD=1;
				}
				$META_FORM->ADD_INPUT_SELECT("Onlinestatus","metadata_onlinestate","Online|Offline","true|false","$META_ONLINESTATE_STANDARD");
				$META_DEBUGGING_STANDARD;
				if($META_DEBUGGING=="true"){
					$META_DEBUGGING_STANDARD=0;
				}else{
					$META_DEBUGGING_STANDARD=1;
				}
				$META_FORM->ADD_INPUT_SELECT("SQL-Debugging","metadata_debugging","An|Aus","true|false","$META_DEBUGGING_STANDARD");
				$META_FORM->ADD_SUBMIT("Speichern","&Auml;nderungen anwenden");
				$CURRENT_PAGE->ADD_CUSTOMSOURCE($META_FORM->GET_SOURCE());
				
				//Logging-Daten.
				$CURRENT_PAGE->ADD_NEW_HEADERBOX("Logging","Seiten-Log ansehen.<br><a href=\"?page=statistics&func_clearlog\">Log l&ouml;schen</a> <a target=\"_blank\" href=\"?page=statistics&func_export\">Log exportieren</a>");
				
				$CURRENT_PAGE->ADD_CUSTOMSOURCE("<textarea id=\"log\" readonly>" . LOGGING_GETCNT() . "</textarea><script>var xObj=document.getElementById('log');xObj.scrollTop=xObj.scrollHeight;</script>");
				
				$BODY->ADD_SOURCE($CURRENT_PAGE->GET_SOURCE());
			}
		}
		break;
	//Seiteneditor.
	case "editor":
		{
			if(isset($_GET["id"])){
				//Speichert eine Seite.
				if(isset($_GET["func_save"])){
					SQL_CHANGE_MULTIPLE("pages","id",$_GET["id"],"title='".$_POST["page_title"]."',page='".$_POST["page_page"]."',category='".$_POST["page_category"]."',source='".$_POST["page_source"]."',active='".$_POST["page_active"]."'");
					PERMSYS_UPDATE($_POST["permid"],$_POST["page_perm_public"],$_POST["page_perm_auth"],$_POST["page_perm_group_level"],$_POST["page_perm_group_id"]);
					LOGGING_PUSH("BACKEND/INFO","Seite '".$_POST["page_page"]."' von '".GET_USER_NAME()." (".GET_USER_ID().")' bearbeitet.");
					header("Location: ?page=editor&id=".$_GET["id"]."");
				
				//Löscht eine Seite.
				}elseif(isset($_GET["func_delete"])){
					SQL_DELETE("pages","id",$_GET["id"]);
					LOGGING_PUSH("BACKEND/INFO","Seite (".$_GET["id"].") von '".GET_USER_NAME()." (".GET_USER_ID().")' gelöscht.");
					header("Location: ?page=pages");
				
				//Zeigt die gegebene Seite zur Bearbeitung an.
				}else{
					$CURRENT_PAGE=new PAGE("Seite bearbeiten","Eine Seite bearbeiten oder Berechtigungen &auml;ndern.");
					$CURRENT_PAGE->ADD_BUTTON_NAVIGATION("Zur Seiten&uuml;bersicht","?page=pages");

					$PAGE=SQL_READ("pages","*","id",$_GET["id"]);
					$PAGE_IS_SPECIAL=false;
					if(mysqli_fetch_array(SQL_EXEC("SELECT EXISTS(SELECT * FROM pages_special WHERE page = '".$PAGE["page"]."')"))[0] == 1) {
						$PAGE_IS_SPECIAL=true;
					}
					if($PAGE["category"] == 3) {
						$PAGE_IS_SPECIAL=true;
					}

					$CURRENT_PAGE->ADD_CUSTOMSOURCE("<div class=\"editor\">");
					
					//Buttons des WYSIWYG-Editors werden bei normalen Seiten angezeigt.
					if(!$PAGE_IS_SPECIAL){
						$CURRENT_PAGE->ADD_CUSTOMSOURCE("<div class=\"menubar\">");
						$CURRENT_PAGE->ADD_CUSTOMSOURCE("<select class=\"button type_format\" title=\"Formate\"><option disabled selected value=\"0\">Formatieren</option><option value=\"1\">Überschrift 1</option><option value=\"2\">&Uuml;berschrift 2</option><option value=\"3\">&Uuml;berschrift 3</option><option value=\"4\" class=\"text\">Textk&ouml;rper</option></select>");
						$CURRENT_PAGE->ADD_CUSTOMSOURCE("<button class=\"button type_easy\" data-attribute=\"undo\" title=\"R&uuml;ckg&auml;ngig\"><i class=\"fa fa-undo\"></i></button>");
						$CURRENT_PAGE->ADD_CUSTOMSOURCE("<button class=\"button type_easy\" data-attribute=\"redo\" title=\"Hing&auml;ngig\"><i class=\"fa fa-redo\"></i></button>");
						$CURRENT_PAGE->ADD_CUSTOMSOURCE("<button class=\"button type_easy\" data-attribute=\"copy\" title=\"Kopieren\"><i class=\"fa fa-copy\"></i></button>");
						$CURRENT_PAGE->ADD_CUSTOMSOURCE("<button class=\"button type_easy\" data-attribute=\"cut\" title=\"Ausschneiden\"><i class=\"fa fa-cut\"></i></button>");
						$CURRENT_PAGE->ADD_CUSTOMSOURCE("<button class=\"button type_easy\" data-attribute=\"bold\" title=\"Fett\"><i class=\"fa fa-bold\"></i></button>");
						$CURRENT_PAGE->ADD_CUSTOMSOURCE("<button class=\"button type_easy\" data-attribute=\"italic\" title=\"Kursiv\"><i class=\"fa fa-italic\"></i></button>");
						$CURRENT_PAGE->ADD_CUSTOMSOURCE("<button class=\"button type_easy\" data-attribute=\"underline\" title=\"Unterstrichen\"><i class=\"fa fa-underline\"></i></button>");
						$CURRENT_PAGE->ADD_CUSTOMSOURCE("<button class=\"button type_easy\" data-attribute=\"justifyfull\" title=\"Textblock\"><i class=\"fa fa-align-justify\"></i></button>");
						$CURRENT_PAGE->ADD_CUSTOMSOURCE("<button class=\"button type_easy\" data-attribute=\"justifyleft\" title=\"Linksb&uuml;ndig\"><i class=\"fa fa-align-left\"></i></button>");
						$CURRENT_PAGE->ADD_CUSTOMSOURCE("<button class=\"button type_easy\" data-attribute=\"justifycenter\" title=\"Zentriert\"><i class=\"fa fa-align-center\"></i></button>");
						$CURRENT_PAGE->ADD_CUSTOMSOURCE("<button class=\"button type_easy\" data-attribute=\"justifyright\" title=\"Rechtsb&uuml;ndig\"><i class=\"fa fa-align-right\"></i></button>");
						$CURRENT_PAGE->ADD_CUSTOMSOURCE("<button class=\"button type_color\" title=\"Farbe anwenden\"><i class=\"fas fa-fill-drip\"></i></button>");
						$CURRENT_PAGE->ADD_CUSTOMSOURCE("<input class=\"button type_colorpicker\" type=\"color\" title=\"Farbe w&auml;hlen\">");
						$CURRENT_PAGE->ADD_CUSTOMSOURCE("<button class=\"button type_link\" id=\"type_link_b\" title=\"Link erstellen\"><i class=\"fa fa-link\"></i></button>");
						$CURRENT_PAGE->ADD_CUSTOMSOURCE("<button class=\"button type_easy\" data-attribute=\"unlink\" title=\"Link entfernen\"><i class=\"fa fa-unlink\"></i></button>");
						$CURRENT_PAGE->ADD_CUSTOMSOURCE("<button class=\"button type_table\" title=\"Tabelle einf&uuml;gen\"><i class=\"fa fa-table\"></i></button>");
						$CURRENT_PAGE->ADD_CUSTOMSOURCE("<button class=\"button type_ulist\" title=\"Aufz&auml;hlung\"><i class=\"fa fa-list\"></i></button>");
						$CURRENT_PAGE->ADD_CUSTOMSOURCE("<button class=\"button type_media\" id=\"type_media_b\" title=\"Medien einf&uuml;gen\"><i class=\"fa fa-image\"></i></button>");
						$CURRENT_PAGE->ADD_CUSTOMSOURCE("<button class=\"button type_upload\" id=\"type_upload_b\" title=\"Datei hochladen\"><i class=\"fa fa-upload\"></i></button>");
						$CURRENT_PAGE->ADD_CUSTOMSOURCE("</div>");
						
						$CURRENT_PAGE->ADD_CUSTOMSOURCE("<div class=\"edit_area\">");
						$CURRENT_PAGE->ADD_CUSTOMSOURCE("<div class=\"edit\" id=\"canvas\" data-text=\"Seite bearbeiten...\" contenteditable>".$PAGE["source"]."</div>");
						$CURRENT_PAGE->ADD_CUSTOMSOURCE("</div>");
					}
					
					//Formular zum Bearbeiten der Seitendaten.
					$EDITOR_FORM=new MODULE_FORM("?page=editor&id=".$_GET["id"]."&func_save");
					$EDITOR_FORM->ADD_SUBMIT("Speichern","Seite speichern");
					if($PAGE_IS_SPECIAL){
						$EDITOR_FORM->ADD_CUSTOMSOURCE("<textarea style=\"display: none;\" name=\"page_source\" id=\"canvassource\" readonly></textarea>");
					}else{
						$EDITOR_FORM->ADD_CUSTOMSOURCE("<label>Quelltext:</label><div class=\"source\"><input class=\"spoiler_source\" type=\"button\" value=\"Quelltext zeigen\" onClick=\"showSourceArea()\"/><br><textarea name=\"page_source\" id=\"canvassource\"></textarea></div>");
					}
					$EDITOR_FORM->ADD_INPUT_TEXT("Seiten-ID","",true,$PAGE["id"]);
					if($PAGE_IS_SPECIAL){
						$EDITOR_FORM->ADD_INPUT_TEXT("Seitentitel","page_title",true,$PAGE["title"]);
						$EDITOR_FORM->ADD_INPUT_TEXT("Seitenname (keine Umlaute)","page_page",true,$PAGE["page"]);
					}else{
						$EDITOR_FORM->ADD_INPUT_TEXT("Seitentitel","page_title",false,$PAGE["title"]);
						$EDITOR_FORM->ADD_INPUT_TEXT("Seitenname (keine Umlaute)","page_page",false,$PAGE["page"]);
					}
					$EDITOR_FORM->ADD_INPUT_SELECT_MYSQL("Kategorie","page_category","categories","id","category",$PAGE["category"]);
					$EDITOR_FORM->ADD_INPUT_SELECT("Freigegeben","page_active","Ja|Nein","1|0",($PAGE["active"] == "0") ? 1 : 0);
					$PAGE_PERMS=SQL_READ("permissions","*","id",$PAGE["permissionid"]);
					$EDITOR_FORM->ADD_INPUT_TEXT("Berechtigungs-ID","permid",true,$PAGE_PERMS["id"]);
					$AUTHOR=SQL_READ("users","id,username","id",$PAGE_PERMS["owner"])["username"];
					$EDITOR_FORM->ADD_INPUT_TEXT("Eigent&uuml;mer/Autor","",true,$AUTHOR);
					$EDITOR_FORM->ADD_INPUT_SELECT_MYSQL("Spezielle Gruppe","page_perm_group_id","usergroups","id","name",$PAGE_PERMS["groupid"]);
					$EDITOR_FORM->ADD_INPUT_SELECT_MYSQL("Spezielle Gruppenberechtigung","page_perm_group_level","permission_levels","internal","display",$PAGE_PERMS["groupperm"]);
					$EDITOR_FORM->ADD_INPUT_SELECT_MYSQL("Authentifizierte Nutzerberechtigung","page_perm_auth","permission_levels","internal","display",$PAGE_PERMS["auth"]);
					$EDITOR_FORM->ADD_INPUT_SELECT_MYSQL("&Ouml;ffentliche Nutzerberechtigung","page_perm_public","permission_levels","internal","display",$PAGE_PERMS["public"]);
					$EDITOR_FORM->ADD_INPUT_TEXT("Aufrufe","",true,$PAGE["views"]);
					$EDITOR_FORM->ADD_SUBMIT("Speichern","Seite speichern");
					if(!$PAGE_IS_SPECIAL){
						$EDITOR_FORM->ADD_BUTTON_CONFIRMATION("L&ouml;schen","Seite l&ouml;schen","Soll die Seite \'".$PAGE["page"]."\' wirklich entfernt werden?","?page=editor&id=".$PAGE["id"]."&func_delete");
					}
					$CURRENT_PAGE->ADD_CUSTOMSOURCE($EDITOR_FORM->GET_SOURCE());
					$CURRENT_PAGE->ADD_CUSTOMSOURCE("</div>");
					
					//Scripteinbindung.
					$CURRENT_PAGE->ADD_CUSTOMSOURCE("<script language=\"javascript\" type=\"text/javascript\" src=\"javascript/editor.js\"></script>");
					$CURRENT_PAGE->ADD_CUSTOMSOURCE("<script>displaySource();</script>");
					$BODY->ADD_SOURCE($CURRENT_PAGE->GET_SOURCE());
				}
			
			//JSON-Daten über Seiten.
			}else if(isset($_GET["func_getpages"])){
				header("Content-Type: application/json");
				DEBUGGING_LOGGER::SUPPRESS_LOGGING();
				$RTN_JSON=array();
				$SQL_QUERY=SQL_READLOOP("pages","id,title,page","title");
				$C=0;
				while($ROW=mysqli_fetch_array($SQL_QUERY)){
					$RTN_JSON["pages"][$C]["id"]=(int)$ROW["id"];
					$RTN_JSON["pages"][$C]["title"]=$ROW["title"];
					$RTN_JSON["pages"][$C]["page"]=$ROW["page"];
					
					$C++;
				}
				exit(json_encode($RTN_JSON));
			
			//JSON-Daten über Medien.
			}elseif(isset($_GET["func_getmedia"])){
				header("Content-Type: application/json");
				DEBUGGING_LOGGER::SUPPRESS_LOGGING();
				$RTN_JSON=array();
				$SQL_QUERY=SQL_READLOOP("media","*","path");
				$C=0;
				while($ROW=mysqli_fetch_array($SQL_QUERY)){
					$RTN_JSON["media"][$C]["id"]=(int)$ROW["id"];
					$RTN_JSON["media"][$C]["date"]=date("d.m.Y", strtotime($ROW["uploaddate"]));
					$RTN_JSON["media"][$C]["time"]=date("H:i:s", strtotime($ROW["uploaddate"]));
					$RTN_JSON["media"][$C]["path"]=$ROW["path"];
					$RTN_JSON["media"][$C]["filename"]=explode("/",$ROW["path"])[3];
					
					$C++;
				}
				exit(json_encode($RTN_JSON));
			
			//Funktion, die hochgeladene Daten verarbeitet.
			}else if(isset($_GET["func_upload"])){
				header("Content-Type: application/json");
				DEBUGGING_LOGGER::SUPPRESS_LOGGING();
				$RTN_MSG=array("status" => 4, "message" => "Unbekannter Fehler bei der Daten&uuml;bertragung.");
				if(isset($_FILES["attachements"])) {
					//Ordner werden überprüft und eventuell erstellt.
					if(isset($_GET["sub"])) {
						$baseDirectory="./media/".$_GET["sub"];
						mkdir("./media");
						mkdir($baseDirectory);
					} else {
						$baseDirectory="./media/".date("Y-m-d");
						mkdir("./media");
						mkdir($baseDirectory);
					}
					
					//Dateiname wird geprüft.
					if(isset($_GET["name"])) {
						$targetFile=$baseDirectory . "/" . $_GET["name"];
						if(file_exists($targetFile)) {
							unlink($targetFile);
						}
					} else {
						$tempName = pathinfo(SIMPLIFY_STRING(basename($_FILES["attachements"]["name"][0])));
						$targetFile=$baseDirectory . "/" . $tempName["filename"] . "." . hash("crc32b", date("Y-m-d-H-i-s")) . "." . $tempName["extension"];
					}
					
					//Wenn Datei nicht existiert:
					if(!file_exists($targetFile)) {
						//Datei wird an die gewünscht Location verschoben.
						if(move_uploaded_file($_FILES["attachements"]["tmp_name"][0], $targetFile)) {
							//Datenbankreferenz wird erstellt.
							if(!isset($_GET["sub"]) or !isset($_GET["name"])) {
								SQL_ADD("media","path,uploaddate","'$targetFile','".date("Y-m-d H:i:s")."'");
							}
							LOGGING_PUSH("BACKEND/INFO","Mediendatei nach '".$targetFile."' von '".GET_USER_NAME()." (".GET_USER_ID().")' hochgeladen.");
							$RTN_MSG=array("status" => 0, "message" => "Datei erfolgreich hochgeladen.");
						} else {
							LOGGING_PUSH("BACKEND/WARNING","Mediendatei konnte nicht hochgeladen werden (FehlerCode 1).");
							$RTN_MSG=array("status" => 1, "message" => "Hochgeladene Daten konnten nicht verarbeitet werden.");
						}
					} else {
						LOGGING_PUSH("BACKEND/WARNING","Mediendatei konnte nicht hochgeladen werden (FehlerCode 2).");
						$RTN_MSG=array("status" => 2, "message" => "Die hochgeladene Datei existiert bereits.");
					}
				} else {
					LOGGING_PUSH("BACKEND/WARNING","Mediendatei konnte nicht hochgeladen werden (FehlerCode 3).");
					$RTN_MSG=array("status" => 3, "message" => "Die Anfrage konnte aufgrund fehlender Parameter nicht verarbeitet werden.");
				}
				exit(json_encode($RTN_MSG));
			}else{
				$CURRENT_PAGE=new PAGE("Fehler","Der Editor ben&ouml;tigt den Parameter 'ID', um eine Seite darzustellen..");
				$CURRENT_PAGE->ADD_BUTTON_NAVIGATION("Zur Seiten&uuml;bersicht","?page=pages");
				$BODY->ADD_SOURCE($CURRENT_PAGE->GET_SOURCE());
			}
		}
		break;
	//Login-Seite.
	case "login":
		{
			if(LOGGED_IN()){
				$CURRENT_PAGE=new PAGE("Anmeldefehler","Sie sind bereits angemeldet.");
				$CURRENT_PAGE->ADD_BUTTON_NAVIGATION("Startseite","?page=home");
				$BODY->ADD_SOURCE($CURRENT_PAGE->GET_SOURCE());
			}else{
				$USER_COUNT=SQL_COUNT_ROWS("users");
				
				if($USER_COUNT == "") {
					$_SESSION["backend"]="admin_gmg";
					header("Location: ?page=home");
				}else{
					$CURRENT_PAGE=new PAGE("Anmeldung","Bitte melden Sie sich an, um Zugriff auf den Backend-Bereich zu erhalten.",false);
					$CURRENT_PAGE->ADD_CUSTOMSOURCE("<div class=\"centered\">");
					$CURRENT_PAGE->ADD_CUSTOMSOURCE("<form class=\"login\" action=\"?page=login_submit\" method=\"post\">");
					$CURRENT_PAGE->ADD_CUSTOMSOURCE("<label>Nutzername:</label>");
					$CURRENT_PAGE->ADD_CUSTOMSOURCE("<input type=\"text\" name=\"username\"/>");
					$CURRENT_PAGE->ADD_CUSTOMSOURCE("<label>Passwort:</label>");
					$CURRENT_PAGE->ADD_CUSTOMSOURCE("<input type=\"password\" name=\"password\"/>");
					if(isset($_GET["redirect"])) {
						$CURRENT_PAGE->ADD_CUSTOMSOURCE("<input style=\"display: none;\" value=\"" . urldecode($_GET["redirect"]) . "\" type=\"text\" name=\"redirect\"/>");
					}
					$CURRENT_PAGE->ADD_CUSTOMSOURCE("<input type=\"submit\" value=\"Anmelden\"/>");
					$CURRENT_PAGE->ADD_CUSTOMSOURCE("</form>");
					$CURRENT_PAGE->ADD_CUSTOMSOURCE("</div>");
					$BODY->ADD_SOURCE($CURRENT_PAGE->GET_SOURCE());
				}
			}
		}
		break;
	//Login-Process-Seite.
	case "login_submit":
		{
			if(LOGGED_IN()){
				$CURRENT_PAGE=new PAGE("Anmeldefehler","Sie sind bereits angemeldet.");
				$CURRENT_PAGE->ADD_BUTTON_NAVIGATION("Startseite","?page=home");
				$BODY->ADD_SOURCE($CURRENT_PAGE->GET_SOURCE());
			}else{
				if(isset($_POST["username"]) and isset($_POST["password"])){
					$DB_PASSWORD=SQL_READ("users","username,password","username",strtolower($_POST["username"]))["password"];
					$PASSWORD=hash("sha512",$_POST["password"]);
					if($PASSWORD==$DB_PASSWORD){
						$USER_TO_LOGIN=SQL_READ("users","username,active,usergroup","username",strtolower($_POST["username"]));
						if($USER_TO_LOGIN["active"]!=0){
							if(SQL_READ("usergroups","id,name","id",$USER_TO_LOGIN["usergroup"])["name"]=="admin"){
								//Login wird erstellt und Datenbank aktualisiert.
								$_SESSION["backend"]=strtolower($_POST["username"]);
								SQL_CHANGE("users","username",strtolower($_POST["username"]),"date_lastlogin",date("Y-m-d H:i:s"));
								$LOGINS=SQL_READ("users","username,logins","username",strtolower($_POST["username"]))["logins"];
								SQL_CHANGE("users","username",strtolower($_POST["username"]),"logins",$LOGINS+1);
								LOGGING_PUSH("BACKEND/INFO","Login durch '".GET_USER_NAME()." (".GET_USER_ID().")' im Backend-Bereich.");
								if(isset($_POST["redirect"])) {
									header("Location: ".$_POST["redirect"]);
								} else {
									header("Location: ?page=home");
								}
							}else{
								$CURRENT_PAGE=new PAGE("Anmeldefehler","Der Backend-Bereich kann nur durch einen Administrator betreten werden.",false);
								$CURRENT_PAGE->ADD_BUTTON_NAVIGATION("Erneut versuchen","?page=login");
								LOGGING_PUSH("WARNING","Es wurde versucht, sich im Backend-Bereich mit einem normalen Nutzerkonto anzumelden ('".$_POST["username"]."').");
								$BODY->ADD_SOURCE($CURRENT_PAGE->GET_SOURCE());
							}
						}else{
							$CURRENT_PAGE=new PAGE("Anmeldefehler","Dieser Nutzer ist gesperrt.",false);
							$CURRENT_PAGE->ADD_BUTTON_NAVIGATION("Erneut versuchen","?page=login");
							LOGGING_PUSH("WARNING","Es wurde versucht, sich im Backend-Bereich mit einem gesperrten Nutzerkonto anzumelden ('".$_POST["username"]."').");
							$BODY->ADD_SOURCE($CURRENT_PAGE->GET_SOURCE());
						}
					}else{
						$CURRENT_PAGE=new PAGE("Anmeldefehler","Die Kombination aus eingegebenem Nutzernamen und Passwort ist falsch.",false);
						$CURRENT_PAGE->ADD_BUTTON_NAVIGATION("Erneut versuchen","?page=login");
						LOGGING_PUSH("WARNING","Fehlerhafter Login durch '".$_POST["username"]."'.");
						$BODY->ADD_SOURCE($CURRENT_PAGE->GET_SOURCE());
					}
				}else{
					$CURRENT_PAGE=new PAGE("Anmeldefehler","Bitte zum Anmelden das Login-Formular verwenden:",false);
					$CURRENT_PAGE->ADD_BUTTON_NAVIGATION("Erneut versuchen","?page=login");
					LOGGING_PUSH("WARNING","Es wurde versucht, sich im Backend-Bereich mit einem direkten Zugang anzumelden.");
					$BODY->ADD_SOURCE($CURRENT_PAGE->GET_SOURCE());
				}
			}
		}
		break;
	//Logout-Seite.
	case "logout":
		{
			if(LOGGED_IN()){
				LOGGING_PUSH("BACKEND/INFO","Logout von '".GET_USER_NAME()." (".GET_USER_ID().")' aus Backend-Bereich.");
				//session_destroy();
				unset($_SESSION["backend"]);
				header("Location: ?page=login");
			}else{
				$CURRENT_PAGE=new PAGE("Abmeldefehler","Die Sitzung kann nicht beendet werden, da Sie nicht angemeldet sind.",false);
				$CURRENT_PAGE->ADD_BUTTON_NAVIGATION("Anmelden","?page=login");
				$BODY->ADD_SOURCE($CURRENT_PAGE->GET_SOURCE());
			}
		}
		break;
	//Reset.
	case "reset":
		if(isset($_GET["target"])) {
			SQL_EXEC("DROP TABLE IF EXISTS `".$_GET["target"]."`;");
			
			//Alle Tabellen mit entsprechenden Standardwerten.
			switch($_GET["target"]) {
				case "calendar":
					SQL_EXEC("CREATE TABLE `calendar` (`id` int(10) unsigned NOT NULL AUTO_INCREMENT,`date` date NOT NULL,`time` time NOT NULL,`event` varchar(512) COLLATE utf8_unicode_ci NOT NULL,`category` int(10) unsigned NOT NULL,`permissionid` int(10) unsigned NOT NULL,`multi` int(10) unsigned NOT NULL,PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");
					break;
				case "calendar_categories":
					SQL_EXEC("CREATE TABLE `calendar_categories` (`id` int(10) unsigned NOT NULL AUTO_INCREMENT,`category` varchar(64) COLLATE utf8_unicode_ci NOT NULL,`color` varchar(6) COLLATE utf8_unicode_ci NOT NULL,PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");
					SQL_EXEC("INSERT INTO `calendar_categories` (`id`, `category`, `color`) VALUES(1, 'Standard', 'c03030');");
					break;
				case "categories":
					SQL_EXEC("CREATE TABLE `categories` (`id` int(10) unsigned NOT NULL AUTO_INCREMENT,`category` varchar(64) COLLATE utf8_unicode_ci NOT NULL,PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");
					SQL_EXEC("INSERT INTO `categories` (`id`, `category`) VALUES(1, 'Standard'),(2, 'Spezialseite'),(3, 'Hauptmen&uuml;-Leerseite');");
					break;
				case "longtexts":
					SQL_EXEC("CREATE TABLE `longtexts` (`id` int(10) unsigned NOT NULL AUTO_INCREMENT,`name` varchar(32) NOT NULL,`text` longtext NOT NULL,PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");
					SQL_EXEC("INSERT INTO `longtexts` (`id`, `name`, `text`) VALUES(1, 'log', ''),(2, 'fachschaften', 'Vorstellung der Fachschaften.'),(3, 'tier1', '[Name]'),(4, 'tier2', '[Name]'),(5, 'tier3', '[Name]');");
					break;
				case "media":
					SQL_EXEC("CREATE TABLE `media` (`id` int(10) unsigned NOT NULL AUTO_INCREMENT,`path` varchar(256) COLLATE utf8_unicode_ci NOT NULL,`uploaddate` datetime NOT NULL,PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");
					break;
				case "menu":
					SQL_EXEC("CREATE TABLE `menu` (`id` int(10) unsigned NOT NULL AUTO_INCREMENT,`pageid` int(10) unsigned NOT NULL,`seq` int(10) unsigned NOT NULL,PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");
					break;
				case "metadata":
					SQL_EXEC("CREATE TABLE `metadata` (`id` int(10) unsigned NOT NULL AUTO_INCREMENT,`name` varchar(32) COLLATE utf8_unicode_ci NOT NULL,`metadata` varchar(512) COLLATE utf8_unicode_ci NOT NULL,PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");
					SQL_EXEC("INSERT INTO `metadata` (`id`, `name`, `metadata`) VALUES(1, 'title', 'GutsMuths Gymnasium Quedlinburg'),(2, 'description', 'Website des GutsMuths Gymnasiums Quedlinburg'),(3, 'revisit', '30'),(4, 'index', 'NOINDEX'),(5, 'onlinestate', 'false'),(6, 'debugging', 'true');");
					break;
				case "pages":
					SQL_EXEC("CREATE TABLE `pages` (`id` int(10) unsigned NOT NULL AUTO_INCREMENT,`title` varchar(64) COLLATE utf8_unicode_ci NOT NULL,`page` varchar(64) COLLATE utf8_unicode_ci NOT NULL,`category` int(10) unsigned NOT NULL,`source` longtext COLLATE utf8_unicode_ci NOT NULL,`active` tinyint(4) unsigned NOT NULL,`views` bigint(20) unsigned NOT NULL,`permissionid` int(10) unsigned NOT NULL,PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");
					SQL_EXEC("INSERT INTO `pages` (`id`, `title`, `page`, `category`, `source`, `active`, `views`, `permissionid`) VALUES(1, 'Seiteneditor', 'edit', 2, '', 1, 0, 1),(2, 'Terminkalender', 'kalender', 2, '', 1, 0, 2),(3, 'Klausurplan', 'klausurplan', 2, '', 1, 0, 3),(4, 'Kollegium', 'kollegium', 2, '', 1, 0, 4),(5, 'Anmelden', 'login', 2, '', 1, 0, 5),(6, 'Login-Process', 'login_submit', 2, '', 1, 0, 6),(7, 'Abmelden', 'logout_submit', 2, '', 1, 0, 7),(8, 'Profilseite', 'profil', 2, '', 1, 0, 8),(9, 'Stundenplan', 'stundenplan', 2, '', 1, 0, 9),(10, 'Seiten&uuml;bersicht', 'uebersicht', 2, '', 1, 0, 10),(11, 'Startseite', 'home', 1, '<h1>Herzlich Willkommen</h1><h3>GutsMuths Gymnasium Quedlinburg</h3><p>Startseite...</p>', 1, 0, 11),(12, 'Datenschutz', 'datenschutz', 1, '<h1>Datenschutz</h1><p>Datenschutzbestimmungen...</p>', 1, 0, 12);");
					break;
				case "pages_special":
					SQL_EXEC("CREATE TABLE `pages_special` (`id` int(10) unsigned NOT NULL AUTO_INCREMENT,`page` varchar(64) COLLATE utf8_unicode_ci NOT NULL,PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");
					SQL_EXEC("INSERT INTO `pages_special` (`id`, `page`) VALUES(1, 'edit'),(2, 'kalender'),(3, 'klausurplan'),(4, 'kollegium'),(5, 'login'),(6, 'login_submit'),(7, 'logout_submit'),(8, 'profil'),(9, 'stundenplan'),(10, 'uebersicht');");
					break;
				case "permissions":
					SQL_EXEC("CREATE TABLE `permissions` (`id` int(10) unsigned NOT NULL AUTO_INCREMENT,`owner` int(10) unsigned NOT NULL,`public` varchar(5) COLLATE utf8_unicode_ci NOT NULL,`auth` varchar(5) COLLATE utf8_unicode_ci NOT NULL,`groupperm` varchar(5) COLLATE utf8_unicode_ci NOT NULL,`groupid` int(10) unsigned NOT NULL,PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");
					SQL_EXEC("INSERT INTO `permissions` (`id`, `owner`, `public`, `auth`, `groupperm`, `groupid`) VALUES(1, 1, 'NONE', 'NONE', 'ALTER', 1),(2, 1, 'VIEW', 'VIEW', 'ALTER', 1),(3, 1, 'NONE', 'VIEW', 'ALTER', 1),(4, 1, 'VIEW', 'VIEW', 'ALTER', 1),(5, 1, 'VIEW', 'NONE', 'ALTER', 1),(6, 1, 'VIEW', 'NONE', 'ALTER', 1),(7, 1, 'NONE', 'VIEW', 'ALTER', 1),(8, 1, 'NONE', 'VIEW', 'ALTER', 1),(9, 1, 'NONE', 'VIEW', 'ALTER', 1),(10, 1, 'NONE', 'NONE', 'ALTER', 1),(11, 1, 'VIEW', 'VIEW', 'ALTER', 1),(12, 1, 'VIEW', 'VIEW', 'ALTER', 1);");
					break;
				case "permission_levels":
					SQL_EXEC("CREATE TABLE `permission_levels` (`id` int(10) unsigned NOT NULL AUTO_INCREMENT,`internal` varchar(8) COLLATE utf8_unicode_ci NOT NULL,`display` varchar(32) COLLATE utf8_unicode_ci NOT NULL,PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");
					SQL_EXEC("INSERT INTO `permission_levels` (`id`, `internal`, `display`) VALUES(1, 'ALTER', 'alles'),(2, 'EDIT', 'bearbeiten'),(3, 'VIEW', 'ansehen'),(4, 'NONE', 'nichts');");
					break;
				case "schedule":
					SQL_EXEC("CREATE TABLE `schedule` (`id` int(10) unsigned NOT NULL AUTO_INCREMENT,`user` int(10) unsigned NOT NULL,`cl` varchar(4) COLLATE utf8_unicode_ci NOT NULL,`co` varchar(64) COLLATE utf8_unicode_ci NOT NULL,PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");
					break;
				case "submenu":
					SQL_EXEC("CREATE TABLE `submenu` (`pageid` int(10) unsigned NOT NULL,`parentid` int(10) unsigned NOT NULL,`seq` int(10) unsigned NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");
					break;
				case "usergroups":
					SQL_EXEC("CREATE TABLE `usergroups` (`id` int(10) unsigned NOT NULL AUTO_INCREMENT,`name` varchar(16) COLLATE utf8_unicode_ci NOT NULL,`expires` date NOT NULL,`description` varchar(128) COLLATE utf8_unicode_ci NOT NULL,PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");
					SQL_EXEC("INSERT INTO `usergroups` (`id`, `name`, `expires`, `description`) VALUES(1, 'admin', '0000-00-00', 'Administratoren'),(2, 'public', '0000-00-00', '&Ouml;ffentliche Nutzer');");
					break;
				case "users":
					SQL_EXEC("CREATE TABLE `users` (`id` int(10) unsigned NOT NULL AUTO_INCREMENT,`username` varchar(16) NOT NULL,`name` varchar(64) NOT NULL,`vorname` varchar(64) NOT NULL,`email` varchar(32) NOT NULL,`password` varchar(128) NOT NULL,`usergroup` int(10) unsigned NOT NULL,`active` tinyint(4) unsigned NOT NULL,`logins` bigint(20) unsigned NOT NULL,`date_register` datetime NOT NULL,`date_lastlogin` datetime NOT NULL,PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");
					SQL_EXEC("INSERT INTO `users` (`id`, `username`, `name`, `vorname`, `email`, `password`, `usergroup`, `active`, `logins`, `date_register`) VALUES(1, 'admin_gmg', 'Root', 'Administrator', '', '34d1e6dffbfd58f76a5b74c9333c8fb5544d596c342de2b0f3e88ac5d114be0d0f1c7e53e3ed4efa86678df4cb9d56dd71330b04358876fa22068229a68e5834', 1, 1, 0, '".date("Y-m-d H:i:s")."');");
					break;
				default:
					break;
			}
			header("Location: ?page=reset");
		//Alle Tabellen werden angezeigt.
		} else {
			$CURRENT_PAGE=new PAGE("Zur&uuml;cksetzen","Tabellen auf Standardwerte zur&uuml;cksetzen.");
			$RESET_TABLE=new MODULE_TABLE("Tabelle|Name|Zur&uuml;cksetzen",true);
			
			$TABLE_NAMES="calendar|calendar_categories|categories|longtexts|media|menu|metadata|pages|pages_special|permissions|permission_levels|schedule|submenu|usergroups|users";
			$TABLE_NAMEPLAIN="Kalenderereignisse|Kalenderkategorien|Seitenkategorien|Log & Fachschaften|Medien|Hauptmen&uuml;punkte|Metadaten|Seiten|Spezialseiten|Berechtigungen|Berechtigungslevel|Stundenplan (Klassen- &amp; Kursauswahl)|Untermen&uuml;punkte|Nutzergruppen|Nutzer";
			
			$TABLE_NAMES=explode("|",$TABLE_NAMES);
			$TABLE_NAMEPLAIN=explode("|",$TABLE_NAMEPLAIN);
			for($i=0;$i<count($TABLE_NAMES);$i++) {
				$RESET_TABLE->ADD_LINE("".$TABLE_NAMES[$i]."|".$TABLE_NAMEPLAIN[$i]."|<a onClick=\"dpBoxConfirm('Tabelle l&ouml;schen?','Soll die Tabelle \'".$TABLE_NAMES[$i]."\' wirklich zur&uuml;ckgesetzt werden? Bitte beachten Sie, dass die betroffenen Daten nicht wiederhergestellt werden k&ouml;nnen und Verweise, die auf diese Tabelle zeigen dadurch ung&uuml;ltig oder fehlerhaft werden.',function(){jsNav('?page=reset&target=".$TABLE_NAMES[$i]."');});\" title=\"Tabelle zur&uuml;cksetzen\"><i class=\"fas fa-times\"></a></a>");
			}

			$CURRENT_PAGE->ADD_CUSTOMSOURCE($RESET_TABLE->GET_SOURCE());
			$BODY->ADD_SOURCE($CURRENT_PAGE->GET_SOURCE());
		}
		break;
	//Fehler.
	default:
		{
			$CURRENT_PAGE=new PAGE("Seite nicht gefunden","Die mit dem Parameter &uuml;bermittelte Seite scheint nicht zu existieren.");
			$CURRENT_PAGE->ADD_BUTTON_NAVIGATION("Startseite","?page=home");
			$BODY->ADD_SOURCE($CURRENT_PAGE->GET_SOURCE());
		}
		break;
}

$BODY->ADD_SOURCE("<div class=\"backend\" id=\"box\"></div>");

//Ausgabe Credits
echo "<!--\nCMS des GutsMuths Gymnasiums Quedlinburg\n@author Noah Wiederhold & Bernhard Birnbaum\n@copyright 2020 Noah Wiederhold & Bernhard Birnbaum\n@version 1.00.00\n-->\n";

//Ausgabe der Daten.
echo "<html lang=\"de\">";
echo "<head>".$HEADER->GET_SOURCE()."</head>";

//SQL-Log ausgeben.
DEBUGGING_LOGGER::FREE();

echo "<body>".$BODY->GET_SOURCE()."</body>";
echo "</html>";
?>