/* Simple Accordion Script 
 * Requires Prototype and Script.aculo.us Libraries
 * By: Brian Crescimanno <brian.crescimanno@gmail.com>
 * http://briancrescimanno.com
 * Adapted to Prado & minor improvements: Gabor Berczi <gabor.berczi@devworx.hu>
 * This work is licensed under the Creative Commons Attribution-Share Alike 3.0
 * http://creativecommons.org/licenses/by-sa/3.0/us/
 */

if (typeof Effect == 'undefined')
  throw("You must have the script.aculo.us Effect library to use this accordion");

Prado.WebUI.TAccordion = Class.create();
Prado.WebUI.TAccordion.prototype =
{
    initialize : function(options)
    {
	this.element = $(options.ID);
	this.onInit(options);
	Prado.Registry.set(options.ID, this);
    },

    onInit : function(options)
    {
        this.accordion = $(options.ID);
        this.options = options;
        this.contents = this.accordion.select('div.'+this.options.contentClass);
        this.isAnimating = false;
        this.current = this.options.defaultExpandedCount ? this.contents[this.options.defaultExpandedCount-1] : this.contents[0];
        this.toExpand = null;

        if (options.maxHeight)
                this.maxHeight = options.maxHeight;
        else
                {
                        this.maxHeight = 0;
                        this.checkMaxHeight();
                }
            
        this.initialHide();
        this.attachInitialMaxHeight();

        var clickHandler =  this.clickHandler.bindAsEventListener(this);
        this.accordion.observe('click', clickHandler);
    },

    expand: function(el) {
        this.toExpand = el.next('div.'+this.options.contentClass);
        if(this.current != this.toExpand){
			this.toExpand.show();
            this.animate();
        }
    },

    checkMaxHeight: function() {
        for(var i=0; i<this.contents.length; i++) {
            if(this.contents[i].getHeight() > this.maxHeight) {
                this.maxHeight = this.contents[i].getHeight();
            }
        }
    },

    attachInitialMaxHeight: function() {
	this.current.previous('div.'+this.options.toggleClass).addClassName(this.options.toggleActive);
        if(this.current.getHeight() != this.maxHeight) this.current.setStyle({height: this.maxHeight+"px"});
    },

    clickHandler: function(e) {
        var el = e.element();
        if(el.hasClassName(this.options.toggleClass) && !this.isAnimating) {
            this.expand(el);
        }
    },

    initialHide: function(){
        for(var i=0; i<this.contents.length; i++){
            if(this.contents[i] != this.current) {
                this.contents[i].hide();
                this.contents[i].setStyle({height: 0});
            }
        }
    },


    enableOverflows: function(enable) {
        if (enable) val = ''; else val = 'hidden';
        this.current.style.overflow = val;
        this.toExpand.style.overflow = val;
    },

    animate: function() {
        var effects = new Array();
        var options = {
            sync: true,
            scaleFrom: 0,
            scaleContent: false,
            transition: Effect.Transitions.sinoidal,
            scaleMode: {
                originalHeight: this.maxHeight,
                originalWidth: this.accordion.getWidth()
            },
            scaleX: false,
            scaleY: true
        };

        effects.push(new Effect.Scale(this.toExpand, 100, options));

        options = {
            sync: true,
            scaleContent: false,
            transition: Effect.Transitions.sinoidal,
            scaleX: false,
            scaleY: true
        };

        effects.push(new Effect.Scale(this.current, 0, options));

        var myDuration = 0.75;

        new Effect.Parallel(effects, {
            duration: myDuration,
            fps: 35,
            queue: {
                position: 'end',
                scope: 'accordion'
            },
            beforeStart: function() {
                this.isAnimating = true;
                this.enableOverflows(false);
                this.current.previous('div.'+this.options.toggleClass).removeClassName(this.options.toggleActive);
                this.toExpand.previous('div.'+this.options.toggleClass).addClassName(this.options.toggleActive);
            }.bind(this),
            afterFinish: function() {
                this.current.hide();
                this.toExpand.setStyle({ height: this.maxHeight+"px" });
                this.current = this.toExpand;
                this.isAnimating = false;
                this.enableOverflows(true);
            }.bind(this)
        });
    }

};

/*
document.observe("dom:loaded", function(){
    accordion = new Accordion("test-accordion", 2);
})
*/