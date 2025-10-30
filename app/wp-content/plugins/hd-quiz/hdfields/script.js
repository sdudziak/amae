const _hd = {
	v: 0.3,
	data: {
		fields: {},
	},
	options: {
		tabAutoScroll: "", // null (default), or px count
		wrapper: document.body,
	},
	init: function () {
		console.log("HDFields init v" + _hd.v);

		_hd.tabs.init(); // set tab navigation
		_hd.radio.init(); // set radio listeners
		_hd.images.init(); // if any image or gallery inputs
		_hd.colour.init(); // colour inputs
		_hd.image_toggle.init(); // image toggles
		_hd.search_list.init(); // searchable lists
		_hd.editor.init(); // tinyMCE editors
		_hd.kb.init(); // keyboard click navigation

		let save_el = document.getElementById("hd_save");
		if (save_el) {
			save_el.addEventListener("click", _hd.save);
		}
	},
	tabs: {
		// currently only compatible with one tabbed container per page
		init: function () {
			const tab_nav_items = document.getElementsByClassName("hd_tab_nav_item");
			for (let i = 0; i < tab_nav_items.length; i++) {
				tab_nav_items[i].addEventListener("click", _hd.tabs.switch);
			}

			// set tab_id for each and every element
			for (let i = 0; i < tab_nav_items.length; i++) {
				let id = tab_nav_items[i].getAttribute("data-id");
				let tab = document.getElementById("hd_tab_content_" + id);
				let fields = tab.getElementsByClassName("hderp");

				for (let ii = 0; ii < fields.length; ii++) {
					fields[ii].setAttribute("data-tab", id);
				}
			}

			if (window.innerWidth < 740) {
				const tabWrapper = document.getElementsByClassName("hd_content_tabs");
				for (let i = 0; i < tabWrapper.length; i++) {
					if (!tabWrapper[i].classList.contains("hd_content_tabs_horizintal")) {
						tabWrapper[i].classList.add("hd_content_tabs_horizontal");
					}
				}
			}
		},
		switch: async function () {
			if (this.classList.contains("hd_tab_nav_item_active")) {
				return;
			}
			let id = this.getAttribute("data-id");
			await _hd.tabs.set(this, "hd_tab_nav_item_active");
			let el = document.getElementById("hd_tab_content_" + id);
			await _hd.tabs.set(el, "hd_tab_content_section_active");
			console.log(_hd.options.tabAutoScroll);
			if (_hd.options.tabAutoScroll === "") {
				document.getElementsByClassName("hd_tabs_anchor")[0].scrollIntoView({
					behavior: "smooth",
					block: "start",
					inline: "nearest",
				});
			} else {
				document.documentElement.scrollTop = _hd.options.tabAutoScroll;
			}
		},
		set: async function (el, className = "") {
			let active = document.getElementsByClassName(className);
			while (active.length > 0) {
				active[0].classList.remove(className);
			}
			el.classList.add(className);
		},
	},
	kb: {
		init: function () {
			const items = document.getElementsByClassName("hd_kb");
			for (let i = 0; i < items.length; i++) {
				items[i].addEventListener("keyup", function (ev) {
					if (ev.which == 32) {
						this.click();
					}
				});
			}
		},
	},
	sortable: {
		init: function (parentEL) {
			Array.prototype.map.call(parentEL, (list) => {
				_hd.sortable.enableDragList(list);
			});
		},
		enableDragList: function (list) {
			Array.prototype.map.call(list.children, (item) => {
				_hd.sortable.enableDragItem(item);
			});
		},
		enableDragItem: function (item) {
			item.setAttribute("draggable", true);
			item.ondrag = _hd.sortable.handleDrag;
			item.ondragend = _hd.sortable.handleDrop;
		},
		handleDrag: function (item) {
			const selectedItem = item.target,
				list = selectedItem.parentNode,
				x = event.clientX,
				y = event.clientY;

			selectedItem.classList.add("drag-sort-active");
			let swapItem = document.elementFromPoint(x, y) === null ? selectedItem : document.elementFromPoint(x, y);

			if (list === swapItem.parentNode) {
				swapItem = swapItem !== selectedItem.nextSibling ? swapItem : swapItem.nextSibling;
				list.insertBefore(selectedItem, swapItem);
			}
		},
		handleDrop: function (item) {
			item.target.classList.remove("drag-sort-active");
		},
	},
	radio: {
		init: function () {
			let radio = document.getElementsByClassName("hd_input_radio");
			for (let i = 0; i < radio.length; i++) {
				let input = radio[i].getElementsByClassName("hd_radio_input");
				for (let x = 0; x < input.length; x++) {
					input[x].addEventListener("change", function () {
						_hd.radio.change(input[x], radio[i]);
					});
				}
			}
		},
		change: function (input, radio) {
			// once a radio option has been selected, radio value canot be unset
			let inputs = radio.getElementsByClassName("hd_radio_input");
			let hasSelection = false;
			for (let i = 0; i < inputs.length; i++) {
				if (inputs[i].checked == true) {
					hasSelection = true;
				}
			}
			if (!hasSelection) {
				if (inputs.length > 1) {
					input.checked = true;
					return;
				}
			}

			// now we ensure that only the current is checked
			if (input.checked != true) {
				return;
			}
			for (let i = 0; i < inputs.length; i++) {
				if (inputs[i] !== input) {
					inputs[i].checked = false;
				}
			}
		},
	},
	colour: {
		init: function () {
			const colours = document.getElementsByClassName("hd_colour");
			for (let i = 0; i < colours.length; i++) {
				colours[i].addEventListener("change", _hd.colour.change);
			}
		},
		change: function () {
			let value = this.value;
			value = value.toUpperCase();
			if (value.length >= 4 && value[0] === "#") {
				if (value.length !== 4 && value.length !== 7) {
					return;
				}
				this.nextSibling.style.backgroundColor = value;
				this.value = value;
			}
		},
	},
	image_toggle: {
		init: function () {
			const toggleItems = document.getElementsByClassName("hd_image_toggle_item");
			for (let i = 0; i < toggleItems.length; i++) {
				toggleItems[i].addEventListener("click", _hd.image_toggle.change);
			}

			const toggles = document.getElementsByClassName("hd_image_toggle");
			for (let i = 0; i < toggles.length; i++) {
				let sortable = toggles[i].getAttribute("data-sortable");
				if (sortable == 1) {
					console.log(toggles[i].getElementsByClassName("hd_image_toggle_items"));
					_hd.sortable.init(toggles[i].getElementsByClassName("hd_image_toggle_items"));
				}
			}
		},
		change: async function () {
			const item = this;
			const field = item.parentElement.parentElement;
			const multi = parseInt(field.getAttribute("data-multiple"));
			const required = field.getAttribute("data-required");
			let active = field.getElementsByClassName("active");
			let wasActive = false;
			if (item.classList.contains("active")) {
				wasActive = true;
			}

			if (multi) {
				await changeMulti();
				_hd.image_toggle.setContent(field);
			} else {
				await changeSingle();
				_hd.image_toggle.setContent(field);
			}

			async function changeMulti() {
				if (required) {
					if (wasActive && active.length > 1) {
						item.classList.toggle("active");
					} else if (!wasActive) {
						item.classList.toggle("active");
					}
				} else {
					item.classList.toggle("active");
				}
			}

			async function changeSingle() {
				while (active.length > 0) {
					active[0].classList.remove("active");
				}

				if (required) {
					if (!wasActive) {
						item.classList.add("active");
					}
				} else {
					if (!wasActive) {
						item.classList.add("active");
					}
				}
			}
		},
		setContent: function (field) {
			const activeContent = field.querySelectorAll(".hd_image_toggle_content.active");
			for (let i = 0; i < activeContent.length; i++) {
				activeContent[i].classList.remove("active");
			}

			const active = field.getElementsByClassName("hd_image_toggle_items")[0].getElementsByClassName("active");
			let value = [];
			for (let i = 0; i < active.length; i++) {
				value.push(active[i].getAttribute("data-id"));
				let content = field.querySelector('.hd_image_toggle_content[data-id = "' + active[i].getAttribute("data-id") + '"]');
				content.classList.add("active");
			}
			field.setAttribute("data-value", JSON.stringify(value));
		},
	},
	images: {
		// NOTE: This handles gallery too
		init: function () {
			const images = document.getElementsByClassName("hd_image");
			for (let i = 0; i < images.length; i++) {
				let set = images[i].getAttribute("data-event-loaded");
				if (set === "loaded") {
					continue;
				}
				images[i].setAttribute("data-event-loaded", "loaded");

				images[i].addEventListener("click", function () {
					let options = {
						title: this.getAttribute("data-title"),
						button: this.getAttribute("data-button"),
						multiple: this.getAttribute("data-multiple"),
					};
					_hd.images.load(this, options);
				});
			}

			// sortable
			const els = document.getElementsByClassName("hd_gallery_content");
			_hd.sortable.init(els);
		},
		load: function (el, options) {
			let type = el.getAttribute("data-type");
			let frame = (wp.media.frames.file_frame = wp.media({
				title: options.title,
				button: {
					text: options.button,
				},
				multiple: options.multiple,
			}));
			// When an image is selected, run a callback.
			frame.on("select", function () {
				let attachment = frame.state().get("selection");
				if (type === "image") {
					setImage(el, attachment);
				} else if (type === "gallery") {
					setGallery(el, attachment);
				} else {
					console.log(attachment);
				}
			});
			frame.open();

			function setImage(el, attachment) {
				let id = el.getAttribute("id");
				attachment = attachment.first().toJSON();
				el.setAttribute("data-value", attachment.id);
				let image = attachment.sizes.full.url;
				if (attachment.sizes.medium) {
					image = attachment.sizes.medium.url;
				}
				el.innerHTML = `<img src = "${image}" alt = ""/>`;
				let remove = el.nextElementSibling.classList.add("active");
			}

			function setGallery(el, attachment) {
				attachment = attachment.toJSON();
				let id = el.getAttribute("data-id");

				for (let i = 0; i < attachment.length; i++) {
					let iid = attachment[i].id;
					let image = attachment[i].sizes.thumbnail.url;
					let html = `<div class = "hd_gallery_image" data-id = "${id}" data-type = "gallery" onClick = "_hd.images.remove(this)" data-value = "${iid}" role = "button" title = "click to delete, drag and drop to reorder"><img src = "${image}" alt = ""/></div>`;

					el.nextElementSibling.insertAdjacentHTML("beforeend", html);
				}
				const els = document.getElementsByClassName("hd_gallery_content");
				_hd.sortable.init(els);
			}
		},
		remove: function (target) {
			let id = target.getAttribute("data-id");
			let type = target.getAttribute("data-type");
			if (type === "image") {
				removeImage(target, id);
			} else if (type === "gallery") {
				removeGallery(target, id);
			}

			function removeImage(target, id) {
				target.classList.remove("active");
				let el = document.getElementById(id);
				el.innerHTML = "upload image";
				el.setAttribute("data-value", 0);
			}

			function removeGallery(target, id) {
				let el = document.getElementById(id);
				target.remove();
			}
		},
	},
	search_list: {
		init: function () {
			const items = document.getElementsByClassName("hd_search_list");
			for (let i = 0; i < items.length; i++) {
				let input = items[i].getElementsByClassName("hd_input")[0];
				input.addEventListener("keyup", function () {
					_hd.search_list.check(this, items[i]);
				});
				input.addEventListener("focus", function () {
					items[i].classList.add("active");
				});
				input.addEventListener("blur", function () {
					setTimeout(function () {
						items[i].classList.remove("active");
					}, 100);
				});
			}
		},
		check: async function (el, parent) {
			let value = el.value;
			let dropdown = parent.getElementsByClassName("hd_search_list_open")[0];
			if (value.length < 3) {
				dropdown.innerText = "Enter " + (3 - value.length) + " or more characters";
				return;
			}
			dropdown.innerHTML = "";
			let options = el.getAttribute("data-list");
			options = JSON.parse(options);
			let results = [];
			let valueCompare = value.toUpperCase();
			for (let i = 0; i < options.length; i++) {
				let option = options[i].label.toUpperCase();
				if (option.includes(valueCompare)) {
					results.push(options[i]);
				}
			}
			if (results.length == 0) {
				dropdown.innerText = "No results found";
				return;
			}

			let html = "";
			for (let i = 0; i < results.length; i++) {
				html += `<div class = "hd_search_list_result_item" onClick = "_hd.search_list.add(this)" data-id = "${parent.getAttribute("id")}" data-value = "${results[i]["value"]}">${results[i]["label"]}</div>`;
			}
			dropdown.innerHTML = html;
		},
		add: function (item) {
			let value = item.getAttribute("data-value");
			let label = item.innerText;
			let id = item.getAttribute("data-id");

			const list = document.getElementById(id).getElementsByClassName("hd_search_list_wrapper")[0];
			const html = `<span onClick = "_hd.search_list.remove(this)" class = "hd_search_list_item" data-value = "${value}">${label}</span>`;
			list.insertAdjacentHTML("beforeend", html);
			const input = document.getElementById(id).getElementsByClassName("hd_input")[0];
			const dropdown = document.getElementById(id).getElementsByClassName("hd_search_list_open")[0];
			dropdown.innerText = "Enter 3 or more characters";
			input.value = "";
		},
		remove: function (item) {
			item.remove();
		},
	},
	editor: {
		init: function () {
			const el = document.getElementsByClassName("hd_editor_input");
			for (let i = 0; i < el.length; i++) {
				el[i].setAttribute("data-type", "editor");
				if (el[i].classList.contains("hd_editor_required")) {
					el[i].setAttribute("data-required", "required");
				}
			}
		},
	},
	saveLocal: async function (wrapper) {
		_hd.options.wrapper = wrapper;
		return await _hd.validate.init();
	},
	save: async function (ev, wrapper = null) {
		if (this.classList.contains("disabled")) {
			return;
		}
		this.classList.add("disabled");
		let label = this.innerHTML;
		this.innerHTML = "...";
		let action = this.getAttribute("data-action");

		if (wrapper === null && this.getAttribute("data-wrapper")) {
			if (wrapper === null) {
				_hd.options.wrapper = document.getElementById(this.getAttribute("data-wrapper"));
			} else {
				_hd.options.wrapper = wrapper;
			}
		}

		let valid = await _hd.validate.init();
		if (!valid) {
			this.innerHTML = label;
			this.classList.remove("disabled");

			// find the first invalid
			let firstInvalid = document.getElementsByClassName("hd_error");
			if (firstInvalid.length > 0) {
				firstInvalid = firstInvalid[0];
				let tab = firstInvalid.getAttribute("data-tab");
				if (tab) {
					let tabEl = document.querySelector('.hd_tab_nav_item[data-id = "' + tab + '"]');
					if (tabEl) {
						tabEl.click();
					}
				}
			}
		} else {
			await _hd.ajax(action, _hd.data.fields, this);
		}
	},
	validate: {
		init: async function () {
			// get all fields
			_hd.data.fields = {};
			let valid = true;
			let fields = _hd.options.wrapper.getElementsByClassName("hderp");
			for (let i = 0; i < fields.length; i++) {
				let type = fields[i].getAttribute("data-type");
				if (!_hd.validate.field[type]) {
					console.error("Field type " + type + " does not have a validation function");
					return;
				}
				let v = await _hd.validate.field[type](fields[i]);
				v = await _hd.validate.required(fields[i], v);
				if (!v.status) {
					valid = false;
					fields[i].classList.add("hd_error");
				} else {
					fields[i].classList.remove("hd_error");
				}

				v.id = fields[i].getAttribute("id");

				v.type = type;
				_hd.data.fields[v.id] = {
					id: v.id,
					type: v.type,
					value: v.value,
				};
			}
			return valid;
		},
		required: async function (el, v) {
			let required = el.getAttribute("data-required");
			if (required === "required") {
				if (v.value === "") {
					v.status = false;
				}
			}
			return v;
		},
		field: {
			text: async function (field) {
				let v = {
					value: field.value,
					status: true,
				};
				return v;
			},
			hidden: async function (field) {
				let v = {
					value: field.value,
					status: true,
				};
				return v;
			},
			textarea: async function (field) {
				let v = {
					value: field.value,
					status: true,
				};
				return v;
			},
			textarea_code: async function (field) {
				let v = {
					value: field.value,
					status: true,
				};
				return v;
			},
			email: async function (field) {
				let v = {
					value: field.value,
					status: await isEmail(field.value),
				};
				return v;

				async function isEmail(email) {
					if (email == "") {
						return true;
					}
					let re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
					return re.test(String(email).toLowerCase());
				}
			},
			website: async function (field) {
				let v = {
					value: field.value,
					status: await isWebsite(field.value),
				};
				return v;

				async function isWebsite(website = "") {
					if (website === "localhost" || website === "") {
						return true;
					}
					try {
						website = new URL(website);
						return true;
					} catch (error) {
						return false;
					}
				}
			},
			integer: async function (field) {
				let v = {
					value: field.value,
					status: true,
				};
				if (v.value !== "") {
					v.value = parseInt(field.value);
				}
				return v;
			},
			float: async function (field) {
				let v = {
					value: field.value,
					status: true,
				};
				if (v.value !== "") {
					v.value = parseFloat(field.value);
				}
				return v;
			},
			currency: async function (field) {
				let v = {
					value: field.value,
					status: true,
				};
				if (v.value !== "") {
					v.value = parseFloat(field.value).toFixed(2);
				}
				return v;
			},
			colour: async function (field) {
				let v = {
					value: field.value,
					status: true,
				};
				return v;
			},
			image: async function (field) {
				let v = {
					value: parseInt(field.getAttribute("data-value")),
					status: true,
				};
				return v;
			},
			gallery: async function (field) {
				let v = {
					value: [],
					status: true,
				};

				let items = field.nextElementSibling.getElementsByClassName("hd_gallery_image");
				for (let i = 0; i < items.length; i++) {
					v.value.push(parseInt(items[i].getAttribute("data-value")));
				}

				return v;
			},
			select: async function (field) {
				let v = {
					value: field.value,
					status: true,
				};
				return v;
			},
			radio: async function (field) {
				let v = {
					value: "",
					status: false,
				};

				let data = "";
				let radios = field.getElementsByClassName("hd_radio_input");
				for (let i = 0; i < radios.length; i++) {
					if (radios[i].checked) {
						data = radios[i].value;
						break;
					}
				}

				if (data.length === 0) {
					data = "hd_null"; // allow for overwriting of default if saved as null
				}

				v = {
					value: data,
					status: true,
				};
				return v;
			},
			image_toggle: async function (field) {
				let v = {
					value: [],
					status: true,
				};

				const items = field.getElementsByClassName("hd_image_toggle_items")[0].getElementsByClassName("active");
				for (let i = 0; i < items.length; i++) {
					v.value.push(items[i].getAttribute("data-id"));
				}

				return v;
			},
			checkbox: async function (field) {
				let v = {
					value: "",
					status: false,
				};
				let data = [];
				let checkboxes = field.getElementsByClassName("hd_check_input");
				for (let i = 0; i < checkboxes.length; i++) {
					if (checkboxes[i].checked) {
						data.push(checkboxes[i].value);
					}
				}

				// if user selects nothing we don't want field `default` to override that
				if (data.length === 0) {
					data = ["hd_null"];
				}

				v = {
					value: data,
					status: true,
				};
				return v;
			},
			date: async function (field) {
				let v = {
					value: field.value,
					status: true,
				};
				return v;
			},
			editor: async function (field) {
				await tinyMCE.triggerSave();
				let v = {
					value: field.value,
					status: true,
				};
				return v;
			},
			search_list: async function (field) {
				let v = {
					value: [],
					status: true,
				};

				const items = field.getElementsByClassName("hd_search_list_item");
				for (let i = 0; i < items.length; i++) {
					v.value.push(parseInt(items[i].getAttribute("data-value")));
				}

				return v;
			},
		},
	},
	ajax: async function (action = "", data, el = null, callback = null) {
		if (action == "") {
			console.warn("HD.ajax: No action was provided");
			return;
		}
		let label = "";
		if (el !== null) {
			label = el.innerText;
			if (el.getAttribute("data-label") != "") {
				label = el.getAttribute("data-label");
			}
			el.classList.add("disabled");
			el.innerText = "...";
		}

		const formdata = new FormData();
		formdata.append("action", action);
		formdata.append("data", JSON.stringify(data));

		const nonce = document.getElementById("hd_wpnonce").value;
		formdata.append("HD_NONCE", nonce);
		let res = await fetch(ajaxurl, {
			method: "POST",
			credentials: "same-origin",
			body: formdata,
		});
		res = await res.json();

		if (el !== null) {
			el.classList.remove("disabled");
			el.innerText = label;
		}

		if (res.status !== "success") {
			console.error(res);
			console.log("Failed running action " + action);
			_hd.error.show(res);
			return;
		}

		if (el !== null) {
			el.classList.remove("disabled");
			el.innerText = label;
		}

		console.log(res);

		if (res.action && res.action != "") {
			console.log("Running action " + res.action.name);
			console.log(res);
			let f = res.action.name;
			f = getF(f);

			// we will limit to only three parameters for brevity
			let args = [];
			if (res.action.data) {
				args.push(res.action.data);
			}
			if (res.action.data2) {
				args.push(res.action.data2);
			}
			if (res.action.data3) {
				args.push(res.action.data3);
			}

			f(...args); // run the custom action

			function getF(f) {
				let scope = window;
				let scopeSplit = f.split(".");
				for (i = 0; i < scopeSplit.length - 1; i++) {
					scope = scope[scopeSplit[i]];
					if (scope == undefined) {
						return;
					}
				}
				return scope[scopeSplit[scopeSplit.length - 1]];
			}
		}
	},
	error: {
		show: function (res) {
			const el = document.getElementById("hdfields_error_log");
			if (el === null) {
				return;
			}
			el.innerHTML = res.message;
			el.classList.add("active");

			setTimeout(function () {
				el.classList.remove("active");
			}, 6000);
		},
	},
};
_hd.init();
