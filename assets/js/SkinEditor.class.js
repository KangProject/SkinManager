/* V1 by @sepsten, revisited by @ephyspotato */
var Editor = function(image, scale, canvas, tools) {
	var workspace = document.createElement('canvas');
	this.workcontext = workspace.getContext('2d');
	this.context = canvas.getContext('2d');
	this.canvas = canvas;

	workspace.width = 64;
	workspace.height = 32;
	canvas.width = 64*scale;
	canvas.height = 32*scale;

	this.context.clearRect(0, 0, canvas.width, canvas.height);
	this.workcontext.drawImage(image, 0, 0);
	this.data = this.workcontext.getImageData(0, 0, 64, 32);
	this.scale = scale;

	this.tamponData = null;

	this.interval = false;

	this.tool = 0; // 0: pen; 1: pipette
	this.color = {r: 0, g: 0, b: 0};

	this.handleMouse();
	this.pos = {x: null, y:null};
	this.rendercursor = false;
	this.cursorSize = 1;

	this.textureEffect = false;
	this.textureRange = 10;

	this.history = [];
	this.state = -1; // -1 to avoid the first save. :)

	this.renderrules = false;

	this.setTools(tools);
	this.startRendering();
	this.pushToHistory();
};

Editor.prototype.setTools = function(toolList) {
	var self = this;

	var activeTool = null;
	var setActiveTool = function(tool) {
		if(activeTool !== null)
			$(activeTool).removeClass('disabled');

		$(tool).addClass('disabled');

		activeTool = tool;
	};

	if(toolList.colorpicker !== undefined) {
		this.colorpicker = $(toolList.colorpicker);
		this.colorpicker.spectrum({
			flat: false,
			showInput: true,
			showAlpha: true,
			showPalette:  true,
			color: '#000000',
			chooseText: _LANGUAGE['SELECT'],
			cancelText: _LANGUAGE['CANCEL'],
			change: function(color) {
				self.color = color.toRgb();
			}
		});
	}

	if(toolList.pen !== undefined) {
		toolList.pen.addEventListener('click', function(e) {
			e.preventDefault();

			setActiveTool(this);
			self.setTool(0);
		}, false);
	}

	if(toolList.pipette !== undefined) {
		toolList.pipette.addEventListener('click', function(e) {
			e.preventDefault();

			setActiveTool(this);
			self.setTool(1);
		}, false);
	}

	if(toolList.eraser !== undefined) {
		toolList.eraser.addEventListener('click', function(e) {
			e.preventDefault();

			setActiveTool(this);
			self.setTool(2);
		}, false);
	}

	if(toolList.copy !== undefined) {
		toolList.copy.addEventListener('click', function(e) {
			e.preventDefault();

			setActiveTool(this);
			self.setTool(3);
		}, false);
	}

	if(toolList.paste !== undefined) {
		toolList.paste.addEventListener('click', function(e) {
			e.preventDefault();

			setActiveTool(this);
			self.setTool(4);
		}, false);
	}

	if(toolList.texture_effect !== undefined) {
		toolList.texture_effect.addEventListener('click', function(e) {
			e.preventDefault();

			self.textureEffect = !self.textureEffect;
			$(this).toggleClass('disabled');
		}, false);
	}

	if(toolList.texture_range !== undefined) {
		toolList.texture_range.addEventListener('change', function(e) {
			e.preventDefault();
			self.textureRange = this.value;
		}, false);
	}

	if(toolList.cusor_size !== undefined) {
		toolList.cusor_size.addEventListener('change', function(e) {
			e.preventDefault();
			self.cursorSize = this.value;
		}, false);
	}

	if(toolList.rules !== undefined) {
		toolList.rules.addEventListener('click', function(e) {
			e.preventDefault();
			self.toggleRules();
		}, false);
	}

	if(toolList.undo !== undefined) {
		toolList.undo.addEventListener('click', function(e) {
			e.preventDefault();
			self.undo();
		}, false);
	}

	if(toolList.redo !== undefined) {
		toolList.redo.addEventListener('click', function(e) {
			e.preventDefault();
			self.redo();
		}, false);
	}

	if(toolList.reset !== undefined) {
		toolList.reset.addEventListener('click', function(e) {
			e.preventDefault();
			self.reset();
		}, false);
	}
};

Editor.prototype.getMousePos = function(evt) {
	var rect = this.context.canvas.getBoundingClientRect(), root = document.documentElement;
	var mouseX = evt.clientX - rect.left - root.scrollTop;
	var mouseY = evt.clientY - rect.top - root.scrollLeft;
	return {
		x: mouseX,
		y: mouseY
	};
};

Editor.prototype.handleMouse = function() {
	var self = this;
	var pressed = false;

	// start drawing only if we pressed on the canvas
	this.canvas.addEventListener('mousedown', function() {
		pressed = true;
	}, false);

	// but stop drawing if we stop pressing, even out of the canvas
	document.addEventListener('mouseup', function() {
		pressed = false;
	}, false);

	this.canvas.addEventListener('mouseup', function() {
		if(self.tool === 0 || self.tool === 2 || self.tool === 4)
			self.pushToHistory();
	}, false);

	this.canvas.addEventListener('mouseover', function() {
		self.rendercursor = true;
	}, false);

	this.canvas.addEventListener('mouseout', function() {
		self.rendercursor = false;
	}, false);

	document.body.addEventListener('keyup', function(e) {
		if(e.ctrlKey) {
			if(e.keyCode === 90)
				self.undo();
			if(e.keyCode === 89)
				self.redo();
		}
	}, false);

	var handler = function(event)
	{
		var pos = self.getMousePos(event);
		self.pos = {x: (pos.x/self.scale) >> 0, y: (pos.y/self.scale) >> 0};

		if(pressed || event.type === 'mousedown') {
			switch(self.tool) {
				case 0:
					self.setPixels_cursorSize(self.color.r, self.color.g, self.color.b, self.color.a*255 || 255);
					break;
				case 1:
					self.color = self.readPixel(self.pos.x, self.pos.y);
					self.colorpicker.spectrum("set", self.rgbToString(self.color.r, self.color.g, self.color.b, self.color.a || 1));
					break;
				case 2:
					self.setPixels_cursorSize(0,0,0,0);
					break;
				case 3:
					self.tamponData = self.readPixels_cursorSize();
					break;
				case 4:
					if(self.tamponData === null)
						break;

					var halfCursor = self.cursorSize >> 1;
					var startpos_x = self.pos.x - halfCursor;
					var startpos_y = self.pos.y - halfCursor;

					for(var i = 0; i < self.tamponData.length; i++) {
						var pixelList_y = self.tamponData[i];

						var posx = startpos_x + i;
						if(posx < 0 || posx > 63)
							continue;
						for(var j = 0; j < pixelList_y.length; j++) {
							var pixel = pixelList_y[j];

							var posy = startpos_y + j;
							if(posy < 0 || posy > 31)
								continue;

							self.setPixel(startpos_x+i, startpos_y+j, pixel.r, pixel.g, pixel.b, pixel.a*255);
						}
					}
					break;
			}
		}
	};

	$(this.context.canvas).mousemove(handler);
	$(this.context.canvas).mousedown(handler);
};

Editor.prototype.setTool = function(tool)
{
	if(typeof tool === 'string') {
		switch(tool) {
			case 'pen':
				this.tool = 0; break;
			case 'pipette':
				this.tool = 1; break;
			case 'eraser':
				this.tool = 2; break;
		}
	} else if(typeof tool === 'number' && tool < 5 && tool >= 0) {
		this.tool = tool;
	}

	return this.tool;
};

// Data manipulation
Editor.prototype.setPixel = function(x, y, r, g, b, a)
{
	var random = function(min, max) {
		return (Math.random()*max-min) >> 0;
	};

	var modifier = { r: 0, g:0, b: 0 };
	if(this.textureEffect && this.tool !== 4) {
		modifier.r = random(-this.textureRange, this.textureRange);
		modifier.g = random(-this.textureRange, this.textureRange);
		modifier.b = random(-this.textureRange, this.textureRange);
	}

	var p = (y*64+x)*4;
	this.data.data[p] = r+modifier.r;
	this.data.data[p+1] = g+modifier.g;
	this.data.data[p+2] = b+modifier.b;
	this.data.data[p+3] = a;

	// this.preview.updateTexture(this.data);
};

Editor.prototype.readPixel = function(x, y)
{
	var p = (y*64+x)*4;

	return {
		r: this.data.data[p],
		g: this.data.data[p+1],
		b: this.data.data[p+2],
		a: this.data.data[p+3]/255
	};
};

// History
Editor.prototype.pushToHistory = function()
{
	if(this.state < this.history.length-1) {
		this.history.splice(this.state+1);
	}

	this.state++;
	this.history[this.state] = this.cloneImageData(this.data);
};

Editor.prototype.cloneImageData = function(original)
{
	var cloned = this.context.createImageData(original);
	for(var i = 0; i < original.data.length; i++) {
		cloned.data[i] = original.data[i];
	}
	return cloned;
};

Editor.prototype.restoreTo = function(id)
{
	this.data = this.cloneImageData(this.history[id]);
	// this.preview.updateTexture(this.data);
};

Editor.prototype.undo = function()
{
	if(this.state > 0) {
		this.state--;
		this.restoreTo(this.state);
	}
};

Editor.prototype.redo = function()
{
	if(this.state < this.history.length-1) {
		this.state++;
		this.restoreTo(this.state);
	}
};

Editor.prototype.reset = function() {
	this.state = 0;
	this.restoreTo(0);
};

// Renderer
Editor.prototype.startRendering = function() {
	var self = this;
	var update = function() {
		self.clear();
		self.draw();
	};

	if(this.interval === false) this.interval = setInterval(update, 1000/60);
};

Editor.prototype.stopRendering = function() {
	if(this.interval !== false) {
		clearInterval(this.interval);
		this.interval = false;
	}
};

Editor.prototype.clear = function() {
	this.context.clearRect(0, 0, 64*this.scale, 32*this.scale);
};

Editor.prototype.draw = function() {
	// rendering skin
	var x = 0; var y = 0;
	for(i = 0; i < this.data.data.length; i += 4)
	{
		y = ((i/4)/64) >> 0;
		x = i/4 - y*64;

		this.drawPixel(x, y, this.data.data[i], this.data.data[i+1], this.data.data[i+2], this.data.data[i+3]/255);
	}

	// rendering rules
	if(this.renderrules) this.drawRules();

	// rendering cursor
	if(this.rendercursor) this.drawCursor();
};

Editor.prototype.readPixels_cursorSize = function(r, g, b, a) {
	var halfCursor = this.cursorSize >> 1;
	var offset = (this.cursorSize & 1);

	var pixelList = [];
	for(var i = -halfCursor; i < halfCursor+offset; i++) {
		var posx = this.pos.x+i;
		if(posx < 0 || posx > 63)
			continue;

		var pixelList_y = [];
		for(var j = -halfCursor; j < halfCursor+offset; j++) {
			var posy = this.pos.y+j;
			if(posy < 0 || posy > 31)
				continue;


			pixelList_y.push(this.readPixel(posx, posy));
		}

		pixelList.push(pixelList_y);
	}

	return pixelList;
};

Editor.prototype.setPixels_cursorSize = function(r, g, b, a) {
	var halfCursor = this.cursorSize >> 1;
	var offset = (this.cursorSize & 1);

	for(var i = -halfCursor; i < halfCursor+offset; i++) {
		var posx = this.pos.x+i;
		if(posx < 0 || posx > 63)
			continue;

		for(var j = -halfCursor; j < halfCursor+offset; j++) {
			var posy = this.pos.y+j;
			if(posy < 0 || posy > 31)
				continue;

			this.setPixel(posx, posy, r,g,b,a);
		}
	}
};

Editor.prototype.drawCursor = function() {
	var halfCursor = this.cursorSize >> 1;
	var offset = (this.cursorSize & 1);

	for(var i = -halfCursor; i < halfCursor+offset; i++) {
		for(var j = -halfCursor; j < halfCursor+offset; j++) {
			this.drawPixel(this.pos.x+i, this.pos.y+j, this.color.r, this.color.g, this.color.b, 0.5);
		}
	}
};

Editor.prototype.drawPixel = function(x, y, r, g, b, a) {
	this.context.fillStyle = this.rgbToString(r,g,b,a);
	this.context.fillRect(this.scale*x, this.scale*y, this.scale, this.scale);
};

Editor.prototype.drawRules = function()
{
	this.context.fillStyle = 'rgb(0, 0, 0)';

	// horizontal
	this.context.fillRect(0, 8*this.scale, 64*this.scale, 1);
	this.context.fillRect(0, 16*this.scale, 64*this.scale, 1);
	this.context.fillRect(0, 20*this.scale, 56*this.scale, 1);

	// vertical
	this.context.fillRect(4*this.scale, 16*this.scale, 1, 32*this.scale);
	this.context.fillRect(8*this.scale, 0, 1, 32*this.scale);
	this.context.fillRect(12*this.scale, 16*this.scale, 1, 32*this.scale);
	this.context.fillRect(16*this.scale, 0, 1, 32*this.scale);
	this.context.fillRect(20*this.scale, 16*this.scale, 1, 32*this.scale);
	this.context.fillRect(24*this.scale, 0, 1, 16*this.scale);
	this.context.fillRect(28*this.scale, 16*this.scale, 1, 16*this.scale);
	this.context.fillRect(32*this.scale, 0, 1, 16*this.scale);
	this.context.fillRect(32*this.scale, 20*this.scale, 1, 12*this.scale);
	this.context.fillRect(36*this.scale, 16*this.scale, 1, 4*this.scale);
	this.context.fillRect(40*this.scale, 8*this.scale, 1, 8*this.scale);
	this.context.fillRect(40*this.scale, 20*this.scale, 1, 12*this.scale);
	this.context.fillRect(44*this.scale, 16*this.scale, 1, 16*this.scale);
	this.context.fillRect(48*this.scale, 0, 1, 32*this.scale);
	this.context.fillRect(52*this.scale, 16*this.scale, 1, 16*this.scale);
	this.context.fillRect(56*this.scale, 0, 1, 16*this.scale);
	this.context.fillRect(56*this.scale, 20*this.scale, 1, 12*this.scale);
};

Editor.prototype.toggleRules = function() {
	this.renderrules = !this.renderrules;
};

// Export
Editor.prototype.export = function() {
	this.workcontext.putImageData(this.data, 0, 0);
	return this.workcontext.canvas.toDataURL('image/png');
};

Editor.prototype.rgbToString = function(r,g,b,a) {
	if(typeof a !== 'number')
		return "rgb("+r+","+g+","+b+")";
	else
		return "rgba("+r+","+g+","+b+","+a+")";
};