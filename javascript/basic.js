/**
* CMS des GutsMuths Gymnasiums Quedlinburg
* @author Noah Wiederhold & Bernhard Birnbaum
* @copyright 2020 Noah Wiederhold & Bernhard Birnbaum
* @version 1.02.00b
* 
* basic.js - Grundsätzliches JS für Back- und Frontend.
*/

//Erstellt ein Popup mit einem gewissen Quelltext.
function createWindowWithSource(NAME,SOURCE) {
	//Fenster wird erstellt.
	var myWindow=window.open("",NAME,"width=960,height=540");
	
	//Quelltext des Fensters wird gesetzt.
	myWindow.document.body.innerHTML=SOURCE; 
}

//Navigiert zu einem entsprechenden Link.
function jsNav(link) {
	//Navigation.
	window.location.href = link;
}

//Globale Variable für den Menüstatus (mobile).
var displayMenu = false;

//Mobile-Menü zeigen / verstecken.
function toggleMenu() {
	//Menüstatus wird geändert.
	displayMenu = !displayMenu;
	
	//Neuer Menüstatus wird ausgewertet.
	if(displayMenu) {
		//Icon auf dem Button wird umgedreht.
		$(".menubutton i").css("transform", "rotate(180deg)");
		
		//Menü wird eingeblendet.
		$("nav").fadeIn(0);
	} else {
		//Icon auf dem Button wird umgedreht.
		$(".menubutton i").css("transform", "rotate(0)");
		
		//Menü wird ausgeblendet.
		$("nav").fadeOut(0);
	}
}

function initCalendar() {
	$("#calendar_select_month").on("change", function() {
		navCalendar($("#calendar_select_year").val(), $("#calendar_select_month").val());
	});
	$("#calendar_select_year").on("change", function() {
		navCalendar($("#calendar_select_year").val(), $("#calendar_select_month").val());
	});
}

function navCalendar(year, month) {
	jsNav("?p=kalender&y="+year+"&m="+month);
}