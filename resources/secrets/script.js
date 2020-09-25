function escape_html(string){
	var elem = document.createElement("p");
	elem.appendChild(document.createTextNode(string));
	return elem.innerHTML;
}

function secret_key(length, keyspace="abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"){
	var key = "";
	for(var i = 0; i < length; i++){
		var random = window.crypto.getRandomValues(new Uint32Array(1))[0];
		random = random / Math.pow(2, 32);
		random = Math.floor(random * keyspace.length);
		key += keyspace[random];
	}
	return key;
}
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
	
	var key = "";

	if(e.op.value.length == 0){
		key = secret_key(key_size);
	}else{
		key = e.op.value;
	}

	var hash = CryptoJS.enc.Base64.stringify(CryptoJS.SHA256(id+key));
	var encrypted = CryptoJS.AES.encrypt(LZString.compressToBase64(note), key).toString();

	var base_url = window.location.origin+window.location.pathname;
	var save_url = base_url+"?api";
	var read_url = base_url+"?id="+id+"#";
	if(e.op.value.length == 0){
		read_url += "k"+key;
	}else{
		read_url += "p";
	}

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
				var html = "<input class='fill' type='text' id='url' autocomplete='off' value='"+read_url+"' onfocus='this.select()'>";
				html += "<button onclick='toggle_qr(this)' class='padding'>Show Qr Code</button>";
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
	if(params["key"] == "p"){
		params["key"] = document.getElementById("op").value;
	}else if(params["key"][0] == "k"){
		params["key"] = params["key"].substr(1);
	}else{
		params["key"] = "null";
	}
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
		if(this.readyState === XMLHttpRequest.DONE){
			var res = JSON.parse(this.response);
			if(this.status == 200){
				var msg = CryptoJS.enc.Latin1.stringify(CryptoJS.AES.decrypt(res["note"], keys["key"]));
				var msg = LZString.decompressFromBase64(msg);
				var content = document.getElementById("content");
				content.innerHTML = "<div class='fill note' id='output' readonly ondblclick='select_text(this.id)'></div>";
				var output = document.getElementById("output");
				output.innerHTML = escape_html(msg);
			}else{
				var content = document.getElementById("content");
				var msg = res.message + " Verify URL or ";
				if(window.location.hash.substr(1) == "p"){
					msg += "password.";
				}else{
					msg += " key.";
				}
				content.insertAdjacentHTML("afterbegin", "<div class='notice error'>"+msg+"</div>");
			}
		}
	}

	request.send(params.toString());
}
function select_text(id){
	var range = document.createRange();
	range.selectNode(document.getElementById(id));
	window.getSelection().empty();
	window.getSelection().addRange(range);
}

function add_password_box(){
	if(window.location.hash.substr(1) == "p"){
		var op = document.getElementById("op-box");
		console.log(op);
		if(op !== null){
			op.classList.add("fill");
			op.classList.add("row");
			op.insertAdjacentHTML("AfterBegin", "<span>Password: <input type='text' name='op' id='op'></span>");
		}
	}
}

