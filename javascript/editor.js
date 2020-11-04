/**
* CMS des GutsMuths Gymnasiums Quedlinburg
* @author Noah Wiederhold & Bernhard Birnbaum
* @copyright 2020 Noah Wiederhold & Bernhard Birnbaum
* @version 1.02.00b
* 
* editor.js - Implementation der Funktionsweise des WYSIWYG-Editors im Back- und Frontend.
*/

//Anwenden eines Befehls auf den gewählten Text.
const setAttribute=element=>{
	document.execCommand(element.dataset.attribute,false);
};

//Anwenden eines Befehls auf den gewählten Text inkl. eines Parameters (wird für einige Befehle benötigt).
const setAttributeParameter=element=>{
	document.execCommand(element.dataset.attribute,false,element.dataset.parameter);
};

//Zeigt den vom Editor generierten Quelltext in einer Textarea an.
function displaySource(){
	$("#canvassource").val($("#canvas").html());
}

//Zeigt den Quelltext in der Preview an (update).
function updateSource(){
	$("#canvas").html($("#canvassource").val());
}

//globale Variable für den Status der Quelltext-Textarea.
var showSourceAreaBool=false;

//Zeigt Textarea an bzw. versteckt diese.
function showSourceArea(){
	showSourceAreaBool=!showSourceAreaBool;
	if(showSourceAreaBool){
		$("#canvassource").css("height","20rem");
		$("#canvassource").css("opacity","1");
		$(".spoiler_source").attr("value","Quelltext verstecken");
	}else{
		$("#canvassource").css("height","0rem");
		$("#canvassource").css("opacity","0");
		$(".spoiler_source").attr("value","Quelltext zeigen");
	}
}

//Loop durch alle Editor-Buttons.
const editorButtons=document.getElementsByClassName("button");
for(let i=0;i<editorButtons.length;i++) {
	//Event-Listener hinzufügen.
	editorButtons[i].addEventListener("click",function(){
		//Bei Klick: JS-Funktion wird ausgeführt mit Parameter des data-Attributes.
		setAttribute(this);
	//Parameter, welcher die Verschachtelung deaktiviert.
	}, true);
}

//Aktualisiert den Quelltext.
$("#canvas").on("input", function(){
	displaySource();
});

//Aktualisiert die Preview.
$("#canvassource").on("change", function(){
	updateSource();
});

//Spezieller Button: Format
$(".type_format").on("change", function(){
	var currentIndex=parseInt($(".type_format").val());
	switch(currentIndex){
		case 1:
			document.execCommand("formatBlock",false,"<h1>");
			break;
		case 2:
			document.execCommand("formatBlock",false,"<h2>");
			break;
		case 3:
			document.execCommand("formatBlock",false,"<h3>");
			break;
		case 4:
			document.execCommand("formatBlock",false,"<div>");
			break;
		}
	$(".type_format").val(0);
});

//Spezieller Button: Color
$(".type_color").on("click", function() {
	document.execCommand("forecolor",false,$(".type_colorpicker").val());
});

//Spezieller Button: Colorpicker
$(".type_colorpicker").on("change", function() {
	$(".type_color").trigger("click");
});

//Spezieller Button: Link
$(document).on("click", ".type_link", function() {
	var objId = $(this).attr("id");
	
	//GetUri unterschiedlich (Back- und Frontend).
	var getUriX = "?page=editor&func_getpages";
	var getUriY = "?page=editor&func_getmedia";
	if(objId == "type_link_f"){
		getUriX = "?p=edit&func_getpages";
		getUriY = "?p=edit&func_getmedia";
	}
	dpBoxEditorLink(getUriX, getUriY, function() {
		var lLink = "";
		var lText = "";
		var lNewTab = false;
		var lDo = false;
		
		//Art der Verlinkung: Externer Link.
		if($("#box-input-type").val() == "type-external") {
			if($("#box-input-link").val() == "" || $("#box-input-link").val() == null) {
				dpBoxMessage("Fehler beim Verlinken", "Es muss eine Eingabe erfolgen, um eine Verlinkung zu erstellen.");
			} else {
				lLink = $("#box-input-link").val();
				lText = "externer Link";
				lNewTab = true;
				lDo = true;
			}
			
		//Art der Verlinkung: Interner Link.
		} else if($("#box-input-type").val() == "type-internal") {
			lLink = "?p=" + $("#box-input-internal").val();
			lText = $("#box-input-internal option:selected").text();
			lNewTab = false;
			lDo = true;
			
		//Art der Verlinkung: Medienlink.
		} else if($("#box-input-type").val() == "type-media") {
			lLink = $("#box-input-media").val();
			lText = $("#box-input-media option:selected").text();
			lNewTab = true;
			lDo = true;
		}
		advBox.hideBox();
		if(lDo) {
			if(lNewTab) {
				document.execCommand("insertHTML", false, "<a target='_blank' href='" + lLink + "'>" + lText + "</a>");
			} else {
				document.execCommand("insertHTML", false, "<a href='" + lLink + "'>" + lText + "</a>");
			}
		}
	});
});

//Spezieller Button: Medien
$(document).on("click", ".type_media", function(){
	var objId = $(this).attr("id");
	var getUri = "?page=editor&func_getmedia";
	if(objId == "type_media_f"){
		getUri = "?p=edit&func_getmedia";
	}
	dpBoxEditorMediaBrowser(getUri, function() {
		var MediaUrl = $("#box-browser-preview").attr("src");
		var MediaSizeX = $("#box-browser-size").val();
		var MediaSizeY = MediaSizeX * 0.5625;
		advBox.hideBox();
		if(MediaUrl == null || MediaUrl == "") {
			alert("Fehler bei der Eingabe.");
		} else {
			//Abfrage Bild ja/nein.
			if(MediaUrl.toLowerCase().endsWith(".jpg") || MediaUrl.toLowerCase().endsWith(".jpeg") || MediaUrl.toLowerCase().endsWith(".png") || MediaUrl.toLowerCase().endsWith(".gif")) {
				var MediaObject = "<img style='width: " + MediaSizeX + "%;' src='" + MediaUrl + "' alt='embedded_image'/>";
				document.execCommand("insertHTML", false, MediaObject);
			} else {
				var MediaObject = "<iframe title='embedded_file' style='width: " + MediaSizeX + "%; height: " + MediaSizeY + "%;' src='" + MediaUrl + "'/><div></div>";
				document.execCommand("insertHTML", false, MediaObject);
			}
		}
	});
});

//Suchfunktion
function searchForMedia() {
	var keyword = $("#box-browser-search").val();
	prepItemsMediaBrowser(keyword);
}

//Spezieller Button: Tabelle
$(document).on("click", ".type_table", function(){
	dpBoxEditorTable(function() {
		var Rows = parseInt($("#box-input-rows").val())+1;
		var Columns = parseInt($("#box-input-columns").val());
		advBox.hideBox();
		
		//Generierung des Quelltextes.
		var TableObject = "<table><tbody>";
		for(var i = 0; i < Rows; i++) {
			TableObject += "<tr>";
			for(var j = 0; j < Columns; j++) {
				if(i == 0) {
					TableObject += "<td class='top'></td>";
				} else {
					TableObject += "<td></td>";
				}
			}
			TableObject += "</tr>";
		}
		TableObject += "</tbody></table><div></div>";
		document.execCommand("insertHTML", false, TableObject);
	});
});

//Spezieller Button: Aufzählung
$(document).on("click", ".type_ulist", function(){
	document.execCommand("insertHTML", false, "<ul><li>neue Aufz&auml;hlung</li></ul><div></div>");
});

//Spezieller Button: Upload
$(document).on("click", ".type_upload", function(){
	var objId = $(this).attr("id");
	var getUri = "?page=editor&func_upload";
	if(objId == "type_upload_f"){
		getUri = "?p=edit&func_upload";
	}
	dpBoxUpload("Datei hochladen","Mediendatei (Bild, PDF-Dokument, ...) ausw&auml;hlen",getUri,function(){});
});