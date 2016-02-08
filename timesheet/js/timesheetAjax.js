/* 
 *Copyright (C) 2015 delcroip <delcroip@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

var xmlTs;

function refreshTimesheet(Wlmode){
      var i;
      var xmlDoc=xmlTs;
	// extrat important info from XML
        var timesheet=xmlDoc.getElementsByTagName("timesheet");
        if (!timesheet)throw "Bad XML: no timesheet Node";
        var timestamp=timesheet[0].getAttribute('timestamp');
        var yearWeek=timesheet[0].getAttribute('yearWeek');
        var prevWeek=timesheet[0].getAttribute('prevWeek');
        var nextWeek=timesheet[0].getAttribute('nextWeek');
        var timetype=timesheet[0].getAttribute('timetype');
	var headers = xmlDoc.getElementsByTagName("headers");
        var days = xmlDoc.getElementsByTagName("days");
        var tasks=  xmlDoc.getElementsByTagName("userTs");
        //get the DOM table
	var MT=document.getElementById("timesheetTable");
        //update the header
	MT.rows[0].innerHTML=generateHeader(headers,days);
        // update the hidden param
        MT.rows[1].cells[0].innerHTML=generateHiddenParam(timestamp,yearWeek);
        
	//delete the old lines
	var idxT = document.getElementById("totalT").rowIndex;
	var idxB = document.getElementById("totalB").rowIndex;
	for (i=idxT+1; i<idxB;i++)
	{
		MT.deleteRow(idxT+1);
	}
        //generate teh task line
        for (j=0;j<tasks.length;j++)
	{
            
            for (i=0;i<tasks[j].childNodes.length;i++)    
            {
                    var CurRow = MT.insertRow(idxT+1);
                    //FIXEME mode whitelist
                    CurRow.className =(i%2==0)?'pair':'impair';
                    var rowContent=generateTaskLine(headers[0],tasks[j].childNodes[i],timetype);
                    CurRow.innerHTML=rowContent;
                    // document.getElementById("timesheetTable").innerHTML = table;
                
            }
            if(tasks.length>1){
                var nameRow=MT.insertRow(idxT+1);
                CurRow.innerHTML='<td colspan="'+headers[0].childNodes.length+'">'+tasks[j].getAttribute('userName')+'</td><td colspan="7"></td>';
            }
        }
        UpdateNavigation(nextWeek,prevWeek);
        updateAll(timetype);
        
}

function generateHeader(headers,days){
    //make the header title
        var hearderRow='';
	for( i =0; i< headers[0].childNodes.length; i++){
		hearderRow+="<th>"+headers[0].childNodes[i].childNodes[0].nodeValue+"</th>";
	}
	//make the  header day
	
	for( i =0; i< days[0].childNodes.length; i++){
		hearderRow+="<th width='60px'>"+days[0].childNodes[i].childNodes[0].nodeValue+"</th>";
	}
        return hearderRow;
}

function generateHiddenParam(timestamp,yearWeek){
    var hiddenParam='<input type="hidden" name="timestamp" value="'+timestamp+"\"/>\n";
    hiddenParam+= '<input type="hidden" name="yearWeek" value="'+yearWeek+'" />';      
    return hiddenParam;
}

function generateTaskLine(headers,task,timetype){
	var html='';
	for( i =0; i< headers.childNodes.length; i++){
		var header=headers.childNodes[i];
		var headerName=header.getAttribute('name');
		var taskdata=task.getElementsByTagName(headerName)[0];
		var link=(header.getAttribute('link')!=null)?('href="'+header.getAttribute('link')+taskdata.getAttribute('id')+'"'):'';
                html+='<td><a '+link+'>'+taskdata.childNodes[0].nodeValue+'</a></td>';
	}
	var days=task.getElementsByTagName('day');
    var taskId=task.getAttribute('id');
	for( i =0; i< days.length; i++){
		if(i!= days[i].getAttribute('col')) throw "badXML:task day";
		var open=days[i].getAttribute('open');
		html += '<th><input type="text"';
		if(open==0)html +=' readonly';
		html +=' class="time4day['+i+']" ';
		html += 'name="task['+taskId+']['+i+']" ';
		var Value=days[i].childNodes[0].nodeValue;
		html +=' value="'+Value;
		html +='" maxlength="5" style="width: 90%;'
		var color='f0fff0';
		if(Value=="00:00"||Value=="0")color='';
		if(open==0)color='909090';
		if(color!='')html +='background:#'+color;
		html +='; " onkeypress="return regexEvent(this,event,\'timeChar\')" ';
		html += 'onblur="regexEvent(this,event,\''+timetype+'\');updateTotal('+i+',\''+timetype+'\')" />';
		html += "</th>";
	}
	return html;
}
//function to update the next and prev week
function UpdateNavigation(nextWeek,prevWeek){
    var nav=document.getElementById('navPrev');
    nav.setAttribute( "onClick",'loadXMLTimesheet("'+prevWeek+'",0);');
    nav=document.getElementById('navNext');
    nav.setAttribute( "onClick",'loadXMLTimesheet("'+nextWeek+'",0);');
}
//function called to load new timesheet based on a yearweek
function loadXMLTimesheet(yearWeek, user)
{
    var Url="timesheet.php?xml=1&yearweek="+yearWeek;
    if(user!==0) Url+="&user="+user;
$.ajax({
    type: "GET",
    url: Url,
    dataType: "xml",
    success: loadXMLSuccess
   });
}
//fucntion to handle toDate submit
function toDateHandler(){
    var toDate=$('#toDate').val();
    if(toDate==''){
        alert('Date ?');
    }else{
        loadXMLTimesheetFromDate(toDate, 0);
    }
    return false;
}

//function called to load new timesheet based on a date
function loadXMLTimesheetFromDate(toDate, user)
{
    var Url="timesheet.php?action=goToDate&xml=1&toDate="+toDate;
    if(user!==0) Url+="&user="+user;
$.ajax({
    type: "GET",
    url: Url,
    dataType: "xml",
    success: loadXMLSuccess
   });
}

//fucntion to store the XML in order to be usable later
 function loadXMLSuccess(XMLdoc){
     xmlTs=XMLdoc;
     refreshTimesheet();
 }

//update both total lines day 0-6, mode hour/day

function updateTotal(days,mode){
	try{
            var curDay = document.getElementsByClassName('time4day['+days+']')
            var nbline = curDay.length;
            if(mode=="hours")
            {
                var total = new Date(0);
                total.setHours(0);
                total.setMinutes(0);

                for (var i=0;i<nbline;i++)
                { 
                        //var id='task['+i+']['+days+']';   
                        var taskTime= new Date(0);
                        var element=curDay[i];
                        if(element)
                        {
                                if (element.value)
                                {   
                                        parseTime(element.value,taskTime);
                                }
                                else
                                {
                                        parseTime(element.innerHTML,taskTime);
                                }
                                total.setHours(total.getHours()+taskTime.getHours());
                                total.setMinutes(total.getMinutes()+taskTime.getMinutes());
                        }
               }
		document.getElementById('totalDayb['+days+']').innerHTML = pad(total.getHours())+':'+pad(total.getMinutes());
		document.getElementById('totalDay['+days+']').innerHTML = pad(total.getHours())+':'+pad(total.getMinutes());
		//addText(,total.getHours()+':'+total.getMinutes());
            }else
            {
		var total =0;
		for (var i=0;i<nbline;i++)
		{ 
			//var id='task['+i+']['+days+']';   
			var taskTime= new Date(0);
			var element=curDay[i];
			if(element)
			{
				if (element.value)
				{   
					total+=parseInt(element.value);

				}
				else
				{
					total+=parseInt(element.innerHTML);
				}
			}
		}
		document.getElementById('totalDay['+days+']').innerHTML = total;
		document.getElementById('totalDayb['+days+']').innerHTML = total;
	}
	}
	catch(err) {
		//document.getElementById("demo").innerHTML = err.message;
	}
}

//function to update all the totals
function updateAll(timetype){
	for(i=0;i<7;i++){
		updateTotal(i,timetype);
	}
}
