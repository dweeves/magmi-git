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
	  var value=$F(listfield)
	  var content='';
	  if(value!="")
	  {
	 	var arr=value.split(",");
	  	var farr=[];
	 	 arr.each(function(it){
	 	 	 if(it!='')
	 	 	 {
	 	 		 var v=typeof(this.vlist[it])!='undefined'?this.vlist[it]:'';
	  			farr.push({'field':it,'value':v});
	 	 	 }
	  	},this);
	 	 farr.each(function(it){content+=this.getinputline(it.field,it.value,this.linetpl)},this);
	  }
	  $(this.dyncontainer).update(content);
	};
}