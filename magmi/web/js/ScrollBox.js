var ScrollBox = Class.create();

/**
 *	OPTION CONTSTANTS
 */

// bar_action: what happens when you click on the blank part of the scrollbar?
//		pass this in when creating a ScrollBox or change in the default object set
//		found in the initialze method to change the default behavior
ScrollBox.NO_ACTION		= 0;	//Do nothing
ScrollBox.PAGE_ACTION	= 1;	//Jump forward or back one full page
ScrollBox.JUMP_ACTION	= 2;	//Jump to the position on the bar that was clicked

Object.extend(ScrollBox.prototype, {
	initialize: function(element, options){
		
		this.element = element;
		Element.addClassName(this.element, 'scrollbox');
		
		this.opts = {
			scroll_increment: 30,				//How many pixels to scroll per click/button press/etc
			hold_delay: 500,					//The delay when holding the mouse down before it starts auto scrolling
			hold_interval: 100,					//The interval between auto scroll increments
			drag_interval: 100,					//How often the display is updated while dragging the handle
			update_check_interval: 100,			//How often to run integrity check and make sure the scrollbar is up to date
			auto_hide: false,					//Does the scrollbar hide when the content is too short for scrolling?
			bar_action: ScrollBox.PAGE_ACTION	//What happens when you click the empty part of the scrollbar?
		};
		Object.extend(this.opts, options);
		
		//Move content into content div
		this.content_div = document.createElement('div');
		Element.addClassName(this.content_div, 'scrollbox_content');
		$A(this.element.childNodes).each(function(n){ this.content_div.appendChild(n);}.bind(this));
		this.element.appendChild(this.content_div);
		this.content_div.style.overflow = 'hidden';
		this.content_div.style.height = '100%';

		//Add scrollbar div to element
		this.scrollbar = document.createElement('div');
		Element.addClassName(this.scrollbar, 'scrollbox_scrollbar');
		this.element.appendChild(this.scrollbar);
		this.scrollbar.style.position = 'absolute';
		this.scrollbar.style.top = '0';
		this.scrollbar.style.right = '0';
		
		//Add up button
		this.up_button = document.createElement('div');
		Element.addClassName(this.up_button, 'scrollbox_up_button');
		this.up_button.style.position = 'absolute';
		this.up_button.style.width = '100%';
		this.up_button.style.top = '0';
		this.up_button.style.right = '0';
		this.scrollbar.appendChild(this.up_button);
		
		//Add down button
		this.down_button = document.createElement('div');
		Element.addClassName(this.down_button, 'scrollbox_down_button');
		this.down_button.style.position = 'absolute';
		this.down_button.style.width = '100%';
		this.down_button.style.bottom = '0';
		this.down_button.style.right = '0';
		this.scrollbar.appendChild(this.down_button);
		
		//Add Scroll Handle
		this.handle = document.createElement('div');
		Element.addClassName(this.handle, 'scrollbox_handle');
		this.handle.style.position = 'absolute';
		this.handle.style.width = '100%';
		this.handle.style.right = '0';
		this.scrollbar.appendChild(this.handle);

		//Setup State Info
		this.scroll_pos = 0;
		this.setSizes();
		
		//buttons actions
		Event.observe(this.up_button, 'mousedown', function(e){this.buttonDown(e, this.scrollUp.bind(this));}.bindAsEventListener(this));
		Event.observe(this.down_button, 'mousedown', function(e){this.buttonDown(e, this.scrollDown.bind(this));}.bindAsEventListener(this));
		Event.observe(document, 'mouseup', this.buttonUp.bindAsEventListener(this));
		
		//handle actions
		Event.observe(document, 'mousemove', this.setMousePos.bindAsEventListener(this));
		Event.observe(this.handle, 'mousedown', this.handleDown.bindAsEventListener(this));
		this.handle_update_interval = setInterval(this.updateCheck.bind(this), this.opts.update_check_interval);
		
		//bar actions
		Event.observe(this.up_button, 'click', function(e){Event.stop(e);}.bindAsEventListener(this));
		Event.observe(this.down_button, 'click', function(e){Event.stop(e);}.bindAsEventListener(this));
		Event.observe(this.handle, 'click', function(e){Event.stop(e);}.bindAsEventListener(this));
		Event.observe(this.scrollbar, 'click', this.scrollBarClick.bindAsEventListener(this));
		
		//handle keypress events
		this.keyboard_events = [
			[document, 'keypress', this.keyboardEvent.bindAsEventListener(this)]
		];
		Event.observe(this.element, 'click', this.enableKeyboardEvents.bindAsEventListener(this));
		Event.observe(document, 'click', this.disableKeyboardEvents.bindAsEventListener(this));
		
		//handle scroll wheel
		Event.observe(this.content_div, 'mousewheel', this.scrollWheel.bindAsEventListener(this), true);
		Event.observe(this.content_div, 'DOMMouseScroll', this.scrollWheel.bindAsEventListener(this), true);
		
	},
	scrollDown: function(){
		if(this.scroll_pos  < this.scroll_max){
			this.scrollTo(this.scroll_pos + this.opts.scroll_increment < this.scroll_max ? this.scroll_pos + this.opts.scroll_increment : this.scroll_max);
			return true;
		}
		else{
			return false;
		}
	},
	scrollUp: function(){
		if(this.scroll_pos > 0){
			this.scrollTo(this.scroll_pos > this.opts.scroll_increment ? this.scroll_pos - this.opts.scroll_increment : 0);
			return true;
		}
		else{
			return false;
		}
	},
	scrollTo: function(new_pos){
		// console.log(new_pos, this.content_div);
		if(new_pos < 0){
			new_pos = 0;
		}
		if(new_pos > this.scroll_max){
			new_pos = this.scroll_max;
		}
		this.content_div.scrollTop = new_pos;
		this.scroll_pos = new_pos;
		this.updateHandle();
	},
	buttonDown: function(event, action){
		action();
		this.timeout = setTimeout(function(){
			action();
			this.timeout = null;
			if(this.interval){ clearInterval(this.interval);}
			this.interval = setInterval(action, this.opts.hold_interval);
		}.bind(this), this.opts.hold_delay);
		Event.stop(event);
	},
	buttonUp: function(event){
		if(this.timeout){
			clearTimeout(this.timeout);
		}
		if(this.interval){
			clearInterval(this.interval);
		}
		this.timeout = null;
		this.interval = null;
		this.down_position = null;
	},
	updateHandle: function(){
		if(this.scroll_max){
			this.handle_height = Math.floor(this.bar_height / this.scroll_height_ratio);
		}
		else{
			this.handle_height = this.bar_height;
		}
		
		if(this.opts.auto_hide){
			if(this.handle_height == this.bar_height){
				this.scrollbar.style.visibility = 'hidden';
			}
			else{
				this.scrollbar.style.visibility = '';
			}
		}

		var handle_top = this.up_button.offsetHeight;
		var handle_bottom = this.up_button.offsetHeight + (this.bar_height - this.handle_height);
		var bar_dist_height = handle_bottom - handle_top;
		if(this.scroll_max)
			this.handle_pos = handle_top + Math.floor(bar_dist_height * (this.scroll_pos / this.scroll_max));
		else
			this.handle_pos = handle_top;
		
		this.handle.style.height = this.handle_height + 'px';
		this.handle.style.top = this.handle_pos + 'px';
	},
	handleDown: function(){
		this.down_position = this.raw_mouse_pos - Position.cumulativeOffset(this.handle)[1];
		// console.log('Down at: ' , this.down_position, Position.cumulativeOffset(this.handle)[1], this.mouse_pos);
		if(this.interval){ clearInterval(this.interval);}
		this.interval = setInterval(function(){
			this.scrollTo(this.mouse_pos - (this.down_position * this.scroll_height_ratio));
		}.bindAsEventListener(this), this.opts.drag_interval);
	},
	setMousePos: function(e){
		if (document.all) { // grab the x-y pos.s if browser is IE
			tempY = event.clientY + document.body.scrollTop;
		} else {  // grab the x-y pos.s if browser is NS
			tempY = e.pageY;
		}  
		// catch possible negative values
		if (tempY < 0){tempY = 0;}  

		this.raw_mouse_pos = tempY;
		this.mouse_pos = Math.floor((tempY - this.scrollbar_top) * this.scroll_height_ratio);
	},
	setSizes: function(){
		this.scroll_max = this.content_div.scrollHeight - this.content_div.offsetHeight;
		if(this.scroll_max < 0) this.scroll_max = 0;

		if(this.scroll_pos > this.scroll_max){
			this.scrollTo(this.scroll_max);
		}

		this.bar_height = this.scrollbar.offsetHeight - (this.up_button.offsetHeight + this.down_button.offsetHeight);
		if(!this.bar_height){
			setTimeout(this.setSizes.bind(this), 100);
		}

		this.scroll_height_ratio = (this.content_div.scrollHeight / this.bar_height);
		this.scroll_height_ratio = this.scroll_height_ratio >= 1 ? this.scroll_height_ratio : 1;

		this.scrollbar_top = Position.cumulativeOffset(this.scrollbar)[1] + this.up_button.offsetHeight;
		this.scrollbar_bottom = this.scrollbar_top + this.bar_height;

		this.updateHandle();
	},
	scrollBarClick: function(event){
		switch(this.opts.bar_action){
			case ScrollBox.PAGE_ACTION:
				//clicked above the handle
				if(this.mouse_pos < this.handle_pos * this.scroll_height_ratio){
					this.pageUp();
				}
				//clicked below the handle
				else{
					this.pageDown();
				}
				break;
			case ScrollBox.JUMP_ACTION:
				this.scrollTo(this.mouse_pos);
				break;
		}
	},
	pageUp: function(){
		this.scrollTo(this.scroll_pos - this.content_div.offsetHeight);
	},
	pageDown: function(){
		this.scrollTo(this.scroll_pos + this.content_div.offsetHeight);
	},
	scrollWheel: function(event){
		var scroll_amount = Event.wheel(event);
		if(scroll_amount > 0){
			for(var i = 0; i < Math.ceil(scroll_amount); ++i){
				this.scrollUp();
			}
			if(this.scroll_pos > 0){
				Event.stop(event);
			}
		}
		else if(scroll_amount < 0){
			for(var i = 0; i > Math.floor(scroll_amount); --i){
				this.scrollDown();
			}
			if(this.scroll_pos < this.scroll_max){
				Event.stop(event);
			}
		}
	},
	updateCheck: function(){
		//Has the scroll pos been changed by something else?
		if(this.content_div.scrollTop != this.scroll_pos){
			this.scrollTo(this.content_div.scrollTop);
		}
		
		if(this.scroll_max != this.content_div.scrollHeight - this.content_div.offsetHeight){
			this.setSizes();
		}
	},
	enableKeyboardEvents: function(event){
		this.disableKeyboardEvents(event);

		this.keyboard_events.each(function(ke){
			Event.observe(ke[0], ke[1], ke[2]);
		});
		
		this.within_enable_event = true;
	},
	disableKeyboardEvents: function(event){
		if(!this.within_enable_event){
			this.keyboard_events.each(function(ke){
				Event.stopObserving(ke[0], ke[1], ke[2]);
			});
		}
		else{
			this.within_enable_event = false;
		}
	},
	keyboardEvent: function(event){
		switch(event.keyCode){
			case Event.KEY_HOME:
				this.scrollTo(0);
				break;
			case Event.KEY_END:
				this.scrollTo(this.scroll_max);
				break;
			case Event.KEY_PAGEUP:
				this.pageUp();
				break;
			case Event.KEY_PAGEDOWN:
				this.pageDown();
				break;
			case Event.KEY_UP:
				this.scrollUp();
				break;
			case Event.KEY_DOWN:
				this.scrollDown();
				break;
			default:
				return;
		}
		Event.stop(event);
	}
});

// Add mouse wheel support to prototype
Object.extend(Event, {
        wheel:function (event){
                var delta = 0;
                if (!event) event = window.event;
                if (event.wheelDelta) {
                        delta = event.wheelDelta/120;
                        if (window.opera) delta = -delta;
                } else if (event.detail) { delta = -event.detail/3;     }
                return delta; //Safari Round
        }
});