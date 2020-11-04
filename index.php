<?php
/**
* CMS des GutsMuths Gymnasiums Quedlinburg
* @author Noah Wiederhold & Bernhard Birnbaum
* @copyright 2020 Noah Wiederhold & Bernhard Birnbaum
* @version 1.02.00b
* 
* index.php - Frontendbereich des CMS.
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
				header("Location: ?p=login");
				return "";
			}
		}else{
			return $this->SOURCE;
		}
	}
}

//Pageedit-Button.
class MODULE_EDITBTN {
	public function __construct($PAGEID) {
		$this->SOURCE="<button class=\"editbutton\" onClick=\"jsNav('?p=edit&id=$PAGEID');\"><i class=\"fas fa-pen\"></i></button>";
	}
	
	public function GET_SOURCE() {
		return $this->SOURCE;
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
if(isset($_GET["p"])) {
	$PARAM_PAGE=$_GET["p"];
} else {
	$PARAM_PAGE="home";
}

  ///////////////////////////////////
 //           Utilities           // 
///////////////////////////////////  

{
	//Gibt ID des angemeldeten Benutzers zurück.
	function GET_USER_ID() {
		return SQL_READ("users","id,username","username",$_SESSION["login"])["id"];
	}
	
	//Gibt Nutzername des angemeldeten Nutzers zurück.
	function GET_USER_NAME() {
		return SQL_READ("users","id,username","username",$_SESSION["login"])["username"];
	}
	
	//Gibt NutzergruppenId des angemeldeten Nutzers zurück.
	function GET_USER_GROUP_ID() {
		return SQL_READ("users","id,usergroup","id",GET_USER_ID())["usergroup"];
	}
	
	//Prüft auf einen bestehenden Login.
	function LOGGED_IN(){
		if(isset($_SESSION["login"])) {
			return true;
		} else {
			return false;
		}
	}
	
	//Prüft, ob angemeldeter Nutzer ein Administrator ist.
	function IS_USER_ADMIN() {
		$CURRENT_USER_GROUP_STRING = SQL_READ("usergroups","id,name","id",GET_USER_GROUP_ID())["name"];
		if($CURRENT_USER_GROUP_STRING == "admin") {
			return true;
		} else {
			return false;
		}
	}
	
	//Zählt Anzahl der Besuche der Seiten.
	function PAGE_ADD_VIEW($PAGE) {
		$CURRENT_VIEWS=SQL_READ("pages","page,views","page",$PAGE)["views"];
		SQL_CHANGE("pages","page",$PAGE,"views",$CURRENT_VIEWS+1);
	}
	
	//Bestimmt das größte für den Benutzer verfügbare Berechtigungslevel eines Eintrags.
	function PERMSYS_GET_PERM_FOR_USER($PERMID){
		//Angeforderter Berechtigungseintrag wird ausgelesen.
		$SQL_REQUEST=SQL_READ("permissions","*","id",$PERMID);
		$PERM_OWNER=$SQL_REQUEST["owner"];
		$PERM_PUBLIC=$SQL_REQUEST["public"];
		$PERM_AUTH=$SQL_REQUEST["auth"];
		$PERM_GROUP=$SQL_REQUEST["groupperm"];
		$PERM_GROUP_ID=$SQL_REQUEST["groupid"];
		
		//Abfrage des Logins.
		if(LOGGED_IN()){
			//Berechtigung für authentifizierte Nutzer gilt.
			
			//Abfrage, ob der angemeldete Benutzer der Eigentümer des Eintrags ist.
			if(GET_USER_ID() == $PERM_OWNER){
				//Bei Eigentum --> Maximale Berechtigung.
				return "ALTER";
			//Abfrage, ob der angemeldete Benutzer ein Administrator ist.
			}elseif(IS_USER_ADMIN()){
				//Bei Adminstatus --> Maximale Berechtigung.
				return "ALTER";
			}else{
				//Gruppenberechtigung wird ausgelesen.
				$TMP_PERM;
				$USERGROUP=GET_USER_GROUP_ID();
				
				//Abgleich auf Gültigkeit für den aktuellen Benutzer.
				if($USERGROUP==$PERM_GROUP_ID){
					$TMP_PERM=$PERM_GROUP;
				}else{
					//Wenn nicht --> niedrigste Berechtigungsstufe wird weiterverwendet.
					$TMP_PERM="NONE";
				}
				
				//IDs der Berechtigungen werden ausgelesen.
				$PERM_PUBLIC_VALUE = SQL_READ("permission_levels","id,internal","internal",$PERM_PUBLIC)["id"];
				$PERM_AUTH_VALUE = SQL_READ("permission_levels","id,internal","internal",$PERM_AUTH)["id"];
				$PERM_GROUP_VALUE = SQL_READ("permission_levels","id,internal","internal",$TMP_PERM)["id"];
				
				//Das höchste der Berechtigungslevels wird zurückgegeben.
				return SQL_READ("permission_levels","id,internal","id",min($PERM_PUBLIC_VALUE,$PERM_AUTH_VALUE,$PERM_GROUP_VALUE))["internal"];
			}
		}else{
			//Kein Login --> Nutzer ist öffentlich.
			return $PERM_PUBLIC;
		}
	}
}

  ///////////////////////////////////////////
 //           Header & Defaults           // 
///////////////////////////////////////////  

{
	//Metadaten werden ausgelesen.
	$META_TITLE = SQL_READ("metadata","name,metadata","name","title")["metadata"];
	$META_DESCRIPTION = SQL_READ("metadata","name,metadata","name","description")["metadata"];
	$META_REVISIT = SQL_READ("metadata","name,metadata","name","revisit")["metadata"];
	$META_INDEX = SQL_READ("metadata","name,metadata","name","index")["metadata"];
	$META_ONLINESTATE = SQL_READ("metadata","name,metadata","name","onlinestate")["metadata"];
	
	//Standard CodeObject für Header.
	$HEADER=new CODE_OBJECT();
	
	//Titel.
	$HEADER->ADD_SOURCE("<title>$META_TITLE</title>");
	
	//Metadaten.
	$HEADER->ADD_SOURCE("<meta name=\"description\" content=\"$META_DESCRIPTION\"/>");
	$HEADER->ADD_SOURCE("<meta name=\"revisit-after\" content=\"$META_REVISIT\"/>");
	$HEADER->ADD_SOURCE("<meta name=\"index\" content=\"$META_INDEX, follow\"/>");
	$HEADER->ADD_SOURCE("<meta name=\"viewport\" content=\"width=device-width\"/>");
	$HEADER->ADD_SOURCE("<meta charset=\"utf-8\"/>");
	$HEADER->ADD_SOURCE("<meta name=\"google-site-verification\" content=\"emRZdhidnQuUvAJvwL4rIIWbu5Zk6cBOekgkwsBjDe0\"/>");
	
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
	$HEADER->ADD_SOURCE("<link rel=\"stylesheet\" type=\"text/css\" href=\"stylesheets/frontend.css\"/>");
	$HEADER->ADD_SOURCE("<link rel=\"stylesheet\" type=\"text/css\" href=\"stylesheets/mainmenu.css\"/>");
	$HEADER->ADD_SOURCE("<link rel=\"stylesheet\" type=\"text/css\" href=\"stylesheets/mainforms.css\"/>");
	$HEADER->ADD_SOURCE("<link rel=\"stylesheet\" type=\"text/css\" href=\"stylesheets/maintables.css\"/>");
	$HEADER->ADD_SOURCE("<link rel=\"stylesheet\" type=\"text/css\" href=\"stylesheets/mainformat.css\"/>");
	$HEADER->ADD_SOURCE("<link rel=\"stylesheet\" type=\"text/css\" href=\"stylesheets/box.css\"/>");
	$HEADER->ADD_SOURCE("<link rel=\"stylesheet\" type=\"text/css\" href=\"stylesheets/calendar.css\"/>");
	$HEADER->ADD_SOURCE("<link rel=\"stylesheet\" type=\"text/css\" href=\"stylesheets/editor.css\"/>");
	$HEADER->ADD_SOURCE("<link rel=\"stylesheet\" type=\"text/css\" href=\"stylesheets/responsive.css\"/>");
	
	//Standard CodeObject für Zugriffsverweigerung.
	$ACCESS_DENIED=new CODE_OBJECT();
	$ACCESS_DENIED->ADD_SOURCE("<h1>Fehler</h1>");
	if(isset($_GET["p"])) {
		$ACCESS_DENIED->ADD_SOURCE("<p class=\"centered\">Bitte melden Sie sich an, wenn Sie berechtigt sind, diese Seite aufzurufen.<br><a href=\"?p=login&redirect=".urlencode($_SERVER["REQUEST_URI"])."\">Hier anmelden</a> oder <a href=\"?p=home\">zur Startseite</a>.</p>");
	} else {
		$ACCESS_DENIED->ADD_SOURCE("<p class=\"centered\">Bitte melden Sie sich an, wenn Sie berechtigt sind, diese Seite aufzurufen.<br><a href=\"?p=login\">Hier anmelden</a> oder <a href=\"?p=home\">zur Startseite</a>.</p>");
	}
	
	//Standard CodeObject das Menü.
	$MENU=new CODE_OBJECT();
	$MENU->ADD_SOURCE("<button class=\"menubutton\" onClick=\"toggleMenu();\"><i class=\"fa fa-angle-double-down\"></i></button>");
	$MENU->ADD_SOURCE("<nav>");
	$MENU->ADD_SOURCE("<ul>");
	$MENU->ADD_SOURCE("<li id=\"menuicon\" onClick=\"jsNav('/');\"><img src=\"media/icon.png\" alt=\"icon\"/></li>");
	
	//Durchlauf durch alle Einträge des Hauptmenüs.
	$MENUENTRIES_TOTAL=SQL_COUNT_ROWS("menu");
	for ($MENUENTRIES=1;$MENUENTRIES<=$MENUENTRIES_TOTAL;$MENUENTRIES++) {
		//Seiteninformationen werden abgerufen.
		$PAGE_ID=SQL_READ("menu","pageid,seq","seq",$MENUENTRIES)["pageid"];
		$PAGE_DATA=SQL_READ("pages","id,title,page,category,permissionid","id",$PAGE_ID);
		$PAGE_TITLE=$PAGE_DATA["title"];
		$PAGE_PAGE=$PAGE_DATA["page"];
		
		$PAGE_WITHOUT_NAVIGATION=($PAGE_DATA["category"]==3)?true:false; 
		
		//Sonderfall: Login-Button.
		$DISPLAYLOGIN=true;
		if($PAGE_PAGE == "login") {
			if(LOGGED_IN()) {
				$DISPLAYLOGIN=false;
			}
		}
		
		//Wenn die Seite NICHT die Login-Seite ist.
		if($DISPLAYLOGIN) {
			//Berechtigung der Seite wird abgerufen.
			$PERM_FOR_USER=PERMSYS_GET_PERM_FOR_USER($PAGE_DATA["permissionid"]);
			
			//Wenn der Nutzer die Seite ansehen darf:
			if($PERM_FOR_USER != "NONE") {
				//Seite wird ins Menü eingebunden.
				if($PAGE_WITHOUT_NAVIGATION) {
					$MENU->ADD_SOURCE("<li><a href=\"#\">$PAGE_TITLE</a>");
				} else {
					$MENU->ADD_SOURCE("<li><a href=\"?p=".$PAGE_PAGE."\">$PAGE_TITLE</a>");
				}
				
				//Untereinträge werden ausgelesen, insofern vorhanden.
				$SUBMENUENTRIES_TOTAL=SQL_COUNT_ROWS_PARAM("submenu","parentid",$PAGE_ID);
				
				//Wenn Untereinträge vorhanden:
				if($SUBMENUENTRIES_TOTAL!=0){
					$MENU->ADD_SOURCE("<ul>");
					
					//Untereinträge werden in der richtigen Reihenfolge ausgelesen.
					$SQL_QUERY=SQL_READLOOP_PARAM("submenu","parentid","$PAGE_ID","seq");
					while($ROW=mysqli_fetch_array($SQL_QUERY)){
						$SUBPAGE_DATA=SQL_READ("pages","id,title,page,permissionid","id",$ROW["pageid"]);
						$SUBPAGE_TITLE=$SUBPAGE_DATA["title"];
						$SUBPAGE_PAGE=$SUBPAGE_DATA["page"];
						$PERM_FOR_USER=PERMSYS_GET_PERM_FOR_USER($SUBPAGE_DATA["permissionid"]);
						
						//Wenn Nutzer die Seite ansehen darf:
						if($PERM_FOR_USER != "NONE") {
							//Seite wird ins Menü eingebunden.
							$MENU->ADD_SOURCE("<li><a href=\"?p=".$SUBPAGE_PAGE."\">$SUBPAGE_TITLE</a></li>");
						}
					}
					$MENU->ADD_SOURCE("</ul>");
				}
				$MENU->ADD_SOURCE("</li>");
			}
		}
	}
	$MENU->ADD_SOURCE("</ul>");
	$MENU->ADD_SOURCE("</nav>");
}

  ////////////////////////////////
 //           Seiten           // 
////////////////////////////////  

$CUSTOM_PAGE=new CODE_OBJECT();
$CUSTOM_PAGE->ADD_SOURCE("<div id=\"page\">");

//Seitenberechtigung.
$SPECIAL_PAGE_PERMS=PERMSYS_GET_PERM_FOR_USER(SQL_READ("pages","page,permissionid","page",strtolower($PARAM_PAGE))["permissionid"]);

//Switch für Spezialseiten.
switch(strtolower($PARAM_PAGE)) {
	//Seiteneditor.
	case "edit":
		if($SPECIAL_PAGE_PERMS != "NONE") {
			if(isset($_GET["id"])){
				//Speichert eine Seite.
				if(isset($_GET["func_save"])){
					$PERMISSION_USER=PERMSYS_GET_PERM_FOR_USER($_POST["permid"]);
					if($PERMISSION_USER == "EDIT" or $PERMISSION_USER == "ALTER") {
						SQL_CHANGE_MULTIPLE("pages","id",$_GET["id"],"title='".$_POST["page_title"]."',page='".$_POST["page_page"]."',category='".$_POST["page_category"]."',source='".$_POST["page_source"]."',active='".$_POST["page_active"]."'");
						PERMSYS_UPDATE($_POST["permid"],$_POST["page_perm_public"],$_POST["page_perm_auth"],$_POST["page_perm_group_level"],$_POST["page_perm_group_id"]);
						LOGGING_PUSH("INFO","Seite '".$_POST["page_page"]."' wurde von '".GET_USER_NAME()." (".GET_USER_ID().")' bearbeitet.");
					}
					header("Location: ?p=edit&id=".$_GET["id"]."");
				//Löscht eine Seite.
				}else if(isset($_GET["func_delete"])){
					$PID=SQL_READ("pages","id,permissionid","id",$_GET["id"])["permissionid"];
					$PERMISSION_USER=PERMSYS_GET_PERM_FOR_USER($PID);
					if($PERMISSION_USER == "ALTER") {
						SQL_DELETE("pages","id",$_GET["id"]);
						PERMSYS_DELETE($PID);
						LOGGING_PUSH("BACKEND/INFO","Seite (".$_GET["id"].") von '".GET_USER_NAME()." (".GET_USER_ID().")' gelöscht.");
					}
					header("Location: ?p=uebersicht");
				//Seiteneditor.
				}else{
					$PAGE_ID=$_GET["id"];
					$PAGE=SQL_READ("pages","*","id",$_GET["id"]);
					$PERMISSION_USER=PERMSYS_GET_PERM_FOR_USER($PAGE["permissionid"]);
					
					if($PERMISSION_USER == "EDIT" or $PERMISSION_USER == "ALTER") {
						PAGE_ADD_VIEW("edit");
						$CUSTOM_PAGE->ADD_SOURCE("<h1>Seite bearbeiten</h1>");
						$EDITOR_BUTTONS=new MODULE_BUTTONLIST();
						$EDITOR_BUTTONS->ADD_BUTTON_NAVIGATION("Zur Seite","?p=".$PAGE["page"]);
						$EDITOR_BUTTONS->ADD_BUTTON_NAVIGATION("Zur Seiten&uuml;bersicht","?p=uebersicht");
						$CUSTOM_PAGE->ADD_SOURCE($EDITOR_BUTTONS->GET_SOURCE());
						$PAGE_IS_SPECIAL=false;
						if(mysqli_fetch_array(SQL_EXEC("SELECT EXISTS(SELECT * FROM pages_special WHERE page = '".$PAGE["page"]."')"))[0] == 1) {
							$PAGE_IS_SPECIAL=true;
						}
						if($PAGE["category"] == 3) {
							$PAGE_IS_SPECIAL=true;
						}
						$CUSTOM_PAGE->ADD_SOURCE("<div class=\"editor\">");
						if(!$PAGE_IS_SPECIAL){
							//WYSIWYG-Editor-Buttons.
							$CUSTOM_PAGE->ADD_SOURCE("<div class=\"menubar\">");
							$CUSTOM_PAGE->ADD_SOURCE("<select class=\"button type_format\" title=\"Formate\"><option disabled selected value=\"0\">Formatieren</option><option value=\"1\">Überschrift 1</option><option value=\"2\">&Uuml;berschrift 2</option><option value=\"3\">&Uuml;berschrift 3</option><option value=\"4\" class=\"text\">Textk&ouml;rper</option></select>");
							$CUSTOM_PAGE->ADD_SOURCE("<button class=\"button type_easy\" data-attribute=\"undo\" title=\"R&uuml;ckg&auml;ngig\"><i class=\"fa fa-undo\"></i></button>");
							$CUSTOM_PAGE->ADD_SOURCE("<button class=\"button type_easy\" data-attribute=\"redo\" title=\"Hing&auml;ngig\"><i class=\"fa fa-redo\"></i></button>");
							$CUSTOM_PAGE->ADD_SOURCE("<button class=\"button type_easy\" data-attribute=\"copy\" title=\"Kopieren\"><i class=\"fa fa-copy\"></i></button>");
							$CUSTOM_PAGE->ADD_SOURCE("<button class=\"button type_easy\" data-attribute=\"cut\" title=\"Ausschneiden\"><i class=\"fa fa-cut\"></i></button>");
							$CUSTOM_PAGE->ADD_SOURCE("<button class=\"button type_easy\" data-attribute=\"bold\" title=\"Fett\"><i class=\"fa fa-bold\"></i></button>");
							$CUSTOM_PAGE->ADD_SOURCE("<button class=\"button type_easy\" data-attribute=\"italic\" title=\"Kursiv\"><i class=\"fa fa-italic\"></i></button>");
							$CUSTOM_PAGE->ADD_SOURCE("<button class=\"button type_easy\" data-attribute=\"underline\" title=\"Unterstrichen\"><i class=\"fa fa-underline\"></i></button>");
							$CUSTOM_PAGE->ADD_SOURCE("</div><div class=\"menubar\">");
							$CUSTOM_PAGE->ADD_SOURCE("<button class=\"button type_easy\" data-attribute=\"justifyfull\" title=\"Textblock\"><i class=\"fa fa-align-justify\"></i></button>");
							$CUSTOM_PAGE->ADD_SOURCE("<button class=\"button type_easy\" data-attribute=\"justifyleft\" title=\"Linksb&uuml;ndig\"><i class=\"fa fa-align-left\"></i></button>");
							$CUSTOM_PAGE->ADD_SOURCE("<button class=\"button type_easy\" data-attribute=\"justifycenter\" title=\"Zentriert\"><i class=\"fa fa-align-center\"></i></button>");
							$CUSTOM_PAGE->ADD_SOURCE("<button class=\"button type_easy\" data-attribute=\"justifyright\" title=\"Rechtsb&uuml;ndig\"><i class=\"fa fa-align-right\"></i></button>");
							$CUSTOM_PAGE->ADD_SOURCE("<button class=\"button type_color\" title=\"Farbe anwenden\"><i class=\"fas fa-fill-drip\"></i></button>");
							$CUSTOM_PAGE->ADD_SOURCE("<input class=\"button type_colorpicker\" type=\"color\" title=\"Farbe w&auml;hlen\">");
							$CUSTOM_PAGE->ADD_SOURCE("<button class=\"button type_link\" id=\"type_link_f\" title=\"Link erstellen\"><i class=\"fa fa-link\"></i></button>");
							$CUSTOM_PAGE->ADD_SOURCE("<button class=\"button type_easy\" data-attribute=\"unlink\" title=\"Link entfernen\"><i class=\"fa fa-unlink\"></i></button>");
							$CUSTOM_PAGE->ADD_SOURCE("<button class=\"button type_table\" title=\"Tabelle einf&uuml;gen\"><i class=\"fa fa-table\"></i></button>");
							$CUSTOM_PAGE->ADD_SOURCE("<button class=\"button type_ulist\" title=\"Aufz&auml;hlung\"><i class=\"fa fa-list\"></i></button>");
							$CUSTOM_PAGE->ADD_SOURCE("<button class=\"button type_media\" id=\"type_media_f\" title=\"Medien einf&uuml;gen\"><i class=\"fa fa-image\"></i></button>");
							$CUSTOM_PAGE->ADD_SOURCE("<button class=\"button type_upload\" id=\"type_upload_f\" title=\"Datei hochladen\"><i class=\"fa fa-upload\"></i></button>");
							$CUSTOM_PAGE->ADD_SOURCE("</div>");

							//WYSIWYG-Editor-Bearbeitungsfeld.
							$CUSTOM_PAGE->ADD_SOURCE("<div class=\"edit_area\">");
							$CUSTOM_PAGE->ADD_SOURCE("<div class=\"edit\" id=\"canvas\" data-text=\"Seite bearbeiten...\" contenteditable>".$PAGE["source"]."</div>");
							$CUSTOM_PAGE->ADD_SOURCE("</div>");
						}
						
						//Formular zum Speichern der Seite.
						$EDITOR_FORM=new MODULE_FORM("?p=edit&id=".$_GET["id"]."&func_save","post");
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
						$EDITOR_FORM->ADD_INPUT_SELECT_MYSQL("Spezielle Gruppe","page_perm_group_id","usergroups","id","name",$PAGE_PERMS["groupid"],($PERMISSION_USER == "ALTER") ? true : false);
						$EDITOR_FORM->ADD_INPUT_SELECT_MYSQL("Spezielle Gruppenberechtigung","page_perm_group_level","permission_levels","internal","display",$PAGE_PERMS["groupperm"],($PERMISSION_USER == "ALTER") ? true : false);
						$EDITOR_FORM->ADD_INPUT_SELECT_MYSQL("Authentifizierte Nutzerberechtigung","page_perm_auth","permission_levels","internal","display",$PAGE_PERMS["auth"],($PERMISSION_USER == "ALTER") ? true : false);
						$EDITOR_FORM->ADD_INPUT_SELECT_MYSQL("&Ouml;ffentliche Nutzerberechtigung","page_perm_public","permission_levels","internal","display",$PAGE_PERMS["public"],($PERMISSION_USER == "ALTER") ? true : false);
						$EDITOR_FORM->ADD_INPUT_TEXT("Aufrufe","",true,$PAGE["views"]);
						$EDITOR_FORM->ADD_SUBMIT("Speichern","Seite speichern");
						if(!$PAGE_IS_SPECIAL){
							if($PERMISSION_USER == "ALTER") {
								$EDITOR_FORM->ADD_BUTTON_CONFIRMATION("L&ouml;schen","Seite l&ouml;schen","Soll die Seite \'".$PAGE["page"]."\' wirklich entfernt werden?","?p=edit&id=".$PAGE["id"]."&func_delete");
							}
						}
						$CUSTOM_PAGE->ADD_SOURCE($EDITOR_FORM->GET_SOURCE());
						$CUSTOM_PAGE->ADD_SOURCE("</div>");
						
						//Scripteinbindung für den Editor.
						$CUSTOM_PAGE->ADD_SOURCE("<script language=\"javascript\" type=\"text/javascript\" src=\"javascript/editor.js\"></script>");
						$CUSTOM_PAGE->ADD_SOURCE("<script>displaySource();</script>");
					}else{
						$CUSTOM_PAGE->ADD_SOURCE($ACCESS_DENIED->GET_SOURCE());
					}
				}
			//Liefert JSON-Code mit Mediendaten.
			}else if(isset($_GET["func_getmedia"])){
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
			//Liefert JSON-Code mit Seitendaten.
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
			//Verarbeitet hochgeladene Dateien.
			}else if(isset($_GET["func_upload"])){
				header("Content-Type: application/json");
				DEBUGGING_LOGGER::SUPPRESS_LOGGING();
				$RTN_MSG=array("status" => 4, "message" => "Unbekannter Fehler bei der Daten&uuml;bertragung.");
				if(isset($_FILES["attachements"])) {
					if(isset($_GET["sub"])) {
						$baseDirectory="./media/".$_GET["sub"];
						mkdir("./media");
						mkdir($baseDirectory);
					} else {
						$baseDirectory="./media/".date("Y-m-d");
						mkdir("./media");
						mkdir($baseDirectory);
					}
					if(isset($_GET["name"])) {
						$targetFile=$baseDirectory . "/" . $_GET["name"];
						if(file_exists($targetFile)) {
							unlink($targetFile);
						}
					} else {
						$tempName = pathinfo(SIMPLIFY_STRING(basename($_FILES["attachements"]["name"][0])));
						$targetFile=$baseDirectory . "/" . $tempName["filename"] . "." . hash("crc32b", date("Y-m-d-H-i-s")) . "." . $tempName["extension"];
					}
					if(!file_exists($targetFile)) {
						if(move_uploaded_file($_FILES["attachements"]["tmp_name"][0], $targetFile)) {
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
				$CUSTOM_PAGE->ADD_SOURCE("<h1>Fehler</h1><p class=\"centered\">Der Editor ben&ouml;tigt den Parameter 'ID', um eine Seite darzustellen.</p>");
				$EDITOR_BUTTONS=new MODULE_BUTTONLIST();
				$EDITOR_BUTTONS->ADD_BUTTON_NAVIGATION("Zur Startseite","?p=home");
				$CUSTOM_PAGE->ADD_SOURCE($EDITOR_BUTTONS->GET_SOURCE());
			}
		} else {
			$CUSTOM_PAGE->ADD_SOURCE($ACCESS_DENIED->GET_SOURCE());
		}
		break;
	//Terminkalender.
	case "kalender":
		if($SPECIAL_PAGE_PERMS != "NONE") {
			date_default_timezone_set("Europe/Berlin");
			$MONTH_NAMES=array("Januar","Februar","M&auml;rz","April","Mai","Juni","Juli","August","September","Oktober","November","Dezember");
			
			//Initialisierung für Arrays (Berechtigungslevel, Kategorien und Nutzergruppen).
			$TMP_ARRAY=array();
			$TMP_ARRAY_VALS=array();
			$SQL_RESULT=SQL_READLOOP("permission_levels","internal,display");
			for($i = 0; $ROW=mysqli_fetch_array($SQL_RESULT); $i++) {
				$TMP_ARRAY[$i]=$ROW["display"];
				$TMP_ARRAY_VALS[$i]=$ROW["internal"];
			}
			$PERMISSIONS_STR=implode(";",$TMP_ARRAY);
			$PERMISSIONS_VALS=implode(";",$TMP_ARRAY_VALS);
			$TMP_ARRAY=array();
			$TMP_ARRAY_VALS=array();
			$SQL_RESULT=SQL_READLOOP("calendar_categories","id,category");
			for($i = 0; $ROW=mysqli_fetch_array($SQL_RESULT); $i++) {
				$TMP_ARRAY[$i]=$ROW["category"];
				$TMP_ARRAY_VALS[$i]=$ROW["id"];
			}
			$CATEGORIES_STR=implode(";",$TMP_ARRAY);
			$CATEGORIES_VALS=implode(";",$TMP_ARRAY_VALS);
			$TMP_ARRAY=array();
			$TMP_ARRAY_VALS=array();
			$SQL_RESULT=SQL_READLOOP("usergroups","id,description");
			for($i = 0; $ROW=mysqli_fetch_array($SQL_RESULT); $i++) {
				$TMP_ARRAY[$i]=$ROW["description"];
				$TMP_ARRAY_VALS[$i]=$ROW["id"];
			}
			$USERGROUPS_STR=implode(";",$TMP_ARRAY);
			$USERGROUPS_VALS=implode(";",$TMP_ARRAY_VALS);
			
			//wichtige Variablen für den Kalender.
			$YEAR;
			if(isset($_GET["y"])){
				$YEAR=$_GET["y"];
			} else {
				$YEAR=date("Y",time());
			}
			$MONTH;
			if(isset($_GET["m"])){
				$MONTH=$_GET["m"];
			} else {
				$MONTH=date("m",time());
			}
			$MONTH_NAME=$MONTH_NAMES[$MONTH-1];
			$MONTH_DAYS=cal_days_in_month(CAL_GREGORIAN,$MONTH,$YEAR);

			$YEAR_NOW=date("Y",time());
			$MONTH_NOW=date("m",time());
			$DAY_NOW=date("j",time());
			
			//Kategorienverwaltung des Kalenders.
			if(isset($_GET["kategorien"])) {
				if($SPECIAL_PAGE_PERMS == "EDIT" or $SPECIAL_PAGE_PERMS == "ALTER") {
					$CUSTOM_PAGE->ADD_SOURCE("<h1>Kategorien</h1>");
					$BTNLST_CATEGORIES=new MODULE_BUTTONLIST();
					$BTNLST_CATEGORIES->ADD_BUTTON_NAVIGATION("Zur&uuml;ck","?p=kalender&y=$YEAR&m=$MONTH");
					$BTNLST_CATEGORIES->ADD_BUTTON_CALENDAR_CATEGORY("Neue Kategorie","?p=kalender&y=$YEAR&m=$MONTH");
					$CUSTOM_PAGE->ADD_SOURCE($BTNLST_CATEGORIES->GET_SOURCE());
					$CATEGORIES_TABLE=new MODULE_TABLE("Kategorie|Farbe|Entfernen",true);
					$SQL_QUERY=SQL_READLOOP("calendar_categories","id,category,color");
					while($ROW=mysqli_fetch_array($SQL_QUERY)){
						$LAST_CELL = "";
						if($SPECIAL_PAGE_PERMS == "ALTER") {
							$LAST_CELL = "<a onClick=\"dpBoxConfirm('Kategorie l&ouml;schen?','Soll die Kategorie \'".$ROW["category"]."\' wirklich entfernt werden?',function(){jsNav('?p=kalender&y=$YEAR&m=$MONTH&func_deletecategory=".$ROW["id"]."');});\"><i class=\"fa fa-times\"></i></a>";
						}
						$CATEGORIES_TABLE->ADD_LINE("<a onClick=\"dpBoxCalendarCategory('Kategorie bearbeiten','".$ROW["category"]."','#".$ROW["color"]."',function(){jsNav('?p=kalender&y=$YEAR&m=$MONTH&func_changecategory=".$ROW["id"]."&name=' + $('#box-input-name').val() + '&color=' + $('#box-input-color').val().substring(1,7));});\">".$ROW["category"]."</a>|<input style=\"border: 1px solid #000000; padding: 0; width: 10rem;\" disabled type=\"color\" value=\"#".$ROW["color"]."\"/>|$LAST_CELL");
					}
					$CUSTOM_PAGE->ADD_SOURCE($CATEGORIES_TABLE->GET_SOURCE());
				}else{
					$CUSTOM_PAGE->ADD_SOURCE($ACCESS_DENIED->GET_SOURCE());
				}
			//Fügt eine Kategorie hinzu.
			}elseif(isset($_GET["func_addcategory"])){
				if(isset($_GET["color"])){
					if($SPECIAL_PAGE_PERMS == "ALTER" or $SPECIAL_PAGE_PERMS == "EDIT") {
						SQL_ADD("calendar_categories","category,color","'".$_GET["func_addcategory"]."','".$_GET["color"]."'");
						LOGGING_PUSH("INFO","Kalenderkategorie hinzugefügt von '".GET_USER_NAME()." (".GET_USER_ID().")'.");
					}
				}
				header("Location: ?p=kalender&y=$YEAR&m=$MONTH&kategorien");
			//Bearbeitet eine Kalenderkategorie.
			}elseif(isset($_GET["func_changecategory"])){
				if($SPECIAL_PAGE_PERMS == "ALTER" or $SPECIAL_PAGE_PERMS == "EDIT") {
					SQL_CHANGE_MULTIPLE("calendar_categories","id",$_GET["func_changecategory"],"category='".$_GET["name"]."',color='".$_GET["color"]."'");
					LOGGING_PUSH("INFO","Kalenderkategorie bearbeitet von '".GET_USER_NAME()." (".GET_USER_ID().")'.");
				}
				header("Location: ?p=kalender&y=$YEAR&m=$MONTH&kategorien");
			//Löscht eine Kalenderkategorie.
			}elseif(isset($_GET["func_deletecategory"])){
				if($SPECIAL_PAGE_PERMS == "ALTER") {
					SQL_DELETE("calendar_categories","id",$_GET["func_deletecategory"]);
					LOGGING_PUSH("INFO","Kalenderkategorie gelöscht von '".GET_USER_NAME()." (".GET_USER_ID().")'.");
				}
				header("Location: ?p=kalender&y=$YEAR&m=$MONTH&kategorien");
			//Fügt ein Ereignis hinzu.
			}elseif(isset($_GET["func_add"])){
				if($SPECIAL_PAGE_PERMS == "ALTER" or $SPECIAL_PAGE_PERMS == "EDIT") {
					$NEW_DATE=$_GET["func_add"];
					$NEW_EVENT=$_GET["event"];
					$NEW_TIME=$_GET["time"];
					$NEW_CATEGORY=$_GET["category"];

					$PERMID=PERMSYS_MAKE_ENTRY($_GET["perm_public"],$_GET["perm_auth"],$_GET["perm_group"],$_GET["group"]);
					SQL_ADD("calendar","date,time,event,category,permissionid","'$NEW_DATE','$NEW_TIME','$NEW_EVENT','$NEW_CATEGORY',$PERMID");
					LOGGING_PUSH("INFO","Kalendereintrag hinzugefügt von '".GET_USER_NAME()." (".GET_USER_ID().")'.");
				}
				header("Location: ?p=kalender&y=$YEAR&m=$MONTH");
			//Fügt ein mehrtägiges Ereignis hinzu.
			}elseif(isset($_GET["func_addmultiple"])){
				if($SPECIAL_PAGE_PERMS == "ALTER" or $SPECIAL_PAGE_PERMS == "EDIT") {
					$NEW_DATE_BEGIN=date("Y-m-d", strtotime($_GET["func_addmultiple"]));
					$NEW_DATE_END=date("Y-m-d", strtotime($_GET["enddate"]));
					$NEW_EVENT=$_GET["event"];
					$NEW_CATEGORY=$_GET["category"];

					$PERMID=PERMSYS_MAKE_ENTRY($_GET["perm_public"],$_GET["perm_auth"],$_GET["perm_group"],$_GET["group"]);
					$NEXT_MULTI_ID=SQL_MAXVALUE("calendar","multi")["multi"]+1;
					for($CURRENT_DATE=$NEW_DATE_BEGIN;$CURRENT_DATE<=$NEW_DATE_END;$CURRENT_DATE=date("Y-m-d", strtotime($CURRENT_DATE . "+1 days"))) {
						SQL_ADD("calendar","date,time,event,category,permissionid,multi","'$CURRENT_DATE','$NEW_TIME','$NEW_EVENT','$NEW_CATEGORY',$PERMID,$NEXT_MULTI_ID");
					}
					LOGGING_PUSH("INFO","Kalendereintrag (mehrtägig) hinzugefügt von '".GET_USER_NAME()." (".GET_USER_ID().")'.");
				}
				header("Location: ?p=kalender&y=$YEAR&m=$MONTH");
			//Ändert ein Ereignis.
			}elseif(isset($_GET["func_change"])){
				$PID = SQL_READ("calendar","id,permissionid","id",$_GET["func_change"])["permissionid"];
				$CALENDAR_PERM=PERMSYS_GET_PERM_FOR_USER($PID);
				if($CALENDAR_PERM == "ALTER" or $CALENDAR_PERM == "EDIT") {
					SQL_CHANGE_MULTIPLE("calendar","id",$_GET["func_change"],"event='".$_GET["name"]."',category='".$_GET["category"]."'");
					PERMSYS_UPDATE($PID,$_GET["perm_public"],$_GET["perm_auth"],$_GET["perm_group"],$_GET["perm_group_id"]);
				}
				header("Location: ?p=kalender&y=$YEAR&m=$MONTH");
			//Ändert ein mehrtägiges Ereignis.
			}elseif(isset($_GET["func_changemultiple"])){
				$PID = SQL_READ("calendar","multi,permissionid","multi",$_GET["func_changemultiple"])["permissionid"];
				$CALENDAR_PERM=PERMSYS_GET_PERM_FOR_USER($PID);
				if($CALENDAR_PERM == "ALTER" or $CALENDAR_PERM == "EDIT") {
					if(isset($_GET["func_changemultiple"])){
						$SQL_QUERY=SQL_READLOOP_PARAM("calendar","multi",$_GET["func_changemultiple"]);
						while($ROW=mysqli_fetch_array($SQL_QUERY)){
							SQL_CHANGE_MULTIPLE("calendar","id",$ROW["id"],"event='".$_GET["name"]."',category='".$_GET["category"]."'");
							PERMSYS_UPDATE($PID,$_GET["perm_public"],$_GET["perm_auth"],$_GET["perm_group"],$_GET["perm_group_id"]);
						}
						LOGGING_PUSH("INFO","Kalendereintrag (mehrtägig) geändert von '".GET_USER_NAME()." (".GET_USER_ID().")'.");
					}
				}
				header("Location: ?p=kalender&y=$YEAR&m=$MONTH");
			//Löscht ein Ereignis.
			}elseif(isset($_GET["func_delete"])){
				$PID=SQL_READ("calendar","id,permissionid","id",$_GET["func_delete"])["permissionid"];
				$CALENDAR_PERM=PERMSYS_GET_PERM_FOR_USER($PID);
				if($CALENDAR_PERM == "ALTER") {
					SQL_DELETE("calendar","id",$_GET["func_delete"]);
					PERMSYS_DELETE($PID);
					LOGGING_PUSH("INFO","Kalendereintrag gelöscht von '".GET_USER_NAME()." (".GET_USER_ID().")'.");
				}
				header("Location: ?p=kalender&y=$YEAR&m=$MONTH");
			//Löscht ein mehrtägiges Ereignis.
			}elseif(isset($_GET["func_deletemultiple"])){
				$CALENDAR_PERM=PERMSYS_GET_PERM_FOR_USER(SQL_READ("calendar","multi,permissionid","multi",$_GET["func_deletemultiple"])["permissionid"]);
				if($CALENDAR_PERM == "ALTER") {
					$SQL_QUERY=SQL_READLOOP_PARAM("calendar","multi",$_GET["func_deletemultiple"]);
					while($ROW=mysqli_fetch_array($SQL_QUERY)){
						SQL_DELETE("calendar","id",$ROW["id"]);
					}
					LOGGING_PUSH("INFO","Kalendereintrag (mehrtägig) gelöscht von '".GET_USER_NAME()." (".GET_USER_ID().")'.");
				}
				header("Location: ?p=kalender&y=$YEAR&m=$MONTH");
			//Kalender.
			}else{
				$CUSTOM_PAGE->ADD_SOURCE("<h1>$MONTH_NAME $YEAR</h1>");
				PAGE_ADD_VIEW("kalender");
				$MONTH_PREVIOUS=$MONTH-1;
				$YEAR_PREVIOUS=$YEAR;
				$MONTH_NEXT=$MONTH+1;
				$YEAR_NEXT=$YEAR;
				if($MONTH == 1) {
					$MONTH_PREVIOUS=12;
					$YEAR_PREVIOUS=$YEAR-1;
				}
				if($MONTH == 12) {
					$MONTH_NEXT=1;
					$YEAR_NEXT=$YEAR+1;
				}
				
				//Buttons.
				$CALENDAR_BUTTONS=new MODULE_BUTTONLIST();
				$CALENDAR_BUTTONS->ADD_CUSTOMSOURCE("<select class=\"calendar_select\" id=\"calendar_select_month\">");
				for($i = 0; $i < count($MONTH_NAMES); $i++) {
					if(($i+1)==$MONTH) {
						$CALENDAR_BUTTONS->ADD_CUSTOMSOURCE("<option selected value=\"".($i+1)."\">".$MONTH_NAMES[$i]."</option>");
					} else {
						$CALENDAR_BUTTONS->ADD_CUSTOMSOURCE("<option value=\"".($i+1)."\">".$MONTH_NAMES[$i]."</option>");
					}
				}
				$CALENDAR_BUTTONS->ADD_CUSTOMSOURCE("</select>");
				$CALENDAR_BUTTONS->ADD_CUSTOMSOURCE("<select class=\"calendar_select\" id=\"calendar_select_year\">");
				for($i = $YEAR_NOW-1; $i < $YEAR_NOW+4; $i++) {
					if($i == $YEAR) {
						$CALENDAR_BUTTONS->ADD_CUSTOMSOURCE("<option selected value=\"".$i."\">".$i."</option>");
					} else {
						$CALENDAR_BUTTONS->ADD_CUSTOMSOURCE("<option value=\"".$i."\">".$i."</option>");
					}
				}
				$CALENDAR_BUTTONS->ADD_CUSTOMSOURCE("</select>");
				$CALENDAR_BUTTONS->ADD_CUSTOMSOURCE("<script>initCalendar();</script>");
				$CALENDAR_BUTTONS->ADD_BUTTON_NAVIGATION("<i class=\"fas fa-angle-up\"></i> aktueller Monat", "?p=kalender");
				
				if($SPECIAL_PAGE_PERMS == "ALTER" or $SPECIAL_PAGE_PERMS == "EDIT") {
					//Buttons zum Bearbeiten.
					$CALENDAR_BUTTONS->ADD_CUSTOMSOURCE("<br>");
					$CALENDAR_BUTTONS->ADD_BUTTON_CALENDAR_SINGLE("<i class=\"fas fa-plus\"></i> neues Ereignis","?p=kalender&y=$YEAR&m=$MONTH",$CATEGORIES_STR,$CATEGORIES_VALS,$PERMISSIONS_STR,$PERMISSIONS_VALS,$USERGROUPS_STR,$USERGROUPS_VALS);
					$CALENDAR_BUTTONS->ADD_BUTTON_CALENDAR_MULTI("<i class=\"fas fa-plus\"></i> mehrt&auml;giges Ereignis","?p=kalender&y=$YEAR&m=$MONTH",$CATEGORIES_STR,$CATEGORIES_VALS,$PERMISSIONS_STR,$PERMISSIONS_VALS,$USERGROUPS_STR,$USERGROUPS_VALS);
					$CALENDAR_BUTTONS->ADD_BUTTON_NAVIGATION("<i class=\"fas fa-calendar\"></i> Kategorien","?p=kalender&y=$YEAR&m=$MONTH&kategorien");
				}
				$CUSTOM_PAGE->ADD_SOURCE($CALENDAR_BUTTONS->GET_SOURCE());
				$CUSTOM_PAGE->ADD_SOURCE("<div id=\"calendar\">");
				
				//CodeObject für Kalender.
				$CALENDAR_TABLE=new CODE_OBJECT();
				$CALENDAR_TABLE->ADD_SOURCE("<table class=\"calendar\">");
				$CALENDAR_TABLE->ADD_SOURCE("<tr>");
				$CALENDAR_TABLE->ADD_SOURCE("<td class=\"top\">Montag</td>");
				$CALENDAR_TABLE->ADD_SOURCE("<td class=\"top\">Dienstag</td>");
				$CALENDAR_TABLE->ADD_SOURCE("<td class=\"top\">Mittwoch</td>");
				$CALENDAR_TABLE->ADD_SOURCE("<td class=\"top\">Donnerstag</td>");
				$CALENDAR_TABLE->ADD_SOURCE("<td class=\"top\">Freitag</td>");
				$CALENDAR_TABLE->ADD_SOURCE("<td class=\"top\">Samstag</td>");
				$CALENDAR_TABLE->ADD_SOURCE("<td class=\"top\">Sonntag</td>");
				$CALENDAR_TABLE->ADD_SOURCE("</tr>");

				$PLACEHOLDERS_COUNT=date("N",strtotime("$YEAR-$MONTH-01")) - 1;
				$PLACEHOLDERS;
				for($i = 0; $i < $PLACEHOLDERS_COUNT; $i++) {
					$PLACEHOLDERS.="<td class=\"disabled\"></td>";
				}
				$CALENDAR_TABLE->ADD_SOURCE("<tr>".$PLACEHOLDERS);
				
				//Loop durch alle Tage des Monats.
				for ($i = 0; $i < $MONTH_DAYS; $i++){
					$CURRENT_MONTH_DAY=$i+1;
					$CURRENT_WEEK_DAY=date("N",strtotime("$YEAR-$MONTH-$CURRENT_MONTH_DAY"));

					$FIELD_CLASSES="<td class=\"";
					if($YEAR == $YEAR_NOW and $MONTH == $MONTH_NOW and $CURRENT_MONTH_DAY == $DAY_NOW) {
						$FIELD_CLASSES.="today";
					}
					$SQL_QUERY=SQL_READLOOP_PARAM("calendar","date","$YEAR-$MONTH-$CURRENT_MONTH_DAY");
					$EVENTS_MULTI=array();
					$index = 0;
					
					//Mehrtägige Ereignisse werden in ein Array umgeschrieben.
					for($j = 0; $ROW=mysqli_fetch_array($SQL_QUERY); $j++){
						if($ROW["multi"]!=0) {
							$CAL_CATEGORY=SQL_READ("calendar_categories","id,category,color","id",$ROW["category"]);
							$EVENTS_MULTI[$index]["id"]=$ROW["id"];
							$EVENTS_MULTI[$index]["date"]=$ROW["date"];
							$EVENTS_MULTI[$index]["event"]=$ROW["event"];
							$EVENTS_MULTI[$index]["category"]=$CAL_CATEGORY["category"];
							$EVENTS_MULTI[$index]["permissionid"]=$ROW["permissionid"];
							$EVENTS_MULTI[$index]["multi"]=$ROW["multi"];
							$EVENTS_MULTI[$index]["color"]=$CAL_CATEGORY["color"];
							$index++;
						}
					}
					$SQL_QUERY=SQL_READLOOP_PARAM("calendar","date","$YEAR-$MONTH-$CURRENT_MONTH_DAY","time");
					$EVENTS_SINGLE=array();
					$index = 0;
					
					//Ereignisse werden in ein Array umgeschrieben.
					for($j = 0; $ROW=mysqli_fetch_array($SQL_QUERY); $j++){
						if($ROW["multi"]==0) {
							$CAL_CATEGORY=SQL_READ("calendar_categories","id,category,color","id",$ROW["category"]);
							$EVENTS_SINGLE[$index]["id"]=$ROW["id"];
							$EVENTS_SINGLE[$index]["date"]=$ROW["date"];
							$EVENTS_SINGLE[$index]["time"]=substr($ROW["time"],0,5);
							if($EVENTS_SINGLE[$index]["time"] == "00:00") {
								$EVENTS_SINGLE[$index]["time"]="NULL";
							}
							$EVENTS_SINGLE[$index]["event"]=$ROW["event"];
							$EVENTS_SINGLE[$index]["category"]=$CAL_CATEGORY["category"];
							$EVENTS_SINGLE[$index]["permissionid"]=$ROW["permissionid"];
							$EVENTS_SINGLE[$index]["color"]=$CAL_CATEGORY["color"];
							$index++;
						}
					}
					
					//Gibt es Events für den aktuellen Tag?
					$HASEVENTS=true;
					if(count($EVENTS_SINGLE) == 0 and count($EVENTS_MULTI) == 0) {
						$HASEVENTS=false;
					}
					$EVENTS_CODE="";
					if($HASEVENTS) {
						//mehrtägige Ereignisse werden zuerst ausgegeben.
						for($j = 0; $j < count($EVENTS_MULTI); $j++) {
							$CALENDAR_PERM=PERMSYS_GET_PERM_FOR_USER(SQL_READ("calendar","multi,permissionid","multi",$EVENTS_MULTI[$j]["multi"])["permissionid"]);
							//Wenn Berechtigung verfügbar.
							if($CALENDAR_PERM != "NONE") {
								$EVENTS_CODE.="<fieldset title=\"".$EVENTS_MULTI[$j]["category"]."\" style=\"background: #".$EVENTS_MULTI[$j]["color"].";\">";
								if($CALENDAR_PERM == "ALTER") {
									$EVENTS_CODE.="<legend><button onClick=\"dpBoxCalendarEdit(true,'".$EVENTS_MULTI[$j]["event"]."','$CATEGORIES_STR','$CATEGORIES_VALS','$PERMISSIONS_STR','$PERMISSIONS_VALS','$USERGROUPS_STR','$USERGROUPS_VALS',function(){jsNav('?p=kalender&y=$YEAR&m=$MONTH&func_deletemultiple=".$EVENTS_MULTI[$j]["multi"]."');},function(){if($('#box-input-category')[0].selectedIndex!=0&&$('#box-input-perm-public')[0].selectedIndex!=0&&$('#box-input-perm-auth')[0].selectedIndex!=0&&$('#box-input-perm-groupname')[0].selectedIndex!=0&&$('#box-input-perm-group')[0].selectedIndex!=0)jsNav('?p=kalender&y=$YEAR&m=$MONTH&func_changemultiple=".$EVENTS_MULTI[$j]["multi"]."&name=' + $('#box-input-event').val() + '&category=' + $('#box-input-category').val() + '&perm_public=' + $('#box-input-perm-public').val() + '&perm_auth=' + $('#box-input-perm-auth').val() + '&perm_group_id=' + $('#box-input-perm-groupname').val() + '&perm_group=' + $('#box-input-perm-group').val());});\"><i class=\"fas fa-pen\"></i></button></legend>";
								} elseif($CALENDAR_PERM == "EDIT") {
									$EVENTS_CODE.="<legend><button onClick=\"dpBoxCalendarEdit(false,'".$EVENTS_MULTI[$j]["event"]."','$CATEGORIES_STR','$CATEGORIES_VALS','$PERMISSIONS_STR','$PERMISSIONS_VALS','$USERGROUPS_STR','$USERGROUPS_VALS',function(){},function(){if($('#box-input-category')[0].selectedIndex!=0&&$('#box-input-perm-public')[0].selectedIndex!=0&&$('#box-input-perm-auth')[0].selectedIndex!=0&&$('#box-input-perm-groupname')[0].selectedIndex!=0&&$('#box-input-perm-group')[0].selectedIndex!=0)jsNav('?p=kalender&y=$YEAR&m=$MONTH&func_changemultiple=".$EVENTS_MULTI[$j]["multi"]."&name=' + $('#box-input-event').val() + '&category=' + $('#box-input-category').val() + '&perm_public=' + $('#box-input-perm-public').val() + '&perm_auth=' + $('#box-input-perm-auth').val() + '&perm_group_id=' + $('#box-input-perm-groupname').val() + '&perm_group=' + $('#box-input-perm-group').val());});\"><i class=\"fas fa-pen\"></i></button></legend>";
								}
								$EVENTS_CODE.="<dt>".$EVENTS_MULTI[$j]["event"]."</dt></fieldset>";
							}
						}
						//normale Ereignisse werden ausgegeben.
						for($j = 0; $j < count($EVENTS_SINGLE); $j++) {
							$CALENDAR_PERM=PERMSYS_GET_PERM_FOR_USER(SQL_READ("calendar","id,permissionid","id",$EVENTS_SINGLE[$j]["id"])["permissionid"]);
							//Wenn Berechtigung verfügbar.
							if($CALENDAR_PERM != "NONE") {
								if($EVENTS_SINGLE[$j]["time"]=="NULL"){
									$EVENTS_CODE.="<fieldset title=\"".$EVENTS_SINGLE[$j]["category"]."\"><legend><p>ganzt&auml;gig</p>";
								}else{
									$EVENTS_CODE.="<fieldset title=\"".$EVENTS_SINGLE[$j]["category"]."\"><legend><p>".$EVENTS_SINGLE[$j]["time"]."</p>";
								}
								if($CALENDAR_PERM == "ALTER") {
									$EVENTS_CODE.="<button onClick=\"dpBoxCalendarEdit(true,'".$EVENTS_SINGLE[$j]["event"]."','$CATEGORIES_STR','$CATEGORIES_VALS','$PERMISSIONS_STR','$PERMISSIONS_VALS','$USERGROUPS_STR','$USERGROUPS_VALS',function(){jsNav('?p=kalender&y=$YEAR&m=$MONTH&func_delete=".$EVENTS_SINGLE[$j]["id"]."');},function(){if($('#box-input-category')[0].selectedIndex!=0&&$('#box-input-perm-public')[0].selectedIndex!=0&&$('#box-input-perm-auth')[0].selectedIndex!=0&&$('#box-input-perm-groupname')[0].selectedIndex!=0&&$('#box-input-perm-group')[0].selectedIndex!=0)jsNav('?p=kalender&y=$YEAR&m=$MONTH&func_change=".$EVENTS_SINGLE[$j]["id"]."&name=' + $('#box-input-event').val() + '&category=' + $('#box-input-category').val() + '&perm_public=' + $('#box-input-perm-public').val() + '&perm_auth=' + $('#box-input-perm-auth').val() + '&perm_group_id=' + $('#box-input-perm-groupname').val() + '&perm_group=' + $('#box-input-perm-group').val());});\"><i class=\"fas fa-pen\"></i></button>";
								} elseif($CALENDAR_PERM == "EDIT") {
									$EVENTS_CODE.="<button onClick=\"dpBoxCalendarEdit(false,'".$EVENTS_SINGLE[$j]["event"]."','$CATEGORIES_STR','$CATEGORIES_VALS','$PERMISSIONS_STR','$PERMISSIONS_VALS','$USERGROUPS_STR','$USERGROUPS_VALS',function(){},function(){if($('#box-input-category')[0].selectedIndex!=0&&$('#box-input-perm-public')[0].selectedIndex!=0&&$('#box-input-perm-auth')[0].selectedIndex!=0&&$('#box-input-perm-groupname')[0].selectedIndex!=0&&$('#box-input-perm-group')[0].selectedIndex!=0)jsNav('?p=kalender&y=$YEAR&m=$MONTH&func_change=".$EVENTS_SINGLE[$j]["id"]."&name=' + $('#box-input-event').val() + '&category=' + $('#box-input-category').val() + '&perm_public=' + $('#box-input-perm-public').val() + '&perm_auth=' + $('#box-input-perm-auth').val() + '&perm_group_id=' + $('#box-input-perm-groupname').val() + '&perm_group=' + $('#box-input-perm-group').val());});\"><i class=\"fas fa-pen\"></i></button>";
								}
								$EVENTS_CODE.="</legend><dt style=\"color: #".$EVENTS_SINGLE[$j]["color"].";\">".$EVENTS_SINGLE[$j]["event"]."</dt></fieldset>";
							}
						}
					}
					$FIELD_CLASSES.="\">";
					$CALENDAR_TABLE->ADD_SOURCE($FIELD_CLASSES);
					if($SPECIAL_PAGE_PERMS == "ALTER" or $SPECIAL_PAGE_PERMS == "EDIT") {
						$CALENDAR_TABLE->ADD_SOURCE("<button class=\"plus\" onClick=\"dpBoxCalendarSingle('Ereignis hinzuf&uuml;gen','$CATEGORIES_STR','$CATEGORIES_VALS','$PERMISSIONS_STR','$PERMISSIONS_VALS','$USERGROUPS_STR','$USERGROUPS_VALS','".date("Y-m-d",strtotime("$YEAR-$MONTH-$CURRENT_MONTH_DAY"))."',function(){jsNav('?p=kalender&y=$YEAR&m=$MONTH&func_add=' + $('#box-input-date').val() + '&event=' + $('#box-input-event').val() + '&time=' + $('#box-input-time').val() + '&category=' + $('#box-input-category').val() + '&perm_public=' + $('#box-input-perm-public').val() + '&perm_auth=' + $('#box-input-perm-auth').val() + '&group=' + $('#box-input-perm-groupname').val() + '&perm_group=' + $('#box-input-perm-group').val());});\"><i class=\"fas fa-plus\"></i></button>");
					}
					$CALENDAR_TABLE->ADD_SOURCE("<p>".$CURRENT_MONTH_DAY.".</p>$EVENTS_CODE");
					$CALENDAR_TABLE->ADD_SOURCE("</td>");
					
					//Tabellenumbruch nach Sonntag.
					if($CURRENT_WEEK_DAY == 7){
						$CALENDAR_TABLE->ADD_SOURCE("</tr><tr>");
					}
				}
				$CALENDAR_TABLE->ADD_SOURCE("</tr>");
				$CALENDAR_TABLE->ADD_SOURCE("</table>");
				$CALENDAR_TABLE->ADD_SOURCE("</div>");
				$CUSTOM_PAGE->ADD_SOURCE($CALENDAR_TABLE->GET_SOURCE());
			}
		} else {
			$CUSTOM_PAGE->ADD_SOURCE($ACCESS_DENIED->GET_SOURCE());
		}
		break;
	//Klausurplan.
	case "klausurplan":
		if(LOGGED_IN()) {
			if($SPECIAL_PAGE_PERMS != "NONE") {
				PAGE_ADD_VIEW("klausurplan");
				$CUSTOM_PAGE->ADD_SOURCE("<h1>Klausurplan</h1>");
				if($SPECIAL_PAGE_PERMS == "EDIT" or $SPECIAL_PAGE_PERMS == "ALTER") {
					//Upload Buttons.
					$BTNLST_KLAUSURPLAN=new MODULE_BUTTONLIST();
					$BTNLST_KLAUSURPLAN->ADD_BUTTON_NAVIGATION("Vorlage herunterladen", "./media/klausurplan/klausurplan_vorlage.csv");
					$BTNLST_KLAUSURPLAN->ADD_BUTTON_UPLOAD("CSV hochladen","Klausurplan hochladen","CSV-Datei ausw&auml;hlen","?p=edit&func_upload&sub=klausurplan&name=klausurplan.csv","?p=klausurplan");
					$CUSTOM_PAGE->ADD_SOURCE($BTNLST_KLAUSURPLAN->GET_SOURCE());
				}
				
				//Pfad des aktuellen Plans.
				$FPATH="./media/klausurplan/klausurplan.csv";
				if(file_exists($FPATH)) {
					$FHANDLE=fopen($FPATH,"r");
					if($FHANDLE) {
						$TABLE_KLAUSURPLAN;
						
						//Datei wird zeilenweise ausgelesen.
						for($i=0;($FLINE=fgets($FHANDLE))!==false;$i++) {
							$FLINE_ARR=explode(";",$FLINE);
							
							//Tabelle wird generiert.
							if($i == 0) {
								$TABLE_KLAUSURPLAN=new MODULE_TABLE(implode("|", $FLINE_ARR));
							}else{
								$TABLE_KLAUSURPLAN->ADD_LINE(implode("|", $FLINE_ARR));
							}
						}
						fclose($handle);
						$CUSTOM_PAGE->ADD_SOURCE($TABLE_KLAUSURPLAN->GET_SOURCE());
					}else{
						$CUSTOM_PAGE->ADD_SOURCE("<p class=\"centered\">Fehler beim Laden des Klausurplans.</p>");
					}
				}else{
					$CUSTOM_PAGE->ADD_SOURCE("<p class=\"centered\">Klausurplan konnte nicht gefunden werden.</p>");
				}
			}
		}
		break;
	//Kollegium.
	case "kollegium":
		if($SPECIAL_PAGE_PERMS != "NONE") {
			PAGE_ADD_VIEW("kollegium");
			$CUSTOM_PAGE->ADD_SOURCE("<h1>Kollegium</h1>");
			$CUSTOM_PAGE->ADD_SOURCE("<h3>Die Schulleitung</h3>");
			$CUSTOM_PAGE->ADD_SOURCE("<center>");
			$CUSTOM_PAGE->ADD_SOURCE("<div class=\"kollegium\" style=\"display: block;\"><img alt=\"Schulleiter\" src=\"./media/kollegium/kollegium1st.png\"/><p><span>Schulleiter</span>".FACHSCHAFTEN_GETTIER1()."</p></div>");
			$CUSTOM_PAGE->ADD_SOURCE("<div class=\"kollegium\" style=\"display: inline-block;\"><img alt=\"Stellvertretender Schulleiter\" src=\"./media/kollegium/kollegium2nd.png\"/><p><span>stellv. Schulleiter</span>".FACHSCHAFTEN_GETTIER2()."</p></div>");
			$CUSTOM_PAGE->ADD_SOURCE("<div class=\"kollegium\" style=\"display: inline-block;\"><img alt=\"Oberstufenkoordinator\" src=\"./media/kollegium/kollegium3rd.png\"/><p><span>Oberstufenkoordinatorin</span>".FACHSCHAFTEN_GETTIER3()."</p></div>");
			$CUSTOM_PAGE->ADD_SOURCE("</center>");
			$CUSTOM_PAGE->ADD_SOURCE("<h3>Die Fachschaften</h3>");
			$CUSTOM_PAGE->ADD_SOURCE("<p>".str_replace("\n","<br>",FACHSCHAFTEN_GETCNT())."</p>");
		}
		break;
	//Login-Seite.
	case "login":
		if(LOGGED_IN()) {
			$CUSTOM_PAGE->ADD_SOURCE("<h1>Sitzungsfehler</h1>");
			$CUSTOM_PAGE->ADD_SOURCE("<p class=\"centered\">Sie sind bereits eingeloggt.</p>");
		} else {
			PAGE_ADD_VIEW("login");
			
			$CUSTOM_PAGE->ADD_SOURCE("<h1>Anmelden</h1>");
			$CUSTOM_PAGE->ADD_SOURCE("<p class=\"centered\">Bitte melden Sie sich an:</p>");
			
			if(isset($_GET["redirect"])) {
				$LOGIN_FORM=new MODULE_FORM("?p=login_submit&redirect=".urlencode($_GET["redirect"]));
			} else {
				$LOGIN_FORM=new MODULE_FORM("?p=login_submit");
			}
			
			$LOGIN_FORM->ADD_INPUT_TEXT("Benutzername","username");
			$LOGIN_FORM->ADD_INPUT_PASSWORD("Passwort","password");
			$LOGIN_FORM->ADD_SUBMIT("Anmelden","Login");
			$CUSTOM_PAGE->ADD_SOURCE($LOGIN_FORM->GET_SOURCE());
		}
		break;
	//Login-Process-Seite.
	case "login_submit":
		$ERRORLVL=true;
		$ERRORMSG="Unbekannter Fehler";
		if(LOGGED_IN()) {
			$ERRORMSG="Sitzung besteht bereits";
		} else {
			//Prüfen auf Parameter.
			if(isset($_POST["username"]) and isset($_POST["password"])) {
				$USERDATA=SQL_READ("users","username,password,usergroup,active","username",$_POST["username"]);
				
				//Prüfen auf Nutzerdaten.
				if(isset($USERDATA["username"]) and isset($USERDATA["password"])) {
					//Passwort wird gehashed.
					$PASSWORD=hash("sha512",$_POST["password"]);
					
					//Abfrage des Passworts.
					if($PASSWORD==$USERDATA["password"]) {
						$USERGROUP_EXP = SQL_READ("usergroups","id,expires","id",$USERDATA["usergroup"])["expires"];
						
						if(strtotime(date("Y-n-j")) < strtotime($USERGROUP_EXP) or $USERGROUP_EXP == "0000-00-00") {
							//Abfrage, ob Nutzer freigeschaltet ist.
							if($USERDATA["active"] != 0) {
								//Session wird erstellt.
								$_SESSION["login"]=strtolower($_POST["username"]);
								
								//Letzter Login wird gesetzt.
								SQL_CHANGE("users","username",strtolower($_POST["username"]),"date_lastlogin",date("Y-m-d H:i:s"));
								
								//Login-Anzahl wird gesetzt.
								$LOGINS=SQL_READ("users","username,logins","username",strtolower($_POST["username"]))["logins"];
								SQL_CHANGE("users","username",strtolower($_POST["username"]),"logins",$LOGINS+1);
								
								$ERRORLVL=false;
								PAGE_ADD_VIEW("login_submit");
								LOGGING_PUSH("INFO","Nutzer '".strtolower($_POST["username"])."' hat sich angemeldet.");
							} else {
								$ERRORMSG="Nutzer ist gesperrt";
								LOGGING_PUSH("WARNING","Fehlversuch beim Login ('".$_POST["username"]."'), Nutzer ist gesperrt");
							}
						} else {
							$ERRORMSG="Diese Nutzergruppe ist abgelaufen und ist deshalb nicht mehr berechtigt, sich anzumelden";
							LOGGING_PUSH("WARNING","Fehlversuch beim Login ('".$_POST["username"]."'), Nutzergruppe ist abgelaufen.");
						}
					} else {
						$ERRORMSG="Kombination aus Nutzername und Passwort ung&uuml;ltig";
						LOGGING_PUSH("WARNING","Fehlversuch beim Login ('".$_POST["username"]."').");
					}
				} else {
					$ERRORMSG="Nutzer existiert nicht";
				}
			} else {
				$ERRORMSG="Fehlende Parameter";
			}
		}
		
		//Fehleranzeige.
		if($ERRORLVL) {
			$CUSTOM_PAGE->ADD_SOURCE("<h1>Fehler</h1>");
			$CUSTOM_PAGE->ADD_SOURCE("<p class=\"centered\">Fehler beim Login: $ERRORMSG. <a href=\"?p=login\">Erneut versuchen</a></p>");
		} else {
			if(isset($_GET["redirect"])) {
				header("Location: ".$_GET["redirect"]);
			} else {
				header("Location: ?p=profil");
			}
		}
		break;
	//Logout-Process-Seite.
	case "logout_submit":
		$ERRORLVL=true;
		$ERRORMSG="Unbekannter Fehler";
		
		if(LOGGED_IN()) {
			PAGE_ADD_VIEW("logout_submit");
			LOGGING_PUSH("INFO","Nutzer '".GET_USER_NAME()." (".GET_USER_ID().")' hat sich abgemeldet.");
			//session_destroy();
			unset($_SESSION["login"]);
			$ERRORLVL=false;
		}else{
			$ERRORMSG="Sie sind nicht angemeldet";
		}
		
		if($ERRORLVL) {
			$CUSTOM_PAGE->ADD_SOURCE("<h1>Fehler</h1>");
			$CUSTOM_PAGE->ADD_SOURCE("<p class=\"centered\">Fehler beim Logout: $ERRORMSG.</p>");
		} else {
			$CUSTOM_PAGE->ADD_SOURCE("<h1>Abgemeldet</h1>");
			$CUSTOM_PAGE->ADD_SOURCE("<p class=\"centered\">Sie wurden erfolgreich abgemeldet.</p>");
			header("Location: ?p=login");
		}
		break;
	//Profil.
	case "profil":
		if(LOGGED_IN()) {
			PAGE_ADD_VIEW("profil");
			$ID=GET_USER_ID();
			
			//Passwortänderung.
			if(isset($_GET["newpassword"])){
				$NEWPW=$_GET["newpassword"];
				$PWHASH=hash("sha512",$NEWPW);
				SQL_CHANGE("users","id",$ID,"password",$PWHASH);
				LOGGING_PUSH("INFO","Nutzer '".GET_USER_NAME()." (".GET_USER_ID().")' hat sein Passwort geändert.");
				header("Location: ?p=profil");
			}
			
			//Nutzerdaten werden ausgelesen.
			$USERDATA=SQL_READ("users","id,username,name,vorname,email,usergroup,logins,date_register,date_lastlogin","id",$ID);
			$USERDATA_GROUP=SQL_READ("usergroups","id,name,expires","id",$USERDATA["usergroup"]);
			$USERDATA_EXPIRES=$USERDATA_GROUP["expires"];
			if($USERDATA_GROUP["expires"] == "0000-00-00") {
				$USERDATA_EXPIRES = "nie";
			} else {
				$USERDATA_EXPIRES = date("d.m.Y",strtotime($USERDATA_EXPIRES));
			}
			
			//Buttons.
			$BTNLIST_PROFIL=new MODULE_BUTTONLIST();
			$BTNLIST_PROFIL->ADD_BUTTON_NAVIGATION("Seiten&uuml;bersicht", "?p=uebersicht");
			$BTNLIST_PROFIL->ADD_BUTTON_PASSWORD("Passwort &auml;ndern", "?p=profil&newpassword=");
			if(IS_USER_ADMIN()) {
				$BTNLIST_PROFIL->ADD_BUTTON_NAVIGATION("Backend-Bereich", "backend.php");
			}
			
			//Ausgabe.
			$CUSTOM_PAGE->ADD_SOURCE("<h1>Profil</h1>");
			$CUSTOM_PAGE->ADD_SOURCE($BTNLIST_PROFIL->GET_SOURCE());
			$CUSTOM_PAGE->ADD_SOURCE("<h3>".$USERDATA["vorname"]." ".$USERDATA["name"]."</h3>");
			$CUSTOM_PAGE->ADD_SOURCE("<p>Username: ".$USERDATA["username"]."</p>");
			$CUSTOM_PAGE->ADD_SOURCE("<p>E-Mail: ".$USERDATA["email"]."</p>");
			$CUSTOM_PAGE->ADD_SOURCE("<p>Nutzergruppe: ".$USERDATA_GROUP["name"]."</p>");
			$CUSTOM_PAGE->ADD_SOURCE("<p>Ablauf des Kontos: ".$USERDATA_EXPIRES."</p>");
			$CUSTOM_PAGE->ADD_SOURCE("<p>Registrierungsdatum: ".date("d.m.Y",strtotime($USERDATA["date_register"]))."</p>");
			$CUSTOM_PAGE->ADD_SOURCE("<p>Logins gesamt: ".$USERDATA["logins"]."</p>");
			$CUSTOM_PAGE->ADD_SOURCE("<p>Letzter Login: ".date("d.m.Y H:i:s",strtotime($USERDATA["date_lastlogin"]))."</p>");
		}else{
			$CUSTOM_PAGE->ADD_SOURCE($ACCESS_DENIED->GET_SOURCE());
		}
		break;
	//Stundenplan.
	case "stundenplan":
		if(LOGGED_IN()) {
			if($SPECIAL_PAGE_PERMS != "NONE") {
				$CUSTOM_PAGE->ADD_SOURCE("<h1>Stundenplan</h1>");
				
				//Konfiguration.
				if(isset($_GET["cfg"])) {
					$BTNLST_SCHEDULE=new MODULE_BUTTONLIST();
					$BTNLST_SCHEDULE->ADD_BUTTON_NAVIGATION("Zur&uuml;ck","?p=stundenplan");
					$CUSTOM_PAGE->ADD_SOURCE($BTNLST_SCHEDULE->GET_SOURCE());
					$CUSTOM_FORM = new MODULE_FORM("?p=stundenplan&cfg&save","post");
					
					//Klassenkonfiguration.
					if(isset($_GET["cl"])) {
						$CLASS_LIST = array();
						$c = 0;
						for($i = 5; $i <= 12; $i++) {
							for($j = 1; $j <= 4; $j++) {
								$CLASS_LIST[$c] = $i . "/" . $j;
								$c++;
							}
						}
						$CLASS_LIST_STR = implode("|", $CLASS_LIST);
						$CUSTOM_FORM->ADD_INPUT_SELECT("Klasse","cl",$CLASS_LIST_STR,$CLASS_LIST_STR);
						
					//Kurskonfiguration.
					} else if(isset($_GET["co"])) {
						//Kursliste.
						$SUBJECT_LIST = explode(";", "bio;che;deu;eng;eth;evr;frz;geo;ges;inf;ita;kun;lat;mat;mus;phy;rdk;rus;soz");
						$CUSTOM_FORM->ADD_CUSTOMSOURCE("<label>Kursauswahl:</label><br>");
						
						//Für jeden Kurs:
						for($i = 0; $i < count($SUBJECT_LIST); $i++) {
							//3 Parallele Kurse.
							for($j = 1; $j <= 3; $j++) {
								//Grund- und Leistungskurse.
								$COURSE_NAME_GK = ucfirst($SUBJECT_LIST[$i])." - Kurs ".$j." - Grundkurs";
								$COURSE_NAME_LK = ucfirst($SUBJECT_LIST[$i])." - Kurs ".$j." - Leistungskurs";
								$COURSE_SHORT_GK = strtolower($SUBJECT_LIST[$i].$j);
								$COURSE_SHORT_LK = strtoupper($SUBJECT_LIST[$i].$j);
								
								//Ausgabe.
								$CUSTOM_FORM->ADD_INPUT_CHECKBOXARRAY($COURSE_NAME_GK, "colistitems", $COURSE_SHORT_GK);
								$CUSTOM_FORM->ADD_INPUT_CHECKBOXARRAY($COURSE_NAME_LK, "colistitems", $COURSE_SHORT_LK);
							}
						}
						$CUSTOM_FORM->ADD_CUSTOMSOURCE("<label>gew&auml;hlte Kurse:</label>");
						$SQL_ROW = SQL_READ("schedule", "id,user,co", "user", GET_USER_ID());
						if($SQL_ROW) {
							$CUSTOM_FORM->ADD_CUSTOMSOURCE("<input readonly type=\"text\" name=\"co\" id=\"colist\" value=\"" . $SQL_ROW["co"] . "\"/>");
						} else {
							$CUSTOM_FORM->ADD_CUSTOMSOURCE("<input readonly type=\"text\" name=\"co\" id=\"colist\"/>");
						}
						
						//Scripteinbindung.
						$CUSTOM_FORM->ADD_CUSTOMSOURCE("<script language=\"javascript\" type=\"text/javascript\" src=\"javascript/stundenplan.js\"></script>");
						$CUSTOM_FORM->ADD_CUSTOMSOURCE("<script>courseInit('" . $SQL_ROW["co"] . "');</script>");
						
					//Speicherfunktionen.
					} else if(isset($_GET["save"])) {
						//Speicherung der Klasse.
						if(isset($_POST["cl"])) {
							$SQL_ROW = SQL_READ("schedule", "id,user,cl", "user", GET_USER_ID());
							if($SQL_ROW) {
								SQL_CHANGE("schedule","id",$SQL_ROW["id"],"cl","".$_POST["cl"]."");
							} else {
								SQL_ADD("schedule","user,cl",GET_USER_ID().",'".$_POST["cl"]."'");
							}
							LOGGING_PUSH("INFO","Nutzer '".GET_USER_NAME()." (".GET_USER_ID().")' hat die Klasse gewechselt.");
							header("Location: ?p=stundenplan");
							
						//Speicherung der Kurse.
						} else if(isset($_POST["co"])) {
							$SQL_ROW = SQL_READ("schedule", "id,user,co", "user", GET_USER_ID());
							if($SQL_ROW) {
								SQL_CHANGE("schedule","id",$SQL_ROW["id"],"co","".$_POST["co"]."");
							} else {
								SQL_ADD("schedule","user,co",GET_USER_ID().",'".$_POST["co"]."'");
							}
							LOGGING_PUSH("INFO","Nutzer '".GET_USER_NAME()." (".GET_USER_ID().")' hat die Kursauswahl aktualisiert.");
							header("Location: ?p=stundenplan");
						} else {
							header("Location: ?p=stundenplan");
						}
					} else {
						header("Location: ?p=stundenplan");
					}
					$CUSTOM_FORM->ADD_SUBMIT("Speichern", "&Auml;nderungen &uuml;bernehmen");
					$CUSTOM_PAGE->ADD_SOURCE($CUSTOM_FORM->GET_SOURCE());
				} else {
					PAGE_ADD_VIEW("stundenplan");
					
					$DATE_TODAY = date("Ymd");
					$DATE_TOMORROW = date("Ymd", strtotime(" +1 Weekday"));
					
					//Buttons.
					$BTNLST_SCHEDULE=new MODULE_BUTTONLIST();
					if($SPECIAL_PAGE_PERMS == "EDIT" or $SPECIAL_PAGE_PERMS == "ALTER") {
						//Upload-Buttons.
						$BTNLST_SCHEDULE->ADD_BUTTON_UPLOAD("XML Upload heute","Vertretungsplan f&uuml;r heute (".date("d.m.Y").")","XML-Datei f&uuml;r heute hochladen","?p=edit&func_upload&sub=stundenplan&name=PlanKl".$DATE_TODAY.".xml");
						$BTNLST_SCHEDULE->ADD_BUTTON_UPLOAD("XML Upload morgen","Vertretungsplan f&uuml;r morgen (".date("d.m.Y", strtotime(" +1 Weekday")).")","XML-Datei f&uuml;r morgen hochladen","?p=edit&func_upload&sub=stundenplan&name=PlanKl".$DATE_TOMORROW.".xml");
					}
					
					//Auslesen der Klassen- und Kursdaten.
					$SQL_ROW = SQL_READ("schedule", "id,user,cl,co", "user", GET_USER_ID());
					if($SQL_ROW) {
						$COURSES = $SQL_ROW["co"];
						if($COURSES == "") {
							$COURSES = "keine";
						}
						
						$BTNLST_SCHEDULE->ADD_BUTTON_SCHEDULE("Plan heute",$DATE_TODAY,$SQL_ROW["cl"],$SQL_ROW["co"]);
						$BTNLST_SCHEDULE->ADD_BUTTON_SCHEDULE("Plan morgen",$DATE_TOMORROW,$SQL_ROW["cl"],$SQL_ROW["co"]);
						
						$CUSTOM_PAGE->ADD_SOURCE($BTNLST_SCHEDULE->GET_SOURCE());
						
						//Ausgabe.
						$CUSTOM_PAGE->ADD_SOURCE("<p class=\"centered\">Aktuelle Klasse: " . $SQL_ROW["cl"] . " (<a href=\"?p=stundenplan&cfg&cl\">&auml;ndern</a>)<br>Aktuelle Kurse: " . str_replace(";", ", ", $COURSES) . " (<a href=\"?p=stundenplan&cfg&co\">&auml;ndern</a>)</p>");
						$CUSTOM_PAGE->ADD_SOURCE("<h3 style=\"display: none;\" id=\"timestamp\"></h3><table style=\"display: none;\" id=\"stundenplan\"></table>");
						
						//Scripteinbindung.
						$CUSTOM_PAGE->ADD_SOURCE("<script language=\"javascript\" type=\"text/javascript\" src=\"javascript/stundenplan.js\"></script>");
					} else {
						$CUSTOM_PAGE->ADD_SOURCE($BTNLST_SCHEDULE->GET_SOURCE());
						$CUSTOM_PAGE->ADD_SOURCE("<p class=\"centered\">Es besteht noch keine Konfiguration &uuml;ber Ihre Klasse bzw. Ihre Kurse.<br>Info: Erst Sch&uuml;ler ab Klassenstufe 10 m&uuml;ssen Kurse w&auml;hlen.</p>");
						$CUSTOM_PAGE->ADD_SOURCE("<p class=\"centered\"><a href=\"?p=stundenplan&cfg&cl\">Klasse w&auml;hlen</a></p>");
					}
				}
			} else {
				$CUSTOM_PAGE->ADD_SOURCE($ACCESS_DENIED->GET_SOURCE());
			}
		} else {
			$CUSTOM_PAGE->ADD_SOURCE($ACCESS_DENIED->GET_SOURCE());
		}
		break;
	//Seitenübersicht.
	case "uebersicht":
		if($SPECIAL_PAGE_PERMS != "NONE") {
			//Erstellt eine Seite.
			if(isset($_GET["func_create"])) {
				if($SPECIAL_PAGE_PERMS == "EDIT" or $SPECIAL_PAGE_PERMS == "ALTER") {
					$PAGE_NAME=str_replace(" ","_",strtolower(SIMPLIFY_STRING($_GET["func_create"])));
					if($PAGE_NAME == "") {
						$PAGE_NAME = rand();
					}
					$PAGE_USER=SQL_READ("users","id,username","id",GET_USER_ID())["username"];
					$NEW_PERM_ID=PERMSYS_MAKE_ENTRY("NONE","VIEW","EDIT",GET_USER_GROUP_ID(GET_USER_ID()));
					SQL_ADD("pages","title,page,category,source,active,views,permissionid","'".$_GET["func_create"]."','$PAGE_NAME',1,'<h1>".$_GET["func_create"]."</h1><div>Neue Seite erstellt von $PAGE_USER.</div>',0,0,$NEW_PERM_ID");
					LOGGING_PUSH("INFO","Seite '".$PAGE_NAME."' erstellt von '".GET_USER_NAME()." (".GET_USER_ID().")'.");
				}
				header("Location: ?p=uebersicht");
				
			//Seitenübersicht.
			}else{
				PAGE_ADD_VIEW("uebersicht");
				$CUSTOM_PAGE->ADD_SOURCE("<h1>Seiten&uuml;bersicht</h1>");
				if($SPECIAL_PAGE_PERMS == "EDIT" or $SPECIAL_PAGE_PERMS == "ALTER") {
					//Buttons.
					$BTNLST_OVERVIEW=new MODULE_BUTTONLIST();
					$BTNLST_OVERVIEW->ADD_BUTTON_TEXT("Neue Seite erstellen","Geben Sie den Namen f&uuml;r die neue Seite ein:","?p=uebersicht&func_create=");
					$CUSTOM_PAGE->ADD_SOURCE($BTNLST_OVERVIEW->GET_SOURCE());
				}
				
				//Tabelle wird generiert.
				$PAGES_TABLE=new MODULE_TABLE("Seite|Kategorie|Autor",true);
				$SQL_QUERY=SQL_READLOOP("pages","id,title,page,category,active,permissionid");
				while($ROW=mysqli_fetch_array($SQL_QUERY)){
					//Spezialseiten werden im Frontend nicht angezeigt.
					$PAGE_ISSPECIAL=false;
					$SQL_QUERY_SPC=SQL_READLOOP("pages_special","page");
					while($ROW_SPC=mysqli_fetch_array($SQL_QUERY_SPC)){
						if($ROW["page"] == $ROW_SPC["page"]){
							$PAGE_ISSPECIAL=true;
						}
					}
					if(!$PAGE_ISSPECIAL){
						$PERMISSION_DAT=PERMSYS_GET_PERM_FOR_USER($ROW["permissionid"]);
						$CATEGORY=SQL_READ("categories","id,category","id",$ROW["category"])["category"];
						$AUTHOR=SQL_READ("users","id,username","id",SQL_READ("permissions","id,owner","id",$ROW["permissionid"])["owner"])["username"];
						
						//Seite wird nur in der Übersicht angezeigt, wenn der Nutzer die Berechtigung zum Bearbeiten hat.
						if($PERMISSION_DAT=="EDIT" or $PERMISSION_DAT=="ALTER"){
							$PAGES_TABLE->ADD_LINE("<a href=\"?p=edit&id=".$ROW["id"]."\">".$ROW["title"]."</a>|".$CATEGORY."|".$AUTHOR);
						}
					}
				}
			}
			$CUSTOM_PAGE->ADD_SOURCE($PAGES_TABLE->GET_SOURCE());
		} else {
			$CUSTOM_PAGE->ADD_SOURCE($ACCESS_DENIED->GET_SOURCE());
		}
		break;
	//Hide-Cookie-Banner.
	case "func_hidecookiebanner":
		if(!isset($_SESSION["cbanner"])){
			$BANNER_VAL = "Hidden";
			
			//Setzt einen Cookie, welcher dafür sorgt, dass der Cookiebanner nicht mehr angezeigt wird.
			$_SESSION["cbanner"] = $BANNER_VAL;
		}
		break;
	//Normale Seiten.
	default:
		$SQL_PAGE=SQL_READ("pages","id,title,page,category,source,active,permissionid","page",strtolower($PARAM_PAGE));
		if(isset($SQL_PAGE["page"])) {
			$EDITBTN=new MODULE_EDITBTN($SQL_PAGE["id"]);
			
			//Permission-Switch.
			switch(PERMSYS_GET_PERM_FOR_USER($SQL_PAGE["permissionid"])){
				case "ALTER":
					//Seite wird inkl. des Bearbeiten-Buttons angezeigt.
					$CUSTOM_PAGE->ADD_SOURCE($EDITBTN->GET_SOURCE());
					$CUSTOM_PAGE->ADD_SOURCE($SQL_PAGE["source"]);
					PAGE_ADD_VIEW($SQL_PAGE["page"]);
					break;
				case "EDIT":
					//Seite wird inkl. des Bearbeiten-Buttons angezeigt.
					$CUSTOM_PAGE->ADD_SOURCE($EDITBTN->GET_SOURCE());
					$CUSTOM_PAGE->ADD_SOURCE($SQL_PAGE["source"]);
					PAGE_ADD_VIEW($SQL_PAGE["page"]);
					break;
				case "VIEW":
					//Nur Ansicht; nur, wenn Seite freigeschaltet ist.
					if($SQL_PAGE["active"] == 1){
						$CUSTOM_PAGE->ADD_SOURCE($SQL_PAGE["source"]);
						PAGE_ADD_VIEW($SQL_PAGE["page"]);
					} else {
						$CUSTOM_PAGE->ADD_SOURCE("<h1>Fehler</h1>");
						$CUSTOM_PAGE->ADD_SOURCE("<p class=\"centered\">Die angeforderte Seite ist aktuell gesperrt.<br>Vielleicht kann die <a href=\"?p=home\">Startseite</a> weiterhelfen.</p>");
						LOGGING_PUSH("WARNING","Es wurde versucht, eine gesperrte Seite aufzurufen ('".$SQL_PAGE["page"]." (".$SQL_PAGE["id"].")').");
					}
					break;
				case "NONE":
					//Zugriff verweigert.
					$CUSTOM_PAGE->ADD_SOURCE($ACCESS_DENIED->GET_SOURCE());
					break;
				default:
					$CUSTOM_PAGE->ADD_SOURCE("<h1>Fehler</h1>");
					$CUSTOM_PAGE->ADD_SOURCE("<p class=\"centered\">Die angeforderte Seite kann aufgrund eines Fehlers im Berechtigungssystem aktuell nicht angezeigt werden.<br>Vielleicht kann die <a href=\"?p=home\">Startseite</a> weiterhelfen.</p>");
					LOGGING_PUSH("WARNING","Es ist möglich, dass ein interner Fehler beim Aufrufen der Seite '".$SQL_PAGE["page"]." (".$SQL_PAGE["id"].")' im Berechtigungssystem ausgelöst wurde.");
					break;
			}
		} else {
			$CUSTOM_PAGE->ADD_SOURCE("<h1>Seite nicht gefunden</h1>");
			$CUSTOM_PAGE->ADD_SOURCE("<p class=\"centered\">Die angeforderte Seite konnte nicht gefunden werden.<br>Vielleicht kann die <a href=\"?p=home\">Startseite</a> weiterhelfen.</p>");
		}
		break;
}
$CUSTOM_PAGE->ADD_SOURCE("</div>");

//Body CodeObject.
$BODY=new CODE_OBJECT();

//Linker Banner.
$MAIN_LEFT=new CODE_OBJECT();
$MAIN_LEFT->ADD_SOURCE("<div id=\"main_left\">");
$MAIN_LEFT->ADD_SOURCE("<img src=\"media/banner_left.png\" alt=\"banner_left\"/>");
$MAIN_LEFT->ADD_SOURCE("</div>");

//Rechter Banner.
$MAIN_RIGHT=new CODE_OBJECT();
$MAIN_RIGHT->ADD_SOURCE("<div id=\"main_right\">");
$MAIN_RIGHT->ADD_SOURCE("<img src=\"media/banner_right.png\" alt=\"banner_left\"/>");
$MAIN_RIGHT->ADD_SOURCE("</div>");

//Zentraler Teil.
$MAIN_CENTER=new CODE_OBJECT();
$MAIN_CENTER->ADD_SOURCE("<div id=\"main_center\">");
$MAIN_CENTER->ADD_SOURCE($MENU->GET_SOURCE());
$MAIN_CENTER->ADD_SOURCE("<div id=\"content\">");
$MAIN_CENTER->ADD_SOURCE($CUSTOM_PAGE->GET_SOURCE());
$MAIN_CENTER->ADD_SOURCE("</div>");
$MAIN_CENTER->ADD_SOURCE("</div>");

//Body.
$BODY->ADD_SOURCE($MAIN_LEFT->GET_SOURCE());
$BODY->ADD_SOURCE($MAIN_CENTER->GET_SOURCE());
$BODY->ADD_SOURCE($MAIN_RIGHT->GET_SOURCE());
$BODY->ADD_SOURCE("<div id=\"box\"></div>");

//Cookiebanner-Anzeige.
if(!isset($_SESSION["cbanner"])){
	$BODY->ADD_SOURCE("<div id=\"cookie_banner\"><p class=\"centered\">Mit der Nutzung der Website erkl&auml;ren Sie sich mit der Verwendung von <a href=\"?p=datenschutz\">Cookies</a> einverstanden (<a onClick=\"$.ajax({url:'?p=func_hidecookiebanner',success:function(){ $('#cookie_banner').fadeOut(250); }});\">ausblenden</a>).</p></div>");
}

//Ausgabe Credits
echo "<!--\nCMS des GutsMuths Gymnasiums Quedlinburg\n@author Noah Wiederhold & Bernhard Birnbaum\n@copyright 2020 Noah Wiederhold & Bernhard Birnbaum\n@version 1.00.00\n-->\n";

//Ausgabe der kompletten Seite.
echo "<html lang=\"de\">";

//Header.
echo "<head>".$HEADER->GET_SOURCE()."</head>";

//SQL-Log ausgeben.
DEBUGGING_LOGGER::FREE();

//Wenn Seite online ist, wird Code ausgegeben.
if($META_ONLINESTATE == "true") {
	echo "<body>".$BODY->GET_SOURCE()."</body>";
} else {
	echo "<body><div id=\"main_center\"><div id=\"content\"><div id=\"page\"><h1>Seite offline</h1><p class=\"centered\">Die Seite befindet sich aktuell in einer Wartung.</p></div></div></div></body>";
}
echo "</html>";
?>