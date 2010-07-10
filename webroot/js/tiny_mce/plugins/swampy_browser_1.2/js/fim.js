/**
	@name Swampy File and Image Manager (SwampyBrowser) Browser JS classes
	@version 1.2
	@author Domas Labokas domas@htg.lt
	@date 2009 04 03
	@see http://www.swampyfoot.com
	@copyright 2009 SwampyFoot
	@license SwampyBrowser is licensed under a Creative Commons Attribution-Noncommercial 3.0
	@license http://creativecommons.org/licenses/by-nc/3.0/
**/
var IE = document.all ? true : false;
/* Mini config */
var viewMode =		{thumb:"thumb", row:"row"};
var viewModeImg =	{thumb:"styles/images/thumb_view.png", row:"styles/images/row_view.png"};
var filenameLen =	{thumb:10, row:32};
var default_view_mode = viewMode.thumb;

/****************** Some usefull misc functions *******************************/
function BBCodeToHTML(str){return str.replace(/\[b\](.*?)\[\/b\]/g, "<b>$1</b>");}
function getNodeValue(node){return IE ? node.text : node.textContent;}

/********************* Ajax Class *********************************************/
// Provide the XMLHttpRequest class for IE 5.x-6.x:
if( typeof XMLHttpRequest == "undefined" ) XMLHttpRequest = function()
{
	try { return new ActiveXObject("Msxml2.XMLHTTP.6.0") } catch(e) {}
	try { return new ActiveXObject("Msxml2.XMLHTTP.3.0") } catch(e) {}
	try { return new ActiveXObject("Msxml2.XMLHTTP") } catch(e) {}
	try { return new ActiveXObject("Microsoft.XMLHTTP") } catch(e) {}
	throw new Error( "This browser does not support XMLHttpRequest." )
};

function Ajax()
{
	var STATE = {UNINITIALIZED:0, LOADING:1, LOADED:2, INTERACTIVE:3, COMPLETE:4};
	var STATUS = {OK:200};

	this.postRequest = function(url, params, opt)
	{
		var xhr = new XMLHttpRequest();

		xhr.onreadystatechange = function()
		{
			switch(xhr.readyState)
			{
				case STATE.UNINITIALIZED:
					if(opt.onFailure)
						opt.onFailure(xhr, opt);
					delete(xhr);
					break;
				case STATE.LOADING:
				case STATE.LOADED:
				case STATE.INTERACTIVE:
					if(opt.onLoading) opt.onLoading(xhr, opt);
					break;
				case STATE.COMPLETE:
					if(xhr.status == STATUS.OK)
						if(opt.onSuccess) opt.onSuccess(xhr, opt);
					else
						if(opt.onFailure) opt.onFailure(xhr, opt);
					delete(xhr);
					break;
			}
		};
		xhr.open("POST", url, true);
		xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		xhr.setRequestHeader("Content-length", params.length);
		xhr.setRequestHeader("Connection", "close");
		xhr.send(params);
	}
}

/********************************** HTML Content Class ************************/
function Content()
{
	var ajax = new Ajax();
	var alert_obj = null;
	var alert_timer = null;
	var alert_lock = false;

	this.loadHTML = function(url, params, targetID, hideLoading)
	{
		var targetObj = document.getElementById(targetID);
		var cnt = this;
		cnt.showAlert('loader', Lang.initiating ,60);
		ajax.postRequest(url, params,
			{
				onLoading : function(xhr, opt){cnt.showAlert('loader', Lang.loading, 60);},
				onFailure : function(xhr, opt){cnt.showAlert('error', Lang.loading_failure, 5);},
 				onSuccess : function(xhr, opt){cnt.showAlert('done', Lang.done , 0.25); targetObj.innerHTML = xhr.responseText;}
			}
		);
	}

	this.loadXML = function(url, params, onResponse)
	{
		var cnt = this;
		ajax.postRequest(url, params,
			{
				onLoading : function(xhr, opt){cnt.showAlert('loader', Lang.loading, 60);},
				onFailure : function(xhr, opt){cnt.showAlert('error', Lang.loading_failure, 5);},
				onSuccess : function(xhr, opt)
				{
//					alert(xhr.responseText);
					var rType = xhr.responseXML.documentElement.getAttribute("type");
					if(!rType) 
						return cnt.showAlert('error', Lang.loading_failure, 5);

					if(rType != "data")
						cnt.showAlert(rType, BBCodeToHTML( getNodeValue( xhr.responseXML.documentElement)) , 5, true);
					else
						cnt.showAlert('done', Lang.done , 0.25);

					onResponse(rType, xhr.responseXML);
				}
			}
		);
	}

	this.updateHTML = function(targetID, html)
	{
		var targetObj = document.getElementById(targetID);
		targetObj.innerHTML = html;
	}

	this.showAlert = function(aType, msg, sec, lock)
	{	
		if(alert_lock && !lock) return;
		if(alert_timer) clearTimeout(alert_timer);
		alert_obj.innerHTML = "<p class='"+aType+"'>"+msg+"</p>\n";
		this.showObject(alert_obj);
		alert_lock = (lock) ? true : false;
		alert_obj.onclick = new Function("browser.cnt.hideAlert();");
		alert_timer = setTimeout (this.hideAlert, sec * 1000);
	}

	this.init = function()
	{
		alert_obj = document.getElementById('alert');
	}

	this.hideAlert = function() {alert_lock = false; alert_obj.style.display = "none";}
	this.showObject =   function(obj) {obj.style.display = "";}
	this.hideObject =   function(obj) {obj.style.display = "none";}
	this.toggleObject = function(obj) {obj.style.display = (obj.style.display == "none") ? "" : "none";}
}
/************************ Upload Class ****************************************/
function Upload(cnt)
{
	var content = cnt;
	var upload_form_obj = null;
	var loadin_form_obj = null;
	
	this.start = function(obj)
	{
		upload_form_obj = obj;
		loadin_form_obj = document.getElementById('loading_form');
		content.hideObject(upload_form_obj);
		content.showObject(loadin_form_obj);
		content.showAlert('loader', Lang.uploading, 60);
	}

	this.stop = function(rType, rMSG)
	{
		content.showObject(upload_form_obj);
		content.hideObject(loadin_form_obj);
		content.showAlert(rType, unescape(rMSG), 30, true);
	}
}

/********************* SwampyBrowser Class ************************************/
function SwampyBrowser(dir, file)
{
	/******************* Private members and functions ********************/
	var current_dir = dir;
	var current_file = file;
	var content = new Content();
	var filesXML = null;
	var view_mode = default_view_mode;
	var thumb_obj = null;
	var options_obj = null;
	
	var updateInfo = function(dir, file, filename, extension, filesize, dimentions)
	{
		content.updateHTML('label[path]', dir+file);
		content.updateHTML('label[directory]', dir);
		content.updateHTML('label[filename]', filename);
		content.updateHTML('label[extension]', extension);
		content.updateHTML('label[filesize]', filesize);
		content.updateHTML('label[dimentions]', dimentions);
	}

	var selectThumb = function(obj)
	{
 		var opt_obj = obj.getElementsByTagName('div')[0];
 		if(options_obj && opt_obj != options_obj)
 			content.hideObject(options_obj);

		if(thumb_obj) thumb_obj.className = "";// "file-"+view_mode;
		thumb_obj = obj;
		obj.className = "selected";//"file-"+view_mode+" selected";
	}

	var showFiles = function()
	{
		if(!filesXML) return;

		var files = filesXML.getElementsByTagName('file');
		var content =  document.getElementById('content');
		var nameLen = (view_mode == viewMode.thumb) ? filenameLen.thumb : filenameLen.row;
		content.innerHTML = "";

		for(var i=0; i < files.length; i++)
		{
			var fName = 	getNodeValue(files[i]);
			var fType =	files[i].getAttribute("type");
			var dir =	files[i].getAttribute("dir");
			var file =	files[i].getAttribute("file");
			var extension =	files[i].getAttribute("extension");
			var size =	files[i].getAttribute("size");
			var dim =	files[i].getAttribute("dimentions");
			var BG =	files[i].getAttribute("bg");
			var cell = 	document.createElement('div');
			var iStyle = 	"";
			var ext = extension;
			var displayFilename = (fName.length < nameLen) ? fName : fName.substr(0, nameLen-3)+"...";
			
			var optDiv = "\n<div class='options' style='display:none;'>\n";

			switch(fType)
			{
				case "parent":
					ext = fType;
					cell.ondblclick = new Function("browser.enterDir('"+dir+"');");
					break;
				case "dir":
					ext = fType;
					cell.ondblclick = new Function("browser.enterDir('"+dir+file+"/');");
					cell.onclick = new Function("browser.selectFile('"+file+"','"+fName+"','"+extension+"','','', this);");
					optDiv += "\t<a href=\"javascript:browser.enterDir('"+dir+file+"/');\">"+Lang.opt_enter+"</a>\n";
					optDiv += "\t<a href=\"javascript:browser.deleteDir('"+file+"');\">"+Lang.opt_delete+"</a>\n";
					optDiv += "\t<a href=\"javascript:browser.renameFile('"+file+"','"+fName+"');\">"+Lang.opt_rename+"</a>\n";
					break;
				case "file":
					cell.ondblclick = new Function("browser.insertFile('"+file+"');");
					cell.onclick = new Function("browser.selectFile('"+file+"','"+fName+"','"+extension+"','"+size+"','', this);");
					optDiv += "\t<a href=\"javascript:browser.insertFile('"+file+"');\">"+Lang.opt_insert+"</a>\n";
					break;
				case "image":
					cell.ondblclick = new Function("browser.insertImage('"+file+"');");
					cell.onclick = new Function("browser.selectFile('"+file+"','"+fName+"','"+extension+"','"+size+"','"+dim+"', this);");
					optDiv += "\t<a href=\"javascript:browser.insertImage('"+file+"');\">"+Lang.opt_insert+"</a>\n";
					if(view_mode == viewMode.thumb) iStyle = "background:url('"+BG+"') no-repeat center;";
					break;
			}
			if(fType == "image" || fType == "file")
			{
				optDiv += "\t<a href=\"javascript:browser.deleteFile('"+file+"');\">"+Lang.opt_delete+"</a>\n";
				optDiv += "\t<a href='/"+dir+file+"' target='_blank'>"+Lang.opt_download+"</a>\n";
				optDiv += "\t<a href=\"javascript:browser.previewFile('"+file+"');\">"+Lang.opt_preview+"</a>\n";
				optDiv += "\t<a href=\"javascript:browser.renameFile('"+file+"','"+fName+"');\">"+Lang.opt_rename+"</a>\n";
			}
			optDiv += "</div>\n";

			optDiv += "<div class='image ext-"+ext+"' style=\""+iStyle+"\"></div>\n";
			optDiv += "<label class='name'>"+displayFilename+"</label>\n";
			optDiv += "<label class='size'>"+size+"</label>\n";
			optDiv += "<label class='dim'>"+dim+"</label>\n";
			if(fType != "parent")
				optDiv += "<div class='ctrl' onclick=\"browser.toggleOptions(this, '"+file+"');\"></div>\n";

			cell.innerHTML = optDiv;
			cell.id = "file-"+view_mode;
			content.appendChild(cell);
		}
	}

	this.upload = new Upload(content);
	this.cnt = content;

	this.toggleViewMode = function(obj)
	{
		obj.src = (view_mode == viewMode.thumb) ? viewModeImg.row : viewModeImg.thumb;
		view_mode = (view_mode == viewMode.thumb) ? viewMode.row : viewMode.thumb;
		showFiles();
	}

	this.toggleOptions = function(obj, file)
	{
		var current_obj = obj.parentNode.getElementsByTagName('div')[0];
		if(options_obj && options_obj != current_obj)
			content.hideObject(options_obj);

		options_obj = current_obj;
		content.toggleObject(options_obj);
	}

	this.enterDir = function(dir)
	{
		current_dir = dir;
		current_file = "";

		content.loadHTML("scripts/dir_listing.php", "&dir="+current_dir, 'directories', true);
		content.loadXML("scripts/file_listing.php","&dir="+current_dir,	function(rType, rXML){ if(rType=='data') {filesXML = rXML; showFiles();}} );
		updateInfo(current_dir, "", "", "", "", "");
	}

	this.selectFile = function(file, filename, extension, size, dimentions, obj)
	{
		current_file = file;
		updateInfo(current_dir, current_file, filename, extension, size, dimentions);
		selectThumb(obj);
	}

	this.previewFile = function(file)
	{
		content.loadHTML("scripts/preview.php", "&dir="+current_dir+"&file="+file, 'content');
	}

	this.renameFile = function(file, filename)
	{
		var br = this;
		new_name = prompt(Lang.rename_prompt, filename);
		if(new_name && new_name != filename)
			content.loadXML
			(
				"scripts/rename.php",
				"&dir="+current_dir+"&file="+file+"&new_name="+new_name,
				function(rType, rXML){ if(rType == "done") br.enterDir(current_dir); }
			);
	}

	this.deleteFile = function(file)
	{
		var br = this;	
		if(confirm(Lang.file_delete_confirm+" '"+file+"'"))
			content.loadXML
			(
				"scripts/delete_file.php",
				"&dir="+current_dir+"&file="+file+"&confirm=1",
				function(rType, rXML){ if(rType == "done") br.enterDir(current_dir); }
			);
	}
	
	this.addDir = function()
	{
		var br = this;	
		var file = prompt(Lang.folder_name_prompt, "");
		if(file) 
			content.loadXML
			(
				"scripts/add_dir.php",
				"&dir="+current_dir+"&file="+file,
				function(rType, rXML){ if(rType == "done") br.enterDir(current_dir+file+"/"); }
			);
	}
	
	this.deleteDir = function(file)
	{
		var br = this;	
		if(confirm(Lang.file_delete_confirm+" '"+file+"'"))
			content.loadXML
			(
				"scripts/delete_dir.php",
				"&dir="+current_dir+"&file="+file+"&confirm=1",
				function(rType, rXML){ if(rType == "done") br.enterDir(current_dir); }
			);
	}
	
	this.insertImage = function(file)
	{
		content.loadHTML("scripts/format_listing.php", "&dir="+current_dir+"&file="+file, 'content');
	}
	
	this.uploadFile = function()
	{
		content.loadHTML("scripts/upload_file.php", "&dir="+current_dir, 'content');
	}

	this.uploadImage = function()
	{
		content.loadHTML("scripts/upload_image.php", "&dir="+current_dir, 'content');
	}

	this.insertFile = function(file)
	{
		window.opener.insertURL("/"+current_dir+file);
		top.window.close();
	}

	this.init = function()
	{
		var vmObj = document.getElementById("view_mode_img");
		vmObj.src = (default_view_mode == viewMode.thumb) ? viewModeImg.thumb : viewModeImg.row;

		content.init();

		if(current_dir) this.enterDir(current_dir);
	}
}