function create_qr(msg, darkMode){
	var arg = msg;
	if(darkMode){
		arg = {
			text: msg,
			colorDark: "#ffffff",
			colorLight: "#000000"
		};
	}
	var qr = new QRCode(document.getElementById("qrcode"), arg);

	return qr;
}
function toggle_qr(e){
	if (e.innerText == "Show Qr Code"){
		e.innerText = "Hide Qr Code";
	}else{
		e.innerText = "Show Qr Code";
	}

	document.getElementById("qrcode").classList.toggle("hidden");

}
function get_form(e, key_size){
	var note = e.children["note"].value;
	if(note.length < 1){
		alert("Please enter a note.");
		return false;
	}
	var id = e.children["id"].value;

	var key = CryptoJS.enc.Base64.stringify(CryptoJS.lib.WordArray.random(key_size))
	var hash = CryptoJS.enc.Base64.stringify(CryptoJS.SHA256(id+key));
	var encrypted = CryptoJS.AES.encrypt(LZString.compressToBase64(note), key).toString();

	var base_url = window.location.origin+window.location.pathname;
	var save_url = base_url+"?api";
	var read_url = base_url+"?id="+id+"#"+key;

	var params = new URLSearchParams({
		"action": "save",
		"id": id,
		"msg": encrypted,
		"fingerprint": hash
	});

	var request = new XMLHttpRequest;
	request.open("POST", save_url);
	request.setRequestHeader("Content-Type", "Application/x-www-form-urlencoded");

	request.onreadystatechange = function(){
		if(this.readyState === XMLHttpRequest.DONE){
			if(this.status === 200){
				var content = document.getElementById("content");
				var html = "<input type='text' id='url' value='"+read_url+"' onfocus='this.select()'>";
				html += "<button onclick='toggle_qr(this)'>Show Qr Code</button>";
				html += "<div id='qrcode' class='hidden'></div>";
				content.innerHTML = html;
				create_qr(read_url, true); 
				document.getElementById("url").select();
			}else{
				var content = document.getElementById("content");
				var html = "<div class='notice'>An error occured while saving the note: \"";
				var res = JSON.parse(this.response);
				html += res["message"] + "\"</div>";
				content.innerHTML = html;
			}
		}
	}

	request.send(params.toString());


	return false;
}
function get_url_values(){
	var search = window.location.search.substr(1);
	var list = search.split("&");
	var params = {};

	for(var i in list){
		var item = list[i].split("=");
		params[item[0]] = item[1];
	}
	params["key"] = window.location.hash.substr(1);
	return params;	
}
function fetch_note(){
	var keys = get_url_values();

	var fingerprint = CryptoJS.enc.Base64.stringify(CryptoJS.SHA256(keys["id"] + keys["key"]));

	var url = window.location.origin+window.location.pathname+"?api";

	var params = new URLSearchParams({
		"action": "fetch",
		"id": keys["id"],
		"fingerprint": fingerprint
	});

	var request = new XMLHttpRequest;
	request.open("POST", url);
	request.setRequestHeader("Content-Type", "Application/x-www-form-urlencoded");

	request.onreadystatechange = function(){
		if(this.readyState === XMLHttpRequest.DONE && this.status == 200){
			var res = JSON.parse(this.response);
			var msg = CryptoJS.enc.Latin1.stringify(CryptoJS.AES.decrypt(res["note"], keys["key"]));
			var msg = LZString.decompressFromBase64(msg);
			var content = document.getElementById("content");
			content.innerHTML = "<textarea id='output'></textarea>";
			document.getElementById("output").textContent = msg;
		}
	}

	request.send(params.toString());

}
