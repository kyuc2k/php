<?php
require "nso/config.php";

if(!getUser()){
header("Location: nso/login.php");
}

?>

<h2>Java Cloud Gaming</h2>

<button onclick="start()">Start</button>
<button onclick="stop()">Stop</button>

<div id="status"></div>

<br>

<iframe id="game" width="100%" height="700"></iframe>

<script>

function start(){

fetch("api/start.php")
.then(r=>r.json())
.then(d=>{

document.getElementById("status").innerHTML="Running"

document.getElementById("game").src=d.url

})

}

function stop(){

fetch("api/stop.php")
.then(()=>{

document.getElementById("status").innerHTML="Stopped"

document.getElementById("game").src=""

})

}

</script>