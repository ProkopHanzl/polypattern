<!DOCTYPE html>
<html>
<head>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/howler/2.1.1/howler.js"></script>
	<link rel="stylesheet" type="text/css" href="resources/styles.css">
	<link rel="icon" type="image/png" href="resources/favicon.png">
	<meta name="description" content="PolyPattern is a free online polyrhythm generator! Try out that nifty 7:11 polyrhythm or something even more gnarly, like a 15:16 one!">
	<title>PolyPattern - Polyrhythm Generator</title>
</head>
<body>
	<section id="bodySection">
		<h1 style="font-size: 2em;">PolyPattern</h1>
		<h3>Polyrhythm Generator</h3>
		<div class="projectInfo">
			<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
				<input type="hidden" name="cmd" value="_s-xclick" />
				<input type="hidden" name="hosted_button_id" value="VNC9RW6MU8QXY" />
				This project, like all my other ones, doesn't have any ads and is funded solely by donations. Consider leaving one to support me!<br>
				<input type="submit" class="paypalButton" name="submit" value="DONATE ON PAYPAL" alt="Donate with PayPal">
			</form>
		</div>
		<div id="generalContainer">
			<div id="settingsContainer">
				<div id="bpmDiv">
					<input type="range" min="40" max="240" value="120" id="bpmSlider">
					<div id="bpmTag">BPM: 120</div>
				</div>
				<div id="globalVolDiv">
					<input type="range" min="0" max="100" value="0" id="globalVolSlider">
					<div id="globalVolTag">GLOBAL VOLUME: OFF</div>
				</div>
			</div>
			<div id="polyContainer">
				<div class="rhythmDiv" id="rhythm0Div">
					<div class="beatDisplayContainer" id="beatDisplayContainer0">
					</div>
					<div class="rhythmSpeed" id="rhythm0" min="0" max="64" autocomplete="off" class="rhythmSpeed" contenteditable="true" onclick="document.execCommand('selectAll',false,null)">0</div>
					<div class="chooseInstrument">
						<div class="instrumentRow1">
							<div id="inst0-0" class="instrumentDiv selectedInst" onclick="setInst(0, 0)">KICK</div>
							<div id="inst0-1" class="instrumentDiv" onclick="setInst(0, 1)">SNARE</div>
						</div>
						<div class="instrumentRow2">
							<div id="inst0-2" class="instrumentDiv" onclick="setInst(0, 2)">HI-HAT</div>
							<div id="inst0-3" class="instrumentDiv" onclick="setInst(0, 3)">CLICK</div>
						</div>
					</div>
					<div class="indiSettings">
						<div class="accentFirstDiv" id="accentFirstDiv0" onclick="toggleAcc(0);">
							ACCENT FIRST BEAT
						</div>
						<div class="volumeDiv" id="volumeDiv0">
							<input type="range" min="0" max="100" value="75" class="slider" id="volumeSlider0">
							<div class="volumeTag">VOLUME</div>
						</div>
					</div>
					<div class="closeRhythm" id="closeRhythm0" onclick="closeRhythm(0)">×</div>
				</div>
			</div>
			<div id="addRhythm" onclick="addRhythm();">
				<div id="rhythmError">Turn up the global volume first!</div>
				<div id="rhythmErrorEdit">Edit your rhythm's speed before creating a new one!</div>
				Add rhythm...
			</div>
		</div>
	</section>
	<section id="footerSection">
		&copy; 2019 Prokop Hanzl. Version 1.2.0
	</section>
	<script type="text/javascript">
		bpm = 120; //measure length == 240/bpm * 1000
		var errorOn = false;
		running = false;

		rhythmNum = 0;

		var rhythmValue = [0];
		var selectedInst = [0];
		var accentFirst = [0];
		var rhythmPlaying = [0];

		var instruments = [[
		new Howl({src: 'resources/sounds/0.wav'}),
		new Howl({src: 'resources/sounds/1.wav'}),
		new Howl({src: 'resources/sounds/2.wav'}),
		new Howl({src: 'resources/sounds/3.wav'})]];

		var allowedLetters = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

		for (var i = 0; i < instruments[0].length; i++) {
			instruments[0][i].volume(0.66);
		}

		$('.rhythmSpeed').keydown(function(e) {
			var keycode = e.keyCode;

			var printable = 
				(keycode > 47 && keycode < 58)   || // number keys
				keycode == 32 || keycode == 13   || // spacebar & return key(s) (if you want to allow carriage returns)
				(keycode > 64 && keycode < 91)   || // letter keys
				(keycode > 95 && keycode < 112)  || // numpad keys
				(keycode > 185 && keycode < 193) || // ;=,-./` (in order)
				(keycode > 218 && keycode < 223);   // [\]' (in order)

				if (printable) {
					return $(this).text().length <= 1; 
				}
			});
		


		$("#rhythm0").on('keydown paste', function () {
			setTimeout(function() {
				rhythmValue[0] = $('#rhythm0').html();
				changeRhythm(0);
				document.getElementById('beatDisplayContainer0').innerHTML = '';
				for (var i = 0; i < document.getElementById('rhythm0').innerHTML; i++) {
					document.getElementById('beatDisplayContainer0').insertAdjacentHTML('beforeend', '<div class="orangeDiv orangeDiv0"></div>');
				}
			}, 1);

		});

		document.getElementById('bpmSlider').oninput = function() {
			if(document.getElementById('bpmSlider').value > 0) {
				bpm = document.getElementById('bpmSlider').value;
				
				document.getElementById('bpmTag').innerHTML = 'BPM: ' + document.getElementById('bpmSlider').value;
			}
		};

		document.getElementById('globalVolSlider').oninput = function() {
			if (document.getElementById('globalVolSlider').value > 0) {
				document.getElementById('globalVolTag').innerHTML = 'GLOBAL VOLUME: ' + document.getElementById('globalVolSlider').value + '%';
				if (!running) {
					startAudio();
					running = true;
				}
			} else {
				document.getElementById('globalVolTag').innerHTML = 'GLOBAL VOLUME: OFF';
			}
			Howler.volume(document.getElementById('globalVolSlider').value / 100);
		};

		document.getElementById('volumeSlider0').oninput = function() {
			for (var i = 0; i < 4; i++) {
				instruments[0][i].volume(document.getElementById('volumeSlider0').value / 100 * 0.66);
			}
		};

		function addRhythm() {
			if(running && rhythmValue[rhythmValue.length - 1]) {
				rhythmNum++
				for (var i = 0; i < rhythmNum + 1; i++) {
					setTimeout(function() {
						$("#rhythm" + i).on('keydown paste', function () {
							setTimeout(function() {
								rhythmValue[i] = $('#rhythm' + i).html();
								document.getElementById('beatDisplayContainer' + i).innerHTML = '';
								for (var j = 0; j < document.getElementById('rhythm' + i).innerHTML; j++) {
									document.getElementById('beatDisplayContainer' + i).insertAdjacentHTML('beforeend', '<div class="orangeDiv orangeDiv' + rhythmNum + '"></div>');
								}
							}, 1);
						});
					}, 1);
				}

				setTimeout(function() {
					$('.rhythmSpeed').keydown(function(e) {
						var keycode = e.keyCode;

						if((keycode > 47 && keycode < 58) || keycode == 32 || keycode == 13 || (keycode > 64 && keycode < 91) || (keycode > 95 && keycode < 112)  || (keycode > 185 && keycode < 193) || (keycode > 218 && keycode < 223)) {
							return $(this).text().length <= 1; 
						}
					});
					document.getElementById('volumeSlider' + rhythmNum).oninput = function() {
						for (var i = 0; i < 4; i++) {
							instruments[rhythmNum][i].volume(document.getElementById('volumeSlider' + rhythmNum).value / 100);
						}
					};
				}, 1);

				document.getElementById('polyContainer').insertAdjacentHTML('beforeend', '<div class="rhythmDiv" id="rhythm' + rhythmNum + 'Div"> <div class="beatDisplayContainer" id="beatDisplayContainer' + rhythmNum + '"> </div> <div class="rhythmSpeed" id="rhythm' + rhythmNum + '" min="0" max="64" autocomplete="off" class="rhythmSpeed" contenteditable="true" onclick="document.execCommand(\'selectAll\', false, null)">0</div> <div class="chooseInstrument"> <div class="instrumentRow1"> <div id="inst' + rhythmNum + '-0" class="instrumentDiv selectedInst" onclick="setInst(' + rhythmNum + ', 0)">KICK</div> <div id="inst' + rhythmNum + '-1" class="instrumentDiv" onclick="setInst(' + rhythmNum + ', 1)">SNARE</div> </div> <div class="instrumentRow2"> <div id="inst' + rhythmNum + '-2" class="instrumentDiv" onclick="setInst(' + rhythmNum + ', 2)">HI-HAT</div> <div id="inst' + rhythmNum + '-3" class="instrumentDiv" onclick="setInst(' + rhythmNum + ', 3)">CLICK</div> </div> </div> <div class="indiSettings"> <div class="accentFirstDiv" id="accentFirstDiv' + rhythmNum + '" onclick="toggleAcc(' + rhythmNum + ');"> ACCENT FIRST BEAT </div> <div class="volumeDiv" id="volumeDiv' + rhythmNum + '"> <input type="range" min="0" max="100" value="75" class="slider" id="volumeSlider' + rhythmNum + '"> <div class="volumeTag">VOLUME</div> </div> </div> <div class="closeRhythm" id="closeRhythm' + rhythmNum + '" onclick="closeRhythm(' + rhythmNum + ')">×</div> </div>');

				rhythmValue.push(0);
				selectedInst.push(0);
				accentFirst.push(0);
				rhythmPlaying.push(0);
				instruments.push([
					new Howl({src: 'resources/sounds/0.wav'}),
					new Howl({src: 'resources/sounds/1.wav'}),
					new Howl({src: 'resources/sounds/2.wav'}),
					new Howl({src: 'resources/sounds/3.wav'})]);

				for (var i = 0; i < instruments[instruments.length - 1].length; i++) {
					instruments[instruments.length - 1][i].volume(0.66);
				}

				var rhythmNumCur = rhythmNum;
				$("#rhythm" + rhythmNumCur).on('keydown paste', function () {
					setTimeout(function() {
						rhythmValue[rhythmNumCur] = $('#rhythm' + rhythmNumCur).html();
						document.getElementById('beatDisplayContainer' + rhythmNumCur).innerHTML = '';
						for (var i = 0; i < document.getElementById('rhythm' + rhythmNumCur).innerHTML; i++) {
							document.getElementById('beatDisplayContainer' + rhythmNumCur).insertAdjacentHTML('beforeend', '<div class="orangeDiv orangeDiv' + rhythmNumCur + '"></div>');
						}
					}, 1);

				});

				setTimeout(function() {document.getElementById("rhythm" + rhythmNum + "Div").style.opacity = "1";}, 1);

				for (var i = 0; i < rhythmNum; i++) {
					if (document.getElementById("rhythm" + i)) {
						document.getElementById("rhythm" + i).value = rhythmValue[i];
					}
				}
			} else {
				if (!running) {
					document.getElementById('addRhythm').style.left = '-16px';
					setTimeout(function() {
						document.getElementById('addRhythm').style.left = '16px';
					}, 50);
					setTimeout(function() {
						document.getElementById('addRhythm').style.left = '0px';
					}, 100);
					document.getElementById('rhythmError').style.opacity = '1';
					setTimeout(function() {document.getElementById('rhythmError').style.opacity = '0';}, 1000);
				} else {
					document.getElementById('addRhythm').style.left = '-16px';
					setTimeout(function() {
						document.getElementById('addRhythm').style.left = '16px';
					}, 50);
					setTimeout(function() {
						document.getElementById('addRhythm').style.left = '0px';
					}, 100);
					document.getElementById('rhythmErrorEdit').style.opacity = '1';
					setTimeout(function() {document.getElementById('rhythmErrorEdit').style.opacity = '0';}, 1500);
				}
			}
		}

		function closeRhythm(rhythm) {
			document.getElementById("rhythm" + rhythm + "Div").style.opacity = "0";
			setTimeout(function() {
				document.getElementById("rhythm" + rhythm).innerHTML = '0';
			}, 300);
			rhythmValue[rhythm] = 'NULL';
			setTimeout(function() {document.getElementById("rhythm" + rhythm + "Div").style.display = 'none';}, 300);
			for (var i = 0; i < 4; i++) {
				instruments[rhythm][i].fade(1, 0, 500);
			}
		}

		function toggleAcc(rhythm) {
			if (accentFirst[rhythm]) {
				accentFirst[rhythm] = false;
				document.getElementById('accentFirstDiv' + rhythm).style.color = "#808080";
			} else {
				accentFirst[rhythm] = true;
				document.getElementById('accentFirstDiv' + rhythm).style.color = "#80ffff";
			}
		}

		function setInst(rhythm, inst) {
			for (var i = 0; i < 4; i++) {
				document.getElementById('inst' + rhythm + '-' + i).className = 'instrumentDiv';
			}
			document.getElementById('inst' + rhythm + '-' + inst).className = 'instrumentDiv selectedInst';
			selectedInst[rhythm] = inst;
		}

		function startAudio() {
			if (bpm > 0) {
				var delay;

				for (var i = 0; i < document.getElementsByClassName('orangeDiv').length; i++) {
					document.getElementsByClassName('orangeDiv')[i].style.opacity = '0';
				}

				for (var j = 0; j < rhythmNum + 1; j++) {
					for (var i = 0; i < rhythmValue[j]; i++) {
						if (i == 0 && accentFirst[j] == 1) {
							for (var k = 0; k < 4; k++) {
								instruments[j][k].volume(instruments[j][k].volume() * 1.5);
								playSoundTimeout(j, i);
								instruments[j][k].volume(instruments[j][k].volume() / 1.5);
							}
						} else {
							playSoundTimeout(j, i);
						}
						
					}
				}
				setTimeout(startAudio, 240 / bpm * 1000);
			}
		}

		function playSoundTimeout(index1, index2) {
			setTimeout(function() {
				instruments[index1][selectedInst[index1]].play();
				delay = 240 / bpm * 1000 + 240 / bpm * 1000 / rhythmValue[index1];
				document.getElementsByClassName('orangeDiv' + index1)[index2].style.opacity = '1';
			}, index2 * 240 / bpm / rhythmValue[index1] * 1000);
		}

		function changeRhythm(rhythm) {
			for (var i = 0; i < rhythmValue[rhythm]; i++) {
				
			}
		}
	</script>
</body>
</html>