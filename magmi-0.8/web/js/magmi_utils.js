
window.afterajax=null;

geturl=function(addr,params,onsuccess) {  
	window.afterajax=null;
	var r = $.ajax({  
	 type: 'POST',  
	 url: addr,  
	 context:document.body,
	 async: false, 
	 data : params,
	 datatype: 'text',
	 success:function(){
		 
		 	if(onsuccess)
		 	{
		 		onsuccess();
		 	}
	 }
	}).responseText;  
	return r;  
	}  

loaddiv=function(zdiv,url,params, onsuccess)
{
	$(zdiv).html(geturl(url,params,onsuccess));
	if(window.afterajax)
	{
		window.afterajax();
	}
}
var magmi_multifield=function(listfield,dyncontainer,linetpl,vlist)
{
	this.vlist=vlist;
	this.listfield=listfield;
	this.dyncontainer=dyncontainer;
	this.linetpl=linetpl
	
	this.getinputline=function(fieldname,dvalue,linetpl)
	{
		linetpl=linetpl.replace('{fieldname}',fieldname).replace('{value}',dvalue).replace('{fieldname.enc}',encodeURIComponent(fieldname));

		return linetpl;
	};


	this.buildparamlist=function()
	{
	var flistfield=$('#'+this.listfield);
	var obj=this;
	if(flistfield.length==0)
		{
		flistfield=$(document.getElementById(this.listfield));
		}
	  var value=flistfield.val();
	  var content='';
	  if(value!="")
	  {
	 	var arr=value.split(",");
	  	var farr=[];
	 	 $.each(arr,function(idx,xval){
	 	 	 if(xval!='')
	 	 	 {
	 	 		 var v=typeof(obj.vlist[xval])!='undefined'?obj.vlist[xval]:'';
	  			farr.push({'field':xval,'value':v});
	 	 	 }
	  	});
	 	 $.each(farr,function(idx,it){content+=obj.getinputline(it.field,it.value,obj.linetpl)});
	  }
	  var dyncont=$('#'+this.dyncontainer);
	  if(dyncont.length==0)
	  {
		  dyncont=$(document.getElementById(this.dyncontainer));
	  }
		
	  $(dyncont).html(content);
	};
}

loadDetails=function(dtype,sessid)
{
	var detdiv='#log_'+dtype+'_details';
	if($(detdiv).hasClass("loaded"))
	{
		$(detdiv).hide();
		$(detdiv).removeClass("loaded");
		$('#'+dtype+'_link').html("Show Details");
	}
	else
	{
		var paramq={key:dtype,PHPSESSID:sessid};
		
		loaddiv(detdiv,'progress_details.php',$.param(paramq),function()
				{
					$(detdiv).addClass("loaded");
					$('#'+dtype+'_link').html("Hide Details");
					$(detdiv).show();
					
				})
	}
};