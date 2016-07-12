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
            var err=false;
            var curDay = document.getElementsByClassName('time4day['+ts+']['+days+']');
            var nbline = curDay.length;
            var TotalList=document.getElementsByClassName('Total['+ts+']['+days+']');
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
                    }else if(TotalList[i].innerHTML ="&nbsp;"){
                        TotalList[i].innerHTML = day_max_hours+":00";
                        TotalList[i].style.backgroundColor = "red";
                    }
                }
                return !err;
            
                
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
                        }
                        else if(element.innerHTML)
                        {
                            total+=parseFloat(element.innerHTML);
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
                    }else if(TotalList[i].innerHTML ="&nbsp;"){
                        TotalList[i].innerHTML = day_max_hours/day_hours;
                        TotalList[i].style.backgroundColor = "red";
                    }
                }
                return !err;
            

            }
	}
	catch(err) {
            if(silent == 0)$.jnotify("updateTotal "+err,'error',true);
	}
}

//function to update all the totals
function updateAll(){
	var tsUser = document.getElementsByName('tsUserId')
    for(i=0;i<7;i++){
             for(j=0;j<tsUser.length;j++){
		updateTotal(tsUser[j].value,i,1);
            }
	}
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
                      if(regex2.test(object.value))
                        object.value=object.value+':00';
      
                      else{
                        object.value=object.defaultValue;
                        object.style.backgroundColor = "red";
                    }
                  } 
              }else
            {
                object.style.backgroundColor = object.style.getPropertyValue("background");
            }
            break;
      }
      if(updateTotal(ts,day,silent)==false){
          object.value=object.defaultValue;
          object.style.backgroundColor = "red";
          
      }
}