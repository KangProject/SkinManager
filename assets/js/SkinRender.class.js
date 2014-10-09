var SkinRender = function (canvas, skin, scale, bRotate, skin2DUrl, is3D) {
    if (scale === undefined)
        this.scale = 800;
    else
        this.scale = scale;

    var self = this;
    this.skin = skin;
    if (typeof canvas === 'string')
        this.canvas = document.getElementById(canvas);
    else
        this.canvas = canvas;

    this.is3D = is3D === undefined ? true : is3D;
    this.skin2DUrl = skin2DUrl;
    this.displayFront = true;
    this.skin2D = {back: null, front: null};

    this.init();

    if (this.is3D) {
        this.startMousePos = {x: 0, y: 0};
        this.meshRotation = {x: 0, y: 0};

        if (bRotate === undefined)
            this.bRotate = false;
        else
            this.bRotate = bRotate;

        var onDocumentMouseMove = function (event) {
            self.meshRotation.x += (this.startMousePos.x - event.clientX) * 0.02;
            self.meshRotation.y += (this.startMousePos.y - event.clientY) * 0.02;
            this.startMousePos.x = event.clientX;
            this.startMousePos.y = event.clientY;
        };

        var onDocumentMouseUp = function (event) {
            self.bAnimate = true;
            self.canvas.removeEventListener('mousemove', onDocumentMouseMove, false);
            self.canvas.removeEventListener('mouseup', onDocumentMouseUp, false);
            self.canvas.removeEventListener('mouseout', onDocumentMouseOut, false);
        };

        var onDocumentMouseOut = function (event) {
            self.bAnimate = true;
            self.canvas.removeEventListener('mousemove', onDocumentMouseMove, false);
            self.canvas.removeEventListener('mouseup', onDocumentMouseUp, false);
            self.canvas.removeEventListener('mouseout', onDocumentMouseOut, false);
        };

        var onDocumentTouchStart = function (event) {
            if (event.touches.length == 1) {
                event.preventDefault();
                this.startMousePos = {x: event.clientX, y: event.clientY};
            }
        };

        var onDocumentTouchMove = function (event) {
            if (event.touches.length == 1) {
                event.preventDefault();
            }
        };

        var animate = function () {
            requestAnimationFrame(animate);
            self.render();
        };

        animate();
        this.canvas.addEventListener('mousedown', function (event) {
            event.preventDefault();
            self.bAnimate = false;
            this.addEventListener('mousemove', onDocumentMouseMove, false);
            this.addEventListener('mouseup', onDocumentMouseUp, false);
            this.addEventListener('mouseout', onDocumentMouseOut, false);
            this.startMousePos = {x: event.clientX, y: event.clientY};
        }, false);
        this.canvas.addEventListener('touchstart', onDocumentTouchStart, false);
        this.canvas.addEventListener('touchmove', onDocumentTouchMove, false);
    } else {
        var onClick = function (event) {
            self.displayFront = !self.displayFront;
            self.render();
        };

        this.canvas.addEventListener('click', onClick, false);
    }
};

SkinRender.prototype.loadMaterials = function (transparent) {
    var texture;
    if (typeof(this.skin) == "string")
        texture = new THREE.ImageUtils.loadTexture(this.skin, 150);
    else
        texture = new THREE.Texture(this.skin);

    texture.magFilter = THREE.NearestFilter;
    texture.minFilter = THREE.NearestFilter;
    texture.format = transparent ? THREE.RGBAFormat : THREE.RGBFormat;
    texture.needsUpdate = true;
    var material = new THREE.MeshBasicMaterial({
        map: texture,
        transparent: transparent ? true : false
    });
    return material;
};

function loadDEM(path, mapping) {
    var image = new Image(), texture = new THREE.Texture(image, mapping);
    image.onload = function () {
        texture.needsUpdate = true;
    };

    image.src = path;
    image.width = 64;
    image.height = 32;
    return texture;
}

SkinRender.prototype.updateTexture = function (textureRaw) {
    var newTexture;
    if (typeof(textureRaw) == "string")
        newTexture = loadDEM(textureRaw, 150);
    else
        newTexture = new THREE.Texture(textureRaw);

    newTexture.width = 64;
    newTexture.height = 32;
    newTexture.magFilter = THREE.NearestFilter;
    newTexture.minFilter = THREE.NearestFilter;
    newTexture.needsUpdate = true;
    newTexture.format = THREE.RGBAFormat;

    this.textureTrans.map = newTexture;
    newTexture.format = THREE.RGBFormat;
    this.texture.map = newTexture;
};

SkinRender.prototype.uvmap = function (mesh, face, x, y, w, h, rotateBy) {
    var tileUvWidth = 1 / 64;
    var tileUvHeight = 1 / 32;
    if (!rotateBy) rotateBy = 0;
    var uvs = mesh.geometry.faceVertexUvs[0][face];
    var tileU = x;
    var tileV = y;

    uvs[(0 + rotateBy) % 4].u = tileU * tileUvWidth;
    uvs[(0 + rotateBy) % 4].v = tileV * tileUvHeight;
    uvs[(1 + rotateBy) % 4].u = tileU * tileUvWidth;
    uvs[(1 + rotateBy) % 4].v = tileV * tileUvHeight + h * tileUvHeight;
    uvs[(2 + rotateBy) % 4].u = tileU * tileUvWidth + w * tileUvWidth;
    uvs[(2 + rotateBy) % 4].v = tileV * tileUvHeight + h * tileUvHeight;
    uvs[(3 + rotateBy) % 4].u = tileU * tileUvWidth + w * tileUvWidth;
    uvs[(3 + rotateBy) % 4].v = tileV * tileUvHeight;
};


SkinRender.prototype.init = function () {
    var i;

    this.canvas.width = 330;
    this.canvas.height = 280;

    if (this.is3D) {
        try {
            // var webGLSupport = (typeof window.WebGLRenderingContext === 'function') && (this.canvas.getContext('experimental-webgl') !== null || this.canvas.getContext('webgl') !== null);

            // if(webGLSupport)
            this.renderer = new THREE.WebGLRenderer({canvas: this.canvas});
            // else
            // this.renderer = new THREE.CanvasRenderer({ canvas: this.canvas });
            this.is3D = true;
        } catch (e) {
            console.log('WebGL not supported, switching to 2D canvas');
            this.is3D = false;
        }
    }

    if (this.is3D) {
        this.camera = new THREE.Camera(22, 1.1, 1.6, 1000);
        this.scene = new THREE.Scene();

        this.xvar = 0;
        this.targetRotationX = 0;
        this.targetRotationXOnMouseDown = 0;
        this.targetRotationY = 0;
        this.targetRotationYOnMouseDown = 0;
        this.mouseX = 0;
        this.mouseY = 0;
        this.mouseXOnMouseDown = 0;
        this.mouseYOnMouseDown = 0;
        this.bAnimate = true;
        this.camera.target.position.x = 0;
        this.camera.target.position.y = -11;
        this.camera.target.position.z = 0;
        this.textureTrans = this.loadMaterials(true);
        this.texture = this.loadMaterials(false);

        // Hat
        hatGeometry = new THREE.CubeGeometry(9, 9, 9);
        hatGeometry.uvsNeedUpdate = true;
        helmet = new THREE.Mesh(hatGeometry, this.textureTrans);
        this.uvmap(helmet, 0, 32 + 16, 8, 8, 8);
        this.uvmap(helmet, 1, 32 + 0, 8, 8, 8);
        this.uvmap(helmet, 2, 32 + 8, 0, 8, 8, 2);
        this.uvmap(helmet, 3, 32 + 16, 0, 8, 8, 2);
        this.uvmap(helmet, 4, 32 + 24, 8, 8, 8);
        this.uvmap(helmet, 5, 32 + 8, 8, 8, 8);
        this.scene.addObject(helmet);

        // Head
        // UVMap: ok !
        headGeometry = new THREE.CubeGeometry(8, 8, 8);
        headGeometry.uvsNeedUpdate = true;
        head = new THREE.Mesh(headGeometry, this.texture);
        head.castShadow = true;
        head.receiveShadow = true;
        head.position.x = 0;
        head.position.y = 0;
        head.position.z = 0;
        head.overdraw = true;
        this.uvmap(head, 0, 16, 8, 8, 8);
        this.uvmap(head, 1, 0, 8, 8, 8);
        this.uvmap(head, 2, 8, 0, 8, 8, 2);
        this.uvmap(head, 3, 16, 0, 8, 8, 2);
        this.uvmap(head, 4, 24, 8, 8, 8);
        this.uvmap(head, 5, 8, 8, 8, 8);
        this.scene.addObject(head);

        // Body
        bodyGeometry = new THREE.CubeGeometry(8, 12, 4);
        bodyGeometry.uvsNeedUpdate = true;
        body = new THREE.Mesh(bodyGeometry, this.texture);
        body.castShadow = true;
        body.receiveShadow = true;
        body.position.x = 0;
        body.position.y = -10;
        body.position.z = 0;
        body.overdraw = true;
        this.uvmap(body, 5, 20, 20, 8, 12);
        this.uvmap(body, 4, 32, 20, 8, 12);
        this.uvmap(body, 3, 20, 16, 8, 4, 1);
        this.uvmap(body, 2, 28, 16, 8, 4, 3);
        this.uvmap(body, 1, 16, 20, 4, 12);
        this.uvmap(body, 0, 28, 20, 4, 12);
        this.scene.addObject(body);

        // Arm_Left
        // UVMap: ok !
        var leftarmgeo = new THREE.CubeGeometry(4, 12, 4);
        for (i = 0; i < 8; i++) {
            leftarmgeo.vertices[i].y -= 4;
        }

        leftarmgeo.uvsNeedUpdate = true;
        arm_left = new THREE.Mesh(leftarmgeo, this.texture);
        arm_left.position.x = 6;
        arm_left.position.y = -10;
        arm_left.position.z = 0;
        arm_left.overdraw = true;
        this.uvmap(arm_left, 5, 44, 20, 4, 12);
        this.uvmap(arm_left, 4, 52, 20, 4, 12);
        this.uvmap(arm_left, 2, 44, 16, 4, 4, 2);
        this.uvmap(arm_left, 3, 48, 16, 4, 4, 2);
        this.uvmap(arm_left, 0, 52, 20, -4, 12);
        this.uvmap(arm_left, 1, 40, 20, 4, 12);
        this.scene.addObject(arm_left);

        //Arm_Right
        // UVMap: ok !
        var rightarmgeo = new THREE.CubeGeometry(4, 12, 4);
        for (i = 0; i < 8; i++) {
            rightarmgeo.vertices[i].y -= 4;
        }
        rightarmgeo.uvsNeedUpdate = true;
        arm_right = new THREE.Mesh(rightarmgeo, this.texture);
        arm_right.position.x = -6;
        arm_right.position.y = -10;
        arm_right.position.z = 0;
        arm_right.overdraw = true;
        this.uvmap(arm_right, 5, 48, 20, -4, 12);
        this.uvmap(arm_right, 4, 56, 20, -4, 12);
        this.uvmap(arm_right, 2, 48, 16, -4, 4, 2);
        this.uvmap(arm_right, 3, 52, 16, -4, 4, 2);
        this.uvmap(arm_right, 0, 44, 20, -4, 12);
        this.uvmap(arm_right, 1, 52, 20, -4, 12);
        this.scene.addObject(arm_right);

        //Leg_Left
        var leg_leftgeo = new THREE.CubeGeometry(4, 12, 4);
        for (i = 0; i < 8; i++) {
            leg_leftgeo.vertices[i].y -= 6;
        }

        leg_leftgeo.uvsNeedUpdate = true;
        leg_left = new THREE.Mesh(leg_leftgeo, this.texture);
        leg_left.position.x = 2;
        leg_left.position.y = -22;
        leg_left.position.z = 0;
        leg_left.overdraw = true;
        this.uvmap(leg_left, 5, 4, 20, 4, 12);
        this.uvmap(leg_left, 4, 12, 20, 4, 12);
        this.uvmap(leg_left, 2, 8, 16, -4, 4, 3);
        this.uvmap(leg_left, 3, 12, 20, -4, -4, 1);
        this.uvmap(leg_left, 1, 0, 20, 4, 12);
        this.uvmap(leg_left, 0, 8, 20, 4, 12);
        this.scene.addObject(leg_left);

        //Leg_Right
        var leg_rightgeo = new THREE.CubeGeometry(4, 12, 4);
        for (i = 0; i < 8; i++) {
            leg_rightgeo.vertices[i].y -= 6;
        }

        leg_rightgeo.uvsNeedUpdate = true;
        leg_right = new THREE.Mesh(leg_rightgeo, this.texture);
        leg_right.position.x = -2;
        leg_right.position.y = -22;
        leg_right.position.z = 0;
        leg_right.overdraw = true;
        this.uvmap(leg_right, 5, 8, 20, -4, 12);
        this.uvmap(leg_right, 4, 16, 20, -4, 12);
        this.uvmap(leg_right, 2, 4, 16, 4, 4, 3);
        this.uvmap(leg_right, 3, 8, 20, 4, -4, 1);
        this.uvmap(leg_right, 1, 12, 20, -4, 12);
        this.uvmap(leg_right, 0, 4, 20, -4, 12);
        this.scene.addObject(leg_right);

        this.renderer.setSize(600 * this.scale, 600 * this.scale);
    } else {
        var context = this.canvas.getContext("2d");
        context.webkitImageSmoothingEnabled = false;
        context.mozImageSmoothingEnabled = false;

        if (this.skin2DUrl === undefined) {
            context.fillText('aucun aperçu disponible', 20, 20);
        } else {
            this.skin2D.front = document.createElement('img');
            this.skin2D.front.src = this.skin2DUrl;
            this.skin2D.back = document.createElement('img');
            this.skin2D.back.src = this.skin2DUrl + '.back';

            var self = this;
            i = 0;
            this.skin2D.back.onload = this.skin2D.front.onload = function () {
                i++;
                if (i === 2)
                    self.render();
            };
        }
    }
};

SkinRender.prototype.render = function () {
    if (this.is3D) {
        this.camera.position.x = -100 * Math.sin(this.meshRotation.x);
        this.camera.position.y = -100 * Math.sin(this.meshRotation.y);
        this.camera.position.z = -100 * Math.cos(this.meshRotation.x);

        if (this.bAnimate) {
            this.xvar += Math.PI / 90;

            if (this.bRotate)
                this.meshRotation.x -= 0.01;

            // head swing
            helmet.rotation.y = head.rotation.y = Math.cos(this.xvar) / 5;
            helmet.rotation.x = head.rotation.x = -Math.cos(this.xvar * 2 + leg_left.position.y / 5) / 10;

            //Leg Swing
            leg_left.rotation.x = Math.cos(this.xvar) / 4;
            leg_left.position.z = 0 - 6 * Math.sin(leg_left.rotation.x);
            leg_left.position.y = -16 - 6 * Math.abs(Math.cos(leg_left.rotation.x));
            leg_right.rotation.x = Math.cos(this.xvar + (Math.PI)) / 4;
            leg_right.position.z = 0 - 6 * Math.sin(leg_right.rotation.x);
            leg_right.position.y = -16 - 6 * Math.abs(Math.cos(leg_right.rotation.x));

            //Arm Swing
            arm_left.rotation.x = Math.cos(this.xvar + (Math.PI)) / 2;
            arm_left.position.z = 0 - 6 * Math.sin(arm_left.rotation.x);
            arm_left.position.y = -4 - 6 * Math.abs(Math.cos(arm_left.rotation.x));
            arm_right.rotation.x = Math.cos(this.xvar) / 2;
            arm_right.position.z = 0 - 6 * Math.sin(arm_right.rotation.x);
            arm_right.position.y = -4 - 6 * Math.abs(Math.cos(arm_right.rotation.x));
        }

        this.renderer.render(this.scene, this.camera);
    } else {
        var context = this.canvas.getContext("2d");
        if (this.skin2DUrl === undefined) {
            return;
        }

        var frontX, backX;
        if (this.displayFront) {
            frontX = 10;
            backX = 180;
        } else {
            frontX = 180;
            backX = 10;
        }

        context.clearRect(0, 0, this.canvas.width, this.canvas.height);
        context.drawImage(this.skin2D.front, frontX, 10, 140, 260);
        context.drawImage(this.skin2D.back, backX, 10, 140, 260);
    }
};