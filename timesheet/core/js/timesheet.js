/* 
 *Copyright (C) 2014 delcroip <delcroip@gmail.com>
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

function submitTs(){
    form=document.getElementById("timesheetForm");
    var element = document.createElement("INPUT");
    element.setAttribute("type", "hidden");
    element.setAttribute("name", "submitTs");
    element.setAttribute("value", "1");
    form.appendChild(element);
           
            
    return true;
}
  
  function regexEvent(object,evt,type)
  {

              //var regex= /^[0-9:]{1}$/;
              //alert(event.charCode);
              var charCode = (evt.which) ? evt.which : event.keyCode;
              
              if(((charCode >= 48) && (charCode <= 57)) || //num
                    (charCode===46) || (charCode===8)||// comma & periode
                    (charCode === 58) || (charCode==44) )// : & all charcode
              {
                  // ((charCode>=96) && (charCode<=105)) || //numpad
                return true;
         
              }else
              {
                  return false;
              }
                

  
  }    
  

function pad(n) {
    return (n < 10) ? ("0" + n) : n;
}



//function from http://www.timlabonne.com/2013/07/parsing-a-time-string-with-javascript/
function parseTime(timeStr, dt) {
    if (!dt) {
        dt = new Date();
    }
 
    var time = timeStr.match(/(\d+)(?::(\d\d))?\s*((p|a)?)/i);
    if (!time) {
        dt.setHours(0);
        return NaN;
    }
    var hours = parseInt(time[1], 10);
    dt.setHours(hours);
    dt.setMinutes(parseInt(time[2], 10) || 0);
    dt.setSeconds(0, 0);
    return dt;
}


//update both total lines day 0-6, mode hour/day

function updateTotal(ts,days,silent){
	try{
            totalMinutes=0;
            var err=false;
            var curDay = document.getElementsByClassName('column_'+ts+'_'+days);
            var nbline = curDay.length;
            var TotalList=document.getElementsByClassName('TotalColumn_'+ts+'_'+days);
            var nblineTotal = TotalList.length;
            if(time_type=="hours")
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
                        else if(element.innerHTML)
                        {
                            parseTime(element.innerHTML,taskTime);
                        }else
                        {
                            parseTime("00:00",taskTime);
                        }
                        totalMinutes+=taskTime.getMinutes()+60*taskTime.getHours();
                        total.setHours(total.getHours()+taskTime.getHours());
                        total.setMinutes(total.getMinutes()+taskTime.getMinutes());
                        }
               }
               if(total.getDate()>1 || (total.getHours()+total.getMinutes()/60)>day_max_hours){
                    if( silent == 0)$.jnotify(err_msg_max_hours_exceded ,'error',false);   //
                     err=true;
               }else if((total.getHours()+total.getMinutes()/60)>day_hours){
                    if( silent == 0)$.jnotify(wng_msg_hours_exceded ,'warning',false); 
                }
               
                for (var i=0;i<nblineTotal;i++)
                {
                    if(!err){
                        
                        TotalList[i].innerHTML = pad((total.getDate()-1)*24+total.getHours())+':'+pad(total.getMinutes());
                        TotalList[i].style.backgroundColor = TotalList[i].style.getPropertyValue("background");
                        if(TotalList[i].innerHTML=='00:00' && hide_zero)TotalList[i].innerHTML ="&nbsp;";
                    }
                }
                return err?-1:totalMinutes;
            
                
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
                            total+=parseFloat(element.value);
                            totalMinutes+=parseFloat(element.value);
                        }
                        else if(element.innerHTML)
                        {
                            total+=parseFloat(element.innerHTML);
                            totalMinutes+=parseFloat(element.innerHTML);
                        }else
                        {
                            total+=0;
                        }
                        
                    }
		}
                if(total>day_max_hours/day_hours){
                   if( silent == 0)$.jnotify(err_msg_max_hours_exceded ,'error',false);   //
                    err=true;
                }else if(total>1 ){
                   if( silent == 0)$.jnotify(wng_msg_hours_exceded ,'warning',false); 
                }

                for (var i=0;i<nblineTotal;i++)
                {
                    if(!err){
                        TotalList[i].innerHTML = total;
                        TotalList[i].style.backgroundColor = TotalList[i].style.getPropertyValue("background");
                        if(TotalList[i].innerHTML=='0' && hide_zero)TotalList[i].innerHTML ="&nbsp;";
                    }
                }
                return err?-1:totalMinutes;
            

            }
	}
	catch(err) {
            if(silent == 0)$.jnotify("updateTotal "+err,'error',true);
	}
}

//function to update all the totals
function updateAll(){
	var tsUser = document.getElementsByName('tsUserId');
        var retVal;
        err=false;
        total=0;
    for(j=0;j<tsUser.length;j++){
            var daysClass='user_'+tsUser[j].value;
            var nbDays= document.getElementsByClassName(daysClass);
            retVal=getTotal(nbDays);
             var TotalList=document.getElementsByClassName('TotalUser_'+tsUser[j].value);
              var nblineTotal = TotalList.length;
                for (var i=0;i<nblineTotal;i++)
                {                 
                    TotalList[i].innerHTML = retVal;
                }          
	}
        updateLineTotal();
}

function validateTime(object,ts, day,silent){
    updated=false;
    switch(time_type)
      {
          case 'days':
            if(object.value!=object.defaultValue)
            {
                object.style.backgroundColor = "lightgreen";       
                object.value=object.value.replace(',','.');
                var regex=/^[0-5]{1}([.,]{1}[0-9]{1,3})?$/;

                if(!regex.test(object.value) ){      
                      object.style.backgroundColor = "red";
                      object.value= object.defaultValue;
                  }                  
                if(hide_zero && object.value=='0')object.value='';
            }else{
                object.style.backgroundColor = object.style.getPropertyValue("background");
            }
          break; 
         
          case 'hours':
          default: 
              if(object.value!=object.defaultValue)
              {
                  object.style.backgroundColor = "lightgreen";
                  var regex= /^([0-1]{0,1}[0-9]{1}|[2]{0,1}[0-4]{1}):[0-9]{2}$/;
                  var regex2=/^([0-1]{0,1}[0-9]{1}|[2]{0,1}[0-4]{1})$/;
                  if(!regex.test(object.value))
                  { 

                       if(regex2.test(object.value)){
                        object.value=object.value+':00';
      
                    }else{
                        object.value=object.defaultValue;
                        object.style.backgroundColor = "red";
                    }
                  } 
                  if(hide_zero && object.value=='0:00')object.value='';
              }else
            {
                object.style.backgroundColor = object.style.getPropertyValue("background");
            }
            break;
      }
      if(updateTotal(ts,day,silent)<0){
          object.value=object.defaultValue;
          object.style.backgroundColor = "red";
         
          
      }else{
        updateAll();
      }
}
/*
 * Function to update the line Total when there is any
 * @param   
 * @returns None
 */
function updateLineTotal(){
        var TotalList=document.getElementsByClassName('lineTotal');
        var nblineTotal = TotalList.length;
        for(i=0;i<nblineTotal;i++){
            var classLine='line_'+ TotalList[i].id;
            var dayList=document.getElementsByClassName(classLine);
            TotalList[i].innerHTML=getTotal(dayList);
        }
    
}

/*
 * Function to generate a total 
 * @param {table of elm} daylist   list of the day element to sumup
 * @param {string} daytype hour or day
 * @returns {undefined}
 */
function getTotal(dayList){
    var nbline = dayList.length;
    var total=0;
    var retVal;

    if(time_type=="hours")
    {
        for (var i=0;i<nbline;i++)
        {  
            var taskTime= new Date(0);
            var element=dayList[i];
            if(element)
            {
                if (element.value){   
                    parseTime(element.value,taskTime);
                }else if(element.innerHTML){
                    parseTime(element.innerHTML,taskTime);
                }else {
                    parseTime("00:00",taskTime);
                }
                total+=taskTime.getMinutes()+60*taskTime.getHours();
            }
        }
        retVal=pad(Math.floor(total/60))+':'+pad(total-Math.floor(total/60)*60.);
        if(hide_zero && retVal=='00:00')retVal='';
    }else{
        for (var i=0;i<nbline;i++)
        {  
                var element=dayList[i];
                if(element)
                {
                    if (element.value){   
                        total+=parseFloat(element.value);
                    }else if(element.innerHTML){
                        total+=parseFloat(element.innerHTML);
                    }else{
                        total+=0;
                    }
                }    
        } 
        retVal= total.toFixed(2);
        if(hide_zero && retVal=='0')retVal='';

    }
    return retVal;
}
            
function openTab(evt, tabName) {
    // Declare all variables
    var i, tabcontent, tablinks;

    // Get all elements with class="tabcontent" and hide them
    tabcontent = document.getElementsByClassName("tabBar");
  if(tabName=="All"){
        for (i = 0; i < tabcontent.length; i++) {
            tabcontent[i].style.display = "block";
        }
    }else{
        for (i = 0; i < tabcontent.length; i++) {
            tabcontent[i].style.display = "none";
        }
    }

    // Get all elements with class="tablinks" and remove the class "active"
    tablinks = document.getElementsByClassName("tabsElem");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" tabsElemActive", "");
    }
    tablinks = document.getElementsByClassName("tab");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace("tabactive", "tabunactive");
    }

    // Show the current tab, and add an "active" class to the button that opened the tab
    document.getElementById(tabName).style.display = "block";
     evt.currentTarget.className += " tabsElemActive";
     evt.currentTarget.firstChild.className=evt.currentTarget.firstChild.className.replace("tabunactive", "tabactive");
}

function showFavoris(evt, tabName) {
    // Declare all variables
    var i, tabcontent, tablinks;
    switch(tabName){
        case 'whitelist':
        case 'blacklist':
        case 'All':
        default:
            break;
    }
    tslist = document.getElementsByClassName("timesheet_line");
    tsNote = document.getElementsByClassName("timesheet_note");
    for (i = 0; i < tsNote.length; i++) {
               tsNote[i].style.display = "none";
    }
    if(tabName=='All'){
        for (i = 0; i < tslist.length; i++) {
            tslist[i].style.display = "";
        }
    }else{
        for (i = 0; i < tslist.length; i++) {
               tslist[i].style.display = "none";
        }
        
        if(tabName=='whitelist'){
            wlist = document.getElementsByClassName("timesheet_whitelist");
            for (i = 0; i < wlist.length; i++) {
               wlist[i].style.display = "";
            }
        }else{
            wlist = document.getElementsByClassName("timesheet_blacklist");
            for (i = 0; i < wlist.length; i++) {
               wlist[i].style.display = "";
            }
        }
    }


    // Get all elements with class="tablinks" and remove the class "active"
    tablinks = document.getElementsByClassName("tabsElem");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" tabsElemActive", "");
    }
    tablinks = document.getElementsByClassName("tab");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace("tabactive", "tabunactive");
    }
     evt.currentTarget.className += " tabsElemActive";
     evt.currentTarget.firstChild.className=evt.currentTarget.firstChild.className.replace("tabunactive", "tabactive");

}

function checkEmptyFormFields(even,Myform,msg){
    var curform=document.forms[Myform];
    var fields=curform.getElementsByTagName("input");
    var error=0;
    for(field in fields){
        if (fields[field].value=='' && fields[field].name!='')error++;
    }
    var selects=curform.getElementsByTagName("select");
    for(select in selects){
        if (selects[select].value=='-1' && fields[field].name!='')error++;
    }
    
    if (error){
        $.jnotify(msg,'error',true);
        return false
    }
           
    
}

  function tristate(control, value1, value2, value3) {
    switch (control.value.charAt(0)) {
      case value1:
        control.value = value2;
        control.style.backgroundColor='green';
      break;
      case value2:
        control.value = value3;
        control.style.backgroundColor='red';
      break;
      case value3:
        control.value = value1;
        control.style.backgroundColor='';
      break;
      default:
        // display the current value if it's unexpected
        alert(control.value);
    }
  }
  function tristate_Marks(control) {
    tristate(control,'\u2753', '\u2705', '\u274C');
  }
  function tristate_Circles(control) {
    tristate(control,'\u25EF', '\u25CE', '\u25C9');
  }
  function tristate_Ballot(control) {
    tristate(control,'\u2610', '\u2611', '\u2612');
  }
  function tristate_Check(control) {
    tristate(control,'\u25A1', '\u2754', '\u2714');
  }
/*
 * Funciton that changed the hidden status off the element with id="id"
 * @param   string      id      id of the target element 
 */
function ShowHide(id){
    elmt=document.getElementById(id);
    if(elmt.style.display != ""){
        //elmt.hidden=false;
        elmt.style.display = "";
    }else{
       /// elmt.hidden=true;
        elmt.style.display = "none";
    }
} 
/*
 * function to add/remove this task as favoris
 */
function favOnOff(evt, prjtId, tskId){
    var favId=evt.target.id;
    var url='TimesheetFavouriteAdmin.php?ajax=1&Project='+prjtId+'&Projecttask='+tskId;
    url+='&action='+((favId>0)?('confirm_delete&confirm=yes&id='+favId):'add');
    httpGetAsync(url,setId, evt);
}

function httpGetAsync(theUrl, callback, callbackParam)
{
    var xmlHttp = new XMLHttpRequest();
    xmlHttp.onreadystatechange = function() { 
        if (xmlHttp.readyState == 4 && xmlHttp.status == 200)
            callback(callbackParam,xmlHttp.responseText);
    }
    xmlHttp.open("GET", theUrl, true); // true for asynchronous 
    xmlHttp.send(null);
}
    
function setId(evt, JsonStr){
    var obj = JSON.parse(JsonStr);
     evt.target.id=obj.id;
     if(obj.id>0){
          evt.target.src="img/fav_on.png";
          evt.target.parentElement.parentElement.className=evt.target.parentElement.parentElement.className.replace('timesheet_blacklist','timesheet_whitelist');
     }else{
         evt.target.src="img/fav_off.png";
         evt.target.parentElement.parentElement.className=evt.target.parentElement.parentElement.className.replace('timesheet_whitelist','timesheet_blacklist');
     }
}
