Prado.Effect = 
{
	Highlight : function(element, duration)
	{
		new Effect.Highlight(element, {'duration':duration});
	},

	Scale : function(element, percent)
	{
		new Effect.Scale(element, percent);
	},
	
	MoveBy : function(element, toTop, toLeft)
	{
		new Effect.MoveBy(element, toTop, toLeft);
	},

	ScrollTo : function(element, duration)
	{
		new Effect.ScrollTo(element, {'duration':duration});
	}
}