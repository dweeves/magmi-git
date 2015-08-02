var magmi_multifield=function(listfield,dyncontainer,linetpl,vlist)
{
	this.vlist=vlist;
	this.listfield=listfield;
	this.dyncontainer=dyncontainer;
	this.linetpl=linetpl;

	this.getinputline=function(fieldname,dvalue,linetpl)
	{
		linetpl=linetpl.replace('{fieldname}',fieldname).replace('{value}',dvalue).replace('{fieldname.enc}',encodeURIComponent(fieldname));

		return linetpl;
	};


	this.buildparamlist=function()
	{
	  var value=$F(this.listfield);
	  var content='';
	  if(value!="")
	  {
	 	var arr=value.split(",");
	 	for(var i=0;i<arr.length;i++)
	 	{
	 	 arr[i]=arr[i].trim();
	 	}
	  	var farr=[];
	 	 arr.each(function(it){
	 		 it=it.trim();
	 	 	 if(it!='')
	 	 	 {
	 	 		 var v=typeof(this.vlist[it])!='undefined'?this.vlist[it]:'';
	  			farr.push({'field':it,'value':v});
	 	 	 }
	  	},this);
	 	 farr.each(function(it){content+=this.getinputline(it.field,it.value,this.linetpl);},this);
	 	 $(this.listfield).setValue(arr.join(','));
	  }
	  $(this.dyncontainer).update(content);
	};
};
