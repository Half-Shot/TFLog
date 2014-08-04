/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */



var exampleSocket = new WebSocket("ws://localhost:8888/ws");

exampleSocket.onopen = function (event) {
   console.log("Live Feed Open!");
   $(".islive").text("Live!")
};

exampleSocket.onmessage = function (event) {
  console.log("Event arrived that needs to be processed!");
  if(event.data == "NewMatch"){
   console.log("Match finished!");
   exampleSocket.close();
  }
  else{
    $.ajax("index.php",{type:"GET",data:{ ajaxEvent: "TFLog.ProcessEvent",ajaxModule:"TFLog",theme:"theme-bootstrap", tfevent:event.data},success:
    function(eventHTML)
    {
      $('tbody').prepend(eventHTML);
    }});
  }
}   

exampleSocket.onclose = function (event){
  console.log("The match has finished!");
  $(".islive").html("Not Live");
}

exampleSocket.onerror = function (event){
  console.log("Coudln't connect, probably not running!");
}