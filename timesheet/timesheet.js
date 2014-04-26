
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


function validate(event) {
    //test it is not a number or :
    var keycode = event.keyCode;
    alert(keycode);
    if (((keycode < 48 || keycode > 58))&(keycode != 8)) {
       event.returnValue = false;
       return false;
    }
    else
    {
        evt=event;
        //evt=document.getElementById(ident)
        var s_time = evt.value;   
        //alert(event);
        var dblpoint = s_time.indexOf(":");
        var Hrs = s_time.substring(0, dblpoint);
        if (dblpoint != -1) {
            minuts = s_time.substring(dblpoint + 1, s_time.length);
            if ((minuts.length == 2) & (Hrs.length > 0)) {
                if (Hrs.length ==2 )
                {            
                 evt.returnValue = false;
                 return false;
                }
            }
        }
    }

  }
  
  function regexEvent(objet,event,type)
  {
      switch(type)
      {
          case 'time':
              var regex= /^[0-9]{1,2}:[0-9]{2}$/;
              if(!regex.test(objet.value))
                objet.value='';
              break;
          case 'timeChar':
              //var regex= /^[0-9:]{1}$/;
              //alert(event.keyCode);
              if(((event.keyCode<96) || (event.keyCode>106)) && (event.keyCode!= 58) && (event.keyCode!= 8))
              {
                return false;
               }
                
              break;    
          default:
              break;
      }
  
  }    
  

