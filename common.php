<?php
/**
* CMS des GutsMuths Gymnasiums Quedlinburg
* @author Noah Wiederhold & Bernhard Birnbaum
* @copyright 2020 Noah Wiederhold & Bernhard Birnbaum
* @version 1.02.00b
* 
* common.php - SQL-Funktionen, Teile des Berechtigungssystems, Modulklassen und weitere Utility.
*/
?><?php
  ////////////////////////////////////////
 //           SQL-Funktionen           // 
////////////////////////////////////////  
	
	//Konstanten für die SQL-Verbindung
	$SQL_SERVER="SQL_SERVER_DOMAIN";
	$SQL_USERNAME="SQL_CREDENTIALS_USERNAME";
	$SQL_PASSWORD="SQL_CREDENTIALS_PASSWORD";
	$SQL_DBNAME="SQL_DATABASE_NAME";
	
	//Debugging Logger.
	class DEBUGGING_LOGGER {
		private static $SQL_REQUEST = false;
		private static $DEBUGGING_STATE = true;
		private static $INITIALIZED = false;
		private static $LOGGING_HTMLCODE = "";
		
		public static function INIT() {
			global $SQL_CONNECTION;
			self::$SQL_REQUEST=mysqli_query($SQL_CONNECTION,"SELECT name,metadata FROM metadata WHERE name = 'debugging'");
			if(!self::$SQL_REQUEST) {
				self::$DEBUGGING_STATE=true;
			} else {
				self::$DEBUGGING_STATE=(mysqli_fetch_assoc(self::$SQL_REQUEST)["metadata"]=="true")?true:false;
			}
			
			self::$LOGGING_HTMLCODE = "";
		}
		
		//DEBUGGING_LOGGER::SUPPRESS_LOGGING(); bei JSON returns!
		public static function SUPPRESS_LOGGING() {
			self::$DEBUGGING_STATE=false;
		}
		
		//Möglichkeit, über PHP etwas in der Browser-Konsole zu loggen.
		public static function CONSOLE_LOG($DATA){
			self::$LOGGING_HTMLCODE .= "console.log(".json_encode($DATA).");";
		}
		
		//Gibt den Logging-Code aus.
		public static function FREE() {
			if(self::$DEBUGGING_STATE == true) {
				echo "<script>".self::$LOGGING_HTMLCODE."</script>";
			}
		}
	}
	
	//Führt eine SQL-Anfrage aus und gibt das Ergebnis zurück.
	function SQL_EXEC($REQUEST){
		global $SQL_CONNECTION;
		DEBUGGING_LOGGER::CONSOLE_LOG("[SQL-DEBUGGING]: ".$REQUEST);
		if((strpos($REQUEST,"pages")!==false) AND (strpos($REQUEST,"source")!==false)){ $_XSS_IGNORE = true; }else{ $_XSS_IGNORE = false; }
		if(!$_XSS_IGNORE){
			$REQUEST = str_replace("<", "[", $REQUEST);
			$REQUEST = str_replace(">", "]", $REQUEST);
		}
		return mysqli_query($SQL_CONNECTION,$REQUEST);
	}
	
	//Etabliert eine SQL-Verbindung.
	function SQL_CONNECT(){
		global $SQL_CONNECTION,$SQL_SERVER,$SQL_USERNAME,$SQL_PASSWORD,$SQL_DBNAME;
		$SQL_CONNECTION=mysqli_connect($SQL_SERVER,$SQL_USERNAME,$SQL_PASSWORD) or die ("SQL Server nicht erreichbar.");
		mysqli_select_db($SQL_CONNECTION,$SQL_DBNAME) or die ("SQL Datenbankaufruf nicht m&ouml;glich.");
	}
	
	//Trennt die bestehende SQL-Verbindung.
	function SQL_DISCONNECT(){
		global $SQL_CONNECTION;
		mysqli_close($SQL_CONNECTION);
	}
	
	//Ließt einen Datensatz aus einer Tabelle aus.
	function SQL_READ($TABLE,$SELECTOR,$COLUMN,$VALUE){
		global $SQL_CONNECTION;
		$SQL_REQUEST="SELECT $SELECTOR FROM $TABLE WHERE $COLUMN = '$VALUE'";
		$ROW=mysqli_fetch_assoc(SQL_EXEC($SQL_REQUEST));
		return $ROW;	
	}
	
	//Ließt einen Datensatz aus einer Tabelle anhand von zwei Parametern aus.
	function SQL_READ_PARAM($TABLE,$SELECTOR,$COLUMN,$VALUE,$COLUMN2,$VALUE2){
		global $SQL_CONNECTION;
		$SQL_REQUEST="SELECT $SELECTOR FROM $TABLE WHERE $COLUMN = '$VALUE' AND $COLUMN2 = '$VALUE2'";
		$ROW=mysqli_fetch_assoc(SQL_EXEC($SQL_REQUEST));
		return $ROW;
	}
	
	//Ließt mehrere Datensätze aus einer Tabelle aus.
	function SQL_READLOOP($TABLE,$SELECTOR,$ORDERBY = "id"){
		global $SQL_CONNECTION;
		$SQL_REQUEST="SELECT $SELECTOR FROM $TABLE ORDER BY $ORDERBY";
		return SQL_EXEC($SQL_REQUEST);
	}
	
	//Ließt mehrere Datensätze aus einer Tabelle anhand von zwei Parametern aus.
	function SQL_READLOOP_PARAM($TABLE,$PROPERTY,$VALUE,$ORDERBY = "id"){
		global $SQL_CONNECTION;
		$SQL_REQUEST="SELECT * FROM $TABLE WHERE $PROPERTY='$VALUE' ORDER BY $ORDERBY";
		return SQL_EXEC($SQL_REQUEST);
	}
	
	//Ändert eine Eigenschaft eines Datensatzes.
	function SQL_CHANGE($TABLE,$SELECTOR_PROPERTY,$SELECTOR_VALUE,$PROPERTY,$VALUE){
		global $SQL_CONNECTION;
		$SQL_REQUEST="UPDATE $TABLE SET $PROPERTY='$VALUE' WHERE $SELECTOR_PROPERTY='$SELECTOR_VALUE'";
		SQL_EXEC($SQL_REQUEST);
	}
	
	//Ändert eine Eigenschaft eines Datensatzes anhang von zwei Parametern.
	function SQL_CHANGE_PARAM($TABLE,$PROPERTY,$VALUE,$SELECTOR_PROPERTY,$SELECTOR_VALUE,$SELECTOR_PROPERTY2,$SELECTOR_VALUE2){
		global $SQL_CONNECTION;
		$SQL_REQUEST="UPDATE $TABLE SET $PROPERTY='$VALUE' WHERE $SELECTOR_PROPERTY='$SELECTOR_VALUE' AND $SELECTOR_PROPERTY2 = '$SELECTOR_VALUE2'";
		SQL_EXEC($SQL_REQUEST);
	}
	
	//Ändert mehrere Eigenschaften eines Datensatzes.
	function SQL_CHANGE_MULTIPLE($TABLE,$SELECTOR_PROPERTY,$SELECTOR_VALUE,$VALUES){
		global $SQL_CONNECTION;
		$SQL_REQUEST="UPDATE $TABLE SET $VALUES WHERE $SELECTOR_PROPERTY='$SELECTOR_VALUE'";
		SQL_EXEC($SQL_REQUEST);
	}
	
	//Fügt einen Datensatz in eine Tabelle ein.
	function SQL_ADD($TABLE,$COLUMNS,$VALUES){
		global $SQL_CONNECTION;
		$SQL_REQUEST="INSERT INTO $TABLE($COLUMNS) VALUES($VALUES)";
		SQL_EXEC($SQL_REQUEST);
	}
	
	//Löscht einen Datensatz aus einer Tabelle.
	function SQL_DELETE($TABLE,$PROPERTY,$VALUE){
		global $SQL_CONNECTION;
		$SQL_REQUEST="DELETE FROM $TABLE WHERE $PROPERTY='$VALUE'";
		SQL_EXEC($SQL_REQUEST);
	}
	
	//Löscht einen Datensatz aus einer Tabelle anhand von zwei Parametern.
	function SQL_DELETE_PARAM($TABLE,$PROPERTY,$VALUE,$PROPERTY2,$VALUE2){
		global $SQL_CONNECTION;
		$SQL_REQUEST="DELETE FROM $TABLE WHERE $PROPERTY='$VALUE' AND $PROPERTY2='$VALUE2'";
		SQL_EXEC($SQL_REQUEST);
	}
	
	//Liefert die Zahl der Datensätze einer Tabelle.
	function SQL_COUNT_ROWS($TABLE){
		global $SQL_CONNECTION;
		$SQL_REQUEST="SELECT COUNT(*) FROM $TABLE";
		return mysqli_fetch_assoc(SQL_EXEC($SQL_REQUEST))["COUNT(*)"];
	}
	
	//Liefert die Zahl der Datensätze einer Tabelle mit einem Parameter.
	function SQL_COUNT_ROWS_PARAM($TABLE,$PROPERTY,$VALUE){
		global $SQL_CONNECTION;
		$SQL_REQUEST="SELECT COUNT(*) FROM $TABLE WHERE $PROPERTY='$VALUE'";
		return mysqli_fetch_assoc(SQL_EXEC($SQL_REQUEST))["COUNT(*)"];
	}
	
	//Liefert den Datensatz mit größten Wert in einer Spalte.
	function SQL_MAXVALUE($TABLE,$COLUMN){
		global $SQL_CONNECTION;
		$SQL_REQUEST="SELECT $COLUMN FROM $TABLE WHERE $COLUMN = (SELECT max($COLUMN) FROM $TABLE)";
		$ROW=mysqli_fetch_assoc(SQL_EXEC($SQL_REQUEST));
		return $ROW;
	}
	
	//Durchsucht eine Tabelle.
	function SQL_SEARCH($TABLE,$SELECTOR,$SEARCH,$KEYWORD){
		global $SQL_CONNECTION;
		$SQL_REQUEST="SELECT $SELECTOR FROM $TABLE WHERE ($SEARCH LIKE '$KEYWORD')";
		return SQL_EXEC($SQL_REQUEST);
	}
	
  ////////////////////////////////////////////
 //           Logging-Funktionen           // 
////////////////////////////////////////////  
	
	//Schreibt das Log in die Tabelle.
	function LOGGING_MAIN($CONTENT){
		SQL_EXEC("UPDATE longtexts SET text=concat(text, \"".$CONTENT."\n\") WHERE longtexts.name=\"log\";");
	}
	
	//Formatiert die Logging-Eingabe.
	function LOGGING_PUSH($TYPE,$MESSAGE){
		LOGGING_MAIN("[" . date("Y-m-d H:i:s") . "/" . strtoupper($TYPE) . "]: " . $MESSAGE);
	}
	
	//Ließt das Log aus.
	function LOGGING_GETCNT(){
		return SQL_READ("longtexts","name,text","name","log")["text"];
	}
	
	//Löscht das Log.
	function LOGGING_CLEAR(){
		SQL_EXEC("UPDATE longtexts SET text=\"\" WHERE longtexts.name=\"log\";");
	}
	
	//Exportiert das Log.
	function LOGGING_EXPORT(){
		$FILENAME="webLog".date("YmdHis")."-".hash("md5", date("Y-m-d H:i:s")).".log";
		$FILEPATH="./media/".$FILENAME;
		if(file_exists($FILEPATH)) {
			unlink($FILEPATH);
		}
		file_put_contents($FILEPATH, LOGGING_GETCNT());
		return $FILEPATH;
	}
	
  //////////////////////////////////////////////
 //           Fachschaften-Utility           // 
//////////////////////////////////////////////  
	
	//Gibt die Beschreibung der Fachschaften zurück.
	function FACHSCHAFTEN_GETCNT(){
		return SQL_READ("longtexts","name,text","name","fachschaften")["text"];
	}
	
	//Name des Schulleiters.
	function FACHSCHAFTEN_GETTIER1(){
		return SQL_READ("longtexts","name,text","name","tier1")["text"];
	}
	
	//Name des stellv. Schulleiters.
	function FACHSCHAFTEN_GETTIER2(){
		return SQL_READ("longtexts","name,text","name","tier2")["text"];
	}
	
	//Name des Oberstufenkoordinators.
	function FACHSCHAFTEN_GETTIER3(){
		return SQL_READ("longtexts","name,text","name","tier3")["text"];
	}
	
  ///////////////////////////////////////////////////////////////
 //           Basic-Utility und Berechtigungssystem           // 
///////////////////////////////////////////////////////////////  
	
	//Filtert Sonderzeichen aus einer Zeichenfolge heraus.
	function SIMPLIFY_STRING($STR) {
		return preg_replace("/[^a-z0-9 .]/i","",$STR);
	}
	
	//Erstellt einen Eintrag im Berechtigungssystem und gibt die ID zurück.
	function PERMSYS_MAKE_ENTRY($PUBLIC,$AUTH,$GROUP,$GROUPID) {
		$NEW_ID=SQL_MAXVALUE("permissions","id")["id"]+1;
		SQL_ADD("permissions","id,owner,public,auth,groupperm,groupid","$NEW_ID,".GET_USER_ID().",'$PUBLIC','$AUTH','$GROUP',$GROUPID");
		return $NEW_ID;
	}
	
	//Aktualisiert einen Eintrag im Berechtigungssystem.
	function PERMSYS_UPDATE($PID,$PUBLIC,$AUTH,$GROUP,$GROUP_ID) {
		SQL_CHANGE_MULTIPLE("permissions","id",$PID,"public='$PUBLIC',auth='$AUTH',groupperm='$GROUP',groupid='$GROUP_ID'");
	}
	
	//Löscht einen Eintrag aus dem Berechtigungssystem.
	function PERMSYS_DELETE($PID) {
		SQL_DELETE("permissions","id",$PID);
	}
	
  //////////////////////////////////////
 //           Modulklassen           // 
//////////////////////////////////////  
	
	//Modul: Tabelle.
	class MODULE_TABLE {
		//Konstruktor.
		public function __construct($TABLE_HEADER,$TABLE_IS_PAGE=true) {
			$this->HEADER=explode("|",$TABLE_HEADER);
			
			$this->SOURCE=new CODE_OBJECT();
			$this->SOURCE->ADD_SOURCE("<tr>");
			
			$this->TABLE_IS_PAGE=$TABLE_IS_PAGE;
			
			for($i=0;$i<count($this->HEADER);$i++){
				$this->SOURCE->ADD_SOURCE("<td class=\"top\">".$this->HEADER[$i]."</td>");
			}
			$this->SOURCE->ADD_SOURCE("</tr>");
		}
		
		//Fügt Tabellenzeile hinzu.
		public function ADD_LINE($LINE) {
			$ARR=explode("|",$LINE);
			
			$this->SOURCE->ADD_SOURCE("<tr>");
			for($i=0;$i<count($ARR);$i++){
				$this->SOURCE->ADD_SOURCE("<td>".$ARR[$i]."</td>");
			}
			$this->SOURCE->ADD_SOURCE("</tr>");
		}
		
		//Liefert den fertigen Quelltext der Tabelle.
		public function GET_SOURCE() {
			if($this->TABLE_IS_PAGE){
				return "<table class=\"page\">".$this->SOURCE->GET_SOURCE()."</table>";
			}else{
				return "<table>".$this->SOURCE->GET_SOURCE()."</table>";
			}
		}
	}
	
	//Modul: Buttonliste.
	class MODULE_BUTTONLIST {
		//Konstruktor.
		public function __construct() {
			$this->SOURCE=new CODE_OBJECT();
		}
		
		//Navigationsbutton.
		public function ADD_BUTTON_NAVIGATION($LABEL,$NAVIGATION) {
			$this->SOURCE->ADD_SOURCE("<button class=\"buttonlist\" onClick=\"location.href='$NAVIGATION'\">$LABEL</button>");
		}
		
		//Button mit SelectBox.
		public function ADD_BUTTON_SELECTION($LABEL,$MESSAGE,$ITEMS,$ITEM_VALUES,$NAVIGATION) {
			$this->SOURCE->ADD_SOURCE("<button class=\"buttonlist\" onClick=\"dpBoxSelection('".$LABEL."','".$MESSAGE."','".$ITEMS."','".$ITEM_VALUES."','selection',function(){jsNav('".$NAVIGATION."'+$('#box-input-selection').val());});\">$LABEL</button>");
		}
		
		//Button mit Textbox.
		public function ADD_BUTTON_TEXT($LABEL,$MESSAGE,$NAVIGATION) {
			$this->SOURCE->ADD_SOURCE("<button class=\"buttonlist\" onClick=\"dpBoxText('$LABEL','$MESSAGE','Eingabe','text',function(){jsNav('".$NAVIGATION."'+$('#box-input-text').val());});\">$LABEL</button>");
		}
		
		//Button mit ConfirmationBox.
		public function ADD_BUTTON_CONFIRMATION($LABEL,$MESSAGE,$NAVIGATION) {
			$this->SOURCE->ADD_SOURCE("<button class=\"buttonlist\" onClick=\"dpBoxConfirm('$LABEL','$MESSAGE',function(){jsNav('$NAVIGATION');});\">$LABEL</button>");
		}
		
		//Button mit externem JS-Popup.
		public function ADD_BUTTON_JSWINDOW($LABEL,$NAME,$SOURCE) {
			$this->SOURCE->ADD_SOURCE("<button class=\"buttonlist\" onClick=\"createWindowWithSource('$NAME','$SOURCE')\">$LABEL</button>");
		}
		
		//Button für Kalenderereignis.
		public function ADD_BUTTON_CALENDAR_SINGLE($LABEL,$NAVIGATION,$CATEGORIES,$CATEGORIES_VALS,$PERMISSIONS,$PERMISSIONS_VALS,$USERGROUPS,$USERGROUPS_VALS) {
			$this->SOURCE->ADD_SOURCE("<button class=\"buttonlist\" onClick=\"dpBoxCalendarSingle('Ereignis hinzuf&uuml;gen','".$CATEGORIES."','".$CATEGORIES_VALS."','".$PERMISSIONS."','".$PERMISSIONS_VALS."','".$USERGROUPS."','".$USERGROUPS_VALS."','',function(){jsNav('".$NAVIGATION."&func_add=' + $('#box-input-date').val() + '&event=' + $('#box-input-event').val() + '&time=' + $('#box-input-time').val() + '&category=' + $('#box-input-category').val() + '&perm_public=' + $('#box-input-perm-public').val() + '&perm_auth=' + $('#box-input-perm-auth').val() + '&group=' + $('#box-input-perm-groupname').val() + '&perm_group=' + $('#box-input-perm-group').val());});\">$LABEL</button>");
		}
		
		//Button für Kalenderereignis (mehrtägig).
		public function ADD_BUTTON_CALENDAR_MULTI($LABEL,$NAVIGATION,$CATEGORIES,$CATEGORIES_VALS,$PERMISSIONS,$PERMISSIONS_VALS,$USERGROUPS,$USERGROUPS_VALS) {
			$this->SOURCE->ADD_SOURCE("<button class=\"buttonlist\" onClick=\"dpBoxCalendarMulti('Mehrt&auml;giges Ereignis hinzuf&uuml;gen','".$CATEGORIES."','".$CATEGORIES_VALS."','".$PERMISSIONS."','".$PERMISSIONS_VALS."','".$USERGROUPS."','".$USERGROUPS_VALS."',function(){jsNav('".$NAVIGATION."&func_addmultiple=' + $('#box-input-date-start').val() + '&enddate=' + $('#box-input-date-end').val() + '&event=' + $('#box-input-event').val() + '&category=' + $('#box-input-category').val() + '&perm_public=' + $('#box-input-perm-public').val() + '&perm_auth=' + $('#box-input-perm-auth').val() + '&group=' + $('#box-input-perm-groupname').val() + '&perm_group=' + $('#box-input-perm-group').val());});\">$LABEL</button>");
		}
		
		//Button für Kalenderkategorie.
		public function ADD_BUTTON_CALENDAR_CATEGORY($LABEL,$NAVIGATION) {
			$this->SOURCE->ADD_SOURCE("<button class=\"buttonlist\" onClick=\"dpBoxCalendarCategory('".$LABEL."','Neue Kategorie','#202020',function(){jsNav('".$NAVIGATION."&func_addcategory=' + $('#box-input-name').val() + '&color=' + $('#box-input-color').val().substring(1,7));});\">$LABEL</button>");
		}
		
		//Button für Passwortänderung.
		public function ADD_BUTTON_PASSWORD($LABEL,$NAVIGATION) {
			$this->SOURCE->ADD_SOURCE("<button class=\"buttonlist\" onClick=\"dpBoxPassword(function(){var box_pw=$('#box-input-password').val();var box_pwr=$('#box-input-passwordrepeat').val();if(box_pw==box_pwr && box_pw!=''){jsNav('".$NAVIGATION."'+encodeURIComponent(box_pw));}});\">$LABEL</button>");
		}
		
		//Button zum Laden des Stundenplans.
		public function ADD_BUTTON_SCHEDULE($LABEL,$DATE,$CL,$CO) {
			$this->SOURCE->ADD_SOURCE("<button class=\"buttonlist\" onClick=\"getScheduleData('$DATE','".$CL."','".$CO."');\">$LABEL</button>");
		}
		
		//Button mit UploadBox.
		public function ADD_BUTTON_UPLOAD($LABEL,$TITLE,$MESSAGE,$UPLOADURI,$NAVIGATION=null) {
			if($NAVIGATION == null) {
				$this->SOURCE->ADD_SOURCE("<button class=\"buttonlist\" onClick=\"dpBoxUpload('".$TITLE."','".$MESSAGE."','".$UPLOADURI."',function(){});\">$LABEL</button>");
			} else {
				$this->SOURCE->ADD_SOURCE("<button class=\"buttonlist\" onClick=\"dpBoxUpload('".$TITLE."','".$MESSAGE."','".$UPLOADURI."',function(){jsNav('".$NAVIGATION."');});\">$LABEL</button>");
			}
		}
		
		//Zusätzlichen Quelltext angehängen.
		public function ADD_CUSTOMSOURCE($SOURCE) {
			$this->SOURCE->ADD_SOURCE($SOURCE);
		}
		
		//Fertigen Quelltext zurückgeben.
		public function GET_SOURCE() {
			return "<div class=\"centered\">".$this->SOURCE->GET_SOURCE()."</div>";
		}
	}
	
	//Modul: Formulare.
	class MODULE_FORM {
		//Konstruktor.
		public function __construct($SUBMIT,$METHOD="post",$ISMULTIPART=false) {
			$this->SOURCE=new CODE_OBJECT();
			$this->SUBMIT=$SUBMIT;
			$this->METHOD=$METHOD;
			$this->ISMULTIPART=$ISMULTIPART;
		}
		
		//Fertigen Quelltext zurückgeben.
		public function GET_SOURCE() {
			if($this->ISMULTIPART){
				return "<form action=\"".$this->SUBMIT."\" method=\"".$this->METHOD."\" enctype=\"multipart/form-data\">".$this->SOURCE->GET_SOURCE()."</form>";
			}else{
				return "<form action=\"".$this->SUBMIT."\" method=\"".$this->METHOD."\">".$this->SOURCE->GET_SOURCE()."</form>";
			}
		}
		
		//Zusätzlichen Quelltext angehängen.
		public function ADD_CUSTOMSOURCE($SOURCE) {
			$this->SOURCE->ADD_SOURCE($SOURCE);
		}
		
		//Textinput-Feld.
		public function ADD_INPUT_TEXT($CAPTION,$NAME,$READONLY=false,$STANDARD="") {
			if($READONLY){
				$this->SOURCE->ADD_SOURCE("<label>".$CAPTION." (nicht ver&auml;nderbar):</label>");
				$this->SOURCE->ADD_SOURCE("<input readonly type=\"text\" name=\"".$NAME."\" value=\"".$STANDARD."\"/>");
			}else{
				$this->SOURCE->ADD_SOURCE("<label>".$CAPTION.":</label>");
				$this->SOURCE->ADD_SOURCE("<input type=\"text\" name=\"".$NAME."\" value=\"".$STANDARD."\"/>");
			}
		}
		
		//Zahleninput.
		public function ADD_INPUT_NUMBER($CAPTION,$NAME,$READONLY=false,$STANDARD="") {
			if($READONLY){
				$this->SOURCE->ADD_SOURCE("<label>".$CAPTION." (nicht ver&auml;nderbar):</label>");
				$this->SOURCE->ADD_SOURCE("<input readonly type=\"number\" name=\"".$NAME."\" value=\"".$STANDARD."\"/>");
			}else{
				$this->SOURCE->ADD_SOURCE("<label>".$CAPTION.":</label>");
				$this->SOURCE->ADD_SOURCE("<input type=\"number\" name=\"".$NAME."\" value=\"".$STANDARD."\"/>");
			}
		}
		
		//Passwort-Feld.
		public function ADD_INPUT_PASSWORD($CAPTION,$NAME,$READONLY=false,$STANDARD="") {
			if($READONLY){
				$this->SOURCE->ADD_SOURCE("<label>".$CAPTION." (nicht ver&auml;nderbar):</label>");
				$this->SOURCE->ADD_SOURCE("<input readonly type=\"password\" name=\"".$NAME."\" value=\"".$STANDARD."\"/>");
			}else{
				$this->SOURCE->ADD_SOURCE("<label>".$CAPTION.":</label>");
				$this->SOURCE->ADD_SOURCE("<input type=\"password\" name=\"".$NAME."\" value=\"".$STANDARD."\"/>");
			}
		}
		
		//Datumseingabe.
		public function ADD_INPUT_DATE($CAPTION,$NAME) {
			$this->SOURCE->ADD_SOURCE("<label>".$CAPTION.":</label>");
			$this->SOURCE->ADD_SOURCE("<input type=\"date\" name=\"".$NAME."\"/>");
		}
		
		//Textfeld-Eingabe.
		public function ADD_INPUT_TEXTAREA($CAPTION,$NAME,$STANDARD="") {
			$this->SOURCE->ADD_SOURCE("<label>".$CAPTION.":</label>");
			$this->SOURCE->ADD_SOURCE("<textarea name=\"".$NAME."\">$STANDARD</textarea>");
		}
		
		//Select-Input.
		public function ADD_INPUT_SELECT($CAPTION,$NAME,$ITEMS,$ITEM_VALUES,$STANDARD=-1) {
			$this->SOURCE->ADD_SOURCE("<label>".$CAPTION.":</label>");
			$this->SOURCE->ADD_SOURCE("<select name=\"".$NAME."\">");
			if($STANDARD==-1){
				$this->SOURCE->ADD_SOURCE("<option disabled selected>Bitte ausw&auml;hlen</option>");
			}
			$arr=explode("|",$ITEMS);
			$arr_vals=explode("|",$ITEM_VALUES);
			for($i=0;$i<count($arr);$i++){
				if($STANDARD==$i){
					$this->SOURCE->ADD_SOURCE("<option value=\"".$arr_vals[$i]."\" selected>".$arr[$i]."</option>");
				}else{
					$this->SOURCE->ADD_SOURCE("<option value=\"".$arr_vals[$i]."\">".$arr[$i]."</option>");
				}
			}
			$this->SOURCE->ADD_SOURCE("</select>");
		}
		
		//Select-Input aus SQL-Tabelle generiert.
		public function ADD_INPUT_SELECT_MYSQL($CAPTION,$NAME,$TABLE,$PARAM1,$PARAM2,$PARAM1VALUE,$DISPLAY=true) {
			$ITEM_VALS;
			$ITEMS;
			$STANDARD;
			
			//SQL-Abfrage:
			$SQL_QUERY=SQL_READLOOP($TABLE,$PARAM1.",".$PARAM2);
			for($i=0;$ROW=mysqli_fetch_array($SQL_QUERY);$i++){
				//eventueller Standardwert wird gesetzt.
				if($ROW[$PARAM1]==$PARAM1VALUE){
					$STANDARD=$i;
				}
				
				//ItemList wird erstellt.
				$ITEM_VALS.=$ROW[$PARAM1]."|";
				$ITEMS.=$ROW[$PARAM2]."|";
			}
			$ITEM_VALS=substr($ITEM_VALS,0,-1);
			$ITEMS=substr($ITEMS,0,-1);
			
			if($DISPLAY) {
				$this->SOURCE->ADD_SOURCE("<label>".$CAPTION.":</label>");
				$this->SOURCE->ADD_SOURCE("<select name=\"".$NAME."\">");
			} else {
				$this->SOURCE->ADD_SOURCE("<label style=\"display: none;\">".$CAPTION.":</label>");
				$this->SOURCE->ADD_SOURCE("<select style=\"display: none;\" name=\"".$NAME."\">");
			}
			
			if($STANDARD==-1){
				$this->SOURCE->ADD_SOURCE("<option disabled selected>Bitte ausw&auml;hlen</option>");
			}
			$arr_vals=explode("|",$ITEM_VALS);
			$arr=explode("|",$ITEMS);
			for($i=0;$i<count($arr);$i++){
				if($STANDARD==$i){
					$this->SOURCE->ADD_SOURCE("<option value=\"".$arr_vals[$i]."\" selected>".$arr[$i]."</option>");
				}else{
					$this->SOURCE->ADD_SOURCE("<option value=\"".$arr_vals[$i]."\">".$arr[$i]."</option>");
				}
			}
			$this->SOURCE->ADD_SOURCE("</select>");
		}
		
		//Submit-Button.
		public function ADD_SUBMIT($CAPTION,$VALUE) {
			$this->SOURCE->ADD_SOURCE("<label>".$CAPTION.":</label>");
			$this->SOURCE->ADD_SOURCE("<input type=\"submit\" value=\"".$VALUE."\"/>");
		}
		
		//Navigation-Button im Formular.
		public function ADD_BUTTON_NAVIGATION($CAPTION,$VALUE,$NAVIGATION) {
			$this->SOURCE->ADD_SOURCE("<label>".$CAPTION.":</label>");
			$this->SOURCE->ADD_SOURCE("<input type=\"button\" value=\"".$VALUE."\" onClick=\"window.location.href='".$NAVIGATION."'\"/>");
		}
		
		//Button mit Passwort-Box.
		public function ADD_BUTTON_BOX_PASSWORD($CAPTION,$VALUE,$NAVIGATION) {
			$this->SOURCE->ADD_SOURCE("<label>".$CAPTION.":</label>");
			$this->SOURCE->ADD_SOURCE("<input type=\"button\" value=\"".$VALUE."\" onClick=\"dpBoxPassword(function(){var box_pw=$('#box-input-password').val();var box_pwr=$('#box-input-passwordrepeat').val();if(box_pw==box_pwr && box_pw!=''){jsNav('$NAVIGATION'+encodeURIComponent(box_pw));}});\"/>");
		}
		
		//Checkbox-Input.
		public function ADD_INPUT_CHECKBOXARRAY($CAPTION,$NAME,$VALUE) {
			$this->SOURCE->ADD_SOURCE("<input type=\"checkbox\" class=\"".$NAME."\" value=\"".$VALUE."\"/><p style=\"display: inline-block; margin: 0;\">".$CAPTION."</p><br>");
		}
		
		//Confirmation-Button im Formular.
		public function ADD_BUTTON_CONFIRMATION($CAPTION,$LABEL,$MESSAGE,$NAVIGATION) {
			$this->SOURCE->ADD_SOURCE("<label>".$CAPTION.":</label>");
			$this->SOURCE->ADD_SOURCE("<input type=\"button\" value=\"$LABEL\" onClick=\"dpBoxConfirm('$LABEL','$MESSAGE',function(){jsNav('$NAVIGATION');});\"/>");
		}
	}
?>
