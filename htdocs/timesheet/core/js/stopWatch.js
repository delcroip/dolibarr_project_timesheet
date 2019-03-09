/*
 * Copyright (C) Billy Brown <https://codepen.io/_Billy_Brown/pen/dbJeh>
 * Copyright (C) 2018 delcroip <patrick@pmpd.eu>
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
class event{
     constructor(){
        this.id='';
	this.date_time_event='';
	this.date_time_event_start='';
	this.event_location_ref="";
	this.event_type='';
	this.note='';
	this.date_modification='';
	this.userid='';
	this.user_modification='';
	this.third_party='';
	this.task='';
	this.project='';
	this.token='';
	this.status='';
        // working var
        this.taskLabel='';
        this.projectLabel='';
        this.third_partyLabel='';
        this.processedTime='';
     }
     // funciton to load data from an array


}
class Stopwatch {
    constructor(display) {
        this.running = false;
        this.display = display;
        this.times = [ 0, 0, 0,0];
        this.event=new event();
        //this.reset();
        this.animationframeID=null;
        this.print(this.times);
    }
    //function called to load stopwatch data (json)
    loadSuccess(data){
       this.reset();
       if( typeof data.event_type !== 'undefined'){
        this.event =data;


        //var date_now.setTime(performance.now());
         this.timestampHeartbeart=performance.now();
         this.timestampClock=performance.now();
         this.time =performance.now()+ (this.event.date_time_event_start-this.event.processedTime)*1000;

        if(this.event.event_type<3  && this.event.event_type!=0) { // launch the clock for heartbeat and
             this.running = true;
             this.updatePlayStopIcon(this.running,this.event.task);
             this.animationframeID=requestAnimationFrame(this.step.bind(this));
         }else if(this.event.event_type>=3 || this.event.event_type==0){ // stop the clock

             this.running = false;
             this.reset();
             this.updatePlayStopIcon(this.running,this.event.task);
             cancelAnimationFrame(this.animationframeID);

         }
         document.getElementById('project').innerText = this.event.projectLabel;
         document.getElementById('customer').innerText = this.event.third_partyLabel;
         document.getElementById('task').innerText = this.event.taskLabel;
         document.getElementById('eventNote').value = this.event.note;

     }else{ // load without data
          this.running = false;
          this.reset();
          this.updatePlayStopIcon(this.running,this.event.task);
     }


     if(typeof data.status!== 'undefined' && data.status!=""){ //  display status
                var obj=JSON.parse(data.status);
                Object.keys(obj).forEach(function(key){
                    $.jnotify(obj[key].text+obj[key].param,obj[key].type)
        });
     }
     this.event.status="";

    }
     // funciton to handle error while parsing the Json answer
    loadError(ErrMsg){
         $.jnotify("Error:"+ErrMsg,'error',true);
	// location = location.href;
     }
     // place the play Icone
    updatePlayStopIcon(play,taskid){
        //update the main play
        if(play==false){

             if(this.event.project==0 && this.event.third_party==0 && this.event.task==0)
                 document.getElementById("mainPlayStop").src= 'img/tinyblack.gif';
             else
                 document.getElementById("mainPlayStop").src= 'img/play-arrow.png';
         }else{
             document.getElementById("mainPlayStop").src= 'img/stop-square.png';
         }
         //set all the task  button to play then put the related to the playing task to stop
         var buttons=document.getElementsByClassName('playStopButton');
        var i;
        for (i = 0; i < buttons.length; i++) {
             if(buttons[i].id!= 'playStop_'+taskid || play==false){
                 buttons[i].src='img/play-arrow.png';
             }else{
                 buttons[i].src='img/stop-square.png';
             }
         }
     }
    // empty the clock & other disply
    reset() {
        this.event=new event();
        this.times = [ 0, 0, 0,0];
        this.print();
    }
    // send a  POST request to create a start event
    start(taskid) {
        //saving the actual update  location
 //       this.event_location_ref="Browser:"+window.navigator.userAgent.replace(/\D+/g, '');
        var Url="AttendanceClock.php?action=start"
        if(taskid!==0) Url+="&taskid="+taskid;
        $.ajax({
            type: "POST",
            url: Url,
            data:this.serialize(),
            success: loadSuccess,
            error: this.loadError
       });
   }
    // sent PSOT request for heartbeat creation
    save() {
//        this.event_location_ref="Browser:"+window.navigator.userAgent.replace(/\D+/g, '');
        var Url="AttendanceClock.php?action=heartbeat"
        Url+="&eventToken="+this.event.token;
        $.ajax({
            type: "POST",
            url: Url,
            data:this.serialize(),
            success: loadSuccess,
            error: this.loadError
       });
    }
    // initial load at page load, teh server will send back the active one is any
    load(){
        //this.event.token=document.getElementById('eventToken').value;
        this.save();
    }

    // fucntion to update the js object and the serialize it in json
    serialize(){
        //save the note
        this.event.note=document.getElementById("eventNote").value;
        this.event.event_location_ref="Browser:"+window.navigator.userAgent.replace(/\D+/g, '');
        return 'json='+JSON.stringify(this.event);
    }
    //sent a POST request to create a stop event
    stop() {
        this.running = false;
        cancelAnimationFrame(this.animationframeID);
 //       this.event_location_ref="Browser:"+window.navigator.userAgent.replace(/\D+/g, '');
        var Url="AttendanceClock.php?action=stop"
        Url+="&eventToken="+this.event.token;
        this.event_type=3;
        $.ajax({
            type: "POST",
            url: Url,
            data:this.serialize(),
            success: loadSuccess,
            error: this.loadError
       });

    }
    //code that will run to update the clock, called by the browser
     step(timestamp) {
        if(!this.running) return;
        if(timestamp-this.timestampClock>300) {
            this.calculate(timestamp);
            this.time = timestamp;
            this.timestampClock=timestamp;
            this.print();
        }

        if(timestamp-this.timestampHeartbeart>60000) {
            this.save('');
            this.timestampHeartbeart=timestamp;
        }//autosave every minute
        // refresh the screen only every 300 ms
        this.animationframeID=requestAnimationFrame(this.step.bind(this));
    }
    // calculation of the time elaspsed via the timestamp since last update (given by the browser on the call back)
    calculate(timestamp) {
        var diff = timestamp - this.time;
        // Hundredths of a second are 10 ms
        this.times[3] += diff / 10;
        while(this.times[3]>100){
            // Seconds are 100 hundredths of a second
            if(this.times[3] >= 100) {
                this.times[2] += 1;
                this.times[3] -= 100;
            }
            // Minutes are 60 seconds
            if(this.times[2] >= 60) {
                this.times[1] += 1;
                this.times[2] -= 60;
            }
                  // hours are 60 minutes
            if(this.times[1] >= 60) {
                this.times[0] += 1;
                this.times[1] -= 60;
            }
        }
    }
    // update the DOM fields
    print() {
        this.display.innerText = this.format(this.times);


    }
    // Format the time for display
    format(times) {
     return pad0(times[0], 2)+":"+pad0(times[1], 2)+":"+pad0(Math.floor(times[2]), 2);
    }
}

// function to pad the number (always 2 digits)
function pad0(value, count) {
    var result = value.toString();
    for (; result.length < count; --count)
        result = '0' + result;
    return result;
}
// start stop fuction
function startStop(evt,user,tsk){
    if(evt.target.src.indexOf('img/stop-square.png')>0){
        stopwatch.stop();
    }else{
        stopwatch.start(tsk);
    }
}
// callback for the ajax call
function loadSuccess(data){
    //workarroun:the call methode can't be called directly because the this doesn't work int he call back
    stopwatch.loadSuccess(data);
}