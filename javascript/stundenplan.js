/**
* CMS des GutsMuths Gymnasiums Quedlinburg
* @author Noah Wiederhold & Bernhard Birnbaum
* @copyright 2020 Noah Wiederhold & Bernhard Birnbaum
* @version 1.02.00b
* 
* stundenplan.js - XML-Parsing und Stundenplananzeige.
*/

//Quelltext in die Tabelle einfügen.
function appendSchedule(htCode) {
	$("#stundenplan").append(htCode);
}

//Tabellenzeile wird formatiert als HTML-Code ausgegeben.
function appendScheduleLine(planSubjectChanged, planTeacherChanged, planRoomChanged, planHour, planSubject, planTeacher, planRoom, planInformation) {
	if(planSubjectChanged) {
		appendSchedule("<tr><td>" + planHour + "</td><td class='marked'>" + planSubject + "</td><td>" + planTeacher + "</td><td>" + planRoom + "</td><td>" + planInformation + "</td></tr>");
	} else if(planTeacherChanged) {
		appendSchedule("<tr><td>" + planHour + "</td><td>" + planSubject + "</td><td class='marked'>" + planTeacher + "</td><td>" + planRoom + "</td><td>" + planInformation + "</td></tr>");
	} else if(planRoomChanged) {
		appendSchedule("<tr><td>" + planHour + "</td><td>" + planSubject + "</td><td>" + planTeacher + "</td><td class='marked'>" + planRoom + "</td><td>" + planInformation + "</td></tr>");
	} else {
		appendSchedule("<tr><td>" + planHour + "</td><td>" + planSubject + "</td><td>" + planTeacher + "</td><td>" + planRoom + "</td></tr>");
	}
}

//XML-Daten werden ausgewertet und weitergegeben.
function applySchedule(xmlData, cl, co = "") {
	var parsedXml = $(xmlData);
	var timestamp = parsedXml.find("DatumPlan").text();
	$("#timestamp").html("Stundenplan f&uuml;r "+timestamp+":");
	$("#stundenplan").html("<tr><td class=\"top\">St.</td><td class=\"top\">Fach</td><td class=\"top\">Lehrer</td><td class=\"top\">Raum</td><td class=\"top\">Informationen</td></tr>");
	
	//Loop durch alle Klassen.
	parsedXml.find("Kl").each(function() {
		var xmlCl = $(this);
		//Wenn Klasse gefunden:
		if(xmlCl.find("Kurz").text() == cl) {
			//Alle Unterrichtsstunden werden durchgegangen:
			xmlCl.find("Pl").find("Std").each(function() {
				//Daten über Stunde werden ausgelesen.
				var planHour = $(this).find("St").text();
				var planSubject = $(this).find("Fa").text();
				var planSubjectChanged = $(this).find("Fa").attr("FaAe");
				var planTeacher = $(this).find("Le").text();
				var planTeacherChanged = $(this).find("Le").attr("LeAe");
				var planRoom = $(this).find("Ra").text();
				var planRoomChanged = $(this).find("Ra").attr("RaAe");
				var planInformation = $(this).find("If").text();
				
				//Abfrage wenn keine Kurse übergeben:
				if(co == "") {
					//Alle Stunden werden ausgegeben.
					appendScheduleLine(planSubjectChanged, planTeacherChanged, planRoomChanged, planHour, planSubject, planTeacher, planRoom, planInformation);
				} else {
					//Kursliste wird einbezogen.
					var clst = "";
					
					//"normale" Unterrichtseinheiten werden ausgelesen.
					xmlCl.find("Unterricht").find("Ue").each(function() {
						clst += $(this).find("UeNr").attr("UeFa") + ";";
					});
					
					//Und um die gewählten Kurse ergänzt.
					clst += co;
					
					//Loop durch alle Kurse:
					for(var i = 0; i < clst.split(";").length; i++) {
						var planCurrentSubject = clst.split(";")[i];
						
						//Wenn Kurs ist relevant:
						if(planSubject == planCurrentSubject || planInformation.startsWith(planCurrentSubject)) {
							//Stunde wird ausgegeben.
							appendScheduleLine(planSubjectChanged, planTeacherChanged, planRoomChanged, planHour, planSubject, planTeacher, planRoom, planInformation);
						}
					}
				}
			});
		}
	});
	$("#timestamp").fadeIn(250);
	$("#stundenplan").fadeIn(250);
}

//Ließt die Daten aus der XML-Datei aus.
function getScheduleData(date, cl, co = ""){
	$("#timestamp").fadeOut(250);
	$("#stundenplan").fadeOut(250, function() {
		//Url der angeforderten Daten.
		var ajaxUri = "media/stundenplan/PlanKl" + date + ".xml";
		
		//AJAX-Abfrage:
		$.ajax({
			cache: false,
			url: ajaxUri,
			success: function(xmlData) { applySchedule(xmlData, cl, co); },
			error: function(x,y,z) {
				if(z == "Not Found") {
					var d_day = date.substr(6, 2);
					var d_month = date.substr(4, 2);
					var d_year = date.substr(0, 4);
					dpBoxMessage("Plan nicht vorhanden", "Der Stundenplan f&uuml;r den " + d_day + "." + d_month + "." + d_year + " ist (noch) nicht vorhanden.");
				} else {
					dpBoxMessage("Fehler beim Laden", "Der Stundenplan konnte nicht geladen werden. Versuchen Sie es sp&auml;ter erneut. Weitere Informationen: " + z + ".");
				}
			}
		});
	});
}

//Gewählte Kurse werden in das Textfeld ergänzt.
function appendCourse(co) {
	if($("#colist").val() == "") {
		$("#colist").val($("#colist").val() + co);
	} else {
		$("#colist").val($("#colist").val() + ";" + co);
	}
}

//Überprüft alle Checkboxen auf gewählte Kurse.
function collectCourses() {
	$("#colist").val("");
	$(".colistitems:checked").each(function() {
		appendCourse($(this).val());
	});
}

//Handelt die Kurskonfigurationsseite.
function courseInit(courseList) {
	//Für alle Checkboxen:
	$(".colistitems").each(function() {
		//Bereits gewählte Kurse werden wieder als ausgewählt dargestellt.
		var courseArray = courseList.split(";");
		for(var i = 0; i < courseArray.length; i++) {
			if($(this).val() == courseArray[i]) {
				//Wenn gewählt, wird Box gecheckt.
				$(this).attr("checked", true);
			}
		}
		
		//Event-Listener zum Aktualisieren der Kurse.
		$(this).on("change", function() {
			collectCourses();
		});
	});
}