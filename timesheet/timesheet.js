//FIXME total not working    
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


  
  function regexEvent(objet,evt,type)
  {
      switch(type)
      {
          case 'days':
              if(objet.value!=objet.defaultValue)
                objet.style.backgroundColor = "lightgreen";
              var regex= /^[0-9]{1}([.,]{1}[0-9]{1})?$/;

              if(regex.test(objet.value) )
              { 
                var tmp=objet.value.replace(',','.');
                if(tmp<=1.5){
                    var tmpint=parseInt(tmp);
                    if(tmp-tmpint>=0.5){
                        objet.value= tmpint+0.5;
                    }else{
                        objet.value= tmpint;
                    }
                }else{
                    objet.value= '1.5';
                }
              }else{
                 objet.style.backgroundColor = "red";
                 objet.value= '0';
            }
          break; 
          case 'hours':
              if(objet.value!=objet.defaultValue)
                objet.style.backgroundColor = "lightgreen";
              var regex= /^[0-9]{1,2}:[0-9]{2}$/;
              var regex2=/^[0-9]{1,2}$/;
              if(!regex.test(objet.value))
              { 
                  if(regex2.test(objet.value))
                    objet.value=objet.value+':00';
                  else{
                    objet.value='00:00';
                    objet.style.backgroundColor = "red";
                }
              }
              break;
          case 'timeChar':
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
                
              break;    
          default:
              break;
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
 
    var time = timeStr.match(/(\d+)(?::(\d\d))?\s*(p?)/i);
    if (!time) {
        return NaN;
    }
    var hours = parseInt(time[1], 10);
    if (hours == 12 && !time[3]) {
        hours = 0;
    }
    else {
        hours += (hours < 12 && time[3]) ? 12 : 0;
    }
 
    dt.setHours(hours);
    dt.setMinutes(parseInt(time[2], 10) || 0);
    dt.setSeconds(0, 0);
    return dt;
}

function updateTotal(days,mode){
    
    if(mode=="hours")
    {
        var total = new Date(0);
        total.setHours(0);
        total.setMinutes(0);   
        var nbline = document.getElementById('numberOfLines').value;
        for (var i=0;i<nbline;i++)
        { 
            var id='task['+i+']['+days+']';   
            var taskTime= new Date(0);
            var element=document.getElementById(id);
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
        document.getElementById('totalDay['+days+']').innerHTML = pad(total.getHours())+':'+pad(total.getMinutes());
        //addText(,total.getHours()+':'+total.getMinutes());
    }else
    {
        var total =0;
        var nbline = document.getElementById('numberOfLines').value;
        for (var i=0;i<nbline;i++)
        { 
            var id='task['+i+']['+days+']';   
            var taskTime= new Date(0);
            var element=document.getElementById(id);
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
    }
    
}

   