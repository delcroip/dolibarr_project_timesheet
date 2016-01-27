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

function refreshTimesheet( xmlDoc){
      var i;
      // parse the response
	//var xmlDoc = xml.responseXML;
	// adapt the navigation menu
	//var yearWeek = xmlDoc.getElementsByTagName("timesheet").getAttribute("yearweek");
	//document.getElementById("timesheetTable").innerHTML = 
	// adapt the table header
        var timesheet=xmlDoc.getElementsByTagName("timesheet");
        if (!timesheet)throw "Bad XML: no timesheet Node";
        var timestamp=timesheet[0].getAttribute('timestamp');
        var yearWeek=timesheet[0].getAttribute('yearweek');
        var timetype=timesheet[0].getAttribute('timetype');
	var headers = xmlDoc.getElementsByTagName("headers");
	var MT=document.getElementById("timesheetTable");
	var hearderRow='';
	for( i =0; i< headers[0].childNodes.length; i++){
		hearderRow+="<th>"+headers[0].childNodes[i].childNodes[0].nodeValue+"</th>";
	}
	//day
	var days = xmlDoc.getElementsByTagName("days");
	for( i =0; i< days[0].childNodes.length; i++){
		hearderRow+="<th width='60px'>"+days[0].childNodes[i].childNodes[0].nodeValue+"</th>";
	}
	MT.rows[0].innerHTML=hearderRow;
	//adapt the table lines 
	 var TotalT=document.getElementById("totalT");
	//remove the old line
	var idxT = TotalT.rowIndex;
	var idxB = document.getElementById("totalB").rowIndex;
	for (i=idxT+1; i<idxB;i++)
	{
		MT.deleteRow(idxT+1);
	}
	var tasks=  xmlDoc.getElementsByTagName("userTs");
	var line=0;
 
        for (j=0;j<tasks.length;j++)
	{
            
            for (i=0;i<tasks[j].childNodes.length;i++)    
            {
                    var CurRow = MT.insertRow(idxT+1);
					CurRow.className =(i%2==0)?'pair':'impair';
                    var rowContent=generateTaskLine(headers[0],tasks[j].childNodes[i],timetype);
                    CurRow.innerHTML=rowContent;
                    // document.getElementById("timesheetTable").innerHTML = table;
                    line++;
            }
            if(tasks.length>1){
                var nameRow=MT.insertRow(idxT+1);
                CurRow.innerHTML='<td colspan="'+headers[0].childNodes.length+'">'+tasks[j].getAttribute('userName')+'</td><td colspan="7"></td>';
            }
        }
        updateAll(timetype);
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

function loadXMLTimesheet(yearWeek, user)
{
$.ajax({
    type: "GET",
    url: "timesheet.php?xml=1&yearWeek="+yearWeek+"&user="+user,
    dataType: "xml",
    success: refreshTimesheet
   });
}


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
function updateAll(timetype){
	for(i=0;i<7;i++){
		updateTotal(i,timetype);
	}
}
