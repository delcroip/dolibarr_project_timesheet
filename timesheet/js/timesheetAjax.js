
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

function refreshTimesheet(){
    // parse the response
            
    // adapt the navigation menu

    // adapt the table header

    //adapt the table lines 
    
}


function loadXMLDoc()
{
    // create and init AJAX object
    var xmlhttp;
    if (window.XMLHttpRequest)
      {// code for IE7+, Firefox, Chrome, Opera, Safari
      xmlhttp=new XMLHttpRequest();
      }
    else
      {// code for IE6, IE5
      xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
      }
     // Async Handler
      xmlhttp.onreadystatechange=refreshTimesheet();
      
     //sonctuct the request (gest and async)
      xmlhttp.open("GET","timesheet.php?ajax=1&yearWeek=2015W02",true);
      // send the request
      xmlhttp.send();

}
    

