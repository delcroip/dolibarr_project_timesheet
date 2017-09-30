<?php
/* Copyright (C) 2017 delcroip <pmpdelcroix@gmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

include 'core/lib/includeMain.lib.php';

llxHeader('',$langs->trans('resourceAllocation'),'','','','',$morejs);

print '<div id="board">';

print '  <table><tr><td><div id="myTask">';
print '<div class="title">Task</div>';
print '    <div id="task1" draggable="true" ondragstart="startDrag(event);"> Task1</div>';
print '    <div id="task2" draggable="true" ondragstart="startDrag(event);"> Task2</div>';
print '  </div></td>';

for ($i=0;$i<7;$i++){
    print '<td>';
    print '  <div id="day['.$i.']" ondragover="event.preventDefault();" ondrop="drop(event);">';
    print '  <div class="title">Day '.$i.'</div></div>';
    print '</td>';
}
print '</tr></table></div>';

print '
    <script>
function   startDrag(event) {

  event.dataTransfer.setData("text/plain", event.target.getAttribute("id"));
}
function drop(event) {
  
    var notecard = event.dataTransfer.getData("text/plain");
    var movedElement = document.getElementById(notecard);
    event.target.appendChild(movedElement);
    event.preventDefault();
    var cln = movedElement.cloneNode(true);
    cln.setAttribute("id",cln.getAttribute("id")+ "_" +Math.floor((Math.random() * 100) + 1));
    document.getElementById("myTask").appendChild(cln);
}
</script>
';


llxFooter();