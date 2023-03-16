/*
 *Copyright (C) 2014 delcroip <patrick@pmpd.eu>
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
        evt = evt||window.event; // IE support
        var charCode = evt.charCode;
        var ctrlDown = evt.ctrlKey||evt.metaKey; // Mac support
              //var regex= /^[0-9:]{1}$/;
              //alert(event.charCode);
              //var charCode = (evt.which) ? evt.which : event.keyCode;
        if ((charCode >= 48) && (charCode <= 57)) return true;
        else if (charCode===46) return true; // comma
        else if (charCode===8) return true;// periode
        else if (charCode === 58)  return true; // : 
        else if (ctrlDown && charCode == 86) return true; //Ctrl + V
        else return false;      

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

function validateTotal(col_id){
    var total=0;
    try
    {
        var Total =document.getElementsByClassName('TotalColumn_'+col_id);
        var lines =document.getElementsByClassName('column_'+col_id);
        total=getTotal(lines);
        if (Total[0].innerHTML!=minutesToHTML(total)){
            var hours=total/60;
            if (hours>day_max_hours){
               $.jnotify(err_msg_max_hours_exceded ,'error',false);   //
                return -1;
            }else if (hours>day_hours){
               $.jnotify(wng_msg_hours_exceded ,'warning',false);
            }
        }
    }
    catch(err) {
        $.jnotify("updateTotal "+err,'error',true);
    }
    return 1;
}

//function to update all the totals
/*
 *
 * @returns {undefined}
 */
function updateAll(){
    var tsUser = document.getElementsByName('tsUserId');
    err=false;
    total=0;
    for(j=0;j<tsUser.length;j++){
        generateDynTotal(tsUser[j].value);
        total=0;
        var daysClass="days_"+tsUser[j].value;
        var days= document.getElementsByClassName('daysClass');
        var nbDays=days.length;
        for(i=0;i<nbDays;i++){
            total+=updateTotals('column_'+days[i].id,'TotalColumn_'+days[i].id);
        }
        
        var TotalList=document.getElementsByClassName('TotalUser_'+tsUser[j].value);
        var nblineTotal = TotalList.length;
          for (var i=0;i<nblineTotal;i++)
          {
              TotalList[i].innerHTML = minutesToHTML(total);
          }
    }
    updateAllLinesTotal();
}



/* function to update the totals
 *
 * @param {type} classSource
 * @param {type} classTarget
 * @returns {undefined}
 */
function updateTotals(classSource,classTarget){
    var source = document.getElementsByClassName(classSource);
    var total= getTotal(source);
    var TotalList=document.getElementsByClassName(classTarget);
    for (var i=0;i<TotalList.length;i++)
    {
        TotalList[i].innerHTML = minutesToHTML(total);
    }
    return total;
}

/** function to remove the task not changed
 * 
 * @returns {undefined}
 */
function removeUnchanged(){
    var tsUser = document.getElementsByName('tsUserId');
    err=false;
    total=0;
    var nbUser=tsUser.length;
    for(j=0;j<nbUser;j++){
        
        var lineClass="line_"+tsUser[j].value;
        //foreach task
        var task = document.getElementsByClassName(lineClass);
        var nbTask = task.length;
        for(i=0;i<nbTask;i++){
            changed=0;
            var inputs = task[i].getElementsByTagName( 'input' );
            var textarea = task[i].getElementsByTagName( 'textarea' );
            var select = task[i].getElementsByTagName( 'select' );
            var nbInputs = inputs.length;
            var nbTextarea = textarea.length;
            var nbSelect = select.length;
            for(k=0;k<nbInputs;k++){
                if (inputs[k].defaultValue!=inputs[k].value)changed++
            }
            for(k=0;k<nbTextarea;k++){
                if (textarea[k].defaultValue!=textarea[k].value)changed++
            }
            for(k=0;k<nbSelect;k++){
                if (select[k].defaultValue!=select[k].value)changed++
            }
            if (changed==0){
                task[i].parentNode.removeChild(task[i]);
                i--;
                nbUser--;
            }
        }
        

    }
}

/*
 *
 * @param {type} object where the data has to e validated
 * @param {type} ts     timesheet id
 * @param {type} day    day to update total
 * @param {type} silent will show message to user or not
 * @returns {undefined}
 */
function validateTime(object,col_id){
    updated=false;
    if (object.value!=object.defaultValue)
    {
    switch(time_type)
      {
          case 'days':
                object.style.backgroundColor = "lightgreen";
                object.value=object.value.replace(',','.');
                //var regex=/^([0-5]{1}([.,]{1}[0-9]{1,3})?|[.,]{1}[0-9]{1,3}|)$/;
                var regex=/^([0-2]{0,1})?([:,.]([0-9]{0,3}))?$/
                if (regex.test(object.value)){
                    object.value=object.value.replace(/:|\,/g,'.'); 
                }else {
                      object.style.backgroundColor = "red";
                      object.value= object.defaultValue;
                }
                if (hide_zero && object.value=='0')object.value='';
          break;
          case 'hours':
          default:
                  object.style.backgroundColor = "lightgreen";
                  var regex= /^(([0-1]{0,1}[0-9]{1})|([2]{1}[0-4]{0,1}))?([:,.]([0-9]{0,2}))?$/;
                  var regex_format= /^0*([0-9]{2,}):([0-9]{2})0*$/;
                  
                  if (regex.test(object.value))
                  {
                      tmp=object.value.replace(regex,'00$01:$0500');
                      object.value=tmp.replace(regex_format,'$1:$2');
                  }else if (!object.value){
                        object.value='0:00';
                }
                else{
                    object.value=object.defaultValue;
                    object.style.backgroundColor = "red";
                }
                if (hide_zero && object.value=='00:00')object.value='';
                /*
                  var regex= /^([0-1]{0,1}[0-9]{1}|[2]{0,1}[0-4]{1}):[0-9]{2}$/;
                  var regex2=/^([0-1]{0,1}[0-9]{1}|[2]{0,1}[0-4]{1})$/;
                  var regex3=/^([0-1]{0,1}[0-9]{1}|[2]{0,1}[0-4]{1}):[0-9]{1}$/;
                  if (!regex.test(object.value))
                  {
                    if (regex2.test(object.value)){ // simple number will assume hours
                        object.value=object.value+':00';
                    }else if (regex3.test(object.value)){ //missing 0 will assume ten of min
                        object.value=object.value+'0';
                    }else if (!object.value){
                        object.value='0:00';
                    }
                    else{
                        object.value=object.defaultValue;
                        object.style.backgroundColor = "red";
                    }
                  }*/
                  if (hide_zero && object.value=='0:00')object.value='';
//              }
            break;
      }
    }else{
        object.style.backgroundColor = object.style.getPropertyValue("background");
    }
    if (validateTotal(col_id)<0){
          object.value=minutesToHTML(0);
          object.style.backgroundColor = "red";
    }
    updateAll();

}

/*
 * Function to remove a recreate the total liens
 * @param @table table object
 * @returns None
 */
function generateDynTotal(userId)
{
    var table = document.getElementById('timesheetTable_' + userId);
    //get all existing lineDynTotal to delete them
    var Tl=table.getElementsByClassName('lineDynTotal');
    var num = Tl.length
    for(var i = 0; i < num ; i++){
        var rid = Tl[0].rowIndex;
        table.deleteRow(rid);
    }
    //get the nb header an day
    header = table.querySelector('tr.liste_titre');
    DCl = table.querySelectorAll('th.daysClass');

    /*for (var cid = 0, col; col = table.rows[0].cells[cid]; cid++) {
        if (col.classList.contains('daysClass')){
            DCl++;
        }
    }  */  
    var daysLenth = DCl.length;
    var headerLenth = header.cells.length - daysLenth;

    //recreate a lineDynTotal every 10 lines actually displayed
    var nld = 0;
    for (var r = 0, row; row = table.rows[r]; r++) {
        //iterate through rows
        //rows would be accessed using the "row" variable assigned in the for loop
        if (row.style.display != 'None' && row.classList.contains('timesheet_line')){
            // generate the line
            if ( nld % 10 == 0 && nld < table.rows.length ){
                var newRow = table.insertRow(r);
                html = '<tr>';
                html += '<td colspan = "' + (headerLenth -1) + '" align = "right" > TOTAL </td>';
                html += "<td><div class = 'TotalUser_"+ userId +"'>&nbsp;</div></td>";
                for (var d = 0; d < daysLenth ; d++)
                {
                    html += "<td><div class = 'TotalColumn_"+userId+" TotalColumn_"+userId+"_"+d+"'>&nbsp;</div></td>";
                }
                newRow.innerHTML=   html + '</tr>';
                newRow.className = 'lineDynTotal';
            }
            nld++;// count to row actually displayed
        }
    }


}


/*
 * Function to update the line Total when there is any
 * @param
 * @returns None
 */
function updateAllLinesTotal(){


    var TotalList=document.querySelectorAll('.lineTotal');
        var nblineTotal = TotalList.length;
        for(i=0;i<nblineTotal;i++){
            var classLine='line_'+ TotalList[i].id;
            var dayList=document.getElementsByClassName(classLine);
            TotalList[i].innerHTML=minutesToHTML(getTotal(dayList));
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
    if (time_type=="hours")
    {
        for (var i=0;i<nbline;i++)
        {
            var taskTime= new Date(0);
            var element=dayList[i];
            if (element)
            {
                if (element.value){
                    parseTime(element.value,taskTime);
                }else if (element.innerHTML){
                    parseTime(element.innerHTML,taskTime);
                }else {
                    parseTime("00:00",taskTime);
                }
                total+=taskTime.getMinutes()+60*taskTime.getHours();
            }
        }
    }else{
        for (var i=0;i<nbline;i++)
        {
                var element=dayList[i];
                if (element)
                {
                    if (element.value){
                        total+=parseFloat(element.value);
                    }else if (element.innerHTML){
                        total+=parseFloat(element.innerHTML);
                    }else{
                        total+=0;
                    }
                }
        }
        total=total*day_hours*60;
    }
    return total;
}

function minutesToHTML(total){
    var retVal='';
    if (time_type=="hours")
    {
        retVal=pad(Math.floor(total/60))+':'+pad(total-Math.floor(total/60)*60.);
        if (hide_zero && retVal=='00:00')retVal='';
    }else{
        retVal= Math.round(total/60/day_hours*1000)/1000;
        if (hide_zero && total==0)retVal='';
    }
    return retVal;
}

function openTab(evt, tabName) {
    // Declare all variables
    var i, tabcontent, tablinks;

    // Get all elements with class="tabcontent" and hide them
    tabcontent = document.getElementsByClassName("tabBar");
  if (tabName=="All"){
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

function searchTask(evt){
    var search= new RegExp(evt.value,"i");
    tslist = document.getElementsByClassName("timesheet_line");
    tsNote = document.getElementsByClassName("timesheet_note");
    //hide all notes without the search
    for (i = 0; i < tsNote.length; i++) {
               tsNote[i].style.display = "none";
    }
    //hide all

    for (i = 0; i < tslist.length; i++) {
        tslist[i].style.display = "";
        fields=tslist[i].children;
        var displayLine=(tslist[i].id=="searchline")?true:false;
        for (j=0; j<fields.length;j++){
           var found=0;
           found+=fields[j].innerHTML.search(search);
           if (found>=0){
             displayLine=true;
            }
        }
        if (!displayLine)tslist[i].style.display = "none";
    }

}
// function use to switch between tabs
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
    if (tabName=='All'){
        for (i = 0; i < tslist.length; i++) {
            tslist[i].style.display = "";
        }
    }else{
        for (i = 0; i < tslist.length; i++) {
               tslist[i].style.display = "none";
        }

        if (tabName=='whitelist'){
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
        var tsUser = document.getElementsByName('tsUserId');
        updateAll();
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
        if (fields[field].value=='' && fields[field].name!=''
        && !selects[select].classList.contains('not_mandatory'))error++;
    }
    var selects=curform.getElementsByTagName("select");
    for(select in selects){
        if (selects[select].value=='-1' && fields[field].name!='' 
            && !selects[select].classList.contains('not_mandatory'))error++;
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
    if (elmt.style.display != ""){
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
    var token = getToken();
    var favId=evt.target.id;
    var url='TimesheetFavouriteAdmin.php?ajax=1&Project='+prjtId+'&Task='+tskId;
    url+='&token='+token;
    url+='&action='+((favId>0)?('confirm_delete&confirm=yes&id='+favId):'add');
    httpGetAsync(url,setId, evt);
}

function getToken(){
    return document.getElementById('csrf-token').value
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
     if (obj.id>0){
          evt.target.src="img/fav_on.png";
          evt.target.parentElement.parentElement.className=evt.target.parentElement.parentElement.className.replace('timesheet_blacklist','timesheet_whitelist');
     }else{
         evt.target.src="img/fav_off.png";
         evt.target.parentElement.parentElement.className=evt.target.parentElement.parentElement.className.replace('timesheet_whitelist','timesheet_blacklist');
     }
}


 // popup modal


// Get the button that opens the modal

function openNote(noteid){
    var modal = document.getElementById(noteid);
    modal.style.display = "block";


}

//function to close note
function closeNotes(){
    var modals = document.getElementsByClassName("modal");
    var patt = /(\w+)\.png$/gi 
    for(var i=0;i<modals.length;i+=1){
        var modalbox = modals[i];
        modalbox.style.display = "none";
        var icon = (modalbox.firstChild.lastChild.value.length>0)?"file":"filenew";
        var imgnote = document.getElementById("img_"+modalbox.id);
        imgnote.src = imgnote.src.replace(patt,"$'"+icon+".png");
    };
}


// When the user clicks anywhere outside of the modal, close it
window.onclick = function(event) {

    if (!event.target.classList.contains('modal')) {
        //closeNotes();
    }
}

// https://www.w3schools.com/howto/howto_js_sort_table.asp
function sortTable(table,col,sort) {
    var table, rows, switching, i, x, y, shouldSwitch;
    table = document.getElementById(table);
    switching = true;
    /* Make a loop that will continue until
    no switching has been done: */
    while (switching) {
      // Start by saying: no switching is done:
      switching = false;
      rows = table.getElementsByClassName('timesheet_line');
      /* Loop through all table rows (except the
      first, which contains table headers): */
      for (i = 1; i < (rows.length - 1); i++) {
        // Start by saying there should be no switching:
        shouldSwitch = false;
        /* Get the two elements you want to compare,
        one from current row and one from the next: */
        x = rows[i].getElementsByClassName(col)[0];
        y = rows[i + 1].getElementsByClassName(col)[0];
        // Check if the two rows should switch place:
        if (typeof(x) !== 'undefined' && typeof(y) !== 'undefined'){
            if (sort == "desc" && (x.innerHTML.toLowerCase() > y.innerHTML.toLowerCase())){
                // If so, mark as a switch and break the loop:
                shouldSwitch = true;
                break;
            }else if (sort == "asc" && (x.innerHTML.toLowerCase() < y.innerHTML.toLowerCase())){
                // If so, mark as a switch and break the loop:
                shouldSwitch = true;
                break;                
            }
        }
      }
      if (shouldSwitch) {
        /* If a switch has been marked, make the switch
        and mark that a switch has been done: */
        rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
        switching = true;
      }
    }
  }
  


function updateProgress(event){
    element= event.target;
    updateProgressElement(element);
}

function updateProgressElement(element){
      // check if value has changed
    // send the jsom
    let ret;
    ret = {name : element.name,
        taskid : element.name.match(/progressTask\[[0-9]*\]\[([0-9]+)\]/)[1],
        progress : element.value,
        status : null};
    
    var Url="ajax.php?action=updateprogress"
    $.ajax({
        type: "POST",
        url: Url,
        data:'json='+JSON.stringify(ret),
        success: updateProgressSuccess,
        error: $.jnotify
    });

  }

  function updateProgressSuccess(data){
        
        if (typeof data.status!== 'undefined' && data.status && data.status!=""){ //  display status
            var obj=JSON.parse(data.status);
            Object.keys(obj).forEach (function(key){
                $.jnotify(obj[key].text+obj[key].param,obj[key].type)
                if (obj[key].type == 'megs'){// only one will be returned
                }
            });
        }
        if ( typeof data.name !== 'undefined' &&  data.name !== null){
            let element = document.getElementsByName(data.name);
            if (element[0] !== 'undefined') element[0].value =  data.progress;
        }

  }

  function updateAllProgress(){
    selectElements = document.getElementsByTagName('select')
    Object.keys(selectElements).forEach (function(key){
            current = selectElements[key];
            selectName = current.getAttribute("name");
            if (selectName && selectName.indexOf("progressTask") === 0) {
                updateProgressElement(current);
            }
        }
    )
    
  }



