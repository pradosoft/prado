Prado.WebUI.TSlider = Class.extend(Prado.WebUI.PostBackControl,
{	
	onInit : function (options)
	{
		this.options=options;
		this.onChange=options.onChange;
		options.onChange=this.change.bind(this);
		
		this.hiddenField=$(this.options.ID+'_1');
		new Control.Slider(options.ID+'_handle',options.ID, options);
		
		if(this.options['AutoPostBack']==true)
			Event.observe(this.hiddenField, "change", Prado.PostBack.bindEvent(this,options));
	},
	
	change : function (v)
	{
		this.hiddenField.value=v;
		if (this.onChange)
		{
			this.onChange(v);
		}
		if(this.options['AutoPostBack']==true)
		{
			Event.fireEvent(this.hiddenField, "change");
		}
	}
});