

handleRunChoice=function(radioname,changeinfo)
{
	var changed=changeinfo.changed;
	var sval=$('input:checked[type="radio"][name="'+radioname+'"]').pluck('value');
	if(sval=='saveprof')
	{
		saveProfile(1,function(){$('#runmagmi').submit();});
	}
	if(sval=='useold')
	{
		$('#runmagmi').submit();
	}
	if(sval=='applyp')
	{
		changed.each(function(it){
			$('<input type="hidden" name="'+it.key+'" value="'+it.value+'">').insertAfter('#runmagmi');
		});
		$('#runmagmi').submit();
	}
}

cancelimport=function()
{
 $('#overlay').hide();	
}

updatelastsaved=function()
{ 
 gatherclasses(['DATASOURCES','GENERAL','ITEMPROCESSORS']);
 window.lastsaved=$('#saveprofile_form').serializeArray();	
};

comparelastsaved=function()
{
 gatherclasses(['DATASOURCES','GENERAL','ITEMPROCESSORS']);
 var curprofvals=$('#saveprofile_form').serializeArray();
 var changeinfo={changed:false,target:''};
 var out="";
 var diff={};
 changeinfo.target='paramchanged';
 curprofvals.each(function(idx,kv)
 {
	 var lastval=window.lastsaved.get(kv.key);
 	if(kv.value!=lastval)
 	{
		diff[kv.key]=kv.value;
		if(kv.key.substr(0,8)=="PLUGINS_")
		{
			changeinfo.target='pluginschanged';
		}
	}
 });

changeinfo.changed=diff;
if(changeinfo.changed.size()==0)
{
	changeinfo.changed=false;
}
 return changeinfo;
};

addclass=function(it,o)
{
	if(it.checked){
		o.arr.push(it.name);
	}
};

gatherclasses=function(tlist)
{
	$.each(tlist,function(idx,t){
		var context={arr:[]};
		$(".pl_"+t.toLowerCase()).each(function(idx,it){addclass(it,context)});
		var target=$("#plc_"+t);
		target.val(context.arr.join(","));
	});
};

initConfigureLink=function(maincont)
{
 var cfgdiv=maincont.children('.pluginconf');
 if(cfgdiv.length>0)
 {
 	cfgdiv=cfgdiv[0];
 	var confpanel=maincont.children('.pluginconfpanel');
	 confpanel=confpanel[0]
	 $(cfgdiv).unbind('click');
 	$(cfgdiv).click(function(ev){
 		
 	 	$(confpanel).toggleClass('selected');
 		 $(confpanel).children('.ifield').each(function(idx,it){
 			$(it).children('.fieldhelp').each(function(idx,fh){
 				$(fh).click(function(idx,ev){
 					$(it).children('.fieldsyntax').each(function(idx,el){$(el).toggle();})
 						});
 				});
 			});
 		ev.stopPropagation(); 
 	});

 }
};
showConfLink=function(maincont)
{
	var cfgdiv=$(maincont).children('.pluginconf');
	if(cfgdiv.length>0)
	 {
	 
	cfgdiv=cfgdiv[0];
	$(cfgdiv).show();
	 }
	
};

loadConfigPanel=function(container,profile,plclass,pltype,engclass)
{
	var params={profile:profile,
			plugintype:pltype,
			pluginclass:plclass,
			engineclass:engclass};
	
	
loaddiv($(container),'ajax_pluginconf.php',decodeURIComponent($.param(params)),	function(){
		showConfLink($(container.parentNode));
 		initConfigureLink($(container.parentNode));
 	}); 
};

removeConfigPanel=function(container)
{
var cfgdiv=$(container.parentNode).children('.pluginconf');
cfgdiv=cfgdiv[0];
$(cfgdiv).unbind('click');
 $(cfgdiv).hide();
 $(container).removeClassName('selected');
 $(container).html('');
};


initAjaxConf=function(profile,engineclass)
{
	//foreach plugin selection
	$('.pluginselect').each(function(idx,pls)
	{
		var children=$(pls).children();
		var del=children[0];
		var dtagname=del.nodeName.toLowerCase();
		del=$(del);
		del.cb=(dtagname=="select"?del.change:del.click);
			
		//check the click
		del.cb(function()
		{
			var el=this;
			var tagname=el.nodeName.toLowerCase();
			el=$(el);
			var plclass=(tagname=="select")?el.val():el.attr('name');
			var elclasses=el.attr('class').split(' ');
			var pltype="";
			$.each(elclasses,function(idx,it){if(it.substr(0,3)=="pl_"){pltype=it.substr(3);}});
			var doload=(tagname=="select")?true:el.attr('checked')=='checked';	
			var targets=$(pls.parentNode).children(".pluginconfpanel");
			var container=targets[0];
			if(doload)
			{
				loadConfigPanel(container,profile,plclass,pltype,engineclass);
			}
			else
			{
				removeConfigPanel(container);
			}
		});
	});			
};

initDefaultPanels=function()
{
	$('.pluginselect').each(function(idx,it){initConfigureLink($(it.parentNode));});
	updatelastsaved();
};


saveProfile=function(confok,onsuccess)
{
	gatherclasses(['DATASOURCES','GENERAL','ITEMPROCESSORS']);
  	updatelastsaved();
  	loaddiv("#profileconf_msg",'magmi_saveprofile.php',$('#saveprofile_form').serialize(),
  		function(){
		  if(confok)
        {
			 onsuccess();
		  }
		  else
		  {
		  	$('profileconf_msg').show();
		  }
  	});	
};


