/**
* CMS des GutsMuths Gymnasiums Quedlinburg
* @author Noah Wiederhold & Bernhard Birnbaum
* @copyright 2020 Noah Wiederhold & Bernhard Birnbaum
* @version 1.02.00b
* 
* box.js - Klassen und Boxpatterns für die MessageBoxen im Back- und Frontend.
*/

//Funktion zum Speichern der Auswahl im WYSIWYG-Editor.
function createRangeFromSelection() {
	//Abfrage aufgrund von Browser-Unterschieden.
	if(window.getSelection) {
		//Auswahl wird gespeichert.
		var sel = window.getSelection();
		
		//Wenn Auswahl vorhanden.
		if (sel.getRangeAt && sel.rangeCount) {
			//Auswahl wird zurückgegeben.
			return sel.getRangeAt(0);
		}
	} else if(document.selection && document.selection.createRange) {
		//Rückgabe der Auswahl.
		return document.selection.createRange();
	}
	//Ausnahme wenn keine Auswahl gefunden wurde.
	return null;
}

//Gespeicherte Auswahl wird auf die Seite angewendet.
function applySelection(range) {
	//Wenn eine Auswahl übergeben wurde.
	if(range) {
		//Abfrage aufgrund von Browser-Unterschieden.
		if(window.getSelection) {
			//Auswahl wird gespeichert.
			var sel = window.getSelection();
			
			//Auswahl wird gelöscht.
			sel.removeAllRanges();
			
			//Auswahl wird neu gesetzt.
			sel.addRange(range);
		} else if(document.selection && range.select) {
			//Auswahl setzen.
			range.select();
		}
	}
}

//Klasse für BoxPatterns (Vorlagen für den Inhalt der Box).
function BoxPattern() {
	//Variable, welche den HTML-Quelltext der Box speichert.
	this.htCode = "";
	
	//Hängt HTML-Code an den Inhalt an.
	this.appendHtCode = function(htCode) {
		this.htCode += htCode;
	}
	
	//Rückgabe des Pattern-Codes.
	this.loadPattern = function() {
		return "<form>" + this.htCode + "</form>";
	}
	
	//Text wird eingefügt.
	this.appendText = function(message) {
		this.appendHtCode("<p>" + message + "</p>");
	}
	
	//Textinput wird eingefügt.
	this.appendTextInput = function(title, name, standard = "") {
		this.appendHtCode("<label>" + title + ":</label><input type='text' id='box-input-" + name + "' value='" + standard + "'/>");
	}
	
	//Zahleninput wird eingefügt.
	this.appendNumberInput = function(title, name) {
		this.appendHtCode("<label>" + title + ":</label><input type='number' id='box-input-" + name + "'/>");
	}
	
	//Passwortinput wird eingefügt.
	this.appendPasswordInput = function(title, name) {
		this.appendHtCode("<label>" + title + ":</label><input type='password' id='box-input-" + name + "'/>");
	}
	
	//Checkboxinput wird eingefügt.
	this.appendCheckboxInput = function(title, name) {
		this.appendHtCode("<label>" + title + ":</label><input type='checkbox' id='box-input-" + name + "'/>");
	}
	
	//Selectfeld wird eingefügt.
	this.appendSelectionInput = function(title, selection, selection_values, sign, name, standard = -1) {
		//Selection-Liste wird als "sign-separated-string" übergeben und in ein Array umgewandelt.
		var tmpArr = selection.split(sign);
		
		//Dasselbe mit den "values" der Indexes.
		var tmpArrVals = selection_values.split(sign);
		
		//Variable zur temporären Speicherung des Quelltextes.
		var tmpHtCode = "";
		
		//Für alle Items des Select-Feldes:
		for(var i = 0; i < tmpArr.length; i++) {
			//Abfrage für den Standard-Wert, insofern übergeben.
			if(standard == i){
				//Option wird ergänzt (Standardauswahl).
				tmpHtCode += "<option value=\"" + tmpArrVals[i] + "\" selected>" + tmpArr[i] + "</option>";
			}else{
				//Option wird ergänzt.
				tmpHtCode += "<option value=\"" + tmpArrVals[i] + "\">" + tmpArr[i] + "</option>";
			}
		}
		
		//Fertiges Select-Element wird an die Box angehängt.
		this.appendHtCode("<label>" + title + ":</label><select id='box-input-" + name + "'><option disabled selected>Ausw&auml;hlen</option>" + tmpHtCode + "</select>");
	}
	
	//Datuminput wird eingefügt.
	this.appendDateInput = function(title, name, date = "") {
		this.appendHtCode("<label>" + title + ":</label><input type='date' id='box-input-" + name + "' value='" + date + "'/>");
	}
	
	//Timeinput wird eingefügt.
	this.appendTimeInput = function(title, name, time = "") {
		this.appendHtCode("<label>" + title + ":</label><input type='time' id='box-input-" + name + "' value='" + time + "'/>");
	}
	
	//Colorinput wird eingefügt.
	this.appendColorInput = function(title, name, std = "#000000") {
		this.appendHtCode("<label>" + title + ":</label><input value='" + std + "' type='color' id='box-input-" + name + "'/>");
	}
	
	//Fileupload wird angehängt.
	this.appendUpload = function(uploadUri, eventDone) {
		//Labels.
		this.appendHtCode("<label>Datei ausw&auml;hlen:</label><input type='file' name='attachements[]' id='fileupload'/>");
		this.appendHtCode("<label id='progress_text'>Fortschritt:</label>");
		
		//Upload-Script.
		this.appendHtCode("<script>\$(function(){");
		
		//FileUpload-Plugin.
		this.appendHtCode("\$('#fileupload').fileupload({");
		
		//Server-URL wird übergeben.
		this.appendHtCode("url:'"+uploadUri+"',");
		
		//Return-Type der AJAX-Anfrage.
		this.appendHtCode("dataType:'json'");
		
		//Progress-Event.
		this.appendHtCode("}).on('fileuploadprogressall',function(e,data){var progress=((data.loaded/data.total)*100).toFixed(2);$('#progress_text').html('Fortschritt: '+progress+'%');");
		this.appendHtCode("if(progress==100){$('#progress_text').html('Fortschritt: Bitte warten...');}");
		
		//Wenn Datei ausgewählt.
		this.appendHtCode("}).on('fileuploadadd',function(e,data){$('#fileupload').attr('disabled','true');$('#box-abort').attr('disabled','true');$('#box-close').attr('disabled','true');data.submit();");
		
		//Wenn Upload fertig.
		this.appendHtCode("}).on('fileuploaddone',function(e,data){");
		this.appendHtCode("$('#box-abort').attr('disabled','false');$('#box-abort').trigger('click');advBox.hideBox();");
		
		//Wenn Upload gescheitert.
		this.appendHtCode("}).on('fileuploadfail',function(e,data){");
		this.appendHtCode("console.log(data.jqXHR.responseJSON.message);advBox.hideBox();");
		this.appendHtCode("});});</script>");
	}
}

//Box-Klasse für MessageBoxen.
function Box() {
	//Variable, die den HTML-Code speichert.
	this.htCode = "";
	
	//Selection-Speicherung.
	this.selRange = null;
	
	//Box ist offen?
	this.isOpen = false;
	
	//HTML-Code der Buttons.
	this.buttons = "";
	
	//Einmalige Initialisierung der Box-Elemente.
	this.init = function() {
		$("#box").css("display", "none");
		$("#box").html("<div id=\"box-overlay\"></div><div id=\"box-main\"></div>");
	}
	
	//Box ausblenden.
	this.hideBox = function() {
		//Gespeicherte Auswahl wird angewendet.
		applySelection(advBox.selRange);
		
		//Gespeicherte Auswahl wird gelöscht.
		advBox.selRange = null;
		
		//Box wird ausgeblendet.
		$("#box").fadeOut(250, function() {
			//Seite wird wieder zum Scrollen freigegeben.
			$("body").css("overflow", "visible");
			
			//Reinitialisierung.
			advBox.init();
			
			//Statusvariable wird neu gesetzt.
			advBox.isOpen = false;
		});
	}
	
	//Box einblenden (confirmEvent = Callback).
	this.showBox = function(titleString, confirmEvent) {
		//Timeout, falls eine Box gerade noch offen ist.
		var timeOut = 0;
		if(advBox.isOpen) {
			timeOut = 275;
		}
		
		//Warten, bis Animation abgeschlossen ist.
		setTimeout(function() {
			//Statusvariable wird neu gesetzt.
			advBox.isOpen = true;
			
			//Auswahl wird gespeichert.
			advBox.selRange = createRangeFromSelection();
			
			//Scroll-Eigenschaft der Seite wird deaktiviert.
			$("body").css("overflow", "hidden");
			
			//Box-Quelltext wird gesetzt.
			$("#box-main").html("<div id=\"box-title\">" + titleString + "<button id=\"box-close\">X</button></div>" + advBox.htCode + "<div id=\"box-controls\">" + advBox.buttons + "</div>");
			
			//Event-Listeners werden gesetzt.
			$("#box-close").on("click", function() {
				advBox.hideBox();
			});
			$("#box-abort").on("click", function() {
				advBox.hideBox();
			});
			
			//Callback.
			$("#box-confirm").click(confirmEvent);
			
			//Event-Listener für ENTER und ESCAPE.
			$(document).keydown(function(event){
				//Abfrage aufgrund von Browser-Unterschieden.
				var keyPressed = (event.keyCode ? event.keyCode : event.which);
				
				//Wenn ENTER:
				if(keyPressed == 13) {
					//Fokus wird auf Button gesetzt.
					$("#box-confirm").focus();
					
					//Buttonklick wird simuliert.
					$("#box-confirm").trigger("click");
				}
				
				//Wenn ESCAPE:
				if(keyPressed == 27) {
					//Fokus wird auf Button gesetzt.
					$("#box-close").focus();
					
					//Buttonklick wird simuliert.
					$("#box-close").trigger("click");
				}
			});
			//Box wird eingeblendet.
			$("#box").fadeIn(250);
		}, timeOut);
	}
	
	//Box einblenden, mit zwei Callbacks (confirmEvent und actionEvent = Callbacks).
	this.showBoxMulti = function(titleString, actionEvent, confirmEvent) {
		var timeOut = 0;
		if(advBox.isOpen) {
			timeOut = 275;
		}
		setTimeout(function() {
			advBox.isOpen = true;
			advBox.selRange = createRangeFromSelection();
			$("body").css("overflow", "hidden");
			$("#box-main").html("<div id=\"box-title\">" + titleString + "<button id=\"box-close\">X</button></div>" + advBox.htCode + "<div id=\"box-controls\">" + advBox.buttons + "</div>");
			$("#box-close").on("click", function() {
				advBox.hideBox();
			});
			$("#box-abort").on("click", function() {
				advBox.hideBox();
			});
			$("#box-confirm").click(confirmEvent);
			$("#box-action").click(actionEvent);
			$("#box").fadeIn(250);
		}, timeOut);
	}
	
	this.prepareBox = function(htCode) {
		this.htCode = htCode;
	}
}

//globale Instanz für MsgBoxen wird erstellt.
var advBox = new Box();

//Wenn Seite geladen, wird die Box initialisiert.
$(document).ready(function() {
	advBox.init();
});

//MessageBox: Text.
function dpBoxMessage(title, message) {
	//BoxPattern wird erstellt.
	var boxPattern = new BoxPattern();
	
	//BoxPattern wird konfiguriert.
	boxPattern.appendText(message);
	
	//MessageBox wird mit Pattern geladen.
	advBox.prepareBox(boxPattern.loadPattern());
	
	//ButtonQuelltext wird gesetzt.
	advBox.buttons = "<button id=\"box-confirm\">Okay</button>";
	
	//Box wird angezeigt.
	advBox.showBox(title, function(){ advBox.hideBox(); });
}

//MessageBox: Confirm.
function dpBoxConfirm(title, message, eventConfirm) {
	var boxPattern = new BoxPattern();
	boxPattern.appendText(message);
	advBox.prepareBox(boxPattern.loadPattern());
	advBox.buttons = "<button id=\"box-confirm\">Best&auml;tigen</button><button id=\"box-abort\">Abbrechen</button>";
	advBox.showBox(title, eventConfirm);
}

//MessageBox: Inputtext.
function dpBoxText(title, message, prompt, name, eventConfirm) {
	var boxPattern = new BoxPattern();
	boxPattern.appendText(message);
	boxPattern.appendTextInput(prompt, name);
	advBox.prepareBox(boxPattern.loadPattern());
	advBox.buttons = "<button id=\"box-confirm\">Best&auml;tigen</button><button id=\"box-abort\">Abbrechen</button>";
	advBox.showBox(title, eventConfirm);
}

//MessageBox: Selection.
function dpBoxSelection(title, message, selection, selection_values, name, eventConfirm) {
	var boxPattern = new BoxPattern();
	boxPattern.appendText(message);
	boxPattern.appendSelectionInput("Auswahl", selection, selection_values, ";", name);
	advBox.prepareBox(boxPattern.loadPattern());
	advBox.buttons = "<button id=\"box-confirm\">Best&auml;tigen</button><button id=\"box-abort\">Abbrechen</button>";
	advBox.showBox(title, eventConfirm);
}

//MessageBox: Upload.
function dpBoxUpload(title, message, uploadUri, eventDone) {
	var boxPattern = new BoxPattern();
	boxPattern.appendText(message);
	boxPattern.appendUpload(uploadUri, eventDone);
	advBox.prepareBox(boxPattern.loadPattern());
	advBox.buttons = "<button id=\"box-abort\">Abbrechen</button>";
	advBox.showBox(title, eventDone);
	setTimeout(function(){
		$("#box-abort").click(eventDone);
	}, 300);
}

//MessageBox: Passwortänderung.
function dpBoxPassword(eventConfirm) {
	var boxPattern = new BoxPattern();
	boxPattern.appendText("Das neue Passwort eingeben:");
	boxPattern.appendPasswordInput("Neues Passwort", "password")
	boxPattern.appendPasswordInput("Passwort wiederholen", "passwordrepeat")
	advBox.prepareBox(boxPattern.loadPattern());
	advBox.buttons = "<button id=\"box-confirm\">Best&auml;tigen</button><button id=\"box-abort\">Abbrechen</button>";
	advBox.showBox("Neues Passwort setzen", eventConfirm);
}

//MessageBox: Kalender - Eintrag.
function dpBoxCalendarSingle(title, categories, categories_vals, permissions, permissions_vals, usergroups, usergroups_vals, date, eventConfirm) {
	var boxPattern = new BoxPattern();
	boxPattern.appendTextInput("Ereignis", "event", "Neues Ereignis");
	boxPattern.appendDateInput("Datum", "date", date);
	boxPattern.appendTimeInput("Zeit", "time");
	boxPattern.appendSelectionInput("Kategorie", categories, categories_vals, ";", "category", 0);
	boxPattern.appendSelectionInput("&Ouml;ffentliche Nutzerberechtigung", permissions, permissions_vals, ";", "perm-public", 3);
	boxPattern.appendSelectionInput("Authentifizierte Nutzerberechtigung", permissions, permissions_vals, ";", "perm-auth", 2);
	boxPattern.appendSelectionInput("Spezielle Gruppe", usergroups, usergroups_vals, ";", "perm-groupname", 0);
	boxPattern.appendSelectionInput("Spezielle Gruppeberechtigung", permissions, permissions_vals, ";", "perm-group", 0);
	advBox.prepareBox(boxPattern.loadPattern());
	advBox.buttons = "<button id=\"box-confirm\">Best&auml;tigen</button><button id=\"box-abort\">Abbrechen</button>";
	advBox.showBox(title, eventConfirm);
}

//MessageBox: Kalender - Eintrag (mehrtägig).
function dpBoxCalendarMulti(title, categories, categories_vals, permissions, permissions_vals, usergroups, usergroups_vals, eventConfirm) {
	var boxPattern = new BoxPattern();
	boxPattern.appendTextInput("mehrt&auml;giges Ereignis", "event", "Neues Ereignis");
	boxPattern.appendDateInput("Startdatum", "date-start");
	boxPattern.appendDateInput("Enddatum", "date-end");
	boxPattern.appendHtCode("<script>$('#box-input-date-start').on('change',function(){$('#box-input-date-end').val($('#box-input-date-start').val());});</script>");
	boxPattern.appendSelectionInput("Kategorie", categories, categories_vals, ";", "category", 0);
	boxPattern.appendSelectionInput("&Ouml;ffentliche Nutzerberechtigung", permissions, permissions_vals, ";", "perm-public", 3);
	boxPattern.appendSelectionInput("Authentifizierte Nutzerberechtigung", permissions, permissions_vals, ";", "perm-auth", 2);
	boxPattern.appendSelectionInput("Spezielle Gruppe", usergroups, usergroups_vals, ";", "perm-groupname", 0);
	boxPattern.appendSelectionInput("Spezielle Gruppeberechtigung", permissions, permissions_vals, ";", "perm-group", 0);
	advBox.prepareBox(boxPattern.loadPattern());
	advBox.buttons = "<button id=\"box-confirm\">Best&auml;tigen</button><button id=\"box-abort\">Abbrechen</button>";
	advBox.showBox(title, eventConfirm);
}

//MessageBox: Kalender - Kategorie.
function dpBoxCalendarCategory(title, stdtext, stdcolor, eventConfirm) {
	var boxPattern = new BoxPattern();
	boxPattern.appendTextInput("Name", "name", stdtext);
	boxPattern.appendColorInput("Farbe", "color", stdcolor);
	advBox.prepareBox(boxPattern.loadPattern());
	advBox.buttons = "<button id=\"box-confirm\">Best&auml;tigen</button><button id=\"box-abort\">Abbrechen</button>";
	advBox.showBox(title, eventConfirm);
}

//MessageBox: Kalender - Eintrag bearbeiten.
function dpBoxCalendarEdit(dpDelBtn, name, categories, categories_vals, permissions, permissions_vals, usergroups, usergroups_vals, actionEvent, eventConfirm) {
	var boxPattern = new BoxPattern();
	
	boxPattern.appendTextInput("Name", "event", name);
	boxPattern.appendSelectionInput("Kategorie", categories, categories_vals, ";", "category");
	boxPattern.appendSelectionInput("&Ouml;ffentliche Nutzerberechtigung", permissions, permissions_vals, ";", "perm-public");
	boxPattern.appendSelectionInput("Authentifizierte Nutzerberechtigung", permissions, permissions_vals, ";", "perm-auth");
	boxPattern.appendSelectionInput("Spezielle Gruppe", usergroups, usergroups_vals, ";", "perm-groupname");
	boxPattern.appendSelectionInput("Spezielle Gruppeberechtigung", permissions, permissions_vals, ";", "perm-group");
	
	advBox.prepareBox(boxPattern.loadPattern());
	if(dpDelBtn){
		advBox.buttons = "<button id=\"box-confirm\">Best&auml;tigen</button><button id=\"box-abort\">Abbrechen</button><button id=\"box-action\">Event l&ouml;schen</button>";
	}else{
		advBox.buttons = "<button id=\"box-confirm\">Best&auml;tigen</button><button id=\"box-abort\">Abbrechen</button>";
	}
	advBox.showBoxMulti("Ereignis neu konfigurieren", actionEvent, eventConfirm);
}

//MessageBox: Editor - Link Einbettung.
function dpBoxEditorLink(getterPages, getterMedia, eventConfirm) {
	var boxPattern = new BoxPattern();
	boxPattern.appendHtCode("<label>Link-Typ:</label><select id='box-input-type'><option disabled selected>Ausw&auml;hlen</option><option value='type-external'>Externe Seite</option><option value='type-internal'>Interne Seite</option><option value='type-media'>Datei</option></select><div id='box-link'></div>");
	boxPattern.appendHtCode("<script>$('#box-input-type').on('change',function(){");
	//Abfrage des Link-Typs: Externer Link
	boxPattern.appendHtCode("if($(this).val()=='type-external'){");
	boxPattern.appendHtCode("$('#box-link').html('<label>externer Link:</label><input type=\"text\" id=\"box-input-link\" placeholder=\"Link hier einf&uuml;gen...\"/>');");
	boxPattern.appendHtCode("}");
	//Abfrage des Link-Typs: Interner Link
	boxPattern.appendHtCode("if($(this).val()=='type-internal'){");
	boxPattern.appendHtCode("$('#box-link').html('<label>interne Seite:</label><select id=\"box-input-internal\"><option disabled selected>Ausw&auml;hlen</option>");
	
	//AJAX-Abfrage für Seitenverlinkung.
	$.ajax({
		url: getterPages,
		cache: false,
		async: false,
		success: function(data){
			//Auswertung der JSON-Daten.
			for(var i = 0; i < data.pages.length; i++) {
				var pName = data.pages[i].page;
				var pTitle = data.pages[i].title;
				//Anhängen an das BoxPattern.
				boxPattern.appendHtCode("<option value=\"" + pName + "\">" + pTitle + "</option>");
			}
		},
		error: function(x,y,errorThrown){
			advBox.hideBox();
			alert("Beim Laden der Seiten trat ein Fehler auf.\nFehler-Code: AJAX#" + errorThrown.replace(" ",""));
		}
	});
	boxPattern.appendHtCode("</select>');");
	boxPattern.appendHtCode("}");
	//Abfrage des Link-Typs: Medienlink
	boxPattern.appendHtCode("if($(this).val()=='type-media'){");
	boxPattern.appendHtCode("$('#box-link').html('<label>Datei w&auml;hlen:</label><select id=\"box-input-media\"><option disabled selected>Ausw&auml;hlen</option>");
	
	//AJAX-Abfrage für Dateiverlinkung.
	$.ajax({
		url: getterMedia,
		cache: false,
		async: false,
		success: function(data){
			//Auswertung der JSON-Daten.
			for(var i = 0; i < data.media.length; i++) {
				var mName = data.media[i].filename;
				var mPath = data.media[i].path;
				//Anhängen an das BoxPattern.
				boxPattern.appendHtCode("<option value=\"" + mPath + "\">" + mName + "</option>");
			}
		},
		error: function(x,y,errorThrown){
			advBox.hideBox();
			alert("Beim Laden der Medien trat ein Fehler auf.\nFehler-Code: AJAX#" + errorThrown.replace(" ",""));
		}
	});
	boxPattern.appendHtCode("</select>');");
	boxPattern.appendHtCode("}");
	boxPattern.appendHtCode("});</script>");
	
	advBox.prepareBox(boxPattern.loadPattern());
	advBox.buttons = "<button id=\"box-confirm\">Best&auml;tigen</button><button id=\"box-abort\">Abbrechen</button>";
	advBox.showBox("Link einf&uuml;gen", eventConfirm);
}

//Globale Variable für Funktionsübergreifende JSON-Datenübertragung.
var editorGlobal = null;

//Auswerten der JSON-Daten der Medienliste.
function prepItemsMediaBrowser(searchParam = "") {
	var data = editorGlobal;
	
	var htCodeList = "<ul>";
	
	//Auswertung der JSON-Daten unter Einbezug des Suchparameters.
	for(var i = 0; i < data.media.length; i++) {
		if(searchParam == "") {
			htCodeList += "<li onClick=\"$('#box-browser-preview').attr('src', '" + data.media[i].path + "');\">" + data.media[i].filename + " <span>vom " + data.media[i].date + " " + data.media[i].time + "</span></li>";
		} else {
			if(data.media[i].id == searchParam || data.media[i].date.includes(searchParam) || data.media[i].time.includes(searchParam) || data.media[i].path.toLowerCase().includes(searchParam)) {
				htCodeList += "<li onClick=\"$('#box-browser-preview').attr('src', '" + data.media[i].path + "');\">" + data.media[i].filename + " <span>vom " + data.media[i].date + " " + data.media[i].time + "</span></li>";
			}
		}
	}
	htCodeList += "</ul>";
	$("#box-browser-data").html(htCodeList);
}

//MessageBox: Editor - MediaBrowser.
function dpBoxEditorMediaBrowser(inputLink, eventConfirm) {
	$.ajax({
		url: inputLink,
		cache: false,
		success: function(data, textStatus, jqXHR) {
			editorGlobal = data;
			var boxPattern = new BoxPattern();
			boxPattern.appendHtCode("<label>Medien suchen:</label><input onInput=\"searchForMedia();\" type=\"text\" id=\"box-browser-search\"/>");
			boxPattern.appendHtCode("<label>Gr&ouml;&szlig;e:</label><input type='text' id='box-browser-size' value=\"50\"/>");
			
			boxPattern.appendHtCode("<div id=\"box-browser-data\"></div>");
			boxPattern.appendHtCode("<iframe id=\"box-browser-preview\"/>");
			
			advBox.prepareBox(boxPattern.loadPattern());
			advBox.buttons = "<button id=\"box-confirm\">Best&auml;tigen</button><button id=\"box-abort\">Abbrechen</button>";
			advBox.showBox("Medien einbinden", eventConfirm);
			
			setTimeout(function() {
				prepItemsMediaBrowser();
			}, 300);
		},
		error: function(jqXHR, textStatus, errorThrown) {
			advBox.hideBox();
			alert("Beim Laden der Medien trat ein Fehler auf.\nFehler-Code: AJAX#" + errorThrown.replace(" ",""));
		}
	});
}

//MessageBox: Editor - Tabelle.
function dpBoxEditorTable(eventConfirm) {
	var boxPattern = new BoxPattern();
	boxPattern.appendNumberInput("Anzahl Spalten", "columns");
	boxPattern.appendNumberInput("Anzahl Zeilen", "rows");
	advBox.prepareBox(boxPattern.loadPattern());
	advBox.buttons = "<button id=\"box-confirm\">Best&auml;tigen</button><button id=\"box-abort\">Abbrechen</button>";
	advBox.showBox("Tabelle einf&uuml;gen", eventConfirm);
}